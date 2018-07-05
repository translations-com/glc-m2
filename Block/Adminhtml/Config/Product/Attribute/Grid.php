<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Config\Product\Attribute;

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data as BackendHelper;
use TransPerfect\GlobalLink\Model\ResourceModel\Entity\Attribute\CollectionFactory;
use Magento\Eav\Model\Config;

/**
 * Class Grid
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Config\Product\Attribute
 */
class Grid extends Extended
{
    /**
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Entity\Attribute\CollectionFactory
     */
    protected $entityAttributeFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $productAttributeCollection;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var null|int
     */
    protected $currentAttributeSet = null;

    /**
     * @var int
     */
    protected $productEntityTypeId;

    protected $productFieldModel;
    /**
     * Grid constructor.
     *
     * @param Context           $context
     * @param BackendHelper     $backendHelper
     * @param CollectionFactory $entityAttributeFactory
     * @param Config            $eavConfig
     * @param array             $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        CollectionFactory $entityAttributeFactory,
        Config $eavConfig,
        array $data = [],
        \TransPerfect\GlobalLink\Model\FieldProductCategory $productFieldModel
    ) {
        $this->entityAttributeFactory = $entityAttributeFactory;
        $this->productEntityTypeId = $eavConfig->getEntityType(\Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE)
            ->getEntityTypeId();
        $this->productFieldModel = $productFieldModel;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('fieldsProductAttributeGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('product_attribute_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_getAttributeCollection();
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
                    'custom_layout_update',
                    'url_key',
                    'url_path',
            ]]
        );
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @throws \Exception
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
                'index' => 'frontend_label'
            ]
        );

        $this->addColumn(
            'attribute_set_name',
            [
                'header' => __('Attribute Set'),
                'index' => 'attribute_set_name',
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('main_table.entity_attribute_id');
        $this->setMassactionIdFilter('main_table.entity_attribute_id');
        $this->getMassactionBlock()->setTemplate('TransPerfect_GlobalLink::grid/massaction.phtml');
        $this->getMassactionBlock()->setFormFieldName('selected');

        $updateConfig = [
            'label' => __('Update Configuration'),
            'confirm' => false,
            'url' => $this->getUrl('translations/config_product_attribute/update', ['attribute_set' => $this->_getCurrentAttributeSetId()])
        ];

        $this->getMassactionBlock()->addItem('update_configuration', $updateConfig);

        return $this;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareMassactionColumn()
    {
        $this->getRequest()->setParams(['internal_selected' => $this->_getSelected()]);

        return parent::_prepareMassactionColumn();
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
     * @param \Magento\Framework\DataObject $item
     *
     * @return array
     */
    public function getMultipleRows($item)
    {
        return [];
    }

    /**
     * Get string of already included to translation attributes
     *
     * @return string
     */
    protected function _getSelected()
    {
        $collection = $this->_getAttributeCollection();
        $selected = [];

        foreach ($collection as $item) {
            $matchingFieldRow = $this->productFieldModel->getRecord($item->getData('entity_attribute_id'));
            if ($matchingFieldRow->getData('include_in_translation') == 1) {
                array_push($selected, $item->getId());
            }
        }

        return implode(',', $selected);
    }

    /**
     * Get Id of current attribute set
     *
     * @return mixed
     */
    protected function _getCurrentAttributeSetId()
    {
        if (!$this->currentAttributeSet) {
            $this->currentAttributeSet = (is_null($this->getRequest()->getParam('attribute_set'))) ? 0 : $this->getRequest()->getParam('attribute_set');
        }

        return $this->currentAttributeSet;
    }

    /**
     * Get attribute collection for proper Attribute Set
     *
     * @return mixed
     */
    protected function _getAttributeCollection()
    {
        if (!$this->productAttributeCollection) {
            $collection = $this->entityAttributeFactory->create();
            $collection->addFieldToFilter('main_table.entity_type_id', ['eq' => $this->productEntityTypeId])
                ->addFieldToFilter('main_table.attribute_set_id', ['eq' => $this->_getCurrentAttributeSetId()])
                ->joinFields(['ea' => $collection->getTable('eav_attribute')], ' AND ', ['ea.attribute_id = main_table.attribute_id'], ['attribute_code', 'frontend_label'])
                ->joinFields(['eas' => $collection->getTable('eav_attribute_set')], ' AND ', ['eas.attribute_set_id = main_table.attribute_set_id'], ['attribute_set_name']);
            $this->productAttributeCollection = $collection;
        }

        return $this->productAttributeCollection;
    }
}
