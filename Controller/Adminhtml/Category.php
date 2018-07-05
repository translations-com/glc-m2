<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Eugene Monakov <emonakov@robofirm.com>
 */

namespace TransPerfect\GlobalLink\Controller\Adminhtml;

use Magento\Backend\App\Action as BackendAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Category
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml
 */
abstract class Category extends BackendAction
{
    protected $resultPageFactory;

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
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * Category constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \TransPerfect\GlobalLink\Helper\Ui\Logger  $logger
     * @param \Magento\Framework\Registry                $registry
     * @param \TransPerfect\GlobalLink\Helper\Data       $helper
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Magento\Framework\Registry $registry,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $collectionFactory,
        Logger $logger
    ) {
        $this->resultPageFactory = $pageFactory;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->helper = $helper;
        $this->session = $context->getSession();
        $this->categoryCollectionFactory = $collectionFactory;

        BackendAction::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }

    /**
     * Check permission via ACL resource
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TransPerfect_GlobalLink::management');
    }
}
