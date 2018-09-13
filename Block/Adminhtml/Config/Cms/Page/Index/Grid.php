<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Page\Index;

use Magento\Backend\Block\Widget\Grid\Extended;

/**
 * Class Grid
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Page
 */
class Grid extends Extended
{
    /**
     * @var \TransPerfect\GlobalLink\Model\Entity\TranslationStatus
     */
    protected $translationStatus;
    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    protected $pageCollectionFactory;
    /**
     * Grid constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                              $context
     * @param \Magento\Backend\Helper\Data                                         $backendHelper
     * @param \TransPerfect\GlobalLink\Model\Entity\TranslationStatus              $translationStatus
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory              $pageCollectionFactory
     * @param array                                                                $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \TransPerfect\GlobalLink\Model\Entity\TranslationStatus $translationStatus,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory,
        array $data = []
    ) {
        $this->translationStatus = $translationStatus;
        $this->pageCollectionFactory = $pageCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('TransPerfect_GlobalLink::grid/extended.phtml');
        $this->setId('cmsPageGrid');
        $this->setDefaultSort('page_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $pageCollection = $this->pageCollectionFactory->create();
        $currentStore = $this->getRequest()->getParam('store', false);
        if (empty($currentStore)) {
            $currentStore = $this->_storeManager->getDefaultStoreView()->getId();
        }
        $pageCollection->getSelect()->joinLeft(
            ['gets' => $pageCollection->getTable('globallink_entity_translation_status')],
            'gets.entity_type_id = '.\TransPerfect\GlobalLink\Helper\Data::CMS_PAGE_TYPE_ID.
            ' AND '.
            'gets.store_view_id = '.$currentStore.
            ' AND '.
            'gets.entity_id = main_table.page_id',
            ['translation_status']
        );
        $this->setCollection($pageCollection);
        parent::_prepareCollection();
        return $this;
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {

        /*$currentStore = $this->getRequest()->getParam('store', false);
        if (empty($currentStore)) {
            $currentStore = $this->_storeManager->getDefaultStoreView()->getId();
        }
        if ($currentStore == $this->_storeManager->getDefaultStoreView()->getId()) {
            $this->addColumn(
                'translation_status',
                [
                    'header' => __('Translation Status'),
                    'sortable' => true,
                    'index' => 'translation_status',
                    'type' => 'options',
                    'options' => $this->translationStatus->optionsToArray()
                ],
                'is_searchable'
            );
        } else {
            $this->addColumn(
                'translation_status',
                [
                    'header' => __('Translation Status'),
                    'sortable' => true,
                    'index' => 'translation_status',
                    'type' => 'options',
                    'options' => $this->translationStatus->productAttributeOptionsToArray()
                ],
                'is_searchable'
            );
        }*/

        return parent::_prepareColumns();;
    }
}
