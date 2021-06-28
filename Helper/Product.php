<?php

namespace TransPerfect\GlobalLink\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use \TransPerfect\GlobalLink\Model\ResourceModel\Category\Attribute\CollectionCustomFactory as CategoryAttributeCollectionFactory;
use TransPerfect\GlobalLink\Model\ResourceModel\Field;
use \TransPerfect\GlobalLink\Model\ResourceModel\Product\Attribute\CollectionCustomFactory as ProductAttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Indexer\Model\Indexer\CollectionFactory as IndexerCollectionFactory;
use Magento\Indexer\Model\IndexerFactory as IndexerFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as BlockCollectionFactory;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use \TransPerfect\GlobalLink\Model\FieldProductCategoryFactory as FieldProductCategoryFactory;

/**
 * Class Product
 *
 * @package TransPerfect\GlobalLink\Helper
 */
class Product extends Data
{
    /**
     * @var use \TransPerfect\GlobalLink\Model\FieldProductCategoryFactory
     */
    protected $fieldProductCategoryFactory;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Framework\Api\Search\SearchCriteriaFactory
     */
    protected $searchCriteriaFactory;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;
    /**
     * @var \TransPerfect\GlobalLink\Model\FieldProductCategory $productFieldModel
     */
    protected $productFieldModel;
    protected $entityAttribute;
    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    protected $pageCollectionFactory;
    /**
     * @var \Magento\Cms\Model\ResourceModel\Block\CollectionFactory
     */
    protected $blockCollectionFactory;
    protected $storeCollectionFactory;
    /**
     * Product constructor.
     *
     * @param \Magento\Framework\App\Helper\Context             $context
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     * @param \Magento\Framework\App\ResourceConnection         $resource
     * @param \Magento\Eav\Model\Config                         $eavConfig
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface   $productRepository
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface  $categoryRepository
     * @param \Magento\Framework\App\ProductMetadataInterface   $productMetadata
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \TransPerfect\GlobalLink\Model\TranslationService $translationService,
        \Magento\Framework\Locale\ListsInterface $localeLists,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Api\Search\SearchCriteriaFactory $searchCriteriaFactory,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        CategoryAttributeCollectionFactory $categoryAttributeCollectionFactory,
        ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSet,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory,
        \TransPerfect\GlobalLink\Model\FieldProductCategory $productFieldModel,
        \TransPerfect\GlobalLink\Model\Entity\Attribute $entityAttribute,
        PageCollectionFactory $pageCollectionFactory,
        BlockCollectionFactory $blockCollectionFactory,
        StoreCollectionFactory $storeCollectionFactory,
        FieldProductCategoryFactory $fieldProductCategoryFactory,
        \TransPerfect\GlobalLink\Helper\Ui\Logger $logger
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaFactory = $searchCriteriaFactory;
        $this->productFieldModel = $productFieldModel;
        $this->entityAttribute = $entityAttribute;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->blockCollectionFactory = $blockCollectionFactory;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->fieldProductCategoryFactory = $fieldProductCategoryFactory;
        parent::__construct(
            $context,
            $eavAttribute,
            $resource,
            $eavConfig,
            $storeManager,
            $translationService,
            $localeLists,
            $categoryAttributeCollectionFactory,
            $productAttributeCollectionFactory,
            $productCollectionFactory,
            $attributeSet,
            $productMetadata,
            $indexerFactory,
            $indexerCollectionFactory,
            $productFieldModel,
            $entityAttribute,
            $pageCollectionFactory,
            $blockCollectionFactory,
            $storeCollectionFactory,
            $fieldProductCategoryFactory,
            $logger
        );
    }

    /**
     * Returns list of associated and parent categories of the product
     *
     * @param array $productItems
     *
     * @return array
     */
    public function getAssociatedAndParentCategories(array $productItems)
    {
        /** @var \Magento\Framework\Api\SearchResults $productResults */
        $productResults = $this->productRepository->getList($this->getSearchCriteria(array_keys($productItems)));
        $products = $productResults->getItems();
        $associatedCategories = $this->getCategories($products);
        return $associatedCategories;
    }

    /**
     * @param \Magento\Framework\Api\AbstractExtensibleObject[] $products
     *
     * @return array
     */
    protected function getCategories(array $products)
    {
        $categoryIds = [];
        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($products as $product) {
            foreach ($product->getCategoryIds() as $categoryId) {
                $categoryIds[$categoryId] = '';
            }
        }
        foreach ($categoryIds as $categoryId => &$categoryName) {
            /** @var \Magento\Catalog\Model\Category $category */
            $category = $this->categoryRepository->get($categoryId);
            $categoryName = $category->getName();
            foreach ($category->getParentIds() as $parentId) {
                /** @var \Magento\Catalog\Model\Category $parentCategory */
                $parentCategory = $this->categoryRepository->get($parentId);
                if ($parentCategory->getLevel() > 1) {
                    $categoryIds[$parentId] = $parentCategory->getName();
                }
            }
        }
        return $categoryIds;
    }

    /**
     * Returns search criteria for entities by ids
     *
     * @param array $items
     *
     * @return \Magento\Framework\Api\Search\SearchCriteriaInterface
     */
    protected function getSearchCriteria(array $items)
    {
        $searchCriteria = $this->searchCriteriaFactory->create();
        $filterEntity = $this->filterBuilder
            ->setField('entity_id')
            ->setConditionType('in')
            ->setValue($items)
            ->create();
        $filterGroup = $this->filterGroupBuilder
            ->addFilter($filterEntity)
            ->create();
        $searchCriteria->setFilterGroups([$filterGroup]);
        return $searchCriteria;
    }
}
