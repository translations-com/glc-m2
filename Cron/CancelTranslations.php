<?php
namespace TransPerfect\GlobalLink\Cron;

use TransPerfect\GlobalLink\Model\Queue;
use TransPerfect\GlobalLink\Model\Queue\Item;
use TransPerfect\GlobalLink\Model\TranslationService;
use TransPerfect\GlobalLink\Helper\Data as HelperData;

/**
 * Class CancelTranslations
 */
class CancelTranslations extends Translations
{
    /**
     * @var string Current run mode cron|cli
     */
    protected $mode;

    /**
     * Cancel task's lock file name
     */
    const LOCK_FILE_NAME = 'cancel.lock';

    /**
     * Cron job execute method
     */
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
     * Console command execute method
     */
    public function executeAutomatic()
    {
        $this->mode = 'automatic';
        $this->execute();
    }
    /**
     * Execute method
     */
    protected function execute()
    {
        try {
            $logData = ['message' => "Start cancel translations task (mode:{$this->mode})"];
            if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }
            if (!$this->lockJob()) {
                return;
            }
            Item::setActor($this->mode . ': Cancel Translation Task');

            if ($this->mode == 'cli') {
                $this->appState->setAreaCode('adminhtml');
            }

            // get all queues which haven't been fully translated yet
            // (i.e. can have cancelled items on both the local and remote sides)
            $queues = $this->queueCollectionFactory->create();
            $queues->addFieldToFilter(
                'status',
                ['in' => [
                    Queue::STATUS_NEW,
                    Queue::STATUS_INPROGRESS,
                    Queue::STATUS_SENT,
                ]]
            );
            $queuesTotal = count($queues);
            if (!$queuesTotal) {
                $logData = ['message' => "There were not any submissions that could be checked found. Finishing....."];
                $this->cliMessage("There were not any submissions that could be checked found. Finishing.....");
                if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
            } else{
                $logData = ['message' => "Found submissions to check for cancellation, number = " . $queuesTotal];
                if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
            }

            $processedQueues = [];
            foreach ($queues as $queue) {
                $this->proceedQueue($queue);
                $processedQueues[] = $queue;
            }
            $this->eventManager->dispatch('transperfect_globallink_cancel_queue_after', ['queues' => $processedQueues]);
        }
        finally {
            $this->unlockJob();
        }
    }

    /**
     * @param Queue $queue
     *
     * @return $this|bool
     */
    protected function proceedQueue($queue)
    {
        $queue->setProcessed(false);
        $queue->setQueueErrors([]);

        // Remove local tasks that have been cancelled on Service side
        $itemResource = $this->itemResourceFactory->create();
        $sbmTickets = $itemResource->getDistinctSbmTicketsForQueue($queue->getId());
        $cancelled = $this->translationService->getCancelledTargetsBySubmissions($sbmTickets);
        $items = $this->itemCollectionFactory->create();
        $logData = ['message' => "Beginning cancellation check for submission = " . $queue->getData('name')];
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        $items->addFieldToFilter(
            'status_id',
            ['nin' => [
                Item::STATUS_FOR_DELETE,
            ]]
        );
        $items->addFieldToFilter(
            'document_ticket',
            ['in' => array_keys($cancelled)]
        );
        $logData = ['message' => "Number of items available to check in submission = " . count($items)];
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        $remoteCancelExists = false;
        foreach ($items as $item) {
            if (in_array($item->getPdLocaleIsoCode(), $cancelled[$item->getDocumentTicket()])) {
                $remoteCancelExists = true;
                $logData = ['message' => "Found remotely cancelled item that needs to be synced, item ID: " . $item->getId()];
                if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
                $item->setStatusId(Item::STATUS_FOR_DELETE);
                $message = 'Local Item ('.$item->getId().') for remotely cancelled task has been removed.';
                $this->cliMessage($message);
                $logData = [
                    'message' => $message,
                ];
                if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
            }
        }
        if($remoteCancelExists == false) {
            $logData = ['message' => "No remotely cancelled items were found for submission " . $queue->getName()];
            if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }
        }
        $items->save();

        // Cancel remote tasks that have been cancelled locally
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'id',
            ['in' => $queue->getItems()]
        );
        $items->addFieldToFilter(
            'status_id',
            ['in' => [
                Item::STATUS_FOR_CANCEL,
                Item::STATUS_CANCEL_FAILED,
            ]]
        );
        if ($items->getSize()) {
            $logData = ['message' => "Found locally cancelled items that need to be synced, count = " . $items->getSize()];
            if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }
            $queue->setProcessed(true);
        } else{
            $logData = ['message' => "No locally cancelled items were found that need to be synced"];
            if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }
        }
        foreach ($items as $item) {
            if ($item->cancelTranslationCall()) {
                $message = 'Remote translation task has been cancelled (item id '.$item->getId().')';
                $this->cliMessage($message);
                $logData = [
                    'message' => $message,
                ];
                if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
            } else {
                $message = "Can't cancel translation (item id ".$item->getId().")";
                $this->cliMessage($message, 'error');
                $logData = [
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'message' => $message,
                ];
                if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                }
                $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
            }
        }
        //06-07-18 Justin: Removed the following call to keep items in the submissions list until they manually hit the remove items button on the submissions page
        //$this->removeItemsForDelete();

        $this->tryToSetQueueFinished($queue);
    }

    /**
     * Remove all Items prepared for deletion
     */
    protected function removeItemsForDelete()
    {
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'status_id',
            ['in' => [
                Item::STATUS_FOR_DELETE,
            ]]
        );
        $items->walk('delete');
    }
}
