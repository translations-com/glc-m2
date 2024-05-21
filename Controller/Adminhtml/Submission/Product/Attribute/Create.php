<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Product\Attribute;

use \Magento\Backend\App\Action as BackendAction;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Product\Attribute
 */
class Create extends BackendAction
{
    /**
     * @var \TransPerfect\GlobalLink\Helper\Ui\Logger
     */
    protected $logger;

    protected $resultPageFactory = false;

    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Logger $logger,
        \TransPerfect\GlobalLink\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->helper = $helper;
    }

    /**
     * @return void
     */
    public function execute()
    {
        if ($this->helper->isClassifierConfigured('globallink_classifiers/classifiers/productattributeclassifier', $this->getRequest()->getParam('store'))) {
            $error = __('Classifier is not configured. Please hit save on the classifiers page.');
            $this->messageManager->addErrorMessage($error);
            return $this->_redirect('catalog/product_attribute');
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::management');
        $resultPage->getConfig()->getTitle()->prepend(__('Create Submission'));
        $resultPage->addBreadcrumb(__('Submission'), __('Submission'));
        if($this->logger->isInfoEnabled()) {
            $this->logger->logAction(Data::PRODUCT_ATTRIBUTE_TYPE_ID, Logger::FORM_ACTION_TYPE, $this->getRequest()->getParams(), Logger::NOTICE);
        }
        return $resultPage;
    }

    /*
     * Check permission via ACL resource
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TransPerfect_GlobalLink::management');
    }
}
