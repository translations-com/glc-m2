<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Config\Category;

use Magento\Backend\Block\Widget\Grid\Extended;
use TransPerfect\GlobalLink\Model\ResourceModel\Category\Attribute\CollectionCustomFactory as CollectionFactory;
use TransPerfect\GlobalLink\Helper\Data;

/**
 * Class Grid
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Config\Category
 */
class Grid extends Extended
{
    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;

    /**
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Category\Attribute\CollectionCustomFactory
     */
    protected $collectionFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        CollectionFactory $collectionFactory,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('category_attribute_config');
        $this->setDefaultSort('attribute_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('attribute_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var \Magento\Framework\Data\Collection\AbstractDb $collection */
        $collection = $this->collectionFactory->create();
        $collection->appendFieldData();
        $collection->addFieldToFilter(
            'frontend_input',
            ['in' => ['text', 'textarea']]
        );
        $collection->addFieldToFilter(
            'backend_type',
            ['in' => ['text', 'varchar']]
        );
        $collection->addFieldToFilter(
            'attribute_code',
            ['nin' => [
                    'all_children',
                    'children',
                    'custom_layout_update',
                    'path_in_store',
                    'url_path',
            ]]
        );
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
            'attribute_code',
            [
                'header' => __('Attribute Code'),
                'index' => 'attribute_code',
            ]
        );

        $this->addColumn(
            'frontend_label',
            [
                'header' => __('Attribute Label'),
                'index' => 'frontend_label',
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('attribute_id');
        $this->getMassactionBlock()->setTemplate('TransPerfect_GlobalLink::grid/massaction.phtml');
        $this->getMassactionBlock()->setFormFieldName('attribute_ids');

        $this->getMassactionBlock()->addItem('update_configuration', [
            'label' => __('Update Configuration'),
            'url' => $this->getUrl('translations/config_category_attribute/save')
        ]);
        return $this;
    }

    /**
     * @owerride
     * Prepare grid massaction column
     *
     * @return $this
     */
    protected function _prepareMassactionColumn()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params['internal_attribute_ids'])) {
            $checked = $this->getSelectedAttributes();
        } else {
            $checked = $params['internal_attribute_ids'];
        }
        $this->getRequest()->setParam('internal_attribute_ids', $checked);

        return parent::_prepareMassactionColumn();
    }

    /**
     * @owerride
     * Retrieve massaction row identifier field
     *
     * @return string
     */
    public function getMassactionIdField()
    {
        return 'main_table.'.parent::getMassactionIdField();
    }

    protected function getSelectedAttributes()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->appendFieldData();
        $collection->addFieldToFilter('globallink_field_product_category.include_in_translation', 1);
        $attributes = $collection->getAllIds();
        return implode(",", $attributes);
    }
}
