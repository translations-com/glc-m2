<?php

namespace TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item
 */
class Collection extends AbstractCollection
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init(
            'TransPerfect\GlobalLink\Model\Queue\Item',
            'TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item'
        );
    }

    public function _beforeLoad()
    {
        $select = $this->getSelect();
        $this->getResource()->joinAdditionalData($select);
        return parent::_beforeLoad();
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
