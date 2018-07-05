<?php
namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config\Product\Attribute;

use \Magento\Backend\App\Action as BackendAction;

/**
 * Class Grid
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Config\Product\Attribute
 */
class Grid extends BackendAction
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->getLayout()->getMessagesBlock()->setMessages($this->messageManager->getMessages(true));
        $this->_view->renderLayout();
    }
}
