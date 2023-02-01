<?php
/**
 * Created by PhpStorm.
 * User: jgriffin
 * Date: 9/3/2019
 * Time: 10:08 AM
 */

namespace TransPerfect\GlobalLink\Cron;

use TransPerfect\GlobalLink\Model\Queue;
use TransPerfect\GlobalLink\Model\Queue\Item;
use TransPerfect\GlobalLink\Model\TranslationService;
use TransPerfect\GlobalLink\Helper\Data;


class ImportTranslations extends Translations
{
    /**
     * @var Item[]
     */
    protected $itemCollection;

    /**
     * @var string current run mode cron|cli
     */
    protected $mode;

    const STATUS_FINISHED = 2;

    public function executeCron()
    {
        $this->mode = 'cron';
        $this->execute();
    }

    /**
     * Console command execute method
     */
    public function executeCli()
    {
        $this->mode = 'cli';
        $this->execute();
    }

    /**
     * Execute method
     */
    protected function execute()
    {
        $logData = ['message' => "Start import translations task (mode:{$this->mode})"];
        $this->cliMessage("Starting import translations task...");
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }

        Item::setActor($this->mode . ': Import Translation Task');

        if ($this->mode == 'cli') {
            $this->appState->setAreaCode('adminhtml');
        }
        $logData = ['message' => "Resetting status of pages waiting for blocks to Ready to Import."];
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter('entity_type_id', Data::CMS_PAGE_TYPE_ID);
        $items->addFieldToFilter('status_id', Item::STATUS_WAIT_FOR_BLOCKS);
        foreach($items as $item){
            $item->setData('status_id', Item::STATUS_FINISHED);
            $item->save();
        }
        $logData = ['message' => "Checking for child blocks not yet ready to import."];
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        $cmsPageRecords = $this->itemCollectionFactory->create();
        $cmsPageRecords->addFieldToFilter('entity_type_id', $this->helper::CMS_PAGE_TYPE_ID);
        $cmsPageRecords->addFieldToFilter('status_id', Item::STATUS_FINISHED);
        if(count($cmsPageRecords) > 0){
            //For each page that meets that criteria, we then......
            foreach($cmsPageRecords as $pageItem){
                //Check to see if there are any cms blocks that have it as a parent and are a part of the same submission
                //That weren't selected as a part of this action.
                $blockChildrenRecords = $this->itemCollectionFactory->create();
                $blockChildrenRecords->addFieldToFilter('entity_type_id', $this->helper::CMS_BLOCK_TYPE_ID);
                $blockChildrenRecords->addFieldToFilter('parent_id', $pageItem->getData('entity_id'));
                $blockChildrenRecords->addFieldToFilter('queue_id', $pageItem->getData('queue_id'));
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

        $logData = ['message' => "Pulling IDs of ready for import translations."];
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'status_id',
            ['in' => [$this::STATUS_FINISHED]]
        );
        $items->setOrder('entity_type_id', 'DESC');
        $itemsTotal = count($items);
        if (!$itemsTotal) {
            $logData = ['message' => "No items ready to be imported were found. Finishing..."];
            $this->cliMessage("No items ready to be imported were found. Finishing...");
            if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }
            return;
        }
        $logData = ['message' => "Number of items to be imported: ".$itemsTotal];
        $this->cliMessage("Number of items to be imported: ".$itemsTotal);
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        $this->registry->register('queues', []);
        try {
            $items->walk('applyTranslation');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $logData = ['message' => $e->getMessage()];
            if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
        } catch (\Exception $e) {
            $logData = ['message' => $e->getMessage()];
            if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
        }
        $this->eventManager->dispatch('transperfect_globallink_apply_translation_after', ['queues' => $this->registry->registry('queues')]);

        $this->cliMessage(__('Submissions were successfully applied to target stores.'));
    }
}
