<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml;

use Magento\Backend\App\Action as BackendAction;
use Magento\Framework\Registry;
use TransPerfect\GlobalLink\Logger\BgTask\Logger as BgLogger;
use TransPerfect\GlobalLink\Model\Queue\Item;
use TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory as ItemCollectionFactory;

/**
 * Class Submission
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml
 */
class Submission extends BackendAction
{
    protected $viewFactory;
    protected $scopeConfig;

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
     * @var \TransPerfect\GlobalLink\Cron\ReceiveTranslations
     */
    protected $receiveTranslations;
    /**
     * @var \TransPerfect\GlobalLink\Cron\CancelTranslations
     */
    protected $cancelTranslations;
    protected $bgLogger;
    protected $isAutomaticMode;
    /**
     * Submission constructor.
     *
     * @param \Magento\Backend\App\Action\Context                                       $context
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory $itemCollectionFactory
     * @param \Magento\Framework\Registry                                               $registry
     * @param \TransPerfect\GlobalLink\Helper\Data                                      $helper
     * @param \TransPerfect\GlobalLink\Logger\BgTask\Logger                             $bgLogger
     * @param \TransPerfect\GlobalLink\Cron\ReceiveTranslations                         $receiveTranslations
     * @param \TransPerfect\GlobalLink\Cron\CancelTranslations                          $cancelTranslations
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                        $scopeConfig
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        ItemCollectionFactory $itemCollectionFactory,
        Registry $registry,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        BgLogger $bgLogger,
        \TransPerfect\GlobalLink\Cron\ReceiveTranslations $receiveTranslations,
        \TransPerfect\GlobalLink\Cron\CancelTranslations $cancelTranslations,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->viewFactory = $context->getResultFactory();
        $this->resultRedirect = $context->getResultRedirectFactory()->create();
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->registry = $registry;
        $this->helper = $helper;
        $this->bgLogger = $bgLogger;
        $this->receiveTranslations = $receiveTranslations;
        $this->cancelTranslations = $cancelTranslations;
        if ($scopeConfig->getValue('globallink/general/automation') == 1) {
            $this->isAutomaticMode = true;
        } else {
            $this->isAutomaticMode = false;
        }
        BackendAction::__construct($context);
        $user = $this->_auth->getUser();
        if (!empty($user)) {
            Item::setActor('user: ' . $user->getUsername() . '(' . $user->getId() . ')');
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
