<?php
/**
 * TransPerfect_GlobalLink
 *
 * Log status changes
 */
namespace TransPerfect\GlobalLink\Model\Observer\Item;

use Psr\Log\LoggerInterface;
use TransPerfect\GlobalLink\Model\Queue\Item\LogDbFactory;
use TransPerfect\GlobalLink\Model\Queue\Item;

/**
 * Class
 */
class StatusLog implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \TransPerfect\GlobalLink\Model\Queue\Item\LogDbFactory
     */
    protected $logDbFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param LogDbFactory    $logDbFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        LogDbFactory $logDbFactory,
        LoggerInterface $logger
    ) {
        $this->logDbFactory = $logDbFactory;
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
        $this->savingItem = $observer->getObject();
        $this->itemId = $this->savingItem->getId();

        $newData = $this->savingItem->getData();

        if ($this->savingItem->isObjectNew()) {
            $status = Item::STATUS_NEW;
        } else {
            $oldData = $this->savingItem->getOrigData();
            if ($oldData['status_id'] === $newData['status_id']) {
                return;
            }
            $status = $newData['status_id'];
        }

        $targetStores = explode(',', trim($newData['target_stores'], ','));

        foreach ($targetStores as $targetStore) {
            $logDb = $this->logDbFactory->create();

            $logDb->setEntityTypeId($newData['entity_type_id']);
            $logDb->setEntityId($newData['entity_id']);
            $logDb->setSourceStoreViewId($newData['store_id']);
            $logDb->setTargetStoreViewId($targetStore);
            $logDb->setChangedBy(Item::getActor());
            $logDb->setStatusId($status);

            $logDb->getResource()->save($logDb);
        }
    }
}
