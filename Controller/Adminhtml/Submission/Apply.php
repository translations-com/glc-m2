<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission;

use TransPerfect\GlobalLink\Controller\Adminhtml\Submission;
use TransPerfect\GlobalLink\Model\Queue\Item;

/**
 * Apply translations of selected submissions to site content
 */
class Apply extends Submission
{
    /**
     * controller main method
     *
     * @return \Magento\Framework\Controller\ResultInterface)
     */
    public function execute()
    {
        // @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect
        $resultRedirect = $this->resultRedirectFactory->create();

        $postData = $this->getRequest()->getPostValue();

        if (empty($postData['ids'])) {
            $this->messageManager->addError(__('Nothing selected'));
            return $this->resultRedirect->setPath('*/*/index');
        }

        $itemIds = $postData['ids'];

        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'id',
            ['in' => $itemIds]
        );
        $items->addFieldToFilter(
            'status_id',
            ['in' => [Item::STATUS_FINISHED]]
        );
        $itemsTotal = count($items);

        if (!$itemsTotal) {
            $this->messageManager->addError(__('No one of selected items is ready to be applied'));
            return $this->resultRedirect->setPath('*/*/index');
        }
        $this->registry->register('queues', []);
        try {
            $items->walk('applyTranslation');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $this->helper->reIndexing();
        $this->_eventManager->dispatch('transperfect_globallink_apply_translation_after', ['queues' => $this->registry->registry('queues')]);
        return $this->resultRedirect->setPath('*/*/index');
    }

    /*
     * Check permission via ACL resource
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TransPerfect_GlobalLink::management');
    }
}
