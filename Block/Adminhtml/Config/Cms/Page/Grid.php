<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Page;

use Magento\Backend\Block\Widget\Grid\Extended;

/**
 * Class Grid
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Page
 */
class Grid extends Extended
{
    /**
     * @var TransPerfect\GlobalLink\Model\ResourceModel\Field\Collection
     */
    protected $collectionFactory;
    /**
     * @var \TransPerfect\GlobalLink\Model\Entity\TranslationStatus
     */
    protected $translationStatus;

    /**
     * Grid constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                              $context
     * @param \Magento\Backend\Helper\Data                                         $backendHelper
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Field\CollectionFactory $collectionFactory
     * @param array                                                                $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \TransPerfect\GlobalLink\Model\ResourceModel\Field\CollectionFactory $collectionFactory,
        \TransPerfect\GlobalLink\Model\Entity\TranslationStatus $translationStatus,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('cms_page_field_config');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('field_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var \Magento\Framework\Data\Collection\AbstractDb $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('object_type', \TransPerfect\GlobalLink\Helper\Data::CMS_PAGE_TYPE_ID);
        /* 2/21/18 Justin Griffin:
         * This was originally added to get MAGII-61 working, but it appears at this point in time that it isn't necessary.
         * Commenting out for the time being.
         *
         * if($this->translationStatus != null) {
            $currentStore = $this->getRequest()->getParam('store', false);
            if (empty($currentStore)) {
                $currentStore = $this->_storeManager->getDefaultStoreView()->getId();
            }

            $collection->getSelect()->joinLeft(
                ['gets' => $collection->getTable('globallink_entity_translation_status')],
                'gets.entity_type_id = ' . \TransPerfect\GlobalLink\Helper\Data::CMS_PAGE_TYPE_ID .
                ' AND ' .
                'gets.store_view_id = ' . $currentStore .
                ' AND ' .
                'gets.entity_id = main_table.attribute_id',
                ['translation_status']
            );
        }*/
        $this->setCollection($collection);
        parent::_prepareCollection();

        return $this;
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'field_name',
            [
                'header' => __('Attribute Code'),
                'index' => 'field_name',
            ]
        );
        $this->addColumn(
            'field_label',
            [
                'header' => __('Attribute Label'),
                'index' => 'field_label',
            ]
        );
        /* 2/21/18 Justin Griffin:
         * This was originally added to get MAGII-61 working, but it appears at this point in time that it isn't necessary.
         * Commenting out for the time being.
         *
         * if($this->translationStatus != null) {
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
        }*/
        $this->addColumn('action', [
            'header' => __('Action'),
            'width' => '100',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => [
                [
                    'caption' => __('Delete'),
                    'url' => ['base' => '*/config_cms_page_field/delete'],
                    //unable to determine how to obtain id of data in this row for the confirm url
                    //'onclick' => 'deleteConfirm("'.__('Are you sure you want to delete this custom CMS attribute?').'", \'' . $this->getUrl('*/config_cms_page_field/delete', ['id' => 111]) . '\')',
                    'field' => 'id'
                ]
            ],
            'filter' => false,
            'sortable' => false,
            'index' => 'id',
            'is_system' => true,
        ]);
        return parent::_prepareColumns();
    }

    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setTemplate('TransPerfect_GlobalLink::grid/massaction.phtml');
        $this->getMassactionBlock()->setFormFieldName('ids');
        $this->getMassactionBlock()->addItem('update_configuration', [
            'label' => __('Update Configuration'),
            'url' => $this->getUrl('*/config_cms_page/save')
        ]);
        return $this;
    }

    /**
     * Prepare grid massaction column
     *
     * @return $this
     */
    protected function _prepareMassactionColumn()
    {
        $savedFields = $this->getSelectedFields();
        $this->getRequest()->setParams(['internal_ids' => $savedFields]);

        return parent::_prepareMassactionColumn();
    }

    /**
     * @return string
     */
    protected function getSelectedFields()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('object_type', \TransPerfect\GlobalLink\Helper\Data::CMS_PAGE_TYPE_ID);
        $collection->addFieldToFilter('include_in_translation', '1');
        $fields = $collection->getAllIds();

        return implode(",", $fields);
    }
}
