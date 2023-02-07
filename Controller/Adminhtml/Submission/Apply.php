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
        $missingBlocksFlag = false;
        $itemIds = $postData['ids'];
        //We need to check for any pages that have been selected that are finished
        $selectedCmsPageRecords = $this->itemCollectionFactory->create();
        $selectedCmsPageRecords->addFieldToFilter(
            'id',
            ['in' => $itemIds]
        );
        $selectedCmsPageRecords->addFieldToFilter('entity_type_id', $this->helper::CMS_PAGE_TYPE_ID);
        $selectedCmsPageRecords->addFieldToFilter('status_id', ['in' => [Item::STATUS_FINISHED, Item::STATUS_WAIT_FOR_BLOCKS]]);
        if(count($selectedCmsPageRecords) > 0){
            //For each page that meets that criteria, we then......
            foreach($selectedCmsPageRecords as $pageItem){
                //Check to see if there are any cms blocks that have it as a parent and are a part of the same submission
                //That weren't selected as a part of this action.
                $blockChildrenRecords = $this->itemCollectionFactory->create();
                $blockChildrenRecords->addFieldToFilter('entity_type_id', $this->helper::CMS_BLOCK_TYPE_ID);
                $blockChildrenRecords->addFieldToFilter('parent_id', $pageItem->getData('entity_id'));
                $blockChildrenRecords->addFieldToFilter('queue_id', $pageItem->getData('queue_id'));
                $blockChildrenRecords->addFieldToFilter('status_id', ['nin' => [Item::STATUS_APPLIED]]);
                $blockChildrenRecords->addFieldToFilter(
                    'id',
                    ['nin' => $itemIds]
                );
                $missingChildBlocks = count($blockChildrenRecords);
                if($missingChildBlocks > 0){
                    $pageItem->setStatusId(Item::STATUS_WAIT_FOR_BLOCKS);
                    $pageItem->save();
                    $missingBlocksFlag = true;

                } else if($pageItem->getStatusId() == Item::STATUS_WAIT_FOR_BLOCKS){
                    $pageItem->setStatusId(Item::STATUS_FINISHED);
                    $pageItem->save();
                }
            }
        }
        if($missingBlocksFlag){
            $this->messageManager->addError(__('Cannot Import, Child Blocks Must Also Be Imported or be Imported at the Same Time'));
            return $this->resultRedirect->setPath('*/*/index');
        }
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'id',
            ['in' => $itemIds]
        );
        $items->addFieldToFilter(
            'status_id',
            ['in' => [Item::STATUS_FINISHED]]
        );
        $items->setOrder('entity_type_id', 'DESC');
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
        $start = microtime(true);
        $this->helper->reIndexing();
        $this->_eventManager->dispatch('transperfect_globallink_apply_translation_after', ['queues' => $this->registry->registry('queues')]);
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels) && $this->scopeConfig->getValue('globallink/general/reindexing', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 1) {
            $logData = [
                'message' => "Reindex and redirect duration: " . (microtime(true) - $start) . " seconds",
            ];
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
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
