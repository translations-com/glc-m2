<?php
/**
 * Created by PhpStorm.
 * User: jgriffin
 * Date: 10/11/2019
 * Time: 9:13 AM
 */
namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Review;

use \Magento\Backend\App\Action as BackendAction;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Review
 */
class Create extends BackendAction
{
    /**
     * @var \TransPerfect\GlobalLink\Helper\Ui\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;
    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    /**
     * @var \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory
     */
    protected $collectionFactory;

    protected $resultPageFactory = false;

    protected $resultRedirect;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Logger $logger,
        \Magento\Framework\Registry $registry,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->helper = $helper;
        $this->filter = $filter;
        $this->session = $context->getSession();
        $this->collectionFactory = $collectionFactory;
        $this->resultRedirect = $context->getResultRedirectFactory()->create();
    }

    /**
     * @return void
     */
    public function execute()
    {
        if ($this->helper->isClassifierConfigured('globallink_classifiers/classifiers/reviewclassifier', $this->getRequest()->getParam('store'))) {
            $error = __('Classifier is not configured. Please hit save on the classifiers page.');
            $this->messageManager->addErrorMessage($error);
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }
        $differentStoresSelected = false;
        $reviewStoreId = null;
        $sessionData = $this->session->getFormData();
        $reviewsToTranslate = $this->getRequest()->getParam('reviews');
        if(!is_array($reviewsToTranslate)){
            $reviewsToTranslate = explode(",", $this->getRequest()->getParam('reviews'));
        }
        if(count($reviewsToTranslate) > 1 && $this->helper->hasDifferentStores(Data::PRODUCT_REVIEW_ID, $reviewsToTranslate)){
            $differentStoresSelected = true;
        }
        else{
            $reviewStoreId = $this->helper->getStoreId(Data::PRODUCT_REVIEW_ID, $reviewsToTranslate[0]);
        }
        if($differentStoresSelected){
            $completedItemsString = "Cannot create submission as multiple source stores are selected. Please select only one source language.";
            $this->messageManager->addErrorMessage($completedItemsString);
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }
        $this->registry->register('differentStoresSelected', $differentStoresSelected);
        $this->registry->register('objectStoreId', $reviewStoreId);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::management');
        $resultPage->getConfig()->getTitle()->prepend(__('Create Submission'));
        $resultPage->addBreadcrumb(__('Submission'), __('Submission'));
        if($this->logger->isInfoEnabled()) {
            $this->logger->logAction(Data::PRODUCT_REVIEW_ID, Logger::FORM_ACTION_TYPE, $this->getRequest()->getParams(), Logger::NOTICE);
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
