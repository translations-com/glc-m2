<?php
namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config\Product\Attribute;

use \Magento\Backend\App\Action as BackendAction;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Update
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Config\Product\Attribute
 */
class Update extends BackendAction
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
     * @var \Transperfect\GlobalLink\Model\ResourceModel\FieldProductCategory
     */
    protected $productFieldCollection;

    protected $productFieldModel;
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

    /**
     * @var \TransPerfect\GlobalLink\Helper\Ui\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $eavAttribute;
    /**
     * Update constructor.
     *
     * @param \Magento\Backend\App\Action\Context                                             $context
     * @param \Magento\Framework\View\Result\PageFactory                                      $resultPageFactory
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Entity\Attribute\CollectionFactory $entityAttributeFactory
     * @param \Magento\Eav\Model\Config                                                       $eavConfig
     * @param \TransPerfect\GlobalLink\Helper\Ui\Logger                                       $logger
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute                               $eavAttribute
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \TransPerfect\GlobalLink\Model\ResourceModel\Entity\Attribute\CollectionFactory $entityAttributeFactory,
        \Magento\Eav\Model\Config $eavConfig,
        Logger $logger,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \TransPerfect\GlobalLink\Model\FieldProductCategory $productFieldModel
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->messageManager = $context->getMessageManager();
        $this->resultPageFactory = $resultPageFactory;
        $this->entityAttributeFactory = $entityAttributeFactory;
        $this->productEntityTypeId = $eavConfig->getEntityType(\Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE)
            ->getEntityTypeId();
        $this->eavAttribute = $eavAttribute;
        $this->productFieldModel = $productFieldModel;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /* @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/*/index');
        }

        /** @var \TransPerfect\GlobalLink\Model\ResourceModel\Entity\Attribute\Collection $productAttributeCollection */
        $productAttributeCollection = $this->_getAttributeCollection();

        $selected = $this->getRequest()->getParam('selected');

        foreach ($productAttributeCollection as $attribute) {
            $existingRow = $this->productFieldModel->getRecord($attribute->getEntityAttributeId());
            $includeInTranslation = (in_array($attribute->getEntityAttributeId(), $selected)) ? 1 : 0;
            if ($existingRow != null) {
                $attribute->setData('include_in_translation', $includeInTranslation);
                $existingRow->setData('include_in_translation', $includeInTranslation);
                $existingRow->setData('entity_attribute_id', $attribute->getEntityAttributeId());
                $existingRow->setData('entity_type_id', $attribute->getData('entity_type_id'));
                $existingRow->setData('attribute_set_id', $attribute->getData('attribute_set_id'));
                $existingRow->setData('attribute_group_id', $attribute->getData('attribute_group_id'));
                $existingRow->setData('attribute_id', $attribute->getData('attribute_id'));
                $existingRow->setData('include_in_translation', $includeInTranslation);
                $attribute->save();
                $existingRow->save();
            } else {
                $attribute->setData('include_in_translation', $includeInTranslation);
                $this->productFieldModel->setData('include_in_translation', $includeInTranslation);
                $this->productFieldModel->setData('entity_attribute_id', $attribute->getEntityAttributeId());
                $this->productFieldModel->setData('entity_type_id', $attribute->getData('entity_type_id'));
                $this->productFieldModel->setData('attribute_set_id', $attribute->getData('attribute_set_id'));
                $this->productFieldModel->setData('attribute_group_id', $attribute->getData('attribute_group_id'));
                $this->productFieldModel->setData('attribute_id', $attribute->getData('attribute_id'));
                $this->productFieldModel->setData('include_in_translation', $includeInTranslation);
                $attribute->save();
                $this->productFieldModel->save();
            }
        }

        try {
            $productAttributeCollection->save();
            $this->logger->logAction(Data::CATALOG_PRODUCT_TYPE_ID, Logger::CONFIG_ACTION_TYPE, $this->getRequest()->getParams());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->logAction(Data::CATALOG_PRODUCT_TYPE_ID, Logger::CONFIG_ACTION_TYPE, $this->getRequest()->getParams(), Logger::CRITICAL, $e->getMessage());
            return $resultRedirect->setPath('*/*/index');
        }

        $this->messageManager->addSuccessMessage(__('Product Attributes have been successfully updated'));

        return $resultRedirect->setPath('*/*/index', ['attribute_set' => $this->_getCurrentAttributeSetId()]);
    }

    /*
     * Check permission via ACL resource
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TransPerfect_GlobalLink::fieldform');
    }

    /**
     * Get attribute collection for proper Attribute Set
     *
     * @return mixed
     */
    protected function _getAttributeCollection()
    {
        if (!$this->productAttributeCollection) {
            $this->productAttributeCollection = $this->entityAttributeFactory->create();
            $this->productAttributeCollection->addFieldToFilter('main_table.entity_type_id', ['eq' => $this->productEntityTypeId]);
            $this->productAttributeCollection->addFieldToFilter('main_table.attribute_set_id', ['eq' => $this->_getCurrentAttributeSetId()]);
            $this->productAttributeCollection->getSelect()->joinLeft(['field_product'=> $this->productAttributeCollection->getTable('globallink_field_product_category')], 'main_table.entity_attribute_id = field_product.entity_attribute_id', ['field_product.include_in_translation']);
        }

        return $this->productAttributeCollection;
    }

    /*protected function _getProductFieldCollection(){
        if(!$this->productFieldCollection){
            $this->productFieldCollection = $this->productFieldCollectionFactory->create();
        }
        return $this->productFieldCollection;
    }*/

    /**
     * Get current attribute set ID
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
}
