<?php
namespace TransPerfect\GlobalLink\Cron;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use TransPerfect\GlobalLink\Helper\Data as HelperData;
use TransPerfect\GlobalLink\Model\Queue;
use TransPerfect\GlobalLink\Model\Queue\Item;


/**
 * Class SubmitTranslations
 */
class SubmitTranslations extends Translations
{
    /**
     * @var boolean includeOptions
     */
    private $includeOptions;
    /**
     * @var string current run mode cron|cli
     */
    protected $mode;
    /**
     * @var boolean due-date override
     */
    protected $ddOverride = null;

    /**
     * @var int Limit uploaded xmls per one cronjob execution
     */
    protected $limitUploads;

    /**
     * @var int count of queue's items which should be proceeded
     */
    protected $qItemsToPass;

    /**
     * default limit if something wrong with limit from DB
     */
    const DEFAULT_LIMIT_UPLOADS = 100;
    /**
     * @var Item Name
     */
    protected $itemName;
    /**
     * Submit task's lock file name
     */
    const LOCK_FILE_NAME = 'submit.lock';

    protected $automaticQueue;

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
     * Automatic setting execute method
     */
    public function executeAutomatic($queue)
    {
        $this->mode = 'automatic';
        $this->automaticQueue = $queue;
        $this->execute();
    }

