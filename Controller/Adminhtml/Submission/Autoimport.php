<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Justin Griffin <jgriffin@translations.com>
 */

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission;

use TransPerfect\GlobalLink\Controller\Adminhtml\Submission;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Model\Queue\Item;

/**
 * Class Autoimport
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission
 */
class Autoimport extends Submission
{

    const STATUS_FINISHED = 2;
    /**
     * controller main method
     *
     * @return \Magento\Framework\Controller\ResultInterface)
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->cancelTranslations->executeAutomatic();
        //Runs receive translations command to import
        $this->receiveTranslations->executeAutomatic();
        $automaticItemIds = $this->receiveTranslations->getAutomaticItemIds();
        $logData = ['message' => "Resetting status of pages waiting for blocks to Ready to Import."];
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }

        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter('entity_type_id', Data::CMS_PAGE_TYPE_ID);
        $items->addFieldToFilter('status_id', Item::STATUS_WAIT_FOR_BLOCKS);
        /*if($automaticItemIds != null) {
            $items->addFieldToFilter(
                'id',
                ['in' => $automaticItemIds]
            );
        }*/
        foreach($items as $item){
            $item->setData('status_id', Item::STATUS_FINISHED);
            $item->save();
        }
        $logData = ['message' => "Checking for child blocks not yet ready to import."];
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        $cmsPageRecords = $this->itemCollectionFactory->create();
        $cmsPageRecords->addFieldToFilter('entity_type_id', Data::CMS_PAGE_TYPE_ID);
        $cmsPageRecords->addFieldToFilter('status_id', Item::STATUS_FINISHED);
        /*$items->addFieldToFilter(
            'id',
            ['in' => $automaticItemIds]
        );*/
        if(count($cmsPageRecords) > 0){
            //For each page that meets that criteria, we then......
            foreach($cmsPageRecords as $pageItem){
                //Check to see if there are any cms blocks that have it as a parent and are a part of the same submission
                //That weren't selected as a part of this action.
                $blockChildrenRecords = $this->itemCollectionFactory->create();
                $blockChildrenRecords->addFieldToFilter('entity_type_id', ['in' => [$this->helper::CMS_BLOCK_TYPE_ID, $this->helper::BANNER_ID]]);
                $blockChildrenRecords->addFieldToFilter('parent_id', array('finset' => $pageItem->getData('entity_id')));
                $blockChildrenRecords->addFieldToFilter('queue_id', $pageItem->getData('queue_id'));
                $blockChildrenRecords->addFieldToFilter('pd_locale_iso_code', $pageItem->getData('pd_locale_iso_code'));
                $blockChildrenRecords->addFieldToFilter('status_id', ['nin' => [Item::STATUS_APPLIED, Item::STATUS_FINISHED]]);

                $missingChildBlocks = count($blockChildrenRecords);
                if($missingChildBlocks > 0){
                    $pageItem->setData('status_id', Item::STATUS_WAIT_FOR_BLOCKS);
                    $logData = ['message' => "Page with item id = ".$pageItem->getData('id')." found with blocks not yet imported or ready to be imported. Setting status to wait."];
                    if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                        $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                    }
                    $pageItem->save();
                }
            }
        }
        //Then imports any translations that are ready to go
        $items = $this->itemCollectionFactory->create();
        /*$items->addFieldToFilter(
            'id',
            ['in' => $automaticItemIds]
        );*/
        $items->addFieldToFilter(
            'status_id',
            ['in' => [$this::STATUS_FINISHED]]
        );
        $items->setOrder('entity_type_id', 'DESC');
        $itemsTotal = count($items);

        if (!$itemsTotal) {
            $this->messageManager->addError(__('No submissions were ready to be applied.'));
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
        $this->messageManager->addSuccessMessage(__('Submissions were successfully received and applied to target stores.'));
        return $resultRedirect->setPath('*/*/index');
    }
}
