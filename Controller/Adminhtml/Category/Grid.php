<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Eugene Monakov <emonakov@robofirm.com>
 */

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Category;

use \Magento\Backend\App\Action as BackendAction;

/**
 * Class Grid
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Category
 */
class Grid extends BackendAction
{
    /**
     * Queue list Ajax action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->getLayout()->getMessagesBlock()->setMessages($this->messageManager->getMessages(true));
        $this->_view->renderLayout();
    }
}
