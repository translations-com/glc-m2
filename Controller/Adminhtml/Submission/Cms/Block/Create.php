<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Cms\Block;

use \Magento\Backend\App\Action as BackendAction;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Cms\Block
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

    protected $resultPageFactory = false;

    protected $messageManager;

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
            $this->_redirect('cms/block');
        }
        $differentStoresSelected = false;
        $nonDefaultDifferentStoresSelected = false;
        $blockStoreId = null;
        $sessionData = $this->session->getFormData();

        if (!empty($sessionData)) {
            $blocksToTranslate = array_keys($sessionData['items']);
        } else {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $blocksToTranslate = $collection->getAllIds();
        }
        $originalSelectedCount = count($blocksToTranslate);
        $originalBlocksToTranslate = $blocksToTranslate;
        if(count($blocksToTranslate) > 1 && $this->helper->hasDifferentStores(Data::CMS_BLOCK_TYPE_ID, $blocksToTranslate)){
            $differentStoresSelected = true;
        }
        else if(count($blocksToTranslate) > 1 && $this->helper->defaultStoreSelected()){
            $blockStoreId = $this->helper->getCommonStoreId(Data::CMS_BLOCK_TYPE_ID, $blocksToTranslate);
        } else{
            $blockStoreId = $this->helper->getStoreId(Data::CMS_BLOCK_TYPE_ID, $blocksToTranslate[0]);
        }
        $blockNames = $this->helper->getOtherEntityNames(
            $this->collectionFactory,
            $this->getRequest()->getParam('store'),
            $blocksToTranslate,
            'block',
            'title',
            null
        );

        $blocksToTranslate = array_keys($blockNames);
        /*$differentStoresSelected = $this->helper->differentStoresSelected(
            $this->collectionFactory,
            $this->getRequest()->getParam('store'),
            $blocksToTranslate,
            'block',
            'store_id',
            null
        );*/
        $itemsToTranslate = [
            'ids' => $blocksToTranslate,
            'names' => $blockNames
        ];
        if($differentStoresSelected){
            $completedItemsString = "Cannot create submission as multiple source stores are selected. Please select only one source language.";
            $this->messageManager->addErrorMessage($completedItemsString);
            return $this->resultRedirect->setPath($this->_redirect->getRefererUrl());
        }
        $this->registry->register('itemsToTranslate', $itemsToTranslate);
        $this->registry->register('differentStoresSelected', $differentStoresSelected);
        //$this->registry->register('nonDefaultDifferentStoresSelected', $nonDefaultDifferentStoresSelected);
        $this->registry->register('objectStoreId', $blockStoreId);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::management');
        $resultPage->getConfig()->getTitle()->prepend(__('Create Submission'));
        $resultPage->addBreadcrumb(__('Submission'), __('Submission'));
        if($this->logger->isInfoEnabled()) {
            $this->logger->logAction(Data::CMS_BLOCK_TYPE_ID, Logger::FORM_ACTION_TYPE, $this->getRequest()->getParams(), Logger::NOTICE);
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
