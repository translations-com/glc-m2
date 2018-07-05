<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission;

use Magento\Backend\Block\Widget\Grid\Extended;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory;
use TransPerfect\GlobalLink\Model\Queue\Item;

/**
 * Class Grid
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission
 */
class Grid extends Extended
{
    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;

    protected $itemCollectionFactory;

    /**
     * Grid constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                                   $context
     * @param \Magento\Backend\Helper\Data                                              $backendHelper
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory $collectionFactory
     * @param \TransPerfect\GlobalLink\Helper\Data                                      $helper
     * @param array                                                                     $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        CollectionFactory $collectionFactory,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->itemCollectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('translation_submission');
        $this->setDefaultSort('request_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('submission_filter');
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        /** @var \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\Collection $collection */
        $collection = $this->itemCollectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'submission_name',
            [
                'header' => __('Submission Name'),
                'index' => 'submission_name',
                'type' => 'text'
            ]
        );
        $this->addColumn(
            'request_date',
            [
                'header' => __('Submission Date'),
                'index' => 'request_date',
                'type' => 'datetime'
            ]
        );
        $this->addColumn(
            'due_date',
            [
                'header' => __('Submission Due Date'),
                'index' => 'due_date',
                'type' => 'datetime',
                'renderer' => '\TransPerfect\GlobalLink\Block\Adminhtml\Submission\Grid\Renderer\DueDate'
            ]
        );
        $this->addColumn(
            'status_id',
            [
                'header' => __('Status'),
                'index' => 'status_id',
                'type' => 'options',
                'options' => Item::getStatusesOptionArray()
            ]
        );
        $this->addColumn(
            'source_locale',
            [
                'header' => __('Source Language'),
                'index' => 'source_locale',
                'type' => 'options',
                'options' => $this->helper->getLocaleOptionsArray(),
            ]
        );
        $this->addColumn(
            'pd_locale_iso_code',
            [
                'header' => __('Target Language'),
                'index' => 'pd_locale_iso_code',
                'type' => 'options',
                'options' => $this->helper->getLocaleOptionsArray(),
            ]
        );
        $this->addColumn(
            'entity_name',
            [
                'header' => __('Entity Name'),
                'index' => 'entity_name',
            ]
        );
        $this->addColumn(
            'entity_type_id',
            [
                'header' => __('Entity Type'),
                'index' => 'entity_type_id',
                'type' => 'options',
                'options' => $this->helper->getEntityTypeOptionArray()
            ]
        );
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
        $this->getMassactionBlock()->addItem('cancel_submission', [
            'label' => __('Cancel Submission'),
            'url' => $this->getUrl('*/submission/cancel')
        ]);
        $this->getMassactionBlock()->addItem('apply_translations', [
            'label' => __('Import Translations'),
            'url' => $this->getUrl('*/submission/apply')
        ]);
        $this->getMassactionBlock()->addItem('remove_translations', [
            'label' => __('Remove Cancelled Translations'),
            'url' => $this->getUrl('*/submission/remove')
        ]);
        return $this;
    }
}
