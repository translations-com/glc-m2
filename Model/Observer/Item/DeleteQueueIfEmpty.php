<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 */

namespace TransPerfect\GlobalLink\Model\Observer\Item;

use TransPerfect\GlobalLink\Model\QueueFactory;
use Psr\Log\LoggerInterface;

/**
 * Class CancelEntityTranslationRequest
 */
class DeleteQueueIfEmpty implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \TransPerfect\GlobalLink\Model\QueueFactory
     */
    protected $queueFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param QueueFactory $queueFactory
     * @param LoggerInterface        $logger
     */
    public function __construct(
        QueueFactory $queueFactory,
        LoggerInterface $logger
    ) {
        $this->queueFactory = $queueFactory;
        $this->logger = $logger;
    }

    /**
     * execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $deletedItem = $observer->getObject();
        $queueId = $deletedItem->getQueueId();

        $queue = $this->queueFactory->create()->load($queueId);

        if (empty($queue->getItems())) {
            $queue->delete();
        }
    }
}
