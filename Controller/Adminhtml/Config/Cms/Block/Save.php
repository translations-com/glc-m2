<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config\Cms\Block;

use Magento\Framework\View\Result\PageFactory;
use TransPerfect\GlobalLink\Controller\Adminhtml\Config\Action;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Save
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Config\Cms\Block
 */
class Save extends Action
{
    protected $collectionFactory;

    protected $fieldFactory;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context                                  $context
     * @param \Magento\Framework\View\Result\PageFactory                           $pageFactory
     * @param \TransPerfect\GlobalLink\Helper\Data                                 $helper
     * @param \TransPerfect\GlobalLink\Helper\Ui\Logger                            $logger
     * @param \TransPerfect\GlobalLink\Model\FieldFactory                          $fieldFactory
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Field\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \TransPerfect\GlobalLink\Helper\Ui\Logger $logger,
        \TransPerfect\GlobalLink\Model\FieldFactory $fieldFactory,
        \TransPerfect\GlobalLink\Model\ResourceModel\Field\CollectionFactory $collectionFactory
    ) {
        $this->fieldFactory = $fieldFactory;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $pageFactory, $helper, $logger);
    }

    public function execute()
    {
        if ($this->_formKeyValidator->validate($this->getRequest())) {
            $data = $this->getRequest()->getParams();
            $fieldIds = $data[$data['massaction_prepare_key']];
            if (!empty($fieldIds)) {
                try {
                    $collection = $this->getFieldCollection();
                    /** @var \TransPerfect\GlobalLink\Model\Field $field */
                    foreach ($collection as $field) {
                        if (in_array($field->getId(), $fieldIds)) {
                            $field->setIncludeInTranslation(1);
                        } else {
                            $field->setIncludeInTranslation(0);
                        }
                    }
                    $collection->save();
                    $this->messageManager->addSuccessMessage(__('Configuration has been saved'));
                    $this->logger->logAction(Data::CMS_BLOCK_TYPE_ID, Logger::CONFIG_ACTION_TYPE, $this->getRequest()->getParams());
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                    $this->logger->logAction(Data::CMS_BLOCK_TYPE_ID, Logger::CONFIG_ACTION_TYPE, $this->getRequest()->getParams(), Logger::CRITICAL, $e->getMessage());
                }
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('translations/config_cms_block/field');
        return $resultRedirect;
    }

    /**
     * @return \TransPerfect\GlobalLink\Model\ResourceModel\Field\Collection
     */
    protected function getFieldCollection()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('object_type', \TransPerfect\GlobalLink\Helper\Data::CMS_BLOCK_TYPE_ID);
        return $collection;
    }
}
