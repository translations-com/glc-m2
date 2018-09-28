<?php

namespace TransPerfect\GlobalLink\Model;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use SoapClient;
use SoapFault;
use TransPerfect\GlobalLink\Model\SoapClient\GLExchangeClient;
use TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory as ItemCollectionFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;

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

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        GLExchangeClient $glExchangeClient,
        ItemCollectionFactory $itemCollectionFactory,
        \TransPerfect\GlobalLink\Logger\BgTask\Logger $bgLogger,
        Filesystem $filesystem,
        File $file
    ) {
        $this->glExchangeClient = $glExchangeClient;
        $this->username = $scopeConfig->getValue('globallink/connection/username', ScopeInterface::SCOPE_STORE);
        $this->password = $scopeConfig->getValue('globallink/connection/password', ScopeInterface::SCOPE_STORE);
        $shortCodes = $scopeConfig->getValue('globallink/general/project_short_codes', ScopeInterface::SCOPE_STORE);
        $this->projectShortCodes = array_map('trim', explode(',', $shortCodes));
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->bgLogger = $bgLogger;
        $this->filesystem = $filesystem;
        $this->file = $file;
    }

    /**
     * Send request using GLExchange library
     *
     * @param string $endpoint
     * @param string $serviceMethodName
     * @param array  $parameters
     *
     * @return
     */
    public function requestGLExchange($endpoint, $serviceMethodName, array $parameters = [])
    {
        $response = $this->glExchangeClient->request($endpoint, $serviceMethodName, $parameters);

        $preparedData = $response;

        return $preparedData;
    }

    /**
     * Init submission task
     *
     * @param array $data
     */
    public function initSubmission(array $data)
    {
        $this->glExchangeClient->initSubmission($data);
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
     * @return string Submission ticket
     */
    public function startSubmission()
    {
        return $this->glExchangeClient->startSubmission();
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
                                .$e->getMessage(),
                            ];
                        $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
                        $queue->setQueueErrors(array_merge($queue->getQueueErrors(), [$this->bgLogger->bgLogMessage($logData)]));
                    }
                }
            } else {
                throw $e;
            }
        }
        $this->moveItemsInError($problemTickets);
        $ticketCount = count($targets);
        $logData = ['message' => "Tickets were found. Count of tickets: {$ticketCount}"];
        $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        return $targets;
    }
    /**
     * Receive translations
     *
     *
     *
     * @return PDTarget[]
     * @throws Exception
     */
    public function receiveTranslationsByProject()
    {
        $targets = [];
        try {
            $targets = $this->glExchangeClient->receiveTranslationsByProject($this->projectShortCodes);
        } catch (\Exception $e) {
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
     * @param string $documentTicket
     *
     * @return string xml
     */
    public function downloadTarget($documentTicket)
    {
        $translatedText = $this->requestGLExchange(
            '/services/TargetService',
            'downloadTargetResource',
            [
                'targetId' => $documentTicket,
            ]
        );

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
     * @param string $documentTicket
     * @param string $localeCode
     *
     * @return bool
     */
    public function cancelTargetByDocumentId($documentTicket, $localeCode)
    {
        $result = $this->requestGLExchange(
            '/services/TargetService',
            'cancelTargetByDocumentId',
            [
                'documentId' => $documentTicket,
                'targetLocale' => $localeCode,
            ]
        );

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
    public function getCancelledTargetsBySubmissions(array $submissionTickets)
    {
        $result = $this->requestGLExchange(
            '/services/TargetService',
            'getCanceledTargetsBySubmissions',
            [
                'submissionTickets' => $submissionTickets,
            ]
        );

        return $result;
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
    public function getCompletedTargetsBySubmission($submissionTicket){
        return $this->glExchangeClient->getCompletedTargetsBySubmission($submissionTicket);
    }
}
