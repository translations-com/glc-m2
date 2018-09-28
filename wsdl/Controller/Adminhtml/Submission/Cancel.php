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

/**
 * Class Cancel
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission
 */
class Cancel extends Submission
{
    /**
     * controller main method
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $postData = $this->getRequest()->getPostValue();

        if (empty($postData['ids'])) {
            $this->messageManager->addError(__('Nothing selected'));
            return $this->resultRedirect->setPath('*/*/index');
        }

        $itemIds = $postData['ids'];
        $completedItems = [];
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter('id', ['in' => $itemIds]);
        $itemsTotal = $items->getSize();
        if (!$itemsTotal) {
            $this->messageManager->addErrorMessage(__('Nothing selected'));
            return $this->resultRedirect->setPath('*/*/index');
        }
        foreach($items as $item){
            if($item->isCompleted()){
                if(!in_array($item->getSubmissionName(), $completedItems)) {
                    $completedItems[] = $item->getSubmissionName();
                }
            }
        }
        try {
            if(count($completedItems) > 0){
                $completedItemsString = "Cannot cancel the following submissions because they are complete in PD: ".implode(",", $completedItems) .". Please import instead.";
                $this->messageManager->addErrorMessage($completedItemsString);
            }
            else{
                $items->walk('cancelItem');
                $this->messageManager->addSuccessMessage(__('Submissions have been moved to cancellation queue'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $this->resultRedirect->setPath('*/*/index');
    }
}
