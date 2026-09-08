<?php
namespace TransPerfect\GlobalLink\Cron;

use TransPerfect\GlobalLink\Model\Queue;
use TransPerfect\GlobalLink\Model\Queue\Item;

/**
 * Class ReceiveTranslations
 */
class ReceiveTranslations extends Translations
{
    /**
     * @var Item[]
     */
    protected $itemCollection;

    /**
     * @var string current run mode cron|cli
     */
    protected $mode;
    /**
     * Receive task's lock file name
     */
    const LOCK_FILE_NAME = 'receive.lock';

    protected $automaticItemIds = null;

    protected $targets = null;

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
     * Automatic command execute method
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
            if (!$this->lockJob()) {
                return;
            }
            Item::setActor($this->mode . ': Receive Translation Task');

            if ($this->mode == 'cli') {
                $this->appState->setAreaCode('adminhtml');
            }
            try {
                $this->targets = $this->translationService->receiveTranslationsByProject();
                if (count($this->targets) > 0 && in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage(['message' => "Targets were found via PD. Count = " . count($this->targets)]));
                } elseif (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels) && count($this->targets) == 0) {
                    $this->cliMessage("PD reported no targets were available.");
                    $this->bgLogger->info($this->bgLogger->bgLogMessage(['message' => "PD reported no targets were available."]));
                } elseif (count($this->targets) == 0) {
                    $this->cliMessage("PD reported no targets were available.");
                }
            } catch (\Exception $e) {
                $errorMessage = 'Exception while receiving targets by project. ' . $e->getMessage();
                $this->cliMessage($errorMessage, 'error');
                $logData = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $errorMessage,
                ];
                if (in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                    $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                }
            }
            $logData = ['message' => "Checking for redelivery targets to finalize queue data."];
            if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }
            $this->redeliveryQueueReset();
            // get all queues that have been fully or partly sent, but haven't been finished yet
            $queues = $this->queueCollectionFactory->create();
            $queues->addFieldToFilter(
                'status',
                ['in' => [
                    Queue::STATUS_INPROGRESS,
                    Queue::STATUS_SENT,
                ]]
            );
            $queuesTotal = count($queues);
            if (!$queuesTotal) {
                $logData = ['message' => "There were not any unfinished items found. Finishing..."];
                $this->cliMessage("There were not any unfinished items found. Finishing...");
                if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
            }

            $processedQueues = [];

            foreach ($queues as $queue) {
                $this->proceedQueue($queue);
                $processedQueues[] = $queue;
            }
            $this->eventManager->dispatch('transperfect_globallink_receive_queue_after', ['queues' => $processedQueues, 'items' => $this->itemCollection]);
        } finally {
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
        $submissionID = $queue->getData('submission_id');
        $xmlFolder = $this->translationService->getReceiveFolder();

        // get items of current queue which have been sent but haven't been translated yet
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'id',
            ['in' => $queue->getItems()]
        );
        $items->addFieldToFilter(
            'status_id',
            ['in' => [
                Item::STATUS_INPROGRESS,
                Item::STATUS_ERROR_DOWNLOAD,
                Item::STATUS_MAXLENGTH,
            ]]
        );

        /**
         * @var array $submissionsAndItems
         *
         *  submission_ticket_hash => Array
         *      item_id,
         *      item_id
         */
        $submissionsAndItems = [];

        /**
         * @var array $documentsAndItems
         *
         *  document_ticket_hash => Array
         *      target_locale_name => item_id,
         *      target_locale_name => item_id
         */
        $documentsAndItems = [];
        $logData = ['message' => "Beginning download of items for submission = " . $queue->getData('name')];
        if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        $targetStoreIds = [];
        foreach ($items as $item) {
            $submissionsAndItems[$queue->getData('submission_id')][] = $item->getId();

            $targetLocale = $item->getPdLocaleIsoCode();
            $documentsAndItems[$item->getData('document_id')][$targetLocale] = $item->getId();

            $storeIds = explode(',', trim($item->getTargetStores(), ','));
            foreach ($storeIds as $storeId) {
                $targetStoreIds[$storeId] = $storeId;
            }
        }
        $stores = $this->storeManager->getStores(true);
        $storeNames = [];
        foreach ($stores as $store) {
            if (in_array($store->getStoreId(), $targetStoreIds)) {
                $storeNames[] = $store->getName();
            }
        }
        $queue->setWebsiteNames(implode(', ', $storeNames));

        if (!empty($submissionsAndItems)) {
            // current queue has items which must be examined if translation completed
            $targets = $this->getSubmissionTargetsFromProject($documentsAndItems);

            $targetArray = implode(array_keys($submissionsAndItems));
            if (empty($targets)) {
                $logData = ['message' => "No targets found that match the available submissions. Finishing. Submission Item Array: {$targetArray}"];
                if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
                $logData = null;
                return $this;
            } else {
                $targetCount = count($targets);
                $logData = ['message' => "Targets found that match the available submissions(count={$targetCount}). Submission Item Array: {$targetArray}"];

                if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
                $logData = null;
            }
            if ($this->targets != null && is_array($this->targets) && count($this->targets) > 0) {
                foreach ($targets as $target) {
                    if (empty($documentsAndItems[$target->documentId][$target->targetLanguage])) {
                        // finished job for item which hasn't been requested while this run
                        // can be found by submission ticket but can already been downloaded before
                        continue;
                    }
                    $errorsEncountered = 0;
                    $targetID = $target->targetId;
                    $maxLengthError = false;
                    $item = $this->getItemByDocTicket($target->documentId, $target->targetLanguage);
                    try {
                        $translatedText = $this->translationService->downloadTarget($submissionID, $targetID);

                        $logMessage = "Target ID: {$target->targetId}, document ID: {$target->documentId}, document name: {$target->documentName}, source locale: {$target->sourceLanguage}, target locale: {$target->targetLanguage} has been downloaded.";
                        $logData = [
                            'message' => $logMessage
                        ];
                        if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                            $this->bgLogger->info($logMessage);
                        }
                        $dom = simplexml_load_string($translatedText);
                        foreach ($dom->children() as $child) {
                            $nodeValue = (string)$child;
                            $nodeLength = mb_strlen($nodeValue);
                            $maxLength = (string)$child->attributes()->max_length;
                            if ($nodeLength > $maxLength && $maxLength != "none" && $maxLength != "") {
                                $errorsEncountered++;
                                $maxLengthError = true;
                                $item->setStatusId(Item::STATUS_MAXLENGTH);
                                $item->save();
                                $errorMessage = "Max length for field \"" . (string)$child->attributes()->attribute_code . "\" for document ID " . $target->documentId . " is greater than the allowed length.";
                                $errorMessage2 = "Field contents: " . $nodeValue;
                                $this->cliMessage($errorMessage, 'error');
                                $this->cliMessage($errorMessage2, 'error');
                                $logData = [
                                    'file' => __FILE__,
                                    'line' => __LINE__,
                                    'message' => $errorMessage,
                                ];
                                if (in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                                    $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                                }
                                $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
                            } elseif ($item->getStatusId() == Item::STATUS_MAXLENGTH && $maxLengthError != true) {
                                $item->setStatusId(Item::STATUS_INPROGRESS);
                                $item->save();
                                $message = "Attempting to download document ticket " . $target->documentId . " again, reverting status to in progress. Previously there was a max_length error.";
                                $this->cliMessage($message, 'info');
                                $logData = [
                                    'file' => __FILE__,
                                    'line' => __LINE__,
                                    'message' => $message,
                                ];
                                if (in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                                    $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $errorMessage = 'Exception while downloading target ID ' . $targetID . ': ' . $e->getMessage();
                        $this->cliMessage($errorMessage, 'error');
                        $logData = [
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'message' => $errorMessage,
                        ];
                        if (in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                            $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                        }
                        $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
                        continue;
                    }
                    $downloadingItemId = $documentsAndItems[$target->documentId][$target->targetLanguage];
                    $fileName = 'item_' . $downloadingItemId . '.xml';

                    $filePath = $xmlFolder . '/' . $fileName;
                    if (!$this->file->write($filePath, $translatedText)) {
                        $errorsEncountered++;
                        $errorMessage = "Can't write xml data to file " . $filePath;
                        $this->cliMessage($errorMessage, 'error');
                        $logData = [
                            'file' => __FILE__,
                            'line' => __LINE__,
                            'message' => $errorMessage,
                        ];
                        if (in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                            $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                        }
                        $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
                        continue;
                    }
                    $this->moveItemsInDownloaded([$downloadingItemId => $targetID]);
                    if ($this->mode == 'automatic') {
                        $this->automaticItemIds[] = $downloadingItemId;
                    }
                    if (!$maxLengthError) {
                        $this->cliMessage($fileName . ' downloaded for item ' . $downloadingItemId . ' for submission ' . $queue->getData('name'));
                    } else {
                        $this->cliMessage('Item ' . $downloadingItemId . ' was not downloaded from queue ' . $queue->getId() . ' due to max length error');
                    }
                    if ($errorsEncountered < 1) {
                        $this->sendDownloadConfirmation($queue, $target);
                    }
                    $queue->setProcessed(true);
                }
            }
        }
        $this->tryToSetQueueFinished($queue);
        return $this;
    }
    protected function redeliveryQueueReset()
    {
        $xmlFolder = $this->translationService->getReceiveFolder();
        $itemResource = $this->itemResourceFactory->create();
        // get all queues that have been submitted
        $queues = $this->queueCollectionFactory->create();
        $queues->addFieldToFilter(
            'status',
            ['in' => [
                Queue::STATUS_FINISHED,
            ]]
        );
        foreach ($queues as $queue) {
            $targetIds = $itemResource->getDistinctTargetTicketsForQueue($queue->getId());
            if (is_array($this->targets) && count($this->targets) > 0) {
                foreach ($this->targets as $target) {
                    if (in_array($target->targetId, $targetIds)) {
                        $logData = ['message' => "Target ID {$target->targetId} found already delivered/cancelled but completed in PD, resetting queue status to sent."];
                        if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                        }
                        if (!$queue->getStatus(Queue::STATUS_SENT)) {
                            $queue->setStatus(Queue::STATUS_SENT);
                            $queue->save();
                        }
                        $this->cliMessage("Target ticket {$target->targetId} from queue {$queue->getId()} found already delivered/cancelled but completed in PD, resetting queue status to sent.");
                        $item = $this->resetStatuses($target->targetId);
                    }
                }
            }
        }
        return $this;
    }
    public function getSubmissionTargetsFromProject($ticketArray)
    {
        $targetArray = [];
        $targetFound = false;
        $targetNotFoundCount = 0;

        if ($this->targets != null && is_array($this->targets) && count($this->targets) > 0) {
            foreach ($this->targets as $target) {
                foreach ($ticketArray as $key => $value) {
                    if ($target->documentId == $key) {
                        $targetArray[] = $target;
                        $targetFound = true;
                    }
                }
                if (!$targetFound) {
                    $targetNotFoundCount++;
                }
            }
        }
        if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels) && $targetNotFoundCount > 0) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage(['message' => "Targets were found that do not match any current submission. Count = " . $targetNotFoundCount]));
        }
        return $targetArray;
    }

    /**
     * Update Items
     *
     * @param array $itemIds where keys is item ids and values is target tickets
     */
    protected function moveItemsInDownloaded(array $itemIds)
    {
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'id',
            ['in' => array_keys($itemIds)]
        );
        foreach ($items as $item) {
            if ($item->getStatusId() != Item::STATUS_MAXLENGTH) {
                $item->setStatusId(Item::STATUS_FINISHED);
            }
            $item->setData('target_id', $itemIds[$item->getId()]);
            $this->itemCollection[] = $item;
        }
        $items->save();
    }

    protected function getItemByDocTicket($docID, $targetLanguage = false)
    {
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'document_id',
            ['eq' => $docID]
        );
        if ($targetLanguage) {
            $items->addFieldToFilter(
                'pd_locale_iso_code',
                ['eq' => $targetLanguage]
            );
        }
        $item = null;
        if ($items->getSize()) {
            $item = $items->getFirstItem();
        }
        return $item;
    }

    protected function resetStatuses($targetId)
    {
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'target_id',
            ['eq' => $targetId]
        );
        if ($items->getSize()) {
            foreach ($items as $item) {
                $item->setStatusId(Item::STATUS_INPROGRESS);
                $item->save();
            }
        }
    }

    public function getAutomaticItemIds()
    {
        return $this->automaticItemIds;
    }
    /**
     * send download confirmation for document of current item
     */
    protected function sendDownloadConfirmation($queue, $target)
    {
        try {
            $confirmation = $this->translationService->requestGLExchange()->confirmTargetDelivery($queue->getData('submission_id'), [$target->targetId]);
            if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage(['message' => "Confirmation sent for target " . $target->targetId]));
            }
        } catch (\Exception $e) {
            $errorMessage = 'Exception while sending download confirmation for target ' . $target->targetId . ': ' . $e->getMessage();
            $logData = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $errorMessage,
            ];
            if (in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
            $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
        }
    }
}
