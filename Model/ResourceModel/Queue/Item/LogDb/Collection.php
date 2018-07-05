<?php

namespace TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\LogDb;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init(
            'TransPerfect\GlobalLink\Model\Queue\Item\LogDb',
            'TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\LogDb'
        );
    }
}
