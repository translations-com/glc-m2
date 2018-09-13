<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Cms\Page;

use \Magento\Backend\App\Action as BackendAction;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Cms\Page
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
     * @var Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    protected $messageManager;

    protected $resultPageFactory = false;

    protected $resultRedirect;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->session = $context->getSession();
        $this->registry = $registry;
        $this->helper = $helper;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->messageManager = $context->getMessageManager();
        $this->resultRedirect = $context->getResultRedirectFactory()->create();
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->_redirect('cms/page');
        }
        $differentStoresSelected = false;
        $nonDefaultDifferentStoresSelected = false;
        $pageStoreId = null;
        $sessionData = $this->session->getFormData();

        if (!empty($sessionData)) {
            $pagesToTranslate = array_keys($sessionData['items']);
        } else {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $pagesToTranslate = $collection->getAllIds();
        }
        if($this->helper->hasDifferentStores(Data::CMS_PAGE_TYPE_ID, $pagesToTranslate)){
            $differentStoresSelected = true;
        }
        else if($this->helper->defaultStoreSelected()){
            $pageStoreId = $this->helper->getStoreId(Data::CMS_PAGE_TYPE_ID, $pagesToTranslate[0]);
        }
        $pageNames = $this->helper->getOtherEntityNames(
            $this->collectionFactory,
            $this->getRequest()->getParam('store'),
            $pagesToTranslate,
            'page',
            'title',
            null
        );
        $pagesToTranslate = array_keys($pageNames);
        /*$differentStoresSelected = $this->helper->differentStoresSelected(
            $this->collectionFactory,
            $this->getRequest()->getParam('store'),
            $pagesToTranslate,
            'page',
            'store_id',
            null
        );*/
        $itemsToTranslate = [
            'ids' => $pagesToTranslate,
            'names' => $pageNames
        ];
        if($differentStoresSelected){
            $completedItemsString = "Cannot create submission as multiple source stores are selected. Please select only one source language.";
            $this->messageManager->addErrorMessage($completedItemsString);
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }
        $this->registry->register('itemsToTranslate', $itemsToTranslate);
        $this->registry->register('differentStoresSelected', $differentStoresSelected);
        $this->registry->register('objectStoreId', $pageStoreId);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::management');
        $resultPage->getConfig()->getTitle()->prepend(__('Create Submission'));
        $resultPage->addBreadcrumb(__('Submission'), __('Submission'));
        $this->logger->logAction(Data::CMS_PAGE_TYPE_ID, Logger::FORM_ACTION_TYPE, $this->getRequest()->getParams());
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
