<?php
/**
 * Extend category collection
 */
namespace TransPerfect\GlobalLink\Model\ResourceModel\Category;

use \Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;

/**
 * Category resource collection
 */
class Collection extends CategoryCollection
{
    /**
     * Join translation status
     *
     * @param int $storeId
     */
    public function joinTranslationStatus($storeId)
    {
        $this->getSelect()->joinLeft(
            ['gets' => $this->getTable('globallink_entity_translation_status')],
            'gets.entity_type_id = '.\TransPerfect\GlobalLink\Helper\Data::CATALOG_CATEGORY_TYPE_ID.
            ' AND '.
            'gets.store_view_id = '.$storeId.
            ' AND '.
            'gets.entity_id = e.entity_id',
            ['translation_status']
        );
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == "translation_status") {
            $field = "gets.translation_status";

            $adminStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            $defaultStoreId = $this->_storeManager->getDefaultStoreView()->getId();
            $storeId = $this->getStoreId();

            if (!empty($condition['eq']) && $condition['eq'] == \TransPerfect\GlobalLink\Model\Entity\TranslationStatus::STATUS_ENTITY_TRANSLATION_REQUIRED) {
                if (!empty($storeId) && !in_array($storeId, [$adminStoreId, $defaultStoreId])) {
                    $conditionFix[] = $condition;
                    $conditionFix[] = ['null' => true];
                    $condition = $conditionFix;
                }
            }

            $resultCondition = $this->_translateCondition($field, $condition);
            $this->_select->where($resultCondition, null, \Magento\Framework\DB\Select::TYPE_CONDITION);
            return $this;
        }

        return parent::addFieldToFilter($field, $condition);
    }
}
