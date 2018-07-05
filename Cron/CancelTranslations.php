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
     * Execute method
     */
    protected function execute()
    {
        try {
            $logData = ['message' => "Start cancel translations task (mode:{$this->mode})"];
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));

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
                $logData = ['message' => "There were no any unfinished items found. Finish."];
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
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
        foreach ($items as $item) {
            if (in_array($item->getPdLocaleIsoCode(), $cancelled[$item->getDocumentTicket()])) {
                $item->setStatusId(Item::STATUS_FOR_DELETE);
                $message = 'Local Item ('.$item->getId().') for remotely cancelled task has been removed.';
                $this->cliMessage($message);
                $logData = [
                    'message' => $message,
                ];
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
            $queue->setProcessed(true);
        }
        foreach ($items as $item) {
            if ($item->cancelTranslationCall()) {
                $message = 'Remote translation task has been cancelled (item id '.$item->getId().')';
                $this->cliMessage($message);
                $logData = [
                    'message' => $message,
                ];
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            } else {
                $message = "Can't cancel translation (item id ".$item->getId().")";
                $this->cliMessage($message, 'error');
                $logData = [
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'message' => $message,
                ];
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
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
