<?php

namespace TransPerfect\GlobalLink\Model\Queue\Item;

use Magento\Framework\Model\AbstractModel;

/**
 * Class
 */
class LogDb extends AbstractModel
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\LogDb');
    }
}
