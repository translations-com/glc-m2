<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Eugene Monakov <emonakov@robofirm.com>
 */

namespace TransPerfect\GlobalLink\Model\Observer\Entity\Status;

use TransPerfect\GlobalLink\Model\Entity\TranslationStatus;
use TransPerfect\GlobalLink\Model\ResourceModel\Entity\TranslationStatus as TranslationStatusResource;

use Psr\Log\LoggerInterface;

/**
 * Class Update
 *
 * @package TransPerfect\GlobalLink\Model\Observer\Entity\Status
 */
class InProgress implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Entity\TranslationStatus
     */
    protected $translationStatusResource;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * constructor
     *
     * @param TranslationStatusResource $translationStatusResource
     * @param \Psr\Log\LoggerInterface  $logger
     */
    public function __construct(
        TranslationStatusResource $translationStatusResource,
        LoggerInterface $logger
    ) {
        $this->translationStatusResource = $translationStatusResource;
        $this->logger = $logger;
    }

    /**
     * Observer execute
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \TransPerfect\GlobalLink\Model\Queue $queue */
        $queue = $observer->getQueue();
        $processedItems = $queue->getProcessedData();
        if (!empty($processedItems)) {
            $allEntities = [];
            foreach ($processedItems as $processedItem) {
                $allEntities[$processedItem['entity_type_id']][$processedItem['entity_id']] = $processedItem['entity_id'];
            }

            $this->translationStatusResource->moveToInProgress($allEntities, $queue->getTargetStoreIds());
        }
    }
}
