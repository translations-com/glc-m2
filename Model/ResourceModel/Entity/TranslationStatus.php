<?php
namespace TransPerfect\GlobalLink\Model\ResourceModel\Entity;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use TransPerfect\GlobalLink\Model\Entity\TranslationStatus as EntityTS;
use TransPerfect\GlobalLink\Helper\Data as HelperData;

/**
 * Class TranslationStatus
 *
 * @package TransPerfect\GlobalLink\Model\ResourceModel\Entity
 */
class TranslationStatus extends AbstractDb
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('globallink_entity_translation_status', 'id');
    }

    /**
     * Get data by type id, entity id
     * can also be limited by store ids
     *
     * @param int   $typeId
     * @param int   $entityId
     * @param array $storeIds
     *
     * @return array - Rows from status table
     */
    public function getForTypeAndEntity($typeId, $entityId, $storeIds = [])
    {
        $data = [];

        $select = $this->getConnection()->select()
            ->from($this->getMainTable())
            ->where('entity_type_id = ?', $typeId)
            ->where('entity_id = ?', $entityId);

        if (!empty($storeIds)) {
            $select->where('store_view_id IN (?)', $storeIds);
        }

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Get data by type id, entity ids and store id
     *
     * @param int   $typeId
     * @param array $entityIds
     * @param int   $storeId
     *
     * @return array - Rows from status table
     */
    protected function getForTypeAndStoreByEntities($typeId, array $entityIds, $storeId)
    {
        $data = [];

        $select = $this->getConnection()->select()
            ->from($this->getMainTable())
            ->where('entity_type_id = ?', $typeId)
            ->where('store_view_id = ?', $storeId)
            ->where('entity_id IN (?)', $entityIds);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Get data by type id, entity ids and store id
     *
     * @param int   $typeId
     * @param array $entityIds
     * @param int   $storeId
     *
     * @return array [
     *      table-row-id => entity_id
     *  ]
     */
    protected function isExistForTypeAndStoreByEntities($typeId, array $entityIds, $storeId)
    {
        $rowset = $this->getForTypeAndStoreByEntities($typeId, $entityIds, $storeId);

        $data = [];

        foreach ($rowset as $row) {
            $data[$row['id']] = $row['entity_id'];
        }

        return $data;
    }

    /**
     * Update|Insert status of given entities for given stores
     *
     * @param array $allEntities
     * @param array $targetStoreIds
     * @param int   $status
     *
     */
    protected function updateInsertStatuses(array $allEntities, array $targetStoreIds, $status)
    {
        $updateWhere = [];
        $insertData = [];

        foreach ($targetStoreIds as $storeId) {
            foreach ($allEntities as $typeId => $entityIds) {
                if (in_array($typeId, [HelperData::CMS_PAGE_TYPE_ID, HelperData::CMS_BLOCK_TYPE_ID])) {
                    //skip cms pages and cms blocks
                    continue;
                }

                $existInDB = $this->isExistForTypeAndStoreByEntities($typeId, $entityIds, $storeId);
                //prepare update request for selected ids
                $updateWhere = array_merge($updateWhere, array_keys($existInDB));

                //remove selected (existence) entity_id from $entityIds array
                $entitiesToInsert = array_diff($entityIds, $existInDB);
                //and prepare insert request for remained $entityIds
                foreach ($entitiesToInsert as $entityId) {
                    $insertData[] = [
                        'entity_type_id' => $typeId,
                        'entity_id' => $entityId,
                        'store_view_id' => $storeId,
                        'translation_status' => $status,
                    ];
                }
            }
        }

        if (!empty($updateWhere)) {
            //execute update request
            $updateWhere = 'id IN ('.implode(',', $updateWhere).')';
            $updateData = ['translation_status' => $status];
            $this->getConnection()->update($this->getMainTable(), $updateData, $updateWhere);
        }

        if (!empty($insertData)) {
            //execute insert request
            $this->getConnection()->insertMultiple($this->getMainTable(), $insertData);
        }
    }

    /**
     * Delete status of given entities for given|all stores
     *
     * @param array $allEntities
     * @param array $storeIds
     *              if 0 key = 'all' rows for all stores will be removed
     */
    protected function deleteStatuses(array $allEntities, array $storeIds)
    {
        if (empty($storeIds)) {
            return;
        }
        /**
         * string $deleteWhere
         *
         * WHERE
         * (
         *   ( entity_type_id = 1 AND entity_id IN (1,2,3,4) )
         *   OR
         *   ( entity_type_id = 2 AND entity_id IN (1,2) )
         * )
         * AND store_view_id IN (2,3);
         */
        $deleteWhere =' ( ';

        $whereOR = [];
        foreach ($allEntities as $typeId => $entityIds) {
            $whereOR[] = ' ( entity_type_id = '.$typeId.' AND entity_id IN ('.implode(',', $entityIds).') ) ';
        }
        if (empty($whereOR)) {
            return;
        }

        $deleteWhere.= implode(' OR ', $whereOR);
        $deleteWhere .= ' ) ';

        if (empty($storeIds[0]) || $storeIds[0] != 'all') {
            $deleteWhere .= ' AND store_view_id IN ('.implode(',', $storeIds).') ';
        }

        $this->getConnection()->delete($this->getMainTable(), $deleteWhere);
    }

    /**
     * Set status of given entities for given stores
     *
     * @param array $allEntities
     * @param array $targetStoreIds
     * @param int   $status
     *
     */
    protected function setStatuses(array $allEntities, array $targetStoreIds, $status)
    {
        switch ($status) {
            case EntityTS::STATUS_ENTITY_IN_PROGRESS:
            case EntityTS::STATUS_ENTITY_TRANSLATION_REQUIRED:
            case EntityTS::STATUS_ENTITY_TRANSLATED:
                // update or insert
                $this->updateInsertStatuses($allEntities, $targetStoreIds, $status);
                break;
            case EntityTS::STATUS_ENTITY_NONE:
                //just delete rows
                $this->deleteStatuses($allEntities, $targetStoreIds);
                break;
        }
    }

    /**
     * Set status to IN_PROGRESS of given entities for given stores
     *
     * @param array $allEntities [
     *      entity_type_id => [entity_id, entity_id, entity_id],
     *      entity_type_id => [entity_id, entity_id, entity_id],
     *  ]
     * @param array $targetStoreIds
     *
     */
    public function moveToInProgress(array $allEntities, array $targetStoreIds)
    {
        $this->setStatuses($allEntities, $targetStoreIds, EntityTS::STATUS_ENTITY_IN_PROGRESS);
    }

    /**
     * Set status to TRANSLATION_REQUIRED of given entities for given stores
     *
     * @param array $allEntities [
     *      entity_type_id => [entity_id, entity_id, entity_id],
     *      entity_type_id => [entity_id, entity_id, entity_id],
     *  ]
     * @param array $targetStoreIds
     *
     */
    public function moveToTranslationRequired(array $allEntities, array $targetStoreIds)
    {
        $this->setStatuses($allEntities, $targetStoreIds, EntityTS::STATUS_ENTITY_TRANSLATION_REQUIRED);
    }

    /**
     * Set status TRANSLATED of given entities for given stores
     *
     * @param array $allEntities [
     *      entity_type_id => [entity_id, entity_id, entity_id],
     *      entity_type_id => [entity_id, entity_id, entity_id],
     *  ]
     * @param array $targetStoreIds
     *
     */
    public function moveToTranslated(array $allEntities, array $targetStoreIds)
    {
        $this->setStatuses($allEntities, $targetStoreIds, EntityTS::STATUS_ENTITY_TRANSLATED);
    }

    /**
     * Set status NONE of given entities for given stores
     *
     * @param array $allEntities [
     *      entity_type_id => [entity_id, entity_id, entity_id],
     *      entity_type_id => [entity_id, entity_id, entity_id],
     *  ]
     * @param array $targetStoreIds
     *
     */
    public function moveToNone(array $allEntities, array $targetStoreIds)
    {
        $this->setStatuses($allEntities, $targetStoreIds, EntityTS::STATUS_ENTITY_NONE);
    }
}
