<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Product;

use \Magento\Backend\App\Action as BackendAction;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Product
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
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

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
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        Filter $filter,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->helper = $helper;
        $this->session = $context->getSession();
        $this->filter = $filter;
        $this->productCollectionFactory = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('id')) {
            $productsToTranslate = [$this->getRequest()->getParam('id')];
        } else {
            $sessionData = $this->session->getFormData();
            if (!empty($sessionData)) {
                $productsToTranslate = array_keys($sessionData['items']);
            } else {
                $collection = $this->filter->getCollection($this->productCollectionFactory->create());
                $productsToTranslate = $collection->getAllIds();
            }
        }

        $productNames = $this->helper->getEntityNames(
            $this->productCollectionFactory,
            $this->getRequest()->getParam('store'),
            $productsToTranslate,
            null
        );
        $productsToTranslate = array_keys($productNames);

        $checkFields = $this->helper->checkFieldsConfigured(Data::CATALOG_PRODUCT_TYPE_ID, $productsToTranslate);
        if (!$checkFields['ok']) {
            $error = __('Fields are not configured under Globallink > Field Configuration > Products. Check config for following Attribute Sets:');
            $error.= ' '.implode(', ', $checkFields['errorMessages']);
            $this->messageManager->addErrorMessage($error);
            return $this->_redirect('catalog/product');
        }

        $itemsToTranslate = [
            'ids' => $productsToTranslate,
            'names' => $productNames
        ];
        $this->registry->register('itemsToTranslate', $itemsToTranslate);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::management');
        $resultPage->getConfig()->getTitle()->prepend(__('Create Submission'));
        $resultPage->addBreadcrumb(__('Submission'), __('Submission'));
        $this->logger->logAction(Data::CATALOG_PRODUCT_TYPE_ID, Logger::FORM_ACTION_TYPE, $this->getRequest()->getParams());
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
