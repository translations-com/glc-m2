<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config;

use Magento\Backend\App\Action as BackendAction;
use Magento\Framework\View\Result\PageFactory;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Action
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Config
 */
class Action extends BackendAction
{
    /**
     * @var \TransPerfect\GlobalLink\Helper\Ui\Logger
     */
    protected $logger;
    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;

    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $pageFactory;

    /**
     * Action constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \TransPerfect\GlobalLink\Helper\Data       $helper
     * @param \TransPerfect\GlobalLink\Helper\Ui\Logger  $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        PageFactory $pageFactory,
        Data $helper,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TransPerfect_GlobalLink::fieldform');
    }
}
