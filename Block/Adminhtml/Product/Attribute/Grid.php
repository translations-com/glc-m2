<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Product\Attribute;

use \Magento\Catalog\Block\Adminhtml\Product\Attribute\Grid as AttributeGrid;

/**
 * Class Grid
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Product\Attribute
 */
class Grid extends AttributeGrid
{
    /**
     * @var \TransPerfect\GlobalLink\Model\Entity\TranslationStatus
     */
    protected $translationStatus;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory
     * @param \TransPerfect\GlobalLink\Model\Entity\TranslationStatus $translationStatus
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory,
        \TransPerfect\GlobalLink\Model\Entity\TranslationStatus $translationStatus,
        array $data = []
    ) {
        $this->translationStatus = $translationStatus;
        parent::__construct($context, $backendHelper, $collectionFactory, $data);
    }

    /**
     * Prepare product attributes grid collection object
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create()->addVisibleFilter();

        $currentStore = $this->getRequest()->getParam('store', false);
        if (empty($currentStore)) {
            $currentStore = $this->_storeManager->getDefaultStoreView()->getId();
        }
        $collection->getSelect()->joinLeft(
            ['gets' => $collection->getTable('globallink_entity_translation_status')],
            'gets.entity_type_id = '.\TransPerfect\GlobalLink\Helper\Data::PRODUCT_ATTRIBUTE_TYPE_ID.
            ' AND '.
            'gets.store_view_id = '.$currentStore.
            ' AND '.
            'gets.entity_id = main_table.attribute_id',
            ['translation_status']
        );
        $this->setCollection($collection);

        return \Magento\Backend\Block\Widget\Grid\Extended::_prepareCollection();
    }

    /**
     * Prepare customer attributes grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $currentStore = $this->getRequest()->getParam('store', false);
        if (empty($currentStore)) {
            $currentStore = $this->_storeManager->getDefaultStoreView()->getId();
        }
        if ($currentStore == $this->_storeManager->getDefaultStoreView()->getId()) {
            $this->addColumnAfter(
                'sort_order',
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
            $this->addColumnAfter(
                'sort_order',
                [
                    'header' => __('Translation Status'),
                    'sortable' => true,
                    'index' => 'translation_status',
                    'type' => 'options',
                    'options' => $this->translationStatus->productAttributeOptionsToArray()
                ],
                'is_searchable'
            );
        }

        return $this;
    }

    protected function _prepareLayout()
    {
        $this->getToolbar()->addChild(
            'store_switcher',
            '\TransPerfect\GlobalLink\Block\Adminhtml\Store\Switcher'
        );

        return parent::_prepareLayout();
    }

    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('attribute_id');
        $this->getMassactionBlock()->setTemplate('TransPerfect_GlobalLink::grid/massaction.phtml');
        $this->getMassactionBlock()->setFormFieldName('selected');
        $this->getMassactionBlock()->addItem(
            'translate',
            [
                'label' => __('Send for Translation'),
                'url' => $this->getUrl('translations/submission_product_attribute/create')
            ]
        );

        return $this;
    }
}
