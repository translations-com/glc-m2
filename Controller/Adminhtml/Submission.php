<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml;

use Magento\Backend\App\Action as BackendAction;
use Magento\Framework\Registry;
use TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory as ItemCollectionFactory;
use TransPerfect\GlobalLink\Model\Queue\Item;

/**
 * Class Submission
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml
 */
class Submission extends BackendAction
{
    protected $viewFactory;

    /**
     * Item collection factory
     *
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory
     */
    protected $itemCollectionFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Controller\Result\Redirect
     */
    protected $resultRedirect;

    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;
    /**
     * Submission constructor.
     *
     * @param \Magento\Backend\App\Action\Context                                       $context
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory $itemCollectionFactory
     * @param \Magento\Framework\Registry                                               $registry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        ItemCollectionFactory $itemCollectionFactory,
        Registry $registry,
        \TransPerfect\GlobalLink\Helper\Data $helper
    ) {
        $this->viewFactory = $context->getResultFactory();
        $this->resultRedirect = $context->getResultRedirectFactory()->create();
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->registry = $registry;
        $this->helper = $helper;
        BackendAction::__construct($context);
        $user = $this->_auth->getUser();
        if (!empty($user)) {
            Item::setActor('user: '.$user->getUsername().'('.$user->getId().')');
        }
    }

    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage  = $this->viewFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::submissions');
        $resultPage->getConfig()->getTitle()->prepend(__('Translations - Submissions'));
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
