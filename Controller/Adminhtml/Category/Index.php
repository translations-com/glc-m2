<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Category;

use TransPerfect\GlobalLink\Controller\Adminhtml\Category;

/**
 * Class Index of Categories
 *
 * @package TransPerfect\GlobalLink\Controller\Category
 */
class Index extends Category
{
    /**
     * @return $this|\Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('ajax')) {
            $this->_forward('grid');
            return $this;
        }
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::categories');
        $resultPage->getConfig()->getTitle()->prepend(__('Categories to Translate'));

        return $resultPage;
    }
}
