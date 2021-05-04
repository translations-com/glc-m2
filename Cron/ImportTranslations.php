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
        $logData = ['message' => "Pulling IDs of ready for import translations."];
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'status_id',
            ['in' => [$this::STATUS_FINISHED]]
        );
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
