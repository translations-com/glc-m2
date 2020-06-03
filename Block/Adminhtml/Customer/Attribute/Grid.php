<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Customer\Attribute;

/**
 * Class Grid
 */
class Grid extends \Magento\Eav\Block\Adminhtml\Attribute\Grid\AbstractGrid
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory
     */
    protected $_attributesFactory;

    /**
     * @var \TransPerfect\GlobalLink\Model\Entity\TranslationStatus
     */
    protected $translationStatus;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attributesFactory
     * @param \TransPerfect\GlobalLink\Model\Entity\TranslationStatus $translationStatus
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attributesFactory,
        \TransPerfect\GlobalLink\Model\Entity\TranslationStatus $translationStatus,
        array $data = []
    ) {
        $this->_attributesFactory = $attributesFactory;
        $this->translationStatus = $translationStatus;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {
        /*$this->setMassactionIdField('attribute_id');
        $this->getMassactionBlock()->setTemplate('TransPerfect_GlobalLink::grid/massaction.phtml');
        $this->getMassactionBlock()->setFormFieldName('selected');
        $this->getMassactionBlock()->addItem(
            'translate',
            [
                'label' => __('Send for Translation'),
                'url' => $this->getUrl('translations/submission_customer_attribute/create')
            ]
        );*/

        return $this;
    }

    /**
     * Prepare customer attributes grid collection object
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var $collection \Magento\Customer\Model\ResourceModel\Attribute\Collection */
        $collection = $this->_attributesFactory->create();
        $collection->addSystemHiddenFilter()->addExcludeHiddenFrontendFilter();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare customer attributes grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        /*$this->addColumn(
            'is_visible',
            [
                'header' => __('Visible to Customer'),
                'sortable' => true,
                'index' => 'is_visible',
                'type' => 'options',
                'options' => ['0' => __('No'), '1' => __('Yes')]
            ]
        );

        $this->addColumn(
            'sort_order',
            ['header' => __('Sort Order'), 'sortable' => true, 'index' => 'sort_order']
        );*/
        return $this;
    }
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
}
