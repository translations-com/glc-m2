<?php
namespace TransPerfect\GlobalLink\Cron;

use TransPerfect\GlobalLink\Model\Queue;
use TransPerfect\GlobalLink\Model\Queue\Item;
use TransPerfect\GlobalLink\Model\TranslationService;
use TransPerfect\GlobalLink\Helper\Data as HelperData;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Bundle\Model\Product\Type as BundleType;

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
    public function executeAutomatic(){
        $this->mode = 'automatic';
        $this->execute();
    }
    /**
     * Execute method
     */
    protected function execute()
    {
        try {
            $logData = ['message' => "Start receive translations task (mode:{$this->mode})"];
            if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }
            if (!$this->lockJob()) {
                return;
            }
            Item::setActor($this->mode . ': Receive Translation Task');

            if ($this->mode == 'cli') {
                $this->appState->setAreaCode('adminhtml');
            }
            $logData = ['message' => "Checking for redelivery targets to finalize queue data."];
            if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
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
                $logData = ['message' => "There were no any unfinished items found. Finishing."];
                if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
            }

            $processedQueues = [];
            foreach ($queues as $queue) {
                $this->proceedQueue($queue);
                $processedQueues[] = $queue;
            }
            $this->eventManager->dispatch('transperfect_globallink_receive_queue_after', ['queues' => $processedQueues, 'items' => $this->itemCollection]);
        }
        finally{
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

        $targetStoreIds = [];
        foreach ($items as $item) {
            $logData = ['message' => "Beginning download of items."];
            if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }
            if (!$item->getSubmissionTicket()) {
                // execution can't enter here in prod, but can in dev
                continue;
            }
            $submissionsAndItems[$item->getSubmissionTicket()][] = $item->getId();

            $targetLocale = $item->getPdLocaleIsoCode();
            $documentsAndItems[$item->getDocumentTicket()][$targetLocale] = $item->getId();

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
            try {
                $targets = $this->translationService->receiveTranslationsByTickets(array_keys($submissionsAndItems), $queue);

            } catch (\Exception $e) {
                $errorMessage = 'Exception while receiving targets by submission tickets. '.$e->getMessage();
                $this->cliMessage($errorMessage, 'error');
                $logData = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $errorMessage,
                    ];
                if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                    $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                }
                $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
            }
            $targetArray = implode(array_keys($submissionsAndItems));
            if (empty($targets)) {
                $logData = ['message' => "No targets found in PD. Finishing. Submission Item Array: {$targetArray}"];
                if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
                $logData = null;
                return $this;
            }

            foreach ($targets as $target) {
                if (empty($documentsAndItems[$target->documentTicket][$target->targetLocale])) {
                    // finished job for item which hasn't been requested while this run
                    // can be found by submission ticket but can already been downloaded before
                    continue;
                }
                $targetTicket = $target->ticket;
                $maxLengthError = false;
                $item = $this->getItemByDocTicket($target->documentTicket);
                try {
                    $translatedText = $this->translationService->downloadTarget($targetTicket);

                    $logMessage = "Target ticket: {$target->ticket}, document ticket: {$target->documentTicket}, document name: {$target->documentName}, source locale: {$target->sourceLocale}, target locale: {$target->targetLocale} has been downloaded.";
                    $logData = [
                        'message' => $logMessage
                    ];
                    if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                        $this->bgLogger->addInfo($logMessage);
                    }
                    $dom = simplexml_load_string($translatedText);
                    foreach ($dom->children() as $child) {
                        $nodeValue = (string) $child;
                        $maxLength = (string)$child->attributes()->max_length;
                        if (strlen($nodeValue) > $maxLength && $maxLength != "none" && $maxLength != "") {
                            $maxLengthError = true;
                            $item->setStatusId(Item::STATUS_MAXLENGTH);
                            $item->save();
                            $errorMessage = "Max length for field \"". (string)$child->attributes()->attribute_code . "\" for document ticket " . $target->documentTicket . " is greater than the allowed length.";
                            $this->cliMessage($errorMessage, 'error');
                            $logData = [
                                'file' => __FILE__,
                                'line' => __LINE__,
                                'message' => $errorMessage,
                            ];
                            if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                            }
                            $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
                        }
                        elseif($item->getStatusId() == Item::STATUS_MAXLENGTH && $maxLengthError != true)
                        {
                            $item->setStatusId(Item::STATUS_INPROGRESS);
                            $item->save();
                            $message = "Max length for document ticket " . $target->documentTicket . " has been corrected, reverting status to in progress.";
                            $this->cliMessage($message, 'info');
                            $logData = [
                                'file' => __FILE__,
                                'line' => __LINE__,
                                'message' => $message,
                            ];
                            if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $errorMessage = 'Exception while downloading target '.$targetTicket.': '.$e->getMessage();
                    $this->cliMessage($errorMessage, 'error');
                    $logData = [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'message' => $errorMessage,
                        ];
                    if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                        $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                    }
                    $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
                    continue;
                }
                $downloadingItemId = $documentsAndItems[$target->documentTicket][$target->targetLocale];
                $fileName = 'item_'.$downloadingItemId.'.xml';

                $filePath = $xmlFolder.'/'.$fileName;
                if (!$this->file->write($filePath, $translatedText)) {
                    $errorMessage = "Can't write xml data to file ".$filePath;
                    $this->cliMessage($errorMessage, 'error');
                    $logData = [
                        'file' => __FILE__,
                        'line' => __LINE__,
                        'message' => $errorMessage,
                        ];
                    if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                        $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                    }
                    $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
                    continue;
                }
                $this->moveItemsInDownloaded([$downloadingItemId=>$targetTicket]);
                if($this->mode == 'automatic'){
                    $this->automaticItemIds[] = $downloadingItemId;
                }
                $this->cliMessage($fileName.' downloaded for item '.$downloadingItemId.' from queue '.$queue->getId());
                $queue->setProcessed(true);
            }
        }
        $this->tryToSetQueueFinished($queue);
        return $this;
    }
    protected function redeliveryQueueReset(){
        $xmlFolder = $this->translationService->getReceiveFolder();
        try{
            $targets = $this->translationService->receiveTranslationsByProject();
        }  catch (\Exception $e) {
            $errorMessage = 'Exception while receiving targets by submission tickets. '.$e->getMessage();
            $this->cliMessage($errorMessage, 'error');
            $logData = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $errorMessage,
            ];
            if(in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
        }
        if (empty($targets)) {
            return $this;
        }
        $itemResource = $this->itemResourceFactory->create();
        // get all queues that have been submitted
        $queues = $this->queueCollectionFactory->create();
        $queues->addFieldToFilter(
            'status',
            ['in' => [
                Queue::STATUS_FINISHED,
            ]]
        );
        foreach($queues as $queue){
            $documentTickets = $itemResource->getDistinctDocTicketsForQueue($queue->getId());
            foreach($targets as $target){
                if(in_array($target->documentTicket, $documentTickets)){
                    $logData = ['message' => "Document ticket {$target->documentTicket} found already delivered but completed in PD, resetting queue status to sent."];
                    if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                        $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                    }
                    if(!$queue->getStatus(Queue::STATUS_SENT)){
                        $queue->setStatus(Queue::STATUS_SENT);
                        $queue->save();
                    }
                    $this->cliMessage("Document ticket {$target->documentTicket} from queue {$queue->getId()} found already delivered but completed in PD, resetting queue status to sent.");
                    $item = $this->getItemByDocTicket($target->documentTicket);
                    $item->setStatusId(Item::STATUS_ERROR_DOWNLOAD);
                    $item->save();
                }
            }
        }
        return $this;
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
            if($item->getStatusId() != Item::STATUS_MAXLENGTH){
                $item->setStatusId(Item::STATUS_FINISHED);
            }
            $item->setTargetTicket($itemIds[$item->getId()]);
            $this->itemCollection[] = $item;
        }
        $items->save();
    }

    protected function getItemByDocTicket($docTicket){
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter('document_ticket',
            ['eq' => $docTicket]);
        $item = null;
        if($items->getSize()){
            $item = $items->getFirstItem();
        }
        return $item;
    }

    public function getAutomaticItemIds(){
        return $this->automaticItemIds;
    }
}
