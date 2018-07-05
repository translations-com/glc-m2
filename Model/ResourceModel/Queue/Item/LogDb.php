<?php

namespace TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class LogDb
 */
class LogDb extends AbstractDb
{
    /**
     * constructor
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('globallink_job_item_status_history', 'id');
    }
}
