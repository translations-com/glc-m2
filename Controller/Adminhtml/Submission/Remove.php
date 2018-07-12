<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Eugene Monakov <emonakov@robofirm.com>
 */

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission;

use TransPerfect\GlobalLink\Controller\Adminhtml\Submission;
use TransPerfect\GlobalLink\Model\Queue\Item;
use TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\Collection;

/**
 * Class Remove
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission
 */
class Remove extends Submission
{
    /**
     * controller main method
     *
     * @return \Magento\Framework\Controller\ResultInterface)
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $itemIds = $this->getRequest()->getParam('ids');

        if (empty($itemIds)) {
            $this->messageManager->addErrorMessage(__('Nothing selected'));
            return $resultRedirect->setPath('*/*/index');
        }
        /** @var Collection $items */
        $items = $this->itemCollectionFactory->create();
        if ($itemIds !== 'all') {
            $items->addFieldToFilter(
                'id',
                ['in' => $itemIds]
            );
        }
        $items->addFieldToFilter(
            'status_id',
            ['in' => [Item::STATUS_FOR_DELETE]]
        );
        $itemsTotal = $items->getSize();

        if (!$itemsTotal) {
            if ($itemIds === 'all') {
                $this->messageManager->addErrorMessage(__('There are no cancelled items'));
            } else {
                $this->messageManager->addErrorMessage(__('None of selected items are cancelled'));
            }
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $items->walk('removeItem');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
