<?php
namespace TransPerfect\GlobalLink\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as CmsPageCollection;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as CmsBlockCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Model\ResourceModel\Db\Context;
use TransPerfect\GlobalLink\Helper\Product;
use TransPerfect\GlobalLink\Model\Queue\ItemFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\Banner\Model\ResourceModel\Banner\CollectionFactory as BannerCollectionFactory;

/**
 * Class Queue
 *
 * @package TransPerfect\GlobalLink\Model\ResourceModel
 */
class Queue extends AbstractDb
{
    /**
     * @var \Magento\Cms\Model\ResourceModel\Block\CollectionFactory
     */
    protected $cmsBlockCollectionFactory;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $cmsPageCollectionFactory
     */
    protected $cmsPageCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var array
     */
    protected $includedCmsBlockIds = [];

    /**
     * @var array
     */
    protected $includedCategoryIds = [];

    /**
     * @var \TransPerfect\GlobalLink\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Store\Model\ResourceModel\Store\CollectionFactory
     */
    protected $storeCollectionFactory;

    /**
     * \TransPerfect\GlobalLink\Model\Queue\ItemFactory
     */
    protected $itemFactory;
    /**
     * @var \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory
     */
    protected $bannerContents;

    private $includedBannerIds = [];

    protected $bannerCollectionFactory;
    /**
     * Queue constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context               $context
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory         $cmsPageCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  $productCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Cms\Model\ResourceModel\Block\CollectionFactory        $cmsBlockCollectionFactory
     * @param \TransPerfect\GlobalLink\Helper\Product                         $productHelper
     * @param \Magento\Framework\Event\ManagerInterface                       $eventManager
     * @param \TransPerfect\GlobalLink\Model\Queue\ItemFactory                $itemFactory
     * @param \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory    $bannerContents
     * @param null                                                            $connectionName
     */
    public function __construct(
        Context $context,
        CmsPageCollection $cmsPageCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        CmsBlockCollectionFactory $cmsBlockCollectionFactory,
        Product $productHelper,
        ManagerInterface $eventManager,
        StoreCollectionFactory $storeCollectionFactory,
        ItemFactory $itemFactory,
        BannerCollectionFactory $bannerCollectionFactory,
        $connectionName = null
    ) {
        $this->eventManager = $eventManager;
        $this->cmsPageCollectionFactory = $cmsPageCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->cmsBlockCollectionFactory = $cmsBlockCollectionFactory;
        $this->productHelper = $productHelper;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->itemFactory = $itemFactory;
        $this->bannerCollectionFactory = $bannerCollectionFactory;
        parent::__construct($context, $connectionName);
    }

    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('globallink_job_queue', 'id');
    }

    /**
     * Get items ids assigned to current queue
     *
     * @param int $id
     *
     * @return array
     */
    protected function lookupItemsIds($id)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getTable('globallink_job_items'),
            ['entity_type_id', 'id']
        )->where(
            'queue_id = :queue_id'
        );
        $binds = [':queue_id' => (int)$id];
        return $connection->fetchAll($select, $binds);
    }

    /**
     * Perform operations after object save
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $items = (array) $object->getItems();
        $targetStoreIds = (array) $object->getLocalizations();

        /*
         * Important: code inside this 'if' works only while submission create
         * code outside 'if' works on every Queue save (status update for example)
         */
        if (!empty($items) && !empty($targetStoreIds)) {
            $object->setTargetStoreIds($targetStoreIds);

            $stores = $this->storeCollectionFactory->create();
            $stores->addFieldToFilter(
                'store_id',
                ['in' => $targetStoreIds]
            );
            foreach ($stores as $store) {
                $localizations[$store->getLocale()][$store->getId()] = $store->getId();
            }
            $object->setLocalizations($localizations);

            $table = $this->getTable('globallink_job_items');
            $data = []; /* see also _includeAssociatedEntities() */
            foreach ($localizations as $localization => $targetStores) {
                foreach ($items as $itemId => $itemName) {
                    $data[] = [
                        'queue_id' => (int) $object->getId(),
                        'entity_id' => (int) $itemId,
                        'entity_name' => $itemName,
                        'entity_type_id' => (int) $object->getEntityTypeId(),
                        'pd_locale_iso_code' => $localization,
                        'target_stores' => ','.implode(',', $targetStores).',',  /*need commas here for LIKE condition*/
                        'parent_id' => null,
                    ];
                }
            }

            // Add included widgets - Only for CMS Pages
            if ($object->getIncludeCmsBlockWidgets() && $object->getEntityTypeId() == \TransPerfect\GlobalLink\Helper\Data::CMS_PAGE_TYPE_ID) {
                $this->_findCmsBlocks($object);
                $this->_findBanners($object);
                $this->_includeAssociatedEntities($object, $this->includedBannerIds, \TransPerfect\GlobalLink\Helper\Data::BANNER_ID, $data, false);
                $this->_includeAssociatedEntities($object, $this->includedCmsBlockIds, \TransPerfect\GlobalLink\Helper\Data::CMS_BLOCK_TYPE_ID, $data, true);
            } elseif($object->getIncludeCmsBlockWidgets() && $object->getEntityTypeId() == \TransPerfect\GlobalLink\Helper\Data::CATALOG_PRODUCT_TYPE_ID){
                $this->_findCmsBlocksProduct($object);
            }

            // Add associated and parent categories to queue - Only for products
            if ($object->getIncludeAssociatedAndParentCategories()) {
                $this->_fetchProductCategories($object);
                $this->_includeAssociatedEntities($object, $this->includedCategoryIds, \TransPerfect\GlobalLink\Helper\Data::CATALOG_CATEGORY_TYPE_ID, $data);
            }
            $this->getConnection()->insertMultiple($table, $data);
            $object->setProcessedData($data);

            foreach ($data as $dataObject) {
                $item = $this->itemFactory->create();
                $item->setData($dataObject);
                $item->setStoreId($object->getOriginStoreId());
                $this->eventManager->dispatch($item->getEventPrefix().'_save_before', ['object' => $item]);
            }
        }
        $this->eventManager->dispatch('transperfect_globallink_queue_save_after', ['queue' => $object]);
        return parent::_afterSave($object);
    }

    /**
     * Perform operations after object load
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getId()) {
            $items = $this->lookupItemsIds($object->getId());
            $object->setData('items', $items);
        }

        return parent::_afterLoad($object);
    }

    /**
     * Process data before deleting
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected function _beforeDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        $condition = ['queue_id = ?' => (int)$object->getId()];

        $this->getConnection()->delete($this->getTable('globallink_job_items'), $condition);

        return parent::_beforeDelete($object);
    }

    /**
     * Find CMS block widgets in CMS page content
     *
     * @param $object
     *
     * @return $this
     */
    protected function _findCmsBlocks($object)
    {
        $cmsPageCollection = $this->cmsPageCollectionFactory->create();
        $cmsPageCollection->addFieldToFilter('page_id', ['in' => array_keys($object->getItems())]);
        foreach ($cmsPageCollection as $cmsPage) {
            $blockIDs = [];
            $matches = [];
            $lastVersionArray = explode(".", $this->productHelper->getMagentoVersion());
            preg_match_all('/{{widget type="(.{0,100})"(.{1,115})block_id="(.{0,100})"(.{0,115})}}/', $cmsPage->getContent(), $matches);
            //preg_match_all('/{{widget.+block_id="(\d+)" type_name="(.+)"}}/', $cmsPage->getContent(), $matches);
            //preg_match_all('/{{block.+block_id="(\d+)" type_name="(.+)"}}/', $cmsPage->getContent(), $matchesBlock);
            if (!empty($matches) && isset($matches[3])) {
                $blockIDs = array_unique($matches[3]);
                foreach($blockIDs as $blockID){
                    $cmsBlockCollection = $this->cmsBlockCollectionFactory->create();
                    if($lastVersionArray[2] < 7 && $lastVersionArray[1] == "4"){
                        $cmsBlockCollection->addFieldToFilter('block_id', $blockID);
                    } else{
                        $cmsBlockCollection->addFieldToFilter('identifier', $blockID);
                    }
                    if ($cmsBlockCollection->getSize() > 0) {
                        $blockName = $cmsBlockCollection->getFirstItem()->getTitle();
                        if(!empty($this->includedCmsBlockIds[$blockID])){
                            $this->includedCmsBlockIds[$blockID]['parent'] = $this->includedCmsBlockIds[$blockID]['parent'] . ','. $cmsPage->getData('page_id');
                        } else{
                            $this->includedCmsBlockIds[$blockID]['name'] = $blockName;
                            $this->includedCmsBlockIds[$blockID]['parent'] = $cmsPage->getData('page_id');
                            $this->includedCmsBlockIds[$blockID]['id'] = $cmsBlockCollection->getFirstItem()->getData('block_id');
                        }

                    }
                    $cmsBlockCollection->clear();
                }
            }
        }
        return $this;
    }
    /**
     * Find CMS block widgets in CMS page content
     *
     * @param $object
     *
     * @return $this
     */
    protected function _findBanners($object)
    {
        $cmsPageCollection = $this->cmsPageCollectionFactory->create();
        $cmsPageCollection->addFieldToFilter('page_id', ['in' => array_keys($object->getItems())]);
        foreach ($cmsPageCollection as $cmsPage) {
            $bannerIDs = [];
            $matches = [];
            preg_match_all('/{{widget type="(.{0,100})"(.{0,100})banner_ids="([ 0-9\,]+)"(.{0,250})}}/', $cmsPage->getContent(), $matches);
            if (!empty($matches) && isset($matches[3])) {
                $bannerIDs = array_unique($matches[3]);
                foreach($bannerIDs as $bannerID){
                    $bannerCollection = $this->bannerCollectionFactory->create();
                    if(strpos($bannerID, ',') !== false) {
                        $bannerCollection->addFieldToFilter('banner_id',
                            ['in' => explode(",", $bannerID)]);
                    } else{
                        $bannerCollection->addFieldToFilter('banner_id', $bannerID);
                    }
                    if ($bannerCollection->getSize() > 0) {
                        foreach($bannerCollection as $banner){
                            if(!empty($this->includedBannerIds[$banner->getId()])){
                                $this->includedBannerIds[$banner->getId()]['parent'] = $this->includedBannerIds[$banner->getId()]['parent'] . ','. $cmsPage->getData('page_id');
                            } else{
                                $this->includedBannerIds[$banner->getId()]['name'] = $banner->getName();
                                $this->includedBannerIds[$banner->getId()]['parent'] = $cmsPage->getData('page_id');
                            }
                        }

                    }
                }
            }
        }
        return $this;
    }
    /**
     * Fetch all product categories
     *
     * @param $object
     *
     * @return $this
     */
    protected function _fetchProductCategories($object)
    {
        $this->includedCategoryIds = $this->productHelper->getAssociatedAndParentCategories($object->getItems());
        return $this;
    }

    /**
     * Include associated entities to the same queue
     *
     * @param $object
     * @param $includedEntities
     * @param $entityTypeId
     * @param $data
     *
     * @return $this
     */
    protected function _includeAssociatedEntities($object, $includedEntities, $entityTypeId, &$data, $isBlock = false)
    {
        $localizations = (array) $object->getLocalizations();

        foreach ($localizations as $localization => $targetStores) {
            if($isBlock){
                foreach ($includedEntities as $item) {
                    $data[] = [
                        'queue_id' => (int)$object->getId(),
                        'entity_id' => (int)$item['id'],
                        'entity_name' => $item['name'],
                        'entity_type_id' => $entityTypeId,
                        'pd_locale_iso_code' => $localization,
                        'parent_id' => $item['parent'],
                        'target_stores' => ',' . implode(',', $targetStores) . ',',  /*need commas here for LIKE condition*/
                    ];
                }
            } else if($entityTypeId == 3){
                foreach ($includedEntities as $itemId => $itemObject) {
                    $data[] = [
                        'queue_id' => (int)$object->getId(),
                        'entity_id' => (int)$itemId,
                        'entity_name' => $itemObject,
                        'entity_type_id' => $entityTypeId,
                        'pd_locale_iso_code' => $localization,
                        'target_stores' => ',' . implode(',', $targetStores) . ',',  /*need commas here for LIKE condition*/
                        'parent_id' => null,
                    ];
                }
            } else {
                foreach ($includedEntities as $itemId => $itemObject) {
                    $data[] = [
                        'queue_id' => (int)$object->getId(),
                        'entity_id' => (int)$itemId,
                        'entity_name' => $itemObject['name'],
                        'entity_type_id' => $entityTypeId,
                        'pd_locale_iso_code' => $localization,
                        'target_stores' => ',' . implode(',', $targetStores) . ',',  /*need commas here for LIKE condition*/
                        'parent_id' => null,
                    ];
                }
            }
        }

        return $this;
    }

    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);
        $this->joinAdditionalData($select);
        return $select;
    }

    /**
     * Join store data to queue model
     *
     * @param \Magento\Framework\DB\Select $select
     *
     * @return $this
     */
    protected function joinAdditionalData(\Magento\Framework\DB\Select $select)
    {
        $select->join(
            ['store_source' => $this->getTable('store')],
            $this->getMainTable() . '.origin_store_id = store_source.store_id',
            ['source_locale' => 'locale']
        );
        return $this;
    }

    /**
     * Map for collection filter fields
     *
     * @param $alias
     *
     * @return mixed
     */
    public function getFieldNameByAlias($alias)
    {
        $aliasesMap = [
            'id' => 'main_table.id',
            'name' => 'main_table.name',
            'status' => 'main_table.status',
            'store_name' => 'store_source.name',
            'source_locale' => 'store_source.locale',
            'request_date' => 'main_table.request_date',
            'due_date' => 'main_table.due_date'
        ];
        return $aliasesMap[$alias];
    }
}
