<?php
namespace TransPerfect\GlobalLink\Model\ResourceModel\Queue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package TransPerfect\GlobalLink\Model\ResourceModel\Queue
 */
class Collection extends AbstractCollection
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init(
            'TransPerfect\GlobalLink\Model\Queue',
            'TransPerfect\GlobalLink\Model\ResourceModel\Queue'
        );
    }

    /**
     * Perform operations after collection load
     * Add job items in job queue
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        // ids from globallink_job_queue
        $linkedIds = $this->getColumnValues('id');

        if (!empty($linkedIds) && is_array($linkedIds)) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from(['items' => $this->getTable('globallink_job_items')])
                ->where('items.queue_id' . ' IN (?)', $linkedIds);

            // items
            $result = $connection->fetchAssoc($select);

            if ($result) {
                // items separated by queues
                $itemsData = [];
                foreach ($result as $itemData) {
                    $itemsData[$itemData['queue_id']][] = $itemData['id'];
                }

                foreach ($this as $item) {
                    $linkedId = $item->getData('id');
                    if (!isset($itemsData[$linkedId])) {
                        continue;
                    }
                    $item->setData('items', $itemsData[$linkedId]);
                }
            }
        }

        return parent::_afterLoad();
    }

    public function _beforeLoad()
    {
        $this->joinAdditionalData();
        return parent::_beforeLoad();
    }

    /**
     * Join store data to collection items
     *
     * @return $this
     */
    protected function joinAdditionalData()
    {
        $this->getSelect()->join(
            ['store_source' => $this->getTable('store')],
            'main_table.origin_store_id = store_source.store_id',
            ['source_locale' => 'locale']
        );
        return $this;
    }

    /**
     * Add field filter to collection
     *
     * @see self::_getConditionSql for $condition
     *
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (is_array($field)) {
            $conditions = [];
            foreach ($field as $key => $value) {
                $field = $this->getResource()->getFieldNameByAlias($field);
                $conditions[] = $this->_translateCondition($value, isset($condition[$key]) ? $condition[$key] : null);
            }
            $resultCondition = '(' . implode(') ' . \Magento\Framework\DB\Select::SQL_OR . ' (', $conditions) . ')';
        } else {
            $field = $this->getResource()->getFieldNameByAlias($field);
            $resultCondition = $this->_translateCondition($field, $condition);
        }

        $this->_select->where($resultCondition, null, \Magento\Framework\DB\Select::TYPE_CONDITION);
        return $this;
    }
}
