<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Customer\Attribute;

use \Magento\Backend\App\Action as BackendAction;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Customer\Attribute
 */
class Create extends BackendAction
{
    /**
     * @var \TransPerfect\GlobalLink\Helper\Ui\Logger
     */
    protected $logger;

    protected $resultPageFactory = false;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $attributeId = $this->getRequest()->getParam('id');
        if($attributeId == null){
            $this->messageManager->addErrorMessage("The attribute does not exist. Please save before attempting to send for translation");
            $redirectFactory = $this->resultRedirectFactory->create();
            return $redirectFactory->setPath('adminhtml/customer_attribute');
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::management');
        $resultPage->getConfig()->getTitle()->prepend(__('Create Submission'));
        $resultPage->addBreadcrumb(__('Submission'), __('Submission'));
        if($this->logger->isInfoEnabled()) {
            $this->logger->logAction(Data::CUSTOMER_ATTRIBUTE_TYPE_ID, Logger::FORM_ACTION_TYPE, $this->getRequest()->getParams(), Logger::NOTICE);
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
