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
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->_redirect('cms/block');
        }

        $sessionData = $this->session->getFormData();
        if (!empty($sessionData)) {
            $blocksToTranslate = array_keys($sessionData['items']);
        } else {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $blocksToTranslate = $collection->getAllIds();
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

        $itemsToTranslate = [
            'ids' => $blocksToTranslate,
            'names' => $blockNames
        ];
        $this->registry->register('itemsToTranslate', $itemsToTranslate);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::management');
        $resultPage->getConfig()->getTitle()->prepend(__('Create Submission'));
        $resultPage->addBreadcrumb(__('Submission'), __('Submission'));
        $this->logger->logAction(Data::CMS_BLOCK_TYPE_ID, Logger::FORM_ACTION_TYPE, $this->getRequest()->getParams());
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