    /**
     * Execute method
     */
    protected function execute()
    {
        try {
            $logData = ['message' => "Start submit translation task (mode:{$this->mode})"];
            if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }

            if (!$this->lockJob()) {
                return;
            }
            Item::setActor($this->mode . ': Submit Translation Task');

            if ($this->mode == 'cli') {
                $this->appState->setAreaCode('adminhtml');
            }

            $this->limitUploads = $this->scopeConfig->getValue(
                'globallink/general/files_per_submission',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            if (empty($this->limitUploads) || (int)$this->limitUploads < 1) {
                $this->limitUploads = self::DEFAULT_LIMIT_UPLOADS;
            }

            if ($this->mode == 'automatic') {
                $queues = $this->queueCollectionFactory->create();
                $queues->addFieldToFilter(
                    'id',
                    ['in' => [
                        $this->automaticQueue->getId(),
                    ]]
                );
            } else {
                // get all queues that have not been fully submitted
                $queues = $this->queueCollectionFactory->create();
                $queues->addFieldToFilter(
                    'status',
                    ['in' => [
                        Queue::STATUS_NEW,
                        Queue::STATUS_INPROGRESS,
                    ]]
                );
            }
            $queuesTotal = count($queues);
            if (!$queuesTotal) {
                $logData = ['message' => "There were no any unsent items found. Finish."];
                if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
            }

            $processedQueues = [];
            foreach ($queues as $queue) {
                if (!($this->qItemsToPass = $this->countItemsToBePassed($queue))) {
                    $queue->setStatus(Queue::STATUS_SENT);
                    $queue->save();
                    continue;
                }
                if($this->mode == 'cli' && isset($this->ddOverride) && $this->ddOverride != null && $this->ddOverride > 0){
                    $dueDate = $this->dateTime->date('Y-m-d H:i:s', $queue->getData('due_date'));
                    $now = $this->dateTime->date('Y-m-d H:i:s');
                    if($now > $dueDate){
                        $newDate = $this->dateTime->date('Y-m-d H:i:s', strtotime($now." +".$this->ddOverride." days"));
                        $queue->setData('due_date', $newDate);
                        $queue->save();
                        $logData = [
                            'message' => 'Due-date of submission with ID ' . $queue->getId().' found to be in the past. Adjusting due-date by '.$this->ddOverride.' day(s)',
                        ];
                        if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                        }
                    }
                }
                while ($this->qItemsToPass > 0) {
                    $this->proceedQueue($queue);
                }
                $processedQueues[] = $queue;
            }

            $this->eventManager->dispatch('transperfect_globallink_submit_queue_after', ['queues' => $processedQueues]);
        } finally {
            $this->unlockJob();
        }
    }

    /**
     * count queue's items which should be treated
     *
     * @param Queue $queue
     */
    protected function countItemsToBePassed($queue)
    {
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'id',
            ['in' => $queue->getItems()]
        );
        $items->addFieldToFilter(
            'status_id',
            ['in' => [
                Item::STATUS_NEW,
                Item::STATUS_ERROR_UPLOAD,
            ]]
        );
        return count($items);
    }

    /**
     * Do required manipulations on queue
     *
     * @param Queue $queue
     */
    protected function proceedQueue($queue)
    {
        $queue->setQueueErrors([]);
        $xmlFolder = $this->translationService->getSendFolder();
        $this->clearDir($xmlFolder);

        //$this->bgLogger->info($this->bgLogger->bgLogMessage(['message' => 'Memory: '.number_format(memory_get_usage()).' : Start Queue']));

        $originStoreId = $queue->getOriginStoreId();

        // get items of current queue which haven't been sent yet
        $itemIds = $queue->getItems();
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'id',
            ['in' => $queue->getItems()]
        );
        $items->addFieldToFilter(
            'status_id',
            ['in' => [
                Item::STATUS_NEW,
                Item::STATUS_ERROR_UPLOAD,
            ]]
        );
        $items->setOrder('entity_type_id');
        $items->setOrder('entity_id', 'ASC');

        //$this->bgLogger->info($this->bgLogger->bgLogMessage(['message' => 'Memory: '.number_format(memory_get_usage()).' : Items collection created']));

        /**
         * @var array $dataToSend
         *
         *  entity_type_id => Array
         *      entity_id => Array
         *          'store_id' => store_id
         *          'entity_name' => entity_name
         *          'item_ids' => Array
         *              item_id,
         *              item_id
         *          'target_locales' => Array
         *              item_id => pd_locale_iso_code,
         *              item_id => pd_locale_iso_code
         *          'document_ticket' => document_ticket // will be updated while submission process
         *          'upload_failed' => bool // will be updated while submission process
         */
        $dataToSend = [];

        // set status optimistically
        $queue->setStatus(Queue::STATUS_SENT);
        $this->includeOptions = $queue->getData('include_options');
        $limitUploads = $this->limitUploads;
        foreach ($items as $item) {
            if (!$this->allowDuplicateSubmissions) {
                $this->cancelExistingDuplicates($item);
            }
            $itemEntityTypeId = $item->getEntityTypeId();
            $itemEntityId = $item->getEntityId();

            $this->itemName =  preg_replace('/[^A-Za-z0-9\-]/', '', $item->getEntityName());
            $filePath = $xmlFolder . '/' . $this->getXmlFileName($originStoreId, $itemEntityTypeId, $itemEntityId, $this->itemName);

            if (!$this->file->fileExists($filePath, true)) {
                if ($limitUploads < 1) {
                    $queue->setStatus(Queue::STATUS_INPROGRESS);
                    break;
                }
                // current item passed only if we were NOT interrupted by file limit or if file already exists (see second decrement in 'else')
                $this->qItemsToPass--;
                try {
                    $this->createSource($filePath, $itemEntityTypeId, $itemEntityId, $originStoreId);
                    $limitUploads--;
                } catch (\Exception $e) {
                    $queue->setStatus(Queue::STATUS_INPROGRESS);
                    $errorMessage = 'Exception in creating XML.'
                        . ' ' . $e->getMessage()
                        . " (site={$originStoreId}, "
                        . $this->helper->getEntityTypeOptionArray()[$itemEntityTypeId] . ' '
                        . "(id={$itemEntityId}))";
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
            } else {
                $this->qItemsToPass--;
            }
            $dataToSend[$itemEntityTypeId][$itemEntityId]['store_id'] = $originStoreId;
            $dataToSend[$itemEntityTypeId][$itemEntityId]['entity_name'] = $item->getEntityName();
            $dataToSend[$itemEntityTypeId][$itemEntityId]['item_ids'][] = $item->getId();
            $dataToSend[$itemEntityTypeId][$itemEntityId]['target_locales'][$item->getId()] = $item->getPdLocaleIsoCode();
            $dataToSend[$itemEntityTypeId][$itemEntityId]['document_ticket'] = '';
            $dataToSend[$itemEntityTypeId][$itemEntityId]['upload_failed'] = 0;
        }

        //$this->bgLogger->info($this->bgLogger->bgLogMessage(['message' => 'Memory: '.number_format(memory_get_usage()).' : Entities id array created']));

        $items = null;

        $submissionTicket = '';
        try {
            $submissionTicket = $this->submitEntities($dataToSend, $queue);
        } catch (\Exception $e) {
            $queue->setStatus(Queue::STATUS_INPROGRESS);
            $errorMessage = 'Exception while submission.'
                . ' ' . $e->getMessage()
                . " (site={$originStoreId}, queue={$queue->getId()})";
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
        }

        if ($submissionTicket) {
            $this->updateTicketsAndStatuses($dataToSend, $submissionTicket);
        }

        // update queue status
        $queue->save();

        //$this->bgLogger->info($this->bgLogger->bgLogMessage(['message' => 'Memory: '.number_format(memory_get_usage()).' : Finish queue']));
    }

    protected function cancelExistingDuplicates($item)
    {
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'status_id',
            ['in' => [
                Item::STATUS_INPROGRESS,
                Item::STATUS_FINISHED,
                Item::STATUS_NEW,
            ]]
        );
        $items->addFieldToFilter('entity_id', ['eq' => $item->getEntityId()]);
        $items->addFieldToFilter('entity_type_id', ['eq' => $item->getEntityTypeId()]);
        $items->addFieldToFilter('pd_locale_iso_code', ['eq' => $item->getPdLocaleIsoCode()]);
        foreach ($items as $duplicateItem) {
            if ($duplicateItem->getId() != $item->getId()) {
                $duplicateItem->cancelItem();
                if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                    $logData = [
                        'message' => "Cancelling duplicate submission for " . $item->getEntityName() . " for locale " . $item->getPdLocaleIsoCode() . ".",
                    ];
                    $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
                }
            }
        }
    }

    /**
     * send all prepared data to service
     *
     * @param array &$dataToSend
     *
     * @return string Submission ticket or empty string
     */
    protected function submitEntities(array &$dataToSend, $queue)
    {
        $submissionTicket = '';

        $data = [];
        $data['projectShortCode'] = $queue->getProjectShortcode();
        $data['submissionName'] = $queue->getName();
        $data['submissionNotes'] = $queue->getSubmissionInstructions();
        $data['submissionDueDate'] = $queue->getDueDate();
        $data['submissionPriority'] = $queue->getPriority();
        $data['attribute_text'] = $queue->getAttributeText();
        $data['attribute_combo'] = $queue->getAttributeCombo();
        $this->translationService->initSubmission($data);

        $haveUploadedDocuments = false;

        // we know all items in $dataToSend array have an appropriate xml files
        foreach ($dataToSend as $entityTypeId => $entities) {
            foreach ($entities as $entityId => $entityData) {
                //$this->bgLogger->info($this->bgLogger->bgLogMessage(['message' => 'Memory: '.number_format(memory_get_usage()).' : Start entity '.$entityId]));

                try {
                    $documentTicket = $this->sendDocument($queue->getOriginStoreId(), $entityTypeId, $entityId, $entityData, $queue);
                } catch (\Exception $e) {
                    $queue->setStatus(Queue::STATUS_INPROGRESS);
                    $errorMessage = 'Exception while sending a document.'
                        . ' ' . $e->getMessage()
                        . " (site={$queue->getOriginStoreId()}, "
                        . $this->helper->getEntityTypeOptionArray()[$entityTypeId] . ' '
                        . "(id={$entityId}), "
                        . "queue={$queue->getId()}, items=" . implode(',', $entityData['item_ids']) . ')';
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
                    $dataToSend[$entityTypeId][$entityId]['upload_failed'] = 1;
                    continue;
                }

                if (empty($documentTicket)) {
                    $queue->setStatus(Queue::STATUS_INPROGRESS);
                    $errorMessage = 'Document ticket recieved from GLPD is empty'
                        . " (site={$queue->getOriginStoreId()}, "
                        . $this->helper->getEntityTypeOptionArray()[$entityTypeId] . ' '
                        . "(id={$entityId}), "
                        . "queue={$queue->getId()}, items=" . implode(',', $entityData['item_ids']) . ')';
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
                    $dataToSend[$entityTypeId][$entityId]['upload_failed'] = 1;
                } else {
                    $dataToSend[$entityTypeId][$entityId]['document_ticket'] = $documentTicket;
                    $this->cliMessage('Document uploaded (document ticket ' . $documentTicket . ')');
                    $haveUploadedDocuments = true;
                }
                //$this->bgLogger->info($this->bgLogger->bgLogMessage(['message' => 'Memory: '.number_format(memory_get_usage()).' : Finish entity '.$entityId]));
            }
        }

        if ($haveUploadedDocuments) {
            $submissionTicket = $this->translationService->startSubmission();
            $this->cliMessage('Submission created (submission ticket ' . $submissionTicket . ')');
        }

        return $submissionTicket;
    }

    /**
     * send document for translation
     *
     * @param int   $originStoreId
     * @param int   $entityTypeId
     * @param int   $entityId
     * @param array $entityData
     * @param Queue $queue
     *
     * @return string Document ticket
     */
    protected function sendDocument($originStoreId, $entityTypeId, $entityId, $entityData, $queue)
    {
        $fileName = $this->getXmlFileName($originStoreId, $entityTypeId, $entityId, $entityData['entity_name']);
        $filePath = $this->translationService->getSendFolder() . '/' . $fileName;
        $sourceStore = $this->storeManager->getStore($originStoreId);
        switch ($entityTypeId) {
            case HelperData::CATALOG_PRODUCT_TYPE_ID:
                $fileFormatType = $this->scopeConfig->getValue(
                    'globallink_classifiers/classifiers/catalogproductclassifier',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $sourceStore->getId()
                );
                break;
            case HelperData::CATALOG_CATEGORY_TYPE_ID:
                $fileFormatType = $this->scopeConfig->getValue(
                    'globallink_classifiers/classifiers/catalogcategoryclassifier',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $sourceStore->getId()
                );
                break;
            case HelperData::CMS_BLOCK_TYPE_ID:
                $fileFormatType = $this->scopeConfig->getValue(
                    'globallink_classifiers/classifiers/cmsblockclassifier',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $sourceStore->getId()
                );
                break;
            case HelperData::CMS_PAGE_TYPE_ID:
                $fileFormatType = $this->scopeConfig->getValue(
                    'globallink_classifiers/classifiers/cmspageclassifier',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $sourceStore->getId()
                );
                break;
            case HelperData::CUSTOMER_ATTRIBUTE_TYPE_ID:
                $fileFormatType = $this->scopeConfig->getValue(
                    'globallink_classifiers/classifiers/customerattributeclassifier',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $sourceStore->getId()
                );
                break;
            case HelperData::PRODUCT_REVIEW_ID:
                $fileFormatType = $this->scopeConfig->getValue(
                    'globallink_classifiers/classifiers/reviewclassifier',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $sourceStore->getId()
                );
                break;
            case HelperData::PRODUCT_ATTRIBUTE_TYPE_ID:
                $fileFormatType = $this->scopeConfig->getValue(
                    'globallink_classifiers/classifiers/productattributeclassifier',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $sourceStore->getId()
                );
                break;
            case HelperData::BANNER_ID:
                $fileFormatType = $this->scopeConfig->getValue(
                    'globallink_classifiers/classifiers/bannerclassifier',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $sourceStore->getId()
                );
                break;
        }

        if (!empty($sourceStore->getLocale())) {
            $sourceLanguage = $sourceStore->getLocale();
        } else {
            $sourceLanguage = str_replace(
                '_',
                '-',
                $this->scopeConfig->getValue(
                    'general/locale/code',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $sourceStore->getId()
                )
            );
        }

        $data = [];
        $data['fileformat'] = $fileFormatType;
        $data['name'] = $fileName;
        $data['sourceLanguage'] = $sourceLanguage;
        $data['targetLanguages'] = $entityData['target_locales'];
        $data['data'] = $this->file->read($filePath);

        $data['logInfo'] = "(site={$originStoreId}, "
            . $this->helper->getEntityTypeOptionArray()[$entityTypeId] . ' '
            . "(id={$entityId}), "
            . "queue={$queue->getId()}, items=" . implode(',', $entityData['item_ids']) . ")";

        return $this->translationService->sendDocumentForTranslate($data);
    }

    /**
     * Walk through $dataToSend array and update all items which have been successfully sent
     *
     * @param array  &$dataToSend
     * @param string $submissionTicket
     */
    protected function updateTicketsAndStatuses(array &$dataToSend, $submissionTicket)
    {
        $allItems = [];
        foreach ($dataToSend as $entityTypeId => $entities) {
            foreach ($entities as $entityId => $entityData) {
                foreach ($entityData['item_ids'] as $itemId) {
                    $allItems[$itemId] = [
                        'document_ticket' => $entityData['document_ticket'],
                        'upload_failed' => $entityData['upload_failed'],
                    ];
                }
            }
        }
        $this->doUpdateTicketsAndStatuses($allItems, $submissionTicket);
    }

    /**
     * Update Items
     *
     * @param array  $allItems
     * @param string $submissionTicket
     */
    protected function doUpdateTicketsAndStatuses(array $allItems, $submissionTicket)
    {
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'id',
            ['in' => array_keys($allItems)]
        );
        foreach ($items as $item) {
            $itemId = $item->getId();
            if (!empty($allItems[$itemId]['document_ticket'])) {
                $item->setStatusId(Item::STATUS_INPROGRESS);
                $item->setDocumentTicket($allItems[$itemId]['document_ticket']);
                $item->setSubmissionTicket($submissionTicket);
            } elseif (!empty($allItems[$itemId]['upload_failed'])) {
                $item->setStatusId(Item::STATUS_ERROR_UPLOAD);
            }
        }
        $items->save();
    }

    /**
     * Returns file name with entity's xml data
     *
     * @param int    $originStoreId
     * @param int    $itemEntityTypeId
     * @param int    $itemEntityId
     * @param string $itemEntityName
     *
     * @return string
     */
    protected function getXmlFileName($originStoreId, $itemEntityTypeId, $itemEntityId, $itemEntityName)
    {
        $name = $this->itemName =  preg_replace('/[^A-Za-z0-9\-]/', '', $itemEntityName);

        if (empty($name)) {
            $name = 'store_' . $originStoreId . '-'
                . 'type_' . $itemEntityTypeId . '-'
                . 'id_' . $itemEntityId;
        }

        $name .= '.xml';

        return $name;
    }

    /**
     * get sql length of 'static' attribute field
     */
    protected function getStaticLength($attributeCode, $entityTypeId)
    {
        $itemResource = $this->itemResourceFactory->create();
        $length = $itemResource->getFieldLength($attributeCode, $entityTypeId);
        if (empty($length)) {
            $length = 'none';
        }
        return $length;
    }

    /**
     * Get fields to translate
     *
     * @param int $entityTypeId
     * @param int $entityId
     *
     * @return array [
     *      [
     *          'name' => field_name,
     *          'max_length' => int | 'none',
     *      ],
     * ]
     */
    protected function getFieldsToTranslate($entityTypeId, $entityId = null)
    {
        $fieldNames = [];
        if (in_array(
            $entityTypeId,
            [HelperData::CMS_BLOCK_TYPE_ID, HelperData::CMS_PAGE_TYPE_ID, HelperData::PRODUCT_REVIEW_ID]
        )) {
            $fields = $this->fieldCollectionFactory->create();
            $fields->addFieldToFilter('object_type', $entityTypeId);
            $fields->addFieldToFilter('include_in_translation', 1);
            foreach ($fields as $field) {
                switch ($field->getFieldName()) {
                    case 'title':
                        $maxLength = 255;
                        break;
                    case 'detail':
                        $maxLength = 65535;
                        break;
                    case 'content_heading':
                    case 'meta_title':
                        $maxLength = 255;
                        break;
                    case 'meta_keywords':
                    case 'meta_description':
                        $maxLength = 65535;
                        break;
                    default:
                        $maxLength = 'none';
                }
                $fieldNames[] = [
                    'name' => $field->getFieldName(),
                    'max_length' => $maxLength,
                ];
            }
        } elseif (in_array(
            $entityTypeId,
            [HelperData::PRODUCT_ATTRIBUTE_TYPE_ID, HelperData::CUSTOMER_ATTRIBUTE_TYPE_ID]
        )) {
            $fieldNames[] = [
                'name' => 'frontend_label',
                'max_length' => 255,
            ];
        } elseif (in_array(
            $entityTypeId,
            [HelperData::CATALOG_PRODUCT_TYPE_ID]
        )) {
            $product = $this->productRepository->getById($entityId);
            $attributeSetId = $product->getAttributeSetId();

            $attributes = $this->productAttributeCollectionFactory->create();
            $attributes->appendData();
            //4-16-18 Justin: These were commented out as we pave the way for the removal of any core table modifications.
            //This will now be joined to globallink_field_product_category
            $attributes->addFieldToFilter('eav_entity_attribute.attribute_set_id', $attributeSetId);
            $attributes->addFieldToFilter('eav_entity_attribute.include_in_translation', 1);
            //$attributes->addFieldToFilter('globallink_field_product_category.attribute_set_id', $attributeSetId);
            //$attributes->addFieldToFilter('globallink_field_product_category.include_in_translation', 1);
            foreach ($attributes as $attribute) {
                if($attribute->getAttributeCode() == 'description'){
                    $maxLength = 16777215;
                } else {
                    switch ($attribute->getBackendType()) {
                        case 'varchar':
                            $maxLength = 255;
                            break;
                        case 'text':
                            $maxLength = 65535;
                            break;
                        case 'static':
                            $maxLength = $this->getStaticLength($attribute->getAttributeCode(), $attribute->getEntityTypeId());
                            break;
                        default:
                            $maxLength = 'none';
                    }
                }
                $fieldNames[] = [
                    'name' => $attribute->getAttributeCode(),
                    'max_length' => $maxLength,
                ];
            }
        } elseif (in_array(
            $entityTypeId,
            [HelperData::CATALOG_CATEGORY_TYPE_ID]
        )) {
            $attributes = $this->categoryAttributeCollectionFactory->create();
            $attributes->appendData();
            $categoryEntityTypeId = 3;
            $attributes->addFieldToFilter('eav_entity_attribute.entity_type_id', $categoryEntityTypeId);
            $attributes->addFieldToFilter('eav_entity_attribute.include_in_translation', 1);
            //$attributes->addFieldToFilter('globallink_field_product_category.entity_type_id', $categoryEntityTypeId);
            //$attributes->addFieldToFilter('globallink_field_product_category.include_in_translation', 1);

            foreach ($attributes as $attribute) {
                switch ($attribute->getBackendType()) {
                    case 'varchar':
                        $maxLength = 255;
                        break;
                    case 'text':
                        $maxLength = 65535;
                        break;
                    case 'static':
                        $maxLength = $this->getStaticLength($attribute->getAttributeCode(), $attribute->getEntityTypeId());
                        break;
                    default:
                        $maxLength = 'none';
                }
                $fieldNames[] = [
                    'name' => $attribute->getAttributeCode(),
                    'max_length' => $maxLength,
                ];
            }
        }

        $fieldNames = array_intersect_key($fieldNames, array_unique(array_map('serialize', $fieldNames)));  // multidim. array unique
        if (empty($fieldNames)) {
            $logData = ['message' => "No field configuration was set up for one of the entities submitted for translation. Check your field configuration pages."];
            if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }
        }
        return $fieldNames;
    }

    /**
     * Get data of cms block
     *
     * @param int $entityId
     * @param int $storeId
     *
     * @return array
     */
    protected function getCmsBlockData($entityId, $storeId)
    {
        $data = [];
        $fieldNames = $this->getFieldsToTranslate(HelperData::CMS_BLOCK_TYPE_ID);
        $block = null;
        $blockCollection = $this->blockCollectionFactory->create();
        $blockCollection->addStoreFilter($storeId);
        $blockCollection->addFieldToFilter('block_id', $entityId);
        if($blockCollection->count() == 1) {
            foreach ($blockCollection as $currentBlock) {
                $block = $currentBlock;
            }
        } else {
            $block = $this->blockFactory->create();
            $block->setStoreId($storeId)->load($entityId);
        }
        $attrArr = [];
        foreach ($fieldNames as $fieldNameData) {
            $fieldName = $fieldNameData['name'];
            $maxLength = $fieldNameData['max_length'];
            $fieldValue = $block->getData($fieldName);
            if (!empty($fieldValue) && strlen($fieldValue) > 0) {
                $attrArr[$fieldName] = $fieldValue;
                $lengthArr[$fieldName] = $maxLength;
            }
        }

        if (empty($attrArr)) {
            return [];
        }

        $data['object_id'] = $entityId;
        $data['object_type_id'] = HelperData::CMS_BLOCK_TYPE_ID;

        $data['attributes'] = $attrArr;
        $data['max_length'] = $lengthArr;

        return $data;
    }

    /**
     * Get data of cms page
     *
     * @param int $entityId
     * @param int $storeId
     *
     * @return array
     */
    protected function getCmsPageData($entityId, $storeId)
    {
        $fieldNames = $this->getFieldsToTranslate(HelperData::CMS_PAGE_TYPE_ID);
        $data = [];
        $page = null;
        $pageCollection = $this->pageCollectionFactory->create();
        $pageCollection->addStoreFilter($storeId);
        $pageCollection->addFieldToFilter('page_id', $entityId);
        if($pageCollection->count() == 1) {
            foreach ($pageCollection as $currentPage) {
                $page = $currentPage;
            }
        } else{
            $page = $this->pageFactory->create();
            $page->setStoreId($storeId)->load($entityId);
        }
        $attrArr = [];
        foreach ($fieldNames as $fieldNameData) {
            $fieldName = $fieldNameData['name'];
            $maxLength = $fieldNameData['max_length'];
            $fieldValue = $page->getData($fieldName);
            if (!empty($fieldValue) && strlen($fieldValue) > 0) {
                $attrArr[$fieldName] = $fieldValue;
                $lengthArr[$fieldName] = $maxLength;
            }
        }

        if (empty($attrArr)) {
            return [];
        }

        $data['object_id'] = $entityId;
        $data['object_type_id'] = HelperData::CMS_PAGE_TYPE_ID;

        //get default store's base url and this cms page's url for the preview
        $data['preview_url'] = $this->storeManager->getStore()->getBaseUrl() . $page->getIdentifier();
        $data['attributes'] = $attrArr;
        $data['max_length'] = $lengthArr;

        return $data;
    }

    /**
     * Get data of customer attribute
     *
     * @param int $entityId
     * @param int $storeId
     *
     * @return array
     */
    protected function getCustomerAttributeData($entityId, $storeId)
    {
        $data = [];

        $fieldNames = $this->getFieldsToTranslate(HelperData::CUSTOMER_ATTRIBUTE_TYPE_ID);

        $attribute = $this->attributeRepository->get(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            $entityId
        );
        $attribute->setStoreId($storeId);

        $attrArr = [];
        $optArr = [];
        foreach ($fieldNames as $fieldNameData) {
            $fieldName = $fieldNameData['name'];
            $maxLength = $fieldNameData['max_length'];
            $fieldValue = $attribute->getData($fieldName);
            if (!empty($fieldValue)) {
                $attrArr[$fieldName] = $fieldValue;
                $lengthArr[$fieldName] = $maxLength;
            }
        }

        if (empty($attrArr)) {
            return [];
        }

        $options = $attribute->getOptions();

        foreach ($options as $option) {
            $value = $option->getValue();
            $label = $option->getLabel();
            if (!empty($value) && !empty($label) && !is_numeric($label)) {
                $optArr['entity_' . $entityId][$value] = $label;
            }
        }

        $data['object_id'] = $entityId;
        $data['object_type_id'] = HelperData::CUSTOMER_ATTRIBUTE_TYPE_ID;

        $data['attributes'] = $attrArr;
        $data['options'] = $optArr;
        $data['max_length'] = $lengthArr;

        return $data;
    }

    /**
     * Get data of product attribute
     *
     * @param int $entityId
     * @param int $storeId
     *
     * @return array
     */
    protected function getProductAttributeData($entityId, $storeId)
    {
        $data = [];

        $fieldNames = $this->getFieldsToTranslate(HelperData::PRODUCT_ATTRIBUTE_TYPE_ID);

        $attribute = $this->attributeRepository->get(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            $entityId
        );
        $attribute->setStoreId($storeId);

        $attrArr = [];
        $optArr = [];
        foreach ($fieldNames as $fieldNameData) {
            $fieldName = $fieldNameData['name'];
            $maxLength = $fieldNameData['max_length'];
            $fieldValue = $attribute->getData($fieldName);
            if (!empty($fieldValue)) {
                $attrArr[$fieldName] = $fieldValue;
                $lengthArr[$fieldName] = $maxLength;
            }
        }

        if (empty($attrArr)) {
            return [];
        }
        if($this->includeOptions == 1) {
            $options = $attribute->getOptions();

            foreach ($options as $option) {
                $value = $option->getValue();
                $label = $option->getLabel();
                if (!empty($value) && !empty($label) && !is_numeric($label)) {
                    $optArr['entity_' . $entityId][$value] = $label;
                }
            }
            $data['options'] = $optArr;
        }
        $data['object_id'] = $entityId;
        $data['object_type_id'] = HelperData::PRODUCT_ATTRIBUTE_TYPE_ID;

        $data['attributes'] = $attrArr;
        $data['max_length'] = $lengthArr;

        return $data;
    }

    /**
     * Get data of product
     *
     * @param int $entityId
     * @param int $storeId
     *
     * @return array
     */
    protected function getProductData($entityId, $storeId)
    {
        $data = [];

        $fieldNames = $this->getFieldsToTranslate(HelperData::CATALOG_PRODUCT_TYPE_ID, $entityId);
        $logData = ['message' => "Product fields selected for translation: ".json_encode($fieldNames)];
        if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        $product = $this->productRepository->getById($entityId, false, $storeId);

        $attrArr = [];
        $optArr = [];
        foreach ($fieldNames as $fieldNameData) {
            $fieldName = $fieldNameData['name'];
            $maxLength = $fieldNameData['max_length'];
            $fieldValue = $product->getData($fieldName);
            if (!empty($fieldValue) && !is_array($fieldValue)) {
                $attrArr[$fieldName] = $fieldValue;
                $lengthArr[$fieldName] = $maxLength;
            }
            $logData = ['message' => "Field name: {$fieldName}, max length: {$maxLength}, field value: {$fieldValue}" ];
            if (in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
                $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
            }
        }
        foreach($product->getMediaGalleryEntries() as $image){
            if(!empty($image->getLabel()) && $this->includeOptions == '1') {
                $attrArr["image_".$image->getId()] = $image->getData('label');
            }
        }

        if (empty($attrArr)) {
            return [];
        }

        // custom options
        //M2 currently doesn't allow to save custom option titles for storeviews
        $customOptions = $this->productOption->getProductOptionCollection($product);
        $optionsTotal = count($customOptions);
        if (!empty($customOptions) && $optionsTotal && $this->includeOptions == '1') {
            $optArr = $this->getProductDataCustomOptions($customOptions, $entityId);
        }

        // bundle product options
        if ($product->getTypeId() == BundleType::TYPE_CODE) {
            $options = $this->bundleOption
                    ->getResourceCollection()
                    ->setProductIdFilter($entityId)
                    ->setPositionOrder();
            $options->joinValues($storeId);
            foreach ($options as $option) {
                $title = $option->getTitle();
                $optionId = $option->getOptionId();
                if (!empty($optionId) && !empty($title) && !is_numeric($title)) {
                    $optArr['entity_' . $entityId][$optionId] = $title;
                }
            }
        }

        $data['object_id'] = $entityId;
        $data['object_type_id'] = HelperData::CATALOG_PRODUCT_TYPE_ID;

        $data['preview_url'] = $product->getProductUrl();

        $data['attributes'] = $attrArr;
        $data['options'] = $optArr;
        $data['max_length'] = $lengthArr;
        return $data;
    }

    /**
     * Prepare custom options array
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\Collection $customOptions
     * @param int                                                            $entityId
     *
     * @return array
     */
    protected function getProductDataCustomOptions(
        \Magento\Catalog\Model\ResourceModel\Product\Option\Collection $customOptions,
        $entityId
    ) {
        $optArr = [];
        foreach ($customOptions as $option) {
            $title = $option->getTitle();
            $optionId = $option->getOptionId();
            if (!empty($optionId) && !empty($title)) {
                $optArr['entity_' . $entityId][$optionId] = $title;
                if ($option->getValues() != null) {
                    foreach ($option->getValues() as $value) {
                        $valueTitle = $value->getTitle();
                        $valueId = $value->getOptionTypeId();
                        if (!empty($valueId) && !empty($valueTitle)) {
                            $optArr['option_' . $optionId][$valueId] = $valueTitle;
                        }
                    }
                }
            }
        }
        return $optArr;
    }

    /**
     * Get data of category
     *
     * @param int $entityId
     * @param int $storeId
     *
     * @return array
     */
    protected function getCategoryData($entityId, $storeId)
    {
        $data = [];

        $fieldNames = $this->getFieldsToTranslate(HelperData::CATALOG_CATEGORY_TYPE_ID);

        $category = $this->categoryRepository->get($entityId, $storeId);

        $attrArr = [];
        $optArr = [];
        foreach ($fieldNames as $fieldNameData) {
            $fieldName = $fieldNameData['name'];
            $maxLength = $fieldNameData['max_length'];
            $fieldValue = $category->getData($fieldName);
            if (!empty($fieldValue) && !is_array($fieldValue)) {
                $attrArr[$fieldName] = $fieldValue;
                $lengthArr[$fieldName] = $maxLength;
            }
        }

        if (empty($attrArr)) {
            return [];
        }

        $data['object_id'] = $entityId;
        $data['object_type_id'] = HelperData::CATALOG_CATEGORY_TYPE_ID;

        $data['attributes'] = $attrArr;
        $data['options'] = $optArr;
        $data['max_length'] = $lengthArr;

        return $data;
    }
    /**
     * Get data of product review
     *
     * @param int $entityId
     * @param int $storeId
     *
     * @return array
     */
    protected function getReviewData($entityId, $storeId)
    {
        $data = [];

        $fieldNames = $this->getFieldsToTranslate(HelperData::PRODUCT_REVIEW_ID);

        $review = $this->reviewCollectionFactory->create()->getItemById($entityId);

        $attrArr = [];
        $optArr = [];
        foreach ($fieldNames as $fieldNameData) {
            $fieldName = $fieldNameData['name'];
            $maxLength = $fieldNameData['max_length'];
            $fieldValue = $review->getData($fieldName);
            if (!empty($fieldValue) && !is_array($fieldValue)) {
                $attrArr[$fieldName] = $fieldValue;
                $lengthArr[$fieldName] = $maxLength;
            }
        }

        if (empty($attrArr)) {
            return [];
        }

        $data['object_id'] = $entityId;
        $data['object_type_id'] = HelperData::PRODUCT_REVIEW_ID;

        $data['attributes'] = $attrArr;
        $data['options'] = $optArr;
        $data['max_length'] = $lengthArr;

        return $data;
    }
    /**
     * Get data of dynamic block
     *
     * @param int $entityId
     * @param int $storeId
     *
     * @return array
     */
    protected function getBannerData($entityId, $storeId)
    {
        $data = [];

        $fieldNames[] = [
            'name' => 'banner_content',
            'max_length' => 16777215,
        ];
        $bannerData = $this->bannerContents->getStoreContent($entityId, $storeId);

        $attrArr = [];
        $optArr = [];
        foreach ($fieldNames as $fieldNameData) {
            $fieldName = $fieldNameData['name'];
            $maxLength = $fieldNameData['max_length'];
            $fieldValue = $bannerData;
            if (!empty($fieldValue) && !is_array($fieldValue)) {
                $attrArr[$fieldName] = $fieldValue;
                $lengthArr[$fieldName] = $maxLength;
            }
        }

        if (empty($attrArr)) {
            return [];
        }

        $data['object_id'] = $entityId;
        $data['object_type_id'] = HelperData::BANNER_ID;

        $data['attributes'] = $attrArr;
        $data['options'] = $optArr;
        $data['max_length'] = $lengthArr;

        return $data;
    }
    /**
     * Create xml file with data for translation
     *
     * @param string $filePath
     * @param int $itemEntityTypeId
     * @param int $itemEntityId
     * @param int $originStoreId
     *
     * @throw Exception
     */
    protected function createSource($filePath, $itemEntityTypeId, $itemEntityId, $originStoreId)
    {
        if ($itemEntityTypeId == HelperData::CATALOG_CATEGORY_TYPE_ID) {
            $data = $this->getCategoryData($itemEntityId, $originStoreId);
        } elseif ($itemEntityTypeId == HelperData::CATALOG_PRODUCT_TYPE_ID) {
            $data = $this->getProductData($itemEntityId, $originStoreId);
        } elseif ($itemEntityTypeId == HelperData::PRODUCT_ATTRIBUTE_TYPE_ID) {
            $data = $this->getProductAttributeData($itemEntityId, $originStoreId);
        } elseif ($itemEntityTypeId == HelperData::CMS_PAGE_TYPE_ID) {
            $data = $this->getCmsPageData($itemEntityId, $originStoreId);
        } elseif ($itemEntityTypeId == HelperData::CMS_BLOCK_TYPE_ID) {
            $data = $this->getCmsBlockData($itemEntityId, $originStoreId);
        } elseif ($itemEntityTypeId == HelperData::CUSTOMER_ATTRIBUTE_TYPE_ID) {
            $data = $this->getCustomerAttributeData($itemEntityId, $originStoreId);
        } elseif ($itemEntityTypeId == HelperData::PRODUCT_REVIEW_ID) {
            $data = $this->getReviewData($itemEntityId, $originStoreId);
        } elseif ($itemEntityTypeId == HelperData::BANNER_ID) {
            $data = $this->getBannerData($itemEntityId, $originStoreId);
        }

        if (empty($data)) {
            $logData = ['message' => "Unable to submit document, there is no data to create xml."];
            if (in_array($this->helper::LOGGING_LEVEL_ERROR, $this->helper->loggingLevels)) {
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
            throw new \Exception("There is no data to create xml.");
        }

        $this->createXmlFile($filePath, $data);
    }

    /**
     * Create xml file from given data
     *
     * @param string $filePath
     * @param array $data
     *
     * @throw Exception
     */
    protected function createXmlFile($filePath, array $data)
    {
        $dom = $this->domDocumentFactory->create();
        $dom->formatOutput = true;

        $root = $dom->createElement("content");
        $dom->appendChild($root);

        $type = $dom->createAttribute('object_type_id');
        $type->value = $data['object_type_id'];
        $root->appendChild($type);

        $pid = $dom->createAttribute('object_id');
        $pid->value = $data['object_id'];
        $root->appendChild($pid);

        //preview url
        if (isset($data['preview_url'])) {
            $previewUrlAttribute = $dom->createAttribute('preview_url');
            $previewUrlAttribute->value = $data['preview_url'];
            $root->appendChild($previewUrlAttribute);
        }

        if (isset($data['name'])) {
            $this->insertChildDomElement($dom, $root, "name", $data['name']);
        }

        if (!empty($data['attributes']) && is_array($data['attributes'])) {
            $this->addAttributesIntoDom($dom, $root, $data['attributes'], $data);
        }

        if (!empty($data['options']) && is_array($data['options'])) {
            $this->addOptionsIntoDom($dom, $root, $data['options'], $data);
        }

        if (!$xmlString = $dom->saveXML()) {
            throw new \Exception("Can't create xml from DOMDocument");
        }

        if (!$this->file->write($filePath, $xmlString)) {
            throw new \Exception("Can't write xml data to file " . $filePath);
        }
    }

    /**
     * Add attributes into Dom
     *
     * @param DROMDocument $dom
     * @param DOMElement   $root
     * @param array        $attrArr
     * @param array       &$data
     */
    protected function addAttributesIntoDom($dom, $root, $attrArr, &$data)
    {
        foreach ($attrArr as $attrCode => $attrValue) {
            if (!empty($attrValue)) {
                $maxLength = "";
                if (isset($data['max_length'][$attrCode])) {
                    $maxLength = $data['max_length'][$attrCode];
                }
                $this->insertChildDomElement(
                    $dom,
                    $root,
                    'attribute',
                    $attrValue,
                    [
                        'attribute_code' => $attrCode,
                        'max_length' => $maxLength
                    ]
                );
            }
        }
    }

    /**
     * Add options into Dom
     *
     * @param DROMDocument $dom
     * @param DOMElement   $root
     * @param array        $optionArr
     * @param array       &$data
     */
    protected function addOptionsIntoDom($dom, $root, $optionArr, &$data)
    {
        foreach ($optionArr as $optionParent => $optionValue) {
            if (!empty($optionValue)) {
                foreach ($optionValue as $optionId => $title) {
                    $maxLength = "";
                    if (isset($data['max_length'][$optionParent])) {
                        $maxLength = $data['max_length'][$optionParent];
                    }
                    $this->insertChildDomElement(
                        $dom,
                        $root,
                        'option',
                        $title,
                        [
                            'parent' => $optionParent,
                            'option_id' => $optionId,
                            'max_length' => $maxLength
                        ]
                    );
                }
            }
        }
    }

    /**
     * Insert child element into dom
     *
     * @param DROMDocument $dom
     * @param DOMElement   $root
     * @param string       $elementName
     * @param string       $elementValue
     * @param array        $attributes
     */
    protected function insertChildDomElement($dom, $root, $elementName, $elementValue, $attributes = null)
    {
        if ($elementName) {
            $item = $dom->createElement($elementName);
            if (isset($attributes) && is_array($attributes)) {
                foreach ($attributes as $key => $value) {
                    $item->setAttribute($key, $value);
                }
            }
            $text = $dom->createCDATASection($elementValue);
            $item->appendChild($text);
            $root->appendChild($item);
        }
    }

    public function setOverride($ddOverride){
        $this->ddOverride = $ddOverride;
    }
}
