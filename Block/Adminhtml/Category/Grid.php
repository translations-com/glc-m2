<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Category;

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\WebsiteFactory;
use Magento\Catalog\Model\CategoryManagement;
use Magento\Catalog\Model\CategoryRepository;
use TransPerfect\GlobalLink\Model\Entity\TranslationStatus;

class Grid extends Extended
{
    /** @var  \Magento\Catalog\Helper\Category */
    protected $categoryHelper;

    /** @var  \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory */
    protected $collectionFactory;

    /** @var \Magento\Store\Model\WebsiteFactory */
    protected $websiteFactory;

    /** @var  \Magento\Catalog\Model\CategoryManagement */
    protected $categoryManagement;

    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \TransPerfect\GlobalLink\Model\Entity\TranslationStatus
     */
    protected $translationStatus;

    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        CategoryHelper $categoryHelper,
        CollectionFactory $collectionFactory,
        WebsiteFactory $websiteFactory,
        CategoryManagement $categoryManagement,
        CategoryRepository $categoryRepository,
        TranslationStatus $translationStatus,
        array $data = []
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryManagement = $categoryManagement;
        $this->categoryHelper = $categoryHelper;
        $this->collectionFactory = $collectionFactory;
        $this->websiteFactory = $websiteFactory;
        $this->translationStatus = $translationStatus;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('categoriesGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('categories_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $currentStore = $this->getRequest()->getParam('store', false);
        if (empty($currentStore)) {
            $currentStore = 0;
        }
        $collection = false;
        if ($currentStore) {
            $rootCategoryId = $this->_storeManager->getStore($currentStore)->getRootCategoryId();
            /** @var \Magento\Catalog\Model\Category $rootCategory */
            $rootCategory = $this->categoryRepository->get($rootCategoryId, $currentStore);
            $collection = $rootCategory->getCategories($rootCategoryId, 0, false, true);
            $collection->setStore($currentStore);
        }
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        if (!$collection) {
            $collection = $this->collectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter('level', ['gt' => 1]);
        }

        $collection->joinTranslationStatus($currentStore);

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
            ]
        );

        $this->addColumn(
            'is_active',
            [
                'header' => __('Status'),
                'index' => 'is_active',
                'type' => 'options',
                'options' => [1 => __('Enabled'), 0 => __('Disabled')]
            ]
        );

        $this->addColumn(
            'url_key',
            [
                'header' => __('Url Key'),
                'index' => 'url_key',
            ]
        );

        $this->addColumn(
            'translation_status',
            [
                'header' => __('Translation Status'),
                'index' => 'translation_status',
                'type' => 'options',
                'sortable' => false,
                'options' => $this->translationStatus->optionsToArray(),
                'renderer' => '\TransPerfect\GlobalLink\Block\Adminhtml\Category\Grid\Renderer\TranslationStatus',
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {
        /** @var \Magento\Backend\Block\Widget\Grid\Massaction\Extended $massactionBlock */
        $massactionBlock = $this->getMassactionBlock();
        $massactionBlock->setTemplate('TransPerfect_GlobalLink::grid/massaction.phtml');
        $this->setMassactionIdField('entity_id');
        $massactionBlock->setFormFieldName('category_ids');
        $translationConfig = [
            'label' => __('Send for Translation')
        ];
        if ($this->getRequest()->getParam('store')) {
            $translationConfig['url'] = $this->getUrl('translations/submission_category/create', ['store' => $this->getRequest()->getParam('store')]);
        } else {
            //$translationConfig['url'] = 'javascript:chooseStoreAlert()';
            $translationConfig['url'] = $this->getUrl('translations/submission_category/create', ['store' => $this->_storeManager->getDefaultStoreView()->getId()]);
        }
        $massactionBlock->addItem('translate', $translationConfig);
        return $this;
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    /**
     * Get children of specified item
     *
     * @param \Magento\Catalog\Model\Category $item
     * @return array
     */
    public function getMultipleRows($item)
    {
        return [];
    }

    /**
     * Filter store condition
     *
     * @param \Magento\Framework\DataObject $column
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _filterStoreCondition(\Magento\Framework\DataObject $column)
    {
        if (!($value = $column->getFilter()->getValue())) {
            return;
        }
        $this->getCollection()->setStoreId($value);
    }
}
