<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config\Category\Attribute;

use Magento\Catalog\Model\Category;
use TransPerfect\GlobalLink\Controller\Adminhtml\Config\Action;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

class Save extends Action
{
    public function execute()
    {
        if ($this->_formKeyValidator->validate($this->getRequest())) {
            $data = $this->getRequest()->getParams();
            try {
                $attributeIds = $data[$data['massaction_prepare_key']];
                if (!empty($attributeIds)) {
                    $this->helper->updateAttributesTranslation($attributeIds, Category::ENTITY);
                    $this->messageManager->addSuccessMessage(__('Category configuration has been saved'));
                    $this->logger->logAction(Data::CATALOG_CATEGORY_TYPE_ID, Logger::CONFIG_ACTION_TYPE, $data);
                } else {
                    $this->logger->logAction(Data::CATALOG_CATEGORY_TYPE_ID, Logger::CONFIG_ACTION_TYPE, $data, Logger::ALERT, __('Empty attributes'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->logAction(Data::CATALOG_CATEGORY_TYPE_ID, Logger::CONFIG_ACTION_TYPE, $data, Logger::CRITICAL, $e->getMessage());
            }
        } else {
            $this->messageManager->addErrorMessage(__('Something went wrong'));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/config_category/attribute');
        return $resultRedirect;
    }
}
