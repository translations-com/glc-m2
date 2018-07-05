<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 */

namespace TransPerfect\GlobalLink\Model\Observer;

use TransPerfect\GlobalLink\Model\Queue\Item;
use TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory as ItemCollectionFactory;
use TransPerfect\GlobalLink\Helper\Data as HelperData;
use Psr\Log\LoggerInterface;

/**
 * Class CancelEntityTranslationRequest
 */
class CancelEntityTranslationRequest implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Item collection factory
     *
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory
     */
    protected $itemCollectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * Constructor
     *
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param \Magento\Backend\Model\Auth\Session $authSession,
     * @param LoggerInterface       $logger
     */
    public function __construct(
        ItemCollectionFactory $itemCollectionFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        LoggerInterface $logger
    ) {
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->authSession = $authSession;
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
        $deletedEntity = $observer->getObject();

        $entityId = $deletedEntity->getId();

        $entityTypeId = '';

        if ($deletedEntity instanceof \Magento\Cms\Api\Data\BlockInterface) {
            $entityTypeId = HelperData::CMS_BLOCK_TYPE_ID;
        } elseif ($deletedEntity instanceof \Magento\Cms\Api\Data\PageInterface) {
            $entityTypeId = HelperData::CMS_PAGE_TYPE_ID;
        } elseif ($deletedEntity instanceof \Magento\Catalog\Api\Data\CategoryInterface) {
            $entityTypeId = HelperData::CATALOG_CATEGORY_TYPE_ID;
        } elseif ($deletedEntity instanceof \Magento\Catalog\Api\Data\ProductInterface) {
            $entityTypeId = HelperData::CATALOG_PRODUCT_TYPE_ID;
        } elseif ($deletedEntity instanceof \Magento\Catalog\Model\ResourceModel\Eav\Attribute) {
            $entityTypeId = HelperData::PRODUCT_ATTRIBUTE_TYPE_ID;
        } elseif ($deletedEntity instanceof \Magento\Customer\Model\Attribute) {
            $entityTypeId = HelperData::CUSTOMER_ATTRIBUTE_TYPE_ID;
        }

        if (empty($entityTypeId)) {
            return;
        }

        $user = $this->authSession->getUser();
        Item::setActor('user: '.$user->getUsername().'('.$user->getId().')');

        // Get all Items related to given Entity and cancel them
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter('entity_id', $entityId);
        $items->addFieldToFilter('entity_type_id', $entityTypeId);
        foreach ($items as $item) {
            $item->cancelItem();
        }
    }
}
