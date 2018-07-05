<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Category;

use TransPerfect\GlobalLink\Controller\Adminhtml\Category;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Category
 */
class Create extends Category
{
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->_redirect('*/category');
        }
        if (empty($this->getRequest()->getParam($this->getRequest()->getParam('massaction_prepare_key')))) {
            return $this->_redirect('*/category');
        }

        $categoriesToTranslate = $this->getRequest()->getParam($this->getRequest()->getParam('massaction_prepare_key'));
        if (empty($categoriesToTranslate)) {
            $sessionData = $this->session->getFormData();
            if (!empty($sessionData)) {
                $categoriesToTranslate = array_keys($sessionData['items']);
            }
        }
        $categoryNames = $this->helper->getEntityNames(
            $this->categoryCollectionFactory,
            $this->getRequest()->getParam('store'),
            $categoriesToTranslate
        );

        $checkFields = $this->helper->checkFieldsConfigured(Data::CATALOG_CATEGORY_TYPE_ID, $categoriesToTranslate);
        if (!$checkFields['ok']) {
            $this->messageManager->addErrorMessage(__('Fields are not configured under Globallink > Field Configuration > Categories'));
            return $this->_redirect('*/category');
        }

        $itemsToTranslate = [
            'ids' => $categoriesToTranslate,
            'names' => $categoryNames
        ];
        $this->registry->register('itemsToTranslate', $itemsToTranslate);


        $this->logger->logAction(Data::CATALOG_CATEGORY_TYPE_ID, Logger::FORM_ACTION_TYPE, $this->getRequest()->getParams());
        return parent::execute();
    }
}
