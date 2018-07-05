<?php

namespace TransPerfect\GlobalLink\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class FieldProductCategory extends AbstractDb
{

    protected function _construct()
    {
        $this->_init('globallink_field_product_category', 'id');
    }
}
