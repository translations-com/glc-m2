<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 */
namespace TransPerfect\GlobalLink\Model\Observer\Entity\Status;

use TransPerfect\GlobalLink\Model\Queue\Item;
use TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory as ItemCollectionFactory;
use TransPerfect\GlobalLink\Model\Source\Grid\Active\Submission as SubmissionState;
use TransPerfect\GlobalLink\Helper\Data as HelperData;
use TransPerfect\GlobalLink\Cron\SubmitTranslations;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Bundle\Model\Option as BundleOption;
use TransPerfect\GlobalLink\Model\ResourceModel\Entity\TranslationStatus as TranslationStatusResource;
use Psr\Log\LoggerInterface;

/**
 * Class TranslationRequired
 */
class TranslationRequired implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Item collection factory
     *
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory
     */
    protected $itemCollectionFactory;

    /**
     * @var \TransPerfect\GlobalLink\Cron\SubmitTranslations
     */
    protected $submitTranslations;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $translatedFields;

    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Bundle\Model\Option
     */
    protected $bundleOption;

    /*
     * @var \Magento\Framework\Model\AbstractModel $savingEntity
     */
    protected $savingEntity;

    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;

    /**
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Entity\TranslationStatus
     */
    protected $translationStatusResource;

    /**
     * @var int
     */
    protected $storeId;
    protected $defaultStoreId;
    protected $categoryStoreId;
    protected $productStoreId;
    protected $entityId;
    protected $entityTypeId;

    /**
     * Constructor
     *
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param SubmitTranslations    $submitTranslations
     * @param StoreManagerInterface $storeManager
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface  $productRepository
     * @param BundleOption          $bundleOption
     * @param HelperData            $helper
     * TranslationStatusResource    $translationStatusResource
     * @param LoggerInterface       $logger
     */
    public function __construct(
        ItemCollectionFactory $itemCollectionFactory,
        SubmitTranslations $submitTranslations,
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        BundleOption $bundleOption,
        HelperData $helper,
        TranslationStatusResource $translationStatusResource,
        LoggerInterface $logger
    ) {
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->submitTranslations = $submitTranslations;
        $this->_storeManager = $storeManager;
        $this->storeId = ''; // edited store
        $this->adminStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        $this->defaultStoreId = $storeManager->getDefaultStoreView()->getId();
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->bundleOption = $bundleOption;
        $this->helper = $helper;
        $this->translationStatusResource = $translationStatusResource;
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
        $this->savingEntity = $observer->getObject();

        if ($this->savingEntity->isObjectNew()) {
            return;
        }

        $this->entityId = $this->savingEntity->getId();
        $this->entityTypeId = $this->getEntityTypeId();

        if (empty($this->entityTypeId)) {
            return;
        }

        $this->storeId = $this->getEditedStoreId();

        if ($this->storeId != $this->adminStoreId && $this->storeId != $this->defaultStoreId) {
            // translation required only if entity's been edited in source language
            return;
        }

        $this->categoryStoreId = $this->storeId;
        $this->productStoreId = $this->storeId;
        if ($this->storeId == $this->adminStoreId) {
            $this->categoryStoreId = $this->defaultStoreId;
            $this->productStoreId = $this->defaultStoreId;
        }

        $sourceStoreLocaleCode = $this->_storeManager->getStore($this->storeId)->getLocale();

        /*
         * check items related to current entity
         */
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter('entity_id', $this->entityId);
        $items->addFieldToFilter('entity_type_id', $this->entityTypeId);
        $items->addFieldToFilter('status_id', ['eq' => Item::STATUS_NEW]);
        $items->addFieldToFilter('pd_locale_iso_code', ['neq' => $sourceStoreLocaleCode]);

        $excludeStores[$this->defaultStoreId] = $this->defaultStoreId;
        // edited entity hasn't been sent yet for these stores
        // so do not need to set 'translation_required' for them
        foreach ($items as $item) {
            foreach (explode(',', trim($item->getTargetStores(), ',')) as $storeId) {
                if (!empty($storeId)) {
                    $excludeStores[$storeId] = $storeId;
                }
            }
        }

        //update entity status for all stores except excluded ones
        $allStores = $this->helper->getAllStoresIds();
        $storesToUpdate = array_diff($allStores, $excludeStores);

        if (empty($storesToUpdate)) {
            // all stores still in queue and haven't been sent yet
            return;
        }

        // check if translation required based on changed fields
        $this->translatedFields = $this->submitTranslations->getFields($this->entityTypeId, $this->entityId);
        if ($this->isTranslationRequired()) {
            $allEntities[$this->entityTypeId] = [$this->entityId];
            $this->translationStatusResource->moveToTranslationRequired($allEntities, $storesToUpdate);
        }
    }

    /**
     * get edited store id
     *
     * @return int
     */
    protected function getEditedStoreId()
    {
        switch ($this->entityTypeId) {
            case HelperData::CMS_BLOCK_TYPE_ID:
            case HelperData::CMS_PAGE_TYPE_ID:
            case HelperData::PRODUCT_ATTRIBUTE_TYPE_ID:
            case HelperData::CUSTOMER_ATTRIBUTE_TYPE_ID:
                return $this->adminStoreId;

            case HelperData::CATALOG_CATEGORY_TYPE_ID:
                return $this->savingEntity->getData('store_id');

            case HelperData::CATALOG_PRODUCT_TYPE_ID:
                return $this->savingEntity->getOrigData('store_id');
        }

        return $this->adminStoreId;
    }

    /**
     * check if new translation required
     *
     * @return bool
     */
    protected function isTranslationRequired()
    {
        $requireTranslate = false;

        switch ($this->entityTypeId) {
            case HelperData::CMS_BLOCK_TYPE_ID:
            case HelperData::CMS_PAGE_TYPE_ID:
                //$requireTranslate = $this->isChangesInFieldsSimple();
                // now entity status belongs to target (not source), there is no strict relation
                // source-target for blocks and pages
                $requireTranslate = false;
                break;

            case HelperData::PRODUCT_ATTRIBUTE_TYPE_ID:
            case HelperData::CUSTOMER_ATTRIBUTE_TYPE_ID:
                $requireTranslate = $this->isChangesInAttribute();
                break;

            case HelperData::CATALOG_CATEGORY_TYPE_ID:
                $requireTranslate = $this->isChangesInCategory();
                break;

            case HelperData::CATALOG_PRODUCT_TYPE_ID:
                $requireTranslate = $this->isChangesInProduct();
                break;
        }

        return $requireTranslate;
    }

    /**
     * check translated fields for changes
     * for cms pages and cms blocks
     *
     * @return bool
     */
    protected function isChangesInFieldsSimple()
    {
        if (empty($this->translatedFields)) {
            return false;
        }
        $oldData = $this->savingEntity->getOrigData();
        $newData = $this->savingEntity->getData();
        if (empty($oldData) || empty($newData)) {
            return false;
        }

        foreach ($this->translatedFields as $field) {
            $fieldName = $field['name'];
            if (empty($newData[$fieldName])) {
                continue;
            } elseif (empty($oldData[$fieldName])) {
                return true;
            }
            if (HelperData::isComplex($oldData[$fieldName]) || HelperData::isComplex($newData[$fieldName])) {
                continue;
            }

            if ($oldData[$fieldName] != $newData[$fieldName]) {
                return true;
            }
        }

        return false;
    }

    /**
     * check translated fields for changes
     * for categories
     *
     * @return bool
     */
    protected function isChangesInCategory()
    {
        if (empty($this->translatedFields)) {
            return false;
        }
        $oldData = $this->savingEntity->getOrigData();
        $newData = $this->savingEntity->getData();
        if (empty($oldData)) {
            $category = $this->categoryRepository->get($this->entityId, $this->categoryStoreId);
            $oldData = $category->getData();
        }
        if (empty($oldData) || empty($newData)) {
            return false;
        }

        foreach ($this->translatedFields as $field) {
            $fieldName = $field['name'];
            if (empty($newData[$fieldName])) {
                continue;
            } elseif (empty($oldData[$fieldName])) {
                return true;
            }

            if (HelperData::isComplex($oldData[$fieldName]) || HelperData::isComplex($newData[$fieldName])) {
                continue;
            }

            if ($oldData[$fieldName] != $newData[$fieldName]) {
                return true;
            }
        }

        return false;
    }

    /**
     * check attributes fields and options for changes
     * for product attr and customer attr
     *
     * @return bool
     */
    protected function isChangesInAttribute()
    {
        if (empty($this->translatedFields)) {
            return false;
        }
        $oldData = $this->savingEntity->getOrigData();
        $newData = $this->savingEntity->getData();
        if (empty($oldData) || empty($newData)) {
            return false;
        }

        if (is_array($newData['frontend_label'])) {
            if ($oldData['frontend_label'] != $newData['frontend_label'][$this->storeId]) {
                return true;
            }
        } else {
            if ($oldData['frontend_label'] != $newData['frontend_label']) {
                return true;
            }
        }

        $options = $this->savingEntity->getOptions();
        if ($options != null) {
            foreach ($options as $option) {
                $value = $option->getValue();
                $label = $option->getLabel();
                if (empty($value)) {
                    continue;
                }
                $oldOptions[$value] = $label;
            }

            $newOptionsAll = $newData['option']['value'];
            foreach ($newOptionsAll as $value => $labels) {
                $newOptions[$value] = $labels[$this->storeId];
            }
            if (!empty(array_diff($oldOptions, $newOptions))) {
                return true;
            }
        }

        return false;
    }

    /**
     * check product options for changes
     *
     * @return bool
     */
    protected function isChangesInProduct()
    {
        if (empty($this->translatedFields)) {
            return false;
        }
        $oldData = $this->savingEntity->getOrigData();
        $newData = $this->savingEntity->getData();

        $product = $this->productRepository->getById($this->entityId, false, $this->productStoreId);

        if (empty($oldData) || empty($newData)) {
            return false;
        }
        foreach ($this->translatedFields as $field) {
            $fieldName = $field['name'];
            if (empty($newData[$fieldName])) {
                continue;
            } elseif (empty($oldData[$fieldName])) {
                return true;
            }
            if (HelperData::isComplex($oldData[$fieldName]) || HelperData::isComplex($newData[$fieldName])) {
                continue;
            }
            if ($oldData[$fieldName] != $newData[$fieldName]) {
                return true;
            }
        }
        if ($product->getTypeId() == BundleType::TYPE_CODE && $newData['type_id'] == BundleType::TYPE_CODE) {
            $oldBundleOptions = $this->getBundleOptions();

            $newBundleOptions = [];
            foreach ($newData['bundle_options_data'] as $optionData) {
                $newBundleOptions[$optionData['option_id']] = $optionData['title'];
            }

            if (!empty(array_diff($oldBundleOptions, $newBundleOptions))) {
                return true;
            }
        }

        return false;
    }

    /**
     * get type of entity
     *
     * @return int
     */
    protected function getEntityTypeId()
    {
        $entityTypeId = '';

        if ($this->savingEntity instanceof \Magento\Cms\Api\Data\BlockInterface) {
            $entityTypeId = HelperData::CMS_BLOCK_TYPE_ID;
        } elseif ($this->savingEntity instanceof \Magento\Cms\Api\Data\PageInterface) {
            $entityTypeId = HelperData::CMS_PAGE_TYPE_ID;
        } elseif ($this->savingEntity instanceof \Magento\Catalog\Api\Data\CategoryInterface) {
            $entityTypeId = HelperData::CATALOG_CATEGORY_TYPE_ID;
        } elseif ($this->savingEntity instanceof \Magento\Catalog\Api\Data\ProductInterface) {
            $entityTypeId = HelperData::CATALOG_PRODUCT_TYPE_ID;
        } elseif ($this->savingEntity instanceof \Magento\Catalog\Model\ResourceModel\Eav\Attribute) {
            $entityTypeId = HelperData::PRODUCT_ATTRIBUTE_TYPE_ID;
        } elseif ($this->savingEntity instanceof \Magento\Customer\Model\Attribute) {
            $entityTypeId = HelperData::CUSTOMER_ATTRIBUTE_TYPE_ID;
        }

        return $entityTypeId;
    }

    /**
     * get bundle options as array
     *
     * @return array
     */
    protected function getBundleOptions()
    {
        $result = [];
        $options = $this->bundleOption
                ->getResourceCollection()
                ->setProductIdFilter($this->entityId)
                ->setPositionOrder();
        $options->joinValues($this->productStoreId);
        foreach ($options as $option) {
            $title = $option->getTitle();
            $optionId = $option->getOptionId();
            $result[$optionId] = $title;
        }

        return $result;
    }
}
