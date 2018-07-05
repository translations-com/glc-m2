<?php
namespace TransPerfect\GlobalLink\Model\ResourceModel\Entity\Attribute;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package TransPerfect\GlobalLink\Model\ResourceModel\Entity\Attribute
 */
class Collection extends AbstractCollection
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init(
            'TransPerfect\GlobalLink\Model\Entity\Attribute',
            'TransPerfect\GlobalLink\Model\ResourceModel\Entity\Attribute'
        );
    }

    /**
     * Join fields to collection
     *
     * @param $table
     * @param $conditionType
     * @param $condition
     * @param $fields
     *
     * @return $this
     */
    public function joinFields($table, $conditionType, $condition, $fields)
    {
        $this->getSelect()->joinLeft($table, join($conditionType, $condition), $fields);

        return $this;
    }
}
