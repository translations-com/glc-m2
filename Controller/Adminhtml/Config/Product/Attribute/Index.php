<?php
namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config\Product\Attribute;

use \Magento\Backend\App\Action as BackendAction;

/**
 * Class Index
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Config\Product\Attribute
 */
class Index extends BackendAction
{
    protected $resultPageFactory = false;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->messageManager = $context->getMessageManager();
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return $this|\Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $attributeSet = $this->getRequest()->getParam('attribute_set');

        if (!$attributeSet) {
            $this->messageManager->addNotice(__('Please specify an Attribute set'));
        }

        if ($this->getRequest()->getParam('ajax')) {
            $this->_forward('grid');
            return $this;
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::product_attribute_fields');
        $resultPage->getConfig()->getTitle()->prepend(__('Product Attributes For Translation'));
        $resultPage->addBreadcrumb(__('Product Attributes For Translation'), __('Product Attributes For Translation'));

        return $resultPage;
    }
    
    /*
     * Check permission via ACL resource
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TransPerfect_GlobalLink::fieldform');
    }
}
