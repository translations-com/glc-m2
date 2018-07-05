<?php


namespace TransPerfect\GlobalLink\Model\ResourceModel\FieldProductCategory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init(
            'TransPerfect\GlobalLink\Model\FieldProductCategory',
            'TransPerfect\GlobalLink\Model\ResourceModel\FieldProductCategory'
        );
    }
}
