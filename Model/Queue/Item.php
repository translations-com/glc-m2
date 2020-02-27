<?php

namespace TransPerfect\GlobalLink\Model\Queue;

use Magento\Cms\Model\Page;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Model\AbstractModel;
use TransPerfect\GlobalLink\Helper\Data as Helper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use TransPerfect\GlobalLink\Model\Queue;
use TransPerfect\GlobalLink\Model\QueueFactory;
use TransPerfect\GlobalLink\Model\TranslationService;
use Magento\Framework\DomDocument\DomDocumentFactory;
use Magento\Framework\Filesystem\Io\File;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as BlockCollectionFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use TransPerfect\GlobalLink\Logger\BgTask\Logger as BgLogger;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Model\Product\Option as ProductOption;
use Magento\Bundle\Model\Option as BundleOption;
use Magento\Customer\Api\CustomerMetadataInterface;
use TransPerfect\GlobalLink\Model\Entity\TranslationStatus;
use TransPerfect\GlobalLink\Model\ResourceModel\Entity\TranslationStatus as TranslationStatusResource;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;

/**
 * Class Item
 *
 * @package TransPerfect\GlobalLink\Model\Queue
 */
class Item extends AbstractModel
{
    /**
     * Item statuses
     */
    const STATUS_NEW = 0;               // item has not been sent for translation yet
    const STATUS_INPROGRESS = 1;        // item been sent but has not been translated yet
    const STATUS_FINISHED = 2;          // item has been translated (xml downloaded)
    const STATUS_ERROR_DOWNLOAD = 3;    // service error while trying to get translation by submission ticket
    const STATUS_APPLIED = 4;           // user applied translation to site
    const STATUS_FOR_CANCEL = 5;        // translation canceled locally, waiting for cancelation cron job run to cancel it on remote
    const STATUS_FOR_DELETE = 6;        // related entity has been deleted or task has been successfully cancelled. Item can be removed.
    const STATUS_CANCEL_FAILED = 7;     // last cancellation request failed
    const STATUS_ERROR_UPLOAD = 8;      // something went wrong while documend uploading (item has not been sent)
    const STATUS_CANCELLED = 9;         // item is cancelled
    const STATUS_MAXLENGTH = 10;        // item has one or more fields that failed the max length test and cannot be imported

    /**
     * Prefix of model events names
     * Important: do not remove underscore
     *
     * @var string
     */
    protected $_eventPrefix = 'transperfect_globallink_item';

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var \TransPerfect\GlobalLink\Model\Queue\QueueFactory
     */
    protected $queueFactory;

    /**
     * @var \Magento\Framework\Message\Manager
     */
    protected $messageManager;

    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $blockFactory;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Block\CollectionFactory
     */
    protected $blockCollectionFactory;

    /**
     * @var \Magento\Cms\Model\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    protected $pageCollectionFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \TransPerfect\GlobalLink\Model\TranslationService
     */
    protected $translationService;

    /**
     * @var \Magento\Framework\DomDocument\DomDocumentFactory
     */
    protected $domDocumentFactory;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $storeManager;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var \Magento\Eav\Api\AttributeOptionManagementInterface
     */
    protected $attributeOptionManagement;

    /**
     * @var \TransPerfect\GlobalLink\Logger\BgTask\Logger
     */
    protected $bgLogger;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\Product\Option
     */
    protected $productOption;

    /**
     * @var \Magento\Bundle\Model\Option
     */
    protected $bundleOption;

    /**
     * @var \Magento\Store\Model\ResourceModel\Store\CollectionFactory
     */
    protected $storeCollectionFactory;

    /**
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Entity\TranslationStatus
     */
    protected $translationStatusResource;

    /**
     * @var array
     */
    static protected $goodStores = [];
    static protected $badStores = [];

    /**
     * who uses the module
     */
    static protected $actor = '';

