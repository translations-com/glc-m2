<?php

namespace TransPerfect\GlobalLink\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Field extends AbstractDb
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('globallink_field', 'id');
    }
}
