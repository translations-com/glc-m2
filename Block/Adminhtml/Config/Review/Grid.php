<?php
/**
 * Created by PhpStorm.
 * User: jgriffin
 * Date: 10/9/2019
 * Time: 10:03 AM
 */

namespace TransPerfect\GlobalLink\Block\Adminhtml\Config\Review;

use Magento\Backend\Block\Widget\Grid\Extended;
use TransPerfect\GlobalLink\Model\ResourceModel\Field\CollectionFactory;

/**
 * Class Grid
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Config\Review
 */
class Grid extends Extended
{
    protected $collectionFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        CollectionFactory $collectionFactory,
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
        $this->setId('review_field_config');
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
        $collection->addFieldToFilter('object_type', \TransPerfect\GlobalLink\Helper\Data::PRODUCT_REVIEW_ID);
        $link = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
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
        $this->addColumn('action', [
            'header' => __('Action'),
            'width' => '100',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => [
                [
                    'caption' => __('Delete'),
                    'url' => ['base' => '*/config_review_field/delete'],
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
            'url' => $this->getUrl('*/config_review/save')
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
        $collection->addFieldToFilter('object_type', \TransPerfect\GlobalLink\Helper\Data::PRODUCT_REVIEW_ID);
        $collection->addFieldToFilter('include_in_translation', '1');
        $fields = $collection->getAllIds();
        return implode(",", $fields);
    }
}