    /**
     * @var \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory
     */
    protected $urlRewriteCollectionFactory;
    protected $isAutomaticMode;
    /**
     * @var \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory
     */
    protected $reviewCollectionFactory;
    /**
     * @var \Magento\Review\Model\ReviewFactory $reviewFactory
     */
    protected $reviewFactory;
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item');
    }

    /**
     * Item constructor.
     *
     * @param \Magento\Framework\Model\Context                                      $context
     * @param \Magento\Framework\Registry                                           $registry
     * @param \Magento\Framework\Message\Manager                                    $messageManager
     * @param \Magento\Cms\Model\BlockFactory                                       $blockFactory
     * @param \Magento\Cms\Model\PageFactory                                        $pageFactory
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface                      $categoryRepository
     * @param \TransPerfect\GlobalLink\Model\TranslationService                     $translationService
     * @param \Magento\Framework\DomDocument\DomDocumentFactory                     $domDocumentFactory
     * @param \Magento\Framework\Filesystem\Io\File                                 $file
     * @param \TransPerfect\GlobalLink\Helper\Data                                  $helper
     * @param \Magento\Store\Model\StoreManagerInterface                            $storeManager
     * @param \Magento\Cms\Model\ResourceModel\Block\CollectionFactory              $blockCollectionFactory
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory               $pageCollectionFactory
     * @param \Magento\Eav\Api\AttributeRepositoryInterface                         $attributeRepository
     * @param \Magento\Eav\Api\AttributeOptionManagementInterface                   $attributeOptionManagement
     * @param \TransPerfect\GlobalLink\Logger\BgTask\Logger                         $bgLogger
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                       $productRepository
     * @param \Magento\Catalog\Model\Product\Option                                 $productOption
     * @param \Magento\Bundle\Model\Option                                          $bundleOption
     * @param \TransPerfect\GlobalLink\Model\Queue\QueueFactory                     $queueFactory
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Entity\TranslationStatus $translationStatusResource
     * @param \Magento\Store\Model\ResourceModel\Store\CollectionFactory            $storeCollectionFactory
     * @param \Magento\UrlRewrite\Model\UrlRewriteCollectionFactory                 $urlRewriteCollectionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                    $scopeConfig
     * @param \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory  $reviewCollectionFactory
     * @param \Magento\Review\Model\ReviewFactory                                   $reviewFactory
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Message\Manager $messageManager,
        \Magento\Cms\Model\BlockFactory $blockFactory,
        \Magento\Cms\Model\PageFactory $pageFactory,
        CategoryRepositoryInterface $categoryRepository,
        TranslationService $translationService,
        DomDocumentFactory $domDocumentFactory,
        File $file,
        Helper $helper,
        StoreManagerInterface $storeManager,
        BlockCollectionFactory $blockCollectionFactory,
        PageCollectionFactory $pageCollectionFactory,
        AttributeRepositoryInterface $attributeRepository,
        AttributeOptionManagementInterface $attributeOptionManagement,
        BgLogger $bgLogger,
        ProductRepositoryInterface $productRepository,
        ProductOption $productOption,
        BundleOption $bundleOption,
        QueueFactory $queueFactory,
        TranslationStatusResource $translationStatusResource,
        StoreCollectionFactory $storeCollectionFactory,
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory $reviewCollectionFactory,
        \Magento\Review\Model\ReviewFactory $reviewFactory
    ) {
        parent::__construct($context, $registry);
        $this->messageManager = $messageManager;
        $this->blockFactory = $blockFactory;
        $this->pageFactory = $pageFactory;
        $this->categoryRepository = $categoryRepository;
        $this->translationService = $translationService;
        $this->domDocumentFactory = $domDocumentFactory;
        $this->file = $file;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->blockCollectionFactory = $blockCollectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->attributeRepository = $attributeRepository;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->bgLogger = $bgLogger;
        $this->productRepository = $productRepository;
        $this->productOption = $productOption;
        $this->bundleOption = $bundleOption;
        $this->queueFactory = $queueFactory;
        $this->translationStatusResource = $translationStatusResource;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->reviewFactory = $reviewFactory;
        if($scopeConfig->getValue('globallink/general/automation') == 1){
            $this->isAutomaticMode = true;
        } else{
            $this->isAutomaticMode = false;
        }
    }

    /**
     * set actor
     *
     * @param string $actor
     */
    public static function setActor($actor)
    {
        self::$actor = $actor;
    }

    /**
     * get actor
     *
     * return string
     */
    public static function getActor()
    {
        if (empty(self::$actor)) {
            self::$actor = '-';
        }
        return self::$actor;
    }

    /**
     * Readable statuses array
     *
     * @return array
     */
    public static function getStatusesOptionArray()
    {
        return [
            self::STATUS_NEW => 'Queued',
            self::STATUS_INPROGRESS => 'In Progress',
            self::STATUS_FINISHED => 'Ready to Import',
            self::STATUS_ERROR_DOWNLOAD => 'Cancelled - Source Page Deleted',
            self::STATUS_APPLIED => 'Completed',
            self::STATUS_FOR_CANCEL => 'Waiting to be Cancelled',
            self::STATUS_FOR_DELETE => 'Cancelled',
            self::STATUS_CANCEL_FAILED => 'Cancel Failed Once, Will Retry',
            self::STATUS_ERROR_UPLOAD => 'Uploading failed',
            self::STATUS_MAXLENGTH => 'Max Length Error'
        ];
    }

    /**
     * @return \TransPerfect\GlobalLink\Model\Queue
     */
    protected function _getQueue()
    {
        $queues = $this->_registry->registry('queues');
        $queueId = $this->getQueueId();
        if (!isset($queues[$queueId])) {
            /** @var Queue $queue */
            $queue = $this->queueFactory->create();
            $queue->getResource()->load($queue, $queueId);
            $queue->setQueueErrors([]);
            $queues[$queueId] = $queue;
            $this->queue = $queue;
            $this->_registry->unregister('queues');
            $this->_registry->register('queues', $queues);
        } else {
            $this->queue = $queues[$queueId];
        }
        return $this->queue;
    }

    /**
     * Apply translation for current item object
     *
     * @return void
     * @throws \Exception
     */
    public function applyTranslation()
    {
        $queue = $this->_getQueue();
        try {
            switch ($this->getEntityTypeId()) :
                case Helper::CATALOG_CATEGORY_TYPE_ID:
                    $this->applyTranslationCatalogCategory();
                    break;
                case Helper::CMS_BLOCK_TYPE_ID:
                    $this->applyTranslationCmsBlock();
                    break;
                case Helper::CMS_PAGE_TYPE_ID:
                    $this->applyTranslationCmsPage();
                    break;
                case Helper::CATALOG_PRODUCT_TYPE_ID:
                    $this->applyTranslationCatalogProduct();
                    break;
                case Helper::PRODUCT_ATTRIBUTE_TYPE_ID:
                    $this->applyTranslationProductAttribute();
                    break;
                case Helper::CUSTOMER_ATTRIBUTE_TYPE_ID:
                    $this->applyTranslationCustomerAttribute();
                    break;
                case Helper::PRODUCT_REVIEW_ID:
                    $this->applyTranslationReview();
                    break;
                default:
                    throw new \Exception(__('Skip item %1. Unknown entity type id %2', $this->getId(), $this->getEntityTypeId()));
            endswitch;
            $start = microtime(true);
            $this->sendDownloadConfirmation();
            $logData = [
                'message' => "Send download confirmation duration: ".(microtime(true) - $start)." seconds",
            ];
            if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }

        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->_logError($e, $queue);
            }
        }
    }

    /**
     * Removes item from db
     *
     * @return $this
     * @throws \Exception
     */
    public function removeItem()
    {
        $queue = $this->_getQueue();
        try {
            $this->getResource()->delete($this);
        } catch (\Exception $e) {
            if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->_logError($e, $queue);
            }
            throw $e;
        }
        return $this;
    }

    /**
     * Moves Item into cancellation queue or removes it
     *
     * @return $this
     * @throws \Exception
     */
    public function cancelItem()
    {
        $queue = $this->_getQueue();
        try {
            switch ($this->getStatusId()) {
                case self::STATUS_NEW:
                    $this->setStatusId(self::STATUS_FOR_DELETE);
                    $this->getResource()->save($this);
                    /*if($this->isAutomaticMode){
                        $this->cancelTranslationCall();
                    }*/
                    break;
                case self::STATUS_ERROR_UPLOAD:
                    // remove item which haven't been sent yet
                    //return $this->removeItem();
                    //$this->setStatusId(self::STATUS_FOR_DELETE);
                    //$this->getResource()->save($this);
                    break;

                case self::STATUS_FINISHED:
                    $this->setStatusId(self::STATUS_FOR_CANCEL);
                    $this->getResource()->save($this);
                    if($this->isAutomaticMode){
                        $this->cancelTranslationCall();
                    }
                    break;
                case self::STATUS_APPLIED:
                    // can't cancel finished item
                    break;

                case self::STATUS_INPROGRESS:
                    $this->setStatusId(self::STATUS_FOR_CANCEL);
                    $this->getResource()->save($this);
                    if($this->isAutomaticMode){
                        $this->cancelTranslationCall();
                    }
                    break;
                case self::STATUS_CANCEL_FAILED:
                case self::STATUS_ERROR_DOWNLOAD:
                    // move item in cancellation queue
                    //$this->setStatusId(self::STATUS_FOR_DELETE);
                    //$this->getResource()->save($this);
                    break;
                case self::STATUS_MAXLENGTH:
                    break;
            }
        } catch (\Exception $e) {
            if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->_logError($e, $queue);
            }
            throw $e;
        }

        return $this;
    }
    /**
     * Makes an API call to see if the item is completed on the PD side
     */
    public function isCompleted(){
        $submissionTicket = $this->getSubmissionTicket();
        $completedTargets = $this->translationService->getCompletedTargetsBySubmission($this->getSubmissionTicket());
        if(count($completedTargets) > 0){
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * Makes an API call to cancell translation on PD side
     * Can be applied only for Items prepared to be cancelled (STATUS_FOR_CANCEL, STATUS_CANCEL_FAILED)
     *
     * @return bool
     */
    public function cancelTranslationCall()
    {
        if ($this->getStatusId() != self::STATUS_FOR_CANCEL
            && $this->getStatusId() != self::STATUS_CANCEL_FAILED) {
            return false;
        }

        $isCancelled = $this->translationService->cancelTargetByDocumentId(
            $this->getDocumentTicket(),
            $this->getPdLocaleIsoCode()
        );
        if ($isCancelled) {
            $this->setStatusId(self::STATUS_FOR_DELETE);
            $this->getResource()->save($this);
            return true;
        }

        $this->setStatusId(self::STATUS_CANCEL_FAILED);
        $this->getResource()->save($this);

        return false;
    }

    /**
     * Get target store ids (selected by user while creation of submission)
     * check a) they exist b) their locales have not been changed
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function getTargetStoreIds()
    {
        $targetLocale = $this->getPdLocaleIsoCode();
        $targetStores = trim($this->getTargetStores(), ',');
        if (empty($targetStores)) {
            throw new \Exception(__('Item %1. Target stores is not specified', $this->getId()));
        }
        $targetStoreIds = explode(',', $targetStores);

        if (!empty(self::$badStores[$targetLocale]) &&
            !empty(array_intersect($targetStoreIds, self::$badStores[$targetLocale]))) {
            // if ANY of target stores are in badStores array, exit with error
            throw new \Exception(__(
                "Item %1. Locale of target store (id: %2, name: %3, locale: %4) is not match to submission's target locale %5",
                $this->getId(),
                $store->getId(),
                $store->getName(),
                $store->getLocale(),
                $targetLocale
            ));
        } elseif (!empty(self::$goodStores[$targetLocale]) &&
                    empty(array_diff($targetStoreIds, self::$goodStores[$targetLocale]))) {
            // if ALL target stores are in good stores array, return target stores

            return $targetStoreIds;
        }
        // else check stores if they good or bad and fill goodStores and badStores arrays

        $stores = $this->storeCollectionFactory->create();
        $stores->addFieldToFilter(
            'store_id',
            ['in' => $targetStoreIds]
        );
        foreach ($stores as $store) {
            if ($store->getLocale() == $targetLocale) {
                self::$goodStores[$targetLocale][$store->getId()] = $store->getId();
            } else {
                self::$badStores[$targetLocale][$store->getId()] = $store->getId();
                throw new \Exception(__(
                    "Item %1. Locale of target store (id: %2, name: %3, locale: %4) is not match to submission's target locale %5",
                    $this->getId(),
                    $store->getId(),
                    $store->getName(),
                    $store->getLocale(),
                    $targetLocale
                ));
            }
        }

        return $targetStoreIds;
    }
    /**
     *
     * Apply translation to product review
     *
     * On successful translation apply, method MUST write success message in $this->messageManager->addSuccess('...')
     * On unsuccessful translation apply, method MUST throw an exception with error message which will be shown to user
     *   execution will continue with other items
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function applyTranslationReview()
    {
        $entityId = $this->getEntityId();
        $targetStoreIds = $this->getTargetStoreIds();
        $sourceStoreId = $this->getSourceStoreId();
        $translatedData = $this->getTranslatedData();

        foreach ($targetStoreIds as $targetStoreId) {
            $reviewId = null;
            $oldEntity = $this->reviewCollectionFactory->create()->addStoreFilter($sourceStoreId)->getItemById($entityId);

            $needNewEntity = true;

            if ($needNewEntity) {
                $newEntity = $this->reviewFactory->create();
                $newEntity->unsetData('review_id');
                $newEntity->setEntityId($newEntity->getEntityIdByCode(\Magento\Review\Model\Review::ENTITY_PRODUCT_CODE));
                $newEntity->setEntityPkValue($oldEntity->getEntityPkValue());
                $newEntity->setStatusId(\Magento\Review\Model\Review::STATUS_PENDING);
                $newEntity->setData('title', $oldEntity->getTitle());
                $newEntity->setNickname($oldEntity->getNickname());
                $newEntity->addData($translatedData['attributes']);
                $newEntity->setStoreId($targetStoreId);
                $newEntity->setStores($targetStoreId);
                $newEntity->save();
                $this->setData('new_entity_id', $newEntity->getReviewId());
            }
        }
        // if we're here all ok. Update item status, set success message
        $this->setStatusId(self::STATUS_APPLIED);
        $this->save();
        $this->messageManager->addSuccess(__('Translation of Item %1 (%2) successfully applied to all target stores', $this->getId(), $this->getEntityName()));
        $this->updateEntitySubmissionStatus($targetStoreIds);
        $this->removeXml();
    }
    /**
     *
     * Apply translation to catalog category
     *
     * On successful translation apply, method MUST write success message in $this->messageManager->addSuccess('...')
     * On unsuccessful translation apply, method MUST throw an exception with error message which will be shown to user
     *   execution will continue with other items
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function applyTranslationCatalogCategory()
    {
        $entityId = $this->getEntityId();
        $targetStoreIds = $this->getTargetStoreIds();
        $sourceStoreId = $this->getSourceStoreId();
        $translatedData = $this->getTranslatedData();

        foreach ($targetStoreIds as $targetStoreId) {
            $entity = $this->categoryRepository->get($entityId, $targetStoreId);
            $store = $this->storeManager->getStore($targetStoreId);
            $this->storeManager->setCurrentStore($store->getCode());
            $entity->addData($translatedData['attributes']);
            $this->categoryRepository->save($entity);
        }
        // if we're here all ok. Update item status, set success message
        $this->setStatusId(self::STATUS_APPLIED);
        $this->save();
        $this->messageManager->addSuccess(__('Translation of Item %1 (%2) successfully applied to all target stores', $this->getId(), $this->getEntityName()));
        $this->updateEntitySubmissionStatus($targetStoreIds);
        $this->removeXml();
    }

    /**
     * Apply translation to cms block
     *
     * On successful translation apply, method MUST write success message in $this->messageManager->addSuccess('...')
     * On unsuccessful translation apply, method MUST throw an exception with error message which will be shown to user
     *   execution will continue with other items
     *
     * @return void
     * @throws \Exception
     */
    protected function applyTranslationCmsBlock()
    {
        $entityId = $this->getEntityId();
        $targetStoreIds = $this->getTargetStoreIds();
        $sourceStoreId = $this->getSourceStoreId();
        $translatedData = $this->getTranslatedData();

        foreach ($targetStoreIds as $targetStoreId) {
            $oldEntity = $this->blockFactory->create();
            $oldEntity->load($entityId);
            $identifier = $oldEntity->getIdentifier();

            // try to find block with our identifier where target store set directly
            $needNewEntity = true;
            $blocks = $this->blockCollectionFactory->create();
            $blocks->addFieldToFilter('identifier', $identifier);
            $blocks->addStoreFilter($targetStoreId);
            $blocksFound = count($blocks);
            if ($blocksFound) {
                foreach ($blocks as $foundBlock) {
                    $stores = $foundBlock->getStores();
                    if ($stores[0] == $targetStoreId && empty($stores[1])) {
                        // if the only store is target one - update this block
                        $foundBlock->addData($translatedData['attributes']);
                        $needNewEntity = false;
                    } elseif($stores[0] == '0'){
                        $foundBlock = $this->resetStoreViews($foundBlock);
                        $updateStores = $this->removeStoreIdFromEntityStores($foundBlock, $targetStoreId);
                        $foundBlock->setStoreId($updateStores);
                        $foundBlock->save();
                        $this->setData('new_entity_id', $foundBlock->getBlockId());
                    }
                    else {
                        // remove target store from store list and create new block
                        $updateStores = $this->removeStoreIdFromEntityStores($foundBlock, $targetStoreId);
                        $foundBlock->setStoreId($updateStores);
                        $foundBlock->save();
                        $this->setData('new_entity_id', $foundBlock->getBlockId());
                    }
                }
                //$blocks->save();
            }

            if ($needNewEntity) {
                $newEntity = $this->blockFactory->create();
                $newEntity->setTitle($oldEntity->getTitle());
                $newEntity->addData($translatedData['attributes']);
                $newEntity->setStoreId($targetStoreId);
                $newEntity->setIdentifier($oldEntity->getIdentifier());
                $newEntity->setIsActive($oldEntity->getIsActive());
                $newEntity->save();
                $this->setData('new_entity_id', $newEntity->getBlockId());
            }

            //$oldEntity->save();
        }
        // if we're here all ok. Update item status, set success message, remove xml
        $this->setStatusId(self::STATUS_APPLIED);
        $this->save();
        $this->messageManager->addSuccess(__('Translation of Item %1 (%2) successfully applied to all target stores', $this->getId(), $this->getEntityName()));
        $this->updateEntitySubmissionStatus($targetStoreIds);
        $this->removeXml();
    }

    /**
     * Apply translation to cms page
     *
     * On successful translation apply, method MUST write success message in $this->messageManager->addSuccess('...')
     * On unsuccessful translation apply, method MUST throw an exception with error message which will be shown to user
     *   execution will continue with other items
     *
     * @return void
     * @throws \Exception
     */
    protected function applyTranslationCmsPage()
    {
        $entityId = $this->getEntityId();
        $targetStoreIds = $this->getTargetStoreIds();
        $sourceStoreId = $this->getSourceStoreId();
        $translatedData = $this->getTranslatedData();

        foreach ($targetStoreIds as $targetStoreId) {
            $oldEntity = $this->pageFactory->create();
            $oldEntity->load($entityId);
            $underVersionControl = (bool)$oldEntity->getUnderVersionControl();
            $identifier = $oldEntity->getIdentifier();

            // try to find page with our identifier where target store set directly
            $needNewEntity = true;
            $pages = $this->pageCollectionFactory->create();
            $pages->addFieldToFilter('identifier', $identifier);
            $pages->addStoreFilter($targetStoreId);
            $pagesFound = $pages->getSize();
            if ($pagesFound) {
                foreach ($pages as $foundPage) {
                    $stores = $foundPage->getStores();
                    if ($stores[0] == $targetStoreId && empty($stores[1])) {
                        // if the only store is target one - update this page
                        if (!$underVersionControl) {
                            $foundPage->addData($translatedData['attributes']);
                        } else {
                            $this->addNewCmsRevision($foundPage, $translatedData['attributes']);
                        }
                        $needNewEntity = false;
                        $this->setData('new_entity_id', $foundPage->getPageId());
                    } elseif ($stores[0] == '0') {
                        $foundPage = $this->resetStoreViews($foundPage);
                        $updateStores = $this->removeStoreIdFromEntityStores($foundPage, $targetStoreId);
                        $foundPage->setStoreId($updateStores);
                        $foundPage->save();
                        $this->setData('new_entity_id', $foundPage->getPageId());
                    } else {
                        // remove target store from store list and create new page
                        $updateStores = $this->removeStoreIdFromEntityStores($foundPage, $targetStoreId);
                        $foundPage->setStoreId($updateStores);
                        $foundPage->save();
                        $this->setData('new_entity_id', $foundPage->getPageId());
                    }
                }
                //$pages->save();
            }

            if ($needNewEntity) {
                $newEntity = $this->pageFactory->create();
                $newEntity->setData($translatedData['attributes']);
                if(!array_key_exists('content', $translatedData['attributes'])){
                    $newEntity->setContent($oldEntity->getContent());
                }
                if(!array_key_exists('title', $translatedData['attributes'])){
                    $newEntity->setTitle($oldEntity->getTitle());
                }
                $newEntity->setIdentifier($oldEntity->getIdentifier());
                $newEntity->setPageLayout($oldEntity->getPageLayout());
                $newEntity->setIsActive($oldEntity->getIsActive());
                $newEntity->setSortOrder($oldEntity->getSortOrder());
                $newEntity->setLayoutUpdateXml($oldEntity->getLayoutUpdateXml());
                $newEntity->setCustomTheme($oldEntity->getCustomTheme());
                $newEntity->setCustomRootTemplate($oldEntity->getCustomRootTemplate());
                $newEntity->setCustomLayoutUpdateXml($oldEntity->getCustomLayoutUpdateXml());
                $newEntity->setCustomThemeFrom($oldEntity->getCustomThemeFrom());
                $newEntity->setCustomThemeTo($oldEntity->getCustomThemeTo());
                if ($underVersionControl) {
                    $newEntity->setUnderVersionControl($underVersionControl);
                }
                $newEntity->setStoreId([$targetStoreId]);

                //remove old entyty's rule from url-rewrite table (seems it is a magento's bug)
                $this->removeUrlRewrite('cms-page', $entityId, $targetStoreId);

                $newEntity->save();
                $this->setData('new_entity_id', $newEntity->getPageId());
            }

            //$oldEntity->save();
        }
        // if we're here all ok. Update item status, set success message, remove xml
        $this->setStatusId(self::STATUS_APPLIED);
        $this->save();
        $this->messageManager->addSuccess(__('Translation of Item %1 (%2) successfully applied to all target stores', $this->getId(), $this->getEntityName()));
        $this->updateEntitySubmissionStatus($targetStoreIds);
        $this->removeXml();
    }

    /**
     * Creates new revision (M2.0 compatible)
     *
     * @param \Magento\Cms\Model\Page $page
     * @param                         $data
     *
     * @return $this
     */
    protected function addNewCmsRevision(Page $page, $data)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\VersionsCms\Model\Page\Revision $oldRevision */
        $oldRevision = $objectManager->create('Magento\VersionsCms\Model\Page\Revision')->load($page->getData('published_revision_id'));
        /** @var \Magento\VersionsCms\Model\Page\Revision $newRevision */
        $newRevision = $objectManager->create('Magento\VersionsCms\Model\Page\Revision');
        $newRevision->setData($oldRevision->getData());
        $newRevision->addData($data);
        $newRevision->unsetData('revision_id');
        $newRevision->save();
        return $this;
    }

    /**
     * Apply translation to catalog product
     *
     * On successful translation apply, method MUST write success message in $this->messageManager->addSuccess('...')
     * On unsuccessful translation apply, method MUST throw an exception with error message which will be shown to user
     *   execution will continue with other items
     *
     * @return void
     * @throws \Exception
     */
    protected function applyTranslationCatalogProduct()
    {
        $entityId = $this->getEntityId();
        $targetStoreIds = $this->getTargetStoreIds();
        $sourceStoreId = $this->getSourceStoreId();
        $translatedData = $this->getTranslatedData();
        $imageFields = ['image_label', 'thumbnail_label', 'small_image_label', 'swatch_image'];
        foreach ($targetStoreIds as $targetStoreId) {
            $product = $this->productRepository->getById($entityId, false, $targetStoreId);

            $store = $this->storeManager->getStore($targetStoreId);
            $this->storeManager->setCurrentStore($store->getCode());
            $mediaGallery = $product->getMediaGalleryEntries();
            $product->addData($translatedData['attributes']);

            if ($product->getTypeId() == BundleType::TYPE_CODE) {
                $bundleOptions = $this->bundleOption
                        ->getResourceCollection()
                        ->setProductIdFilter($entityId)
                        ->setPositionOrder();
                $bundleOptions->joinValues($sourceStoreId);
                foreach ($bundleOptions as $option) {
                    $optionId = $option->getOptionId();
                    if (!empty($optionId)) {
                        if (empty($translatedData['options']['entity_'.$entityId][$optionId])) {
                            $this->messageManager->addError(__(
                                'Item %1. Translation for bundle option (id %2) not found. Entity skipped.',
                                $this->getId(),
                                $optionId
                            ));
                            return;
                        }
                        $newOptionTitle = $translatedData['options']['entity_'.$entityId][$optionId];
                        $option->setTitle($newOptionTitle);
                        $option->setStoreId($targetStoreId);
                    }
                }
                // M2 currently has an issue #5931. It recreates (delete and add new) options instead of update them
                // Disable save() until that will be fixed
                // $bundleOptions->save();
            } else {
                if($translatedData['options'] != null) {
                    $customOptions = $this->productOption->getProductOptionCollection($product);
                    $newCustomOptions = array();
                    foreach ($customOptions as $option) {
                        $optionId = $option->getData('option_id');
                        $translatedOptions = $translatedData['options'];
                        if($translatedOptions['entity_'.$entityId][$optionId] != null){
                            $option->setTitle($translatedOptions['entity_'.$entityId][$optionId]);
                            $option->setDefaultTitle($translatedOptions['entity_'.$entityId][$optionId]);
                            $option->setStoreTitle($translatedOptions['entity_'.$entityId][$optionId]);
                        }
                        if(isset($translatedOptions['option_'.$optionId])) {
                            foreach ($translatedOptions['option_' . $optionId] as $valueId => $translatedValue) {
                                if ($option->getValueById($valueId) != null) {
                                    $value = $option->getValueById($valueId);
                                    $value->setTitle($translatedValue);
                                    $option->addValue($value);
                                }
                            }
                        }
                        $option->setStoreId($product->getStoreId());
                        $option->save();
                        $newCustomOptions[] = $option;
                        $hasOptions = true;

                    }
                    $product->setCustomOptions($newCustomOptions);
                    // M2 currently doesn't allow to save custom option titles for storeviews
                    // Also it has a bug #5931
                }
            }
            //$this->productRepository->save($product); //returns 'The image content is not valid' error for some products
            $start = microtime(true);
            foreach($translatedData['attributes'] as $attributeName => $attributeValue){
                $product->getResource()->saveAttribute($product, $attributeName);
            }
            $existingImageFields = array_intersect($imageFields, array_keys($translatedData['attributes']));
            if(count($existingImageFields) > 0) {
                foreach ($mediaGallery as $image) {
                    foreach ($existingImageFields as $currentField) {
                        $matchingField = str_replace("_label", "", $currentField);
                        if (in_array($matchingField, $image->getTypes())) {
                            $image->setLabel($translatedData['attributes'][$currentField]);
                        }
                    }
                }
                $product->setMediaGalleryEntries($mediaGallery);
                $product->save();
            }
            $logData = [
                'message' => "Save attribute duration: ".(microtime(true) - $start)." seconds",
            ];
            if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }

        }
        $start = microtime(true);
        // if we're here all ok. Update item status, set success message, remove xml
        $this->setStatusId(self::STATUS_APPLIED);
        $this->save();
        $this->messageManager->addSuccess(__('Translation of Item %1 (%2) successfully applied to all target stores', $this->getId(), $this->getEntityName()));
        $logData = [
            'message' => "Update queue status duration: ".(microtime(true) - $start)." seconds",
        ];
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }


        $start = microtime(true);
        $this->updateEntitySubmissionStatus($targetStoreIds);
        $this->removeXml();
        $logData = [
            'message' => "Update submission status duration: ".(microtime(true) - $start)." seconds",
        ];
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
    }

    /**
     * Apply translation to product attribute
     *
     * On successful translation apply, method MUST write success message in $this->messageManager->addSuccess('...')
     * On unsuccessful translation apply, method MUST throw an exception with error message which will be shown to user
     *   execution will continue with other items
     *
     * @return void
     * @throws \Exception
     */
    protected function applyTranslationProductAttribute()
    {
        $entityId = $this->getEntityId();
        $targetStoreIds = $this->getTargetStoreIds();
        $sourceStoreId = $this->getSourceStoreId();
        $translatedData = $this->getTranslatedData();

        foreach ($targetStoreIds as $targetStoreId) {
            $optionsArray = [];

            $stores = [0];
            $stores = array_merge($stores, $this->helper->getAllStoresIds());
            foreach ($stores as $storeId) {
                $attribute = $this->attributeRepository->get(
                    ProductAttributeInterface::ENTITY_TYPE_CODE,
                    $entityId
                );
                $attribute->setStoreId($storeId);

                $options = $this->attributeOptionManagement->getItems(
                    ProductAttributeInterface::ENTITY_TYPE_CODE,
                    $entityId
                );

                foreach ($options as $option) {
                    $optionId = $option->getValue();
                    if (empty($optionId)) {
                        continue;
                    }
                    $optionsArray['order'][$optionId] = (int)$option->getSortOrder();
                    if ($storeId == $targetStoreId && !empty($translatedData['options']['entity_'.$entityId][$optionId])) {
                        $optionsArray['value'][$optionId][$storeId] = $translatedData['options']['entity_'.$entityId][$optionId];
                    } else {
                        $optionsArray['value'][$optionId][$storeId] = $option->getLabel();
                    }
                }
            }

            $attribute = $this->attributeRepository->get(
                ProductAttributeInterface::ENTITY_TYPE_CODE,
                $entityId
            );
            $storeLabels = $attribute->getStoreLabels();
            $storeLabels[$targetStoreId] = $translatedData['attributes']['frontend_label'];
            $attribute->setStoreLabels($storeLabels);
            $attribute->setOption($optionsArray);
            $this->attributeRepository->save($attribute);
        }

        // if we're here all ok. Update item status, set success message, remove xml
        $this->setStatusId(self::STATUS_APPLIED);
        $this->save();
        $this->messageManager->addSuccess(__('Translation of Item %1 (%2) successfully applied to all target stores', $this->getId(), $this->getEntityName()));
        $this->updateEntitySubmissionStatus($targetStoreIds);
        $this->removeXml();
    }

    /**
     * Apply translation to customer attribute
     *
     * On successful translation apply, method MUST write success message in $this->messageManager->addSuccess('...')
     * On unsuccessful translation apply, method MUST throw an exception with error message which will be shown to user
     *   execution will continue with other items
     *
     * @return void
     * @throws \Exception
     */
    protected function applyTranslationCustomerAttribute()
    {
        $entityId = $this->getEntityId();
        $targetStoreIds = $this->getTargetStoreIds();
        $sourceStoreId = $this->getSourceStoreId();
        $translatedData = $this->getTranslatedData();

        foreach ($targetStoreIds as $targetStoreId) {
            $optionsArray = [];

            $stores = [0];
            $stores = array_merge($stores, $this->helper->getAllStoresIds());
            foreach ($stores as $storeId) {
                $attribute = $this->attributeRepository->get(
                    CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                    $entityId
                );
                $attribute->setStoreId($storeId);

                $options = $this->attributeOptionManagement->getItems(
                    CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                    $entityId
                );

                foreach ($options as $option) {
                    $optionId = $option->getValue();
                    if (empty($optionId)) {
                        continue;
                    }
                    $optionsArray['order'][$optionId] = (int)$option->getSortOrder();
                    if ($storeId == $targetStoreId) {
                        $optionsArray['value'][$optionId][$storeId] = $translatedData['options']['entity_'.$entityId][$optionId];
                    } else {
                        $optionsArray['value'][$optionId][$storeId] = $option->getLabel();
                    }
                }
            }

            $attribute = $this->attributeRepository->get(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                $entityId
            );
            $storeLabels = $attribute->getStoreLabels();
            $storeLabels[$targetStoreId] = $translatedData['attributes']['frontend_label'];
            $attribute->setStoreLabels($storeLabels);
            $attribute->setOption($optionsArray);
            $this->attributeRepository->save($attribute);
        }
        // if we're here all ok. Update item status, set success message, remove xml
        $this->setStatusId(self::STATUS_APPLIED);
        $this->save();
        $this->messageManager->addSuccess(__('Translation of Item %1 (%2) successfully applied to all target stores', $this->getId(), $this->getEntityName()));
        $this->updateEntitySubmissionStatus($targetStoreIds);
        $this->removeXml();
    }

    /**
     * Get path to xml file
     *
     * @return string
     */
    protected function getFilePath()
    {
        $xmlFolder = $this->translationService->getReceiveFolder();
        $filename = 'item_'.$this->getId();
        return $xmlFolder.'/'.$filename.'.xml';
    }

    /**
     * Get translated data from xml
     *
     * @return array
     */
    protected function getTranslatedData()
    {
        $filePath = $this->getFilePath();
        $xml = $this->file->read($filePath);

        $xmlData = $this->parseXml($xml);

        return $xmlData;
    }

    /**
     * Parse given xml string
     *
     * @param $xml
     *
     * @return array
     * @throws \Exception
     */
    protected function parseXml($xml)
    {
        $dom = $this->domDocumentFactory->create();
        $dom->preserveWhiteSpace = false;

        try {
            $dom->loadXML($xml);

            $contents = $dom->getElementsByTagName('content');

            $xmlData = [];
            foreach ($contents as $content) {
                if (!is_null($content->attributes)) {
                    foreach ($content->attributes as $attrName => $attrNode) {
                        if ('object_type' == $attrName) {
                            $xmlData['object_type_id'] = $attrNode->value;
                        } elseif ('object_id' == $attrName) {
                            $xmlData['object_id'] = $attrNode->value;
                        }
                    }
                }
            }

            $names = $dom->getElementsByTagName('name');
            foreach ($names as $name) {
                $xmlData['name'] = $name->nodeValue;
            }

            $attributes = $dom->getElementsByTagName('attribute');
            $xmlData['attributes'] = $this->parseXmlGetAttributesArray($attributes);


            $options = $dom->getElementsByTagName('option');
            $xmlData['options'] = $this->parseXmlGetOptionsArray($options);

            return $xmlData;
        } catch (\Exception $e) {
            $logData = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => 'Exception in parsing target XML. '.$e->getMessage(),
            ];
            if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
            throw new \Exception($this->bgLogger->bgLogMessage($logData), $e->getCode(), $e);
        }
    }

    /**
     * get array of attributes
     *
     * @param \DOMNodeList[] $attributes
     *
     * @return array
     */
    protected function parseXmlGetAttributesArray($attributes)
    {
        $attributesArray = [];

        foreach ($attributes as $attribute) {
            $translatedValue = $attribute->nodeValue;
            if (is_null($attribute->attributes)) {
                continue;
            }
            $attr_id = '';
            $attr_code = '';
            foreach ($attribute->attributes as $attrName => $attrNode) {
                switch ($attrName) {
                    case 'attribute_id':
                        $attr_id = $attrNode->value;
                        continue 2;
                    case 'attribute_code':
                        $attr_code = $attrNode->value;
                        continue 2;
                    case 'max_length':
                        $xmlData['max_length'][] = $attrNode->value;
                        continue 2;
                }
            }
            $attributesArray[$attr_code] = $translatedValue;
        }

        return $attributesArray;
    }

    /**
     * get array of options
     *
     * @param \DOMNodeList[] $options
     *
     * @return array
     */
    protected function parseXmlGetOptionsArray($options)
    {
        $optionsArray = [];

        foreach ($options as $option) {
            $translatedValue = $option->nodeValue;
            if (is_null($option->attributes)) {
                continue;
            }
            $option_id = '';
            $parent = '';
            foreach ($option->attributes as $optionName => $optionNode) {
                switch ($optionName) {
                    case 'option_id':
                        $option_id = $optionNode->value;
                        continue 2;
                    case 'parent':
                        $parent = $optionNode->value;
                        continue 2;
                    case 'max_length':
                        $xmlData['option_max_length'][] = $optionNode->value;
                        continue 2;
                }
            }
            $optionsArray[$parent][$option_id] = $translatedValue;
        }

        return $optionsArray;
    }

    /**
     * remove xml file
     */
    protected function removeXml()
    {
        $filePath = $this->getFilePath();
        $res = $this->file->rm($filePath);
    }

    /**
     * calculate new entity stores
     * get current stores
     * if 'all store views' - get ids of all stores
     * remove required store
     *
     * @param mixed $entity
     * @param int   $storeIdToRemove
     *
     * @return array
     */
    protected function removeStoreIdFromEntityStores($entity, $storeIdToRemove)
    {
        $stores = $entity->getStores();
        if (array_search(0, $stores) !== false) {
            // assigned to all stores
            // fix if user selected both all stores and some specific stores
            $stores = [0];
        } elseif (($key = array_search($storeIdToRemove, $stores)) !== false) {
            unset($stores[$key]);
        }

        return $stores;
    }

    protected function resetStoreViews($entity){
        $stores = $this->storeManager->getStores();
        $storeIds = [];
        foreach($stores as $currentStore){
            $storeIds[] = $currentStore->getId();
        }
        $entity->setStoreId($storeIds);
        return $entity;
    }

    /**
     * Logs errors to file and put them to queue
     *
     * @param \Exception                           $e
     * @param \TransPerfect\GlobalLink\Model\Queue $queue
     *
     * @return $this
     */
    protected function _logError(\Exception $e, Queue $queue)
    {
        $logData = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'message' => $e->getMessage(),
        ];
        $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
        $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
        return $this;
    }

    /**
     * update submission status of current entity for given stores
     *
     * @param iarray $targetStoreIds
     */
    protected function updateEntitySubmissionStatus($targetStoreIds)
    {
        $statusesByStores = $this->translationStatusResource->getForTypeAndEntity($this->getEntityTypeId(), $this->getEntityId(), $targetStoreIds);
        foreach ($statusesByStores as $status) {
            $storeId = $status['store_view_id'];
            switch ($status['translation_status']) {
                case TranslationStatus::STATUS_ENTITY_TRANSLATION_REQUIRED:
                    // do not change status
                    $targetStoreIds = array_diff($targetStoreIds, [$storeId]);
                    break;

                case TranslationStatus::STATUS_ENTITY_IN_PROGRESS:
                    $items = $this->getCollection();
                    $items->addFieldToFilter('entity_id', $this->getEntityId());
                    $items->addFieldToFilter('entity_type_id', $this->getEntityTypeId());
                    $items->addFieldToFilter('target_stores', ['like' => '%,'.$storeId.',%']);
                    $items->setOrder('id', 'desc');
                    $items->setPageSize(1)->setCurPage(1);
                    foreach ($items as $item) {
                        if ($this->getId() != $item->getId()) {
                            // applied item is not last from all which were created for the entity
                            // so do not change status
                            $targetStoreIds = array_diff($targetStoreIds, [$storeId]);
                        }
                    }
                    break;
            }
        }
        $allEntities[$this->getEntityTypeId()] = [$this->getEntityId()];
        $this->translationStatusResource->moveToTranslated($allEntities, $targetStoreIds);
    }

    /**
     * send download confirmation for document of current item
     */
    protected function sendDownloadConfirmation()
    {
        try {
            $confirmationTicket = $this->translationService->sendDownloadConfirmation($this->getTargetTicket());
        } catch (\Exception $e) {
            $errorMessage = 'Exception while sending download confirmation for target '.$this->getTargetTicket().': '.$e->getMessage();
            $logData = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $errorMessage,
                ];
            if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
            $queue = $this->_getQueue();
            $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
        }
    }

    /**
     * remove data from url_rewrite table
     *
     * @param string $entityType
     * @param int    $entityId
     * @param int    $storeId
     */
    protected function removeUrlRewrite($entityType, $entityId, $storeId)
    {
        $ur = $this->urlRewriteCollectionFactory->create();
        $ur->addFieldToFilter(
            'entity_type',
            ['in' => [$entityType]]
        );
        $ur->addFieldToFilter(
            'entity_id',
            ['in' => [$entityId]]
        );
        $ur->addFieldToFilter(
            'store_id',
            ['in' => [$storeId]]
        );

        $ur->walk('delete');
    }
}
