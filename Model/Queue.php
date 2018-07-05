<?php
namespace TransPerfect\GlobalLink\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Queue
 *
 * @package TransPerfect\GlobalLink\Model
 */
class Queue extends AbstractModel
{
    /**
     * Queue statuses
     */
    const STATUS_NEW = 0;        // there are no any items from queue have been treated
    const STATUS_INPROGRESS = 1; // some items have been sent for translation
    const STATUS_SENT = 2;       // all items have been sent for translations
    const STATUS_FINISHED = 3;   // translations for all items have been downloaded

    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('TransPerfect\GlobalLink\Model\ResourceModel\Queue');
    }

    public function hasQueueErrors()
    {
        if (!empty($this->getData('queue_errors'))) {
            return true;
        } else {
            return false;
        }
    }
    public function getQueueErrors()
    {
        return $this->getData('queue_errors');
    }
}
