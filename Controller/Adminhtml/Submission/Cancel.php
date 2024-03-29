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
        //Modified 10/11/21 by Justin Griffin: Removing functionality like this makes it harder to restore functionality to other things when they break
        $completedItems = [];
        /*foreach ($items as $item) {
            if ($item->isCompleted()) {
                if (!in_array($item->getSubmissionName(), $completedItems)) {
                    $completedItems[] = $item->getSubmissionName();
                }
            }
        }*/
        try {
            if (count($completedItems) > 0) {
                $completedItemsString = "Cannot cancel the following submissions because they are complete in PD: " . implode(",", $completedItems) . ". Please import instead.";
                $this->messageManager->addErrorMessage($completedItemsString);
            } else {
                if ($this->isAutomaticMode) {
                    $items->walk('cancelItem');
                    $this->cancelTranslations->executeAutomatic();
                    $this->messageManager->addSuccessMessage(__('Submissions have been cancelled'));
                } else {
                    $items->walk('cancelItem');
                    $this->messageManager->addSuccessMessage(__('Submissions have been moved to cancellation queue'));
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $this->resultRedirect->setPath('*/*/index');
    }
}
