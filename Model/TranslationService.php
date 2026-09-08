<?php

namespace TransPerfect\GlobalLink\Model;

use Exception;
use GlobalLink\RestClient\Exception\GlobalLinkException;
use GlobalLink\RestClient\Request\CancelSubmissionRequest;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Store\Model\ScopeInterface;
use TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory as ItemCollectionFactory;
use TransPerfect\GlobalLink\Model\SoapClient\GLExchangeClient;

class TranslationService
{
    /**
     * folder (inside magento's var) for store xml files to be sent
     */
    const SEND_FOLDER = 'transperfect/globallink/send';

    /**
     * folder (inside magento's var) for store received translated files
     */
    const RECEIVE_FOLDER = 'transperfect/globallink/receive';

    /**
     * folder (inside magento's var) for store lock files
     */
    const LOCK_FOLDER = 'transperfect/globallink/lock';

    /**
     * @var GLExchangeClient
     */
    private $glExchangeClient;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $password;
    private $oauthclient;
    private $oauthsecret;
    /**
     * @var array
     */
    private $projectShortCodes;
    /**
     * @var string|null
     */
    private $sessionId;
    /**
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory
     */
    protected $itemCollectionFactory;
    /**
     * @var \TransPerfect\GlobalLink\Logger\BgTask\Logger
     */
    protected $bgLogger;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    protected $enabledLevels;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    const LOGGING_LEVEL_DEBUG = 0;
    const LOGGING_LEVEL_INFO = 1;
    const LOGGING_LEVEL_ERROR = 2;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \TransPerfect\GlobalLink\Model\SoapClient\GLExchangeClient $glExchangeClient,
        ItemCollectionFactory $itemCollectionFactory,
        \TransPerfect\GlobalLink\Logger\BgTask\Logger $bgLogger,
        Filesystem $filesystem,
        File $file
    ) {
        $this->glExchangeClient = $glExchangeClient;
        $this->username = $scopeConfig->getValue('globallink/connection/username', ScopeInterface::SCOPE_STORE);
        $this->password = $scopeConfig->getValue('globallink/connection/password', ScopeInterface::SCOPE_STORE);
        $this->oauthclient = (string)$scopeConfig->getValue('globallink/connection/oauthclient', ScopeInterface::SCOPE_STORE);
        $this->oauthsecret = (string)$scopeConfig->getValue('globallink/connection/oauthsecret', ScopeInterface::SCOPE_STORE);
        $shortCodes = $scopeConfig->getValue('globallink/general/project_short_codes', ScopeInterface::SCOPE_STORE);
        $this->projectShortCodes = array_map('trim', $shortCodes == null ? [''] : explode(',', $shortCodes));
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->bgLogger = $bgLogger;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->enabledLevels = $scopeConfig->getValue('globallink/general/logging_level') == null ? [''] : explode(',', $scopeConfig->getValue('globallink/general/logging_level', ScopeInterface::SCOPE_STORE));
    }

    /**
     * Request GlobalLinkClient library
     * @return \GlobalLink\RestClient\GlobalLinkClient
     */
    public function requestGLExchange()
    {
        return $this->glExchangeClient->getConnect();
    }
    /**
     * Get all project IDs configured
     * @return array
     */
    public function getProjectIds(){
        return $this->glExchangeClient->getProjectIds($this->projectShortCodes);
    }


    /**
     * Init submission task
     *
     * @param array $data
     */
    public function initSubmission(array $data)
    {
        return $this->glExchangeClient->initSubmission($data);
    }

    /**
     * Get custom attributes
     *
     * @param string $shortCode
     */
    public function getCustomAttributes($projectID)
    {
        return $this->requestGLExchange()->getProjectCustomAttributes($projectID);
    }

    /**
     * Submit translation
     *
     * @param array $data
     *
     * @return string Document ticket
     */
    public function sendDocumentForTranslate($data)
    {
        return $this->glExchangeClient->sendDocumentForTranslate($data);
    }

    /**
     * Start submission task
     *
     * @var int $submissionID
     */
    public function startSubmission($submissionID)
    {
        $this->glExchangeClient->startSubmission($submissionID);
    }

    /**
     * Receive translations
     *
     * @param array $tickets Submission tickets
     *
     * @return PDTarget[]
     * @throws Exception
     */
    public function receiveTranslationsByTickets(array $tickets, $queue)
    {
        $problemTickets = [];
        $targets = [];
        try {
            $targets = $this->glExchangeClient->receiveTranslationsByTickets($tickets);
        } catch (\Exception $e) {
            if ($e->getMessage() == 'looks like we got no XML document') {
                // at least one of tickets in array haven't been found while request
                // and we don't know which one. Have to send them by one now
                foreach ($tickets as $ticket) {
                    try {
                        $targets = array_merge(
                            $targets,
                            $this->glExchangeClient->receiveTranslationsByTickets($ticket)
                        );
                    } catch (\Exception $e) {
                        $problemTickets[] = $ticket;
                        $logData = [
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'message' => "Can't get translation by ticket {$ticket}. "
                                . $e->getMessage(),
                            ];
                        if (in_array($this::LOGGING_LEVEL_ERROR, $this->enabledLevels)) {
                            $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                        }
                        $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
                    }
                }
            } else {
                throw $e;
            }
        }
        $this->moveItemsInError($problemTickets);
        /*$ticketCount = count($targets);
        $logData = ['message' => "Tickets were found. Count of tickets: {$ticketCount}"];
        if (in_array($this::LOGGING_LEVEL_INFO, $this->enabledLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }*/
        return $targets;
    }
    /**
     * Receive translations
     * @return int[]
     * @throws GlobalLinkException
     */
    public function receiveTranslationsByProject()
    {
        $targets = [];
        $projectIds = $this->getProjectIds();
        try {
            $targets = array_merge($targets, $this->glExchangeClient->receiveTranslationsByProject($projectIds));
        } catch (GlobalLinkException $e) {
            if (in_array($this::LOGGING_LEVEL_ERROR, $this->enabledLevels)) {
                $logData = [
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'message' => $e->getMessage()
                ];
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
            throw $e;
        }

        return $targets;
    }
    /**
     * update items status for problem tickets
     *
     * @param array $problemTickets Submission tickets
     */
    protected function moveItemsInError(array $problemTickets)
    {
        if (!empty($problemTickets)) {
            $items = $this->itemCollectionFactory->create();
            $items->addFieldToFilter(
                'submission_ticket',
                ['in' => $problemTickets]
            );
            foreach ($items as $item) {
                $item->setStatusId(\TransPerfect\GlobalLink\Model\Queue\Item::STATUS_ERROR_DOWNLOAD);
            }
            $items->save();
        }
    }

    /**
     * Download translated text
     *
     * @param string $submissionID
     * @param string $targetID
     *
     * @return string xml
     */
    public function downloadTarget($submissionID, $targetID)
    {
        $translatedText = $this->requestGLExchange()->downloadTargetDeliverable($submissionID, $targetID);

        return $translatedText;
    }

    /**
     * Send download confirmation
     *
     * @param string $documentTicket
     *
     * @return string xml
     */
    public function sendDownloadConfirmation($documentTicket)
    {
        $response = $this->requestGLExchange(
            '/services/TargetService',
            'sendDownloadConfirmation',
            [
                'targetId' => $documentTicket,
            ]
        );

        return $response;
    }

    /**
     * Cancel target by document ticket and locale code
     *
     * @param string $documentID
     * @param string $submissionID
     *
     * @return bool
     */
    public function cancelTargetByDocumentId($documentID, $submissionID)
    {
        $result = $this->glExchangeClient->getConnect()->cancelSubmission($submissionID, new CancelSubmissionRequest(
            documentIds: [$documentID]
        ));

        return $result;
    }

    /**
     * Cancel Document for all languages
     *
     * @param string $documentTicket
     *
     * @return bool
     */
    public function cancelDocument($documentTicket)
    {
        $result = $this->requestGLExchange(
            '/services/DocumentService',
            'cancelDocument',
            [
                'documentTicket' => $documentTicket,
            ]
        );

        return $result;
    }

    /**
     * get document tickets for remotely cancelled submissions
     *
     * @param array $submissionTickets
     *
     * @return array [
     *      document_ticket => [
     *          target_locale,
     *          target_locale,
     *      ],
     *      document_ticket => [
     *          target_locale,
     *      ],
     *  ]
     */
    public function getCancelledTargetsBySubmissions(array $submissionIds)
    {
        return $this->glExchangeClient->getCancelledTargetsBySubmissions($submissionIds);
    }

    /**
     * Returns path to receive dir
     *
     * @return string
     */
    public function getReceiveFolder()
    {
        return $this->getFolder(self::RECEIVE_FOLDER);
    }

    /**
     * Returns path to send dir
     *
     * @return string
     */
    public function getSendFolder()
    {
        return $this->getFolder(self::SEND_FOLDER);
    }

    /**
     * Returns path to lock dir
     *
     * @return string
     */
    public function getLockFolder()
    {
        return $this->getFolder(self::LOCK_FOLDER);
    }

    /**
     * Returns path to dir. Without trailing slash.
     * Try to create it if it doesn't exist
     *
     * @return string
     * @throw Exception
     */
    protected function getFolder($subPath)
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $path = $directory->getAbsolutePath($subPath);

        $this->file->checkAndCreateFolder($path, 0755);

        return $path;
    }

    /**
     * Gets completed targets by submission
     * @return Target[]
     */
    public function getCompletedTargetsBySubmission($documentID)
    {
        return $this->glExchangeClient->getCompletedTargetsBySubmission($documentID);
    }
}
