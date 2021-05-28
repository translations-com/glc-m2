<?php

namespace TransPerfect\GlobalLink\Model\SoapClient;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PDConfig;
use TransPerfect\GlobalLink\Model\SoapClient\GLExchange\GLExchangeLocalFactory;

/**
 * Class GLExchangeClient
 */
class GLExchangeClient
{
    /**
     * @var string
     */
    protected $connectionUrl;
    protected $username;
    protected $password;
    protected $userAgent;
    protected $maxTargetCount;
    protected $maxCancelledCount;

    /**
     * default value for max targets
     */
    const DEFAULT_MAX_TARGETS = 9999;

    /**
     * default value for max cancelled targets
     */
    const DEFAULT_MAX_CANCELLED = 9999;
    const LOGGING_LEVEL_DEBUG = 0;
    const LOGGING_LEVEL_INFO = 1;
    const LOGGING_LEVEL_ERROR = 2;

    /**
     * @var GLExchange
     */
    protected $connect;

    /**
     * @var \TransPerfect\GlobalLink\Logger\BgTask\Logger
     */
    protected $bgLogger;

    /**
     * GlobalLink Logging levels
     */
    protected $enabledLevels = [];
    /**
     * constructor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \TransPerfect\GlobalLink\Logger\BgTask\Logger $bgLogger,
        \Magento\Framework\App\Request\Http $request,
        PDConfig $pdConfig,
        GLExchangeLocalFactory $glExchangeLocalFactory
    ) {
        $this->connectionUrl = $scopeConfig->getValue('globallink/connection/url', ScopeInterface::SCOPE_STORE);
        $this->username = $scopeConfig->getValue('globallink/connection/username', ScopeInterface::SCOPE_STORE);
        $this->password = $scopeConfig->getValue('globallink/connection/password', ScopeInterface::SCOPE_STORE);
        $this->enabledLevels = explode(',', $scopeConfig->getValue('globallink/general/logging_level', ScopeInterface::SCOPE_STORE));

        $this->userAgent = $request->getServerValue('HTTP_USER_AGENT');
        if (empty($this->userAgent)) {
            $this->userAgent = 'GLExchangeClient';
        }
        $this->maxTargetCount = $scopeConfig->getValue('globallink/general/max_target_count', ScopeInterface::SCOPE_STORE);
        if (empty($this->maxTargetCount) || (int)$this->maxTargetCount < 1) {
            $this->maxTargetCount = self::DEFAULT_MAX_TARGETS;
        }
        $this->maxCancelledCount = self::DEFAULT_MAX_CANCELLED;
        $this->bgLogger = $bgLogger;
        $this->connectionConfig = $pdConfig;
        $this->glExchangeLocalFactory = $glExchangeLocalFactory;
    }

    /**
     * get connection to service
     *
     * @return \GLExchange
     */
    public function getConnect()
    {
        if (!($this->connect instanceof \GLExchange)) {
            $this->connect = $this->connect();
        }

        return $this->connect;
    }

    /**
     * Connect to service
     *
     * @return \TransPerfect\GlobalLink\Model\SoapClient\GLExchange\GLExchangeLocal
     * @throws \Exception
     */
    protected function connect()
    {
        if (empty($this->connectionUrl)) {
            throw new \Exception("GLExchangeClient: Configuration option 'connectionUrl' is not set");
        } elseif (empty($this->username)) {
            throw new \Exception("GLExchangeClient: Configuration option 'username' is not set");
        } elseif (empty($this->password)) {
            throw new \Exception("GLExchangeClient: Configuration option 'password' is not set");
        } elseif (empty($this->userAgent)) {
            throw new \Exception("GLExchangeClient: Configuration option 'userAgent' is not set");
        }
        $this->connectionConfig->url = $this->connectionUrl;
        $this->connectionConfig->username = $this->username;
        $this->connectionConfig->password = $this->password;
        $this->connectionConfig->userAgent = $this->userAgent;

        $data = ['connectionConfig' => $this->connectionConfig];
        $connection = $this->glExchangeLocalFactory->create($data);

        $logData = ['message' => 'Connected to GLPD'];
        if (in_array($this::LOGGING_LEVEL_INFO, $this->enabledLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }

        return $connection;
    }
    /**
     * DO NOT USE THIS METHOD IN USUAL WORK
     *
     * It has been added to test connection with user's values from
     * php bin/magento test:glexchangeclient:connect
     *
     * @return string
     */
    public function testConnectError($username, $password, $url)
    {
        $this->connectionConfig->url = $url;
        $this->connectionConfig->username = $username;
        $this->connectionConfig->password = $password;
        $this->connectionConfig->userAgent = $this->userAgent;

        $error = '';
        try {
            $data = ['connectionConfig' => $this->connectionConfig];
            $test = $this->glExchangeLocalFactory->create($data);
            if (!($test instanceof \GLExchange)) {
                $error = 'Connection failed';
            }
        } catch (\Exception $e) {
            $error = 'Connection failed. ' . $e->getMessage();
        }
        return $error;
    }

    /**
     * Send request
     *
     * @param string $endpoint
     * @param string $serviceMethodName
     * @param array  $parameters
     *
     * @return mixed
     */
    public function request($endpoint, $serviceMethodName, array $parameters = [])
    {
        $response = null;

        if (empty($endpoint)) {
            throw new \Exception("GLExchangeClient: 'Endpoint' is not given");
        } elseif (empty($serviceMethodName)) {
            throw new \Exception("GLExchangeClient: 'Service MethodName' is not given");
        }

        if ($endpoint == '/services/ProjectService') {
            $response = $this->useProjectService($serviceMethodName, $parameters);
        } elseif ($endpoint == '/services/TargetService') {
            $response = $this->useTargetService($serviceMethodName, $parameters);
        } elseif ($endpoint == '/services/DocumentService') {
            $response = $this->useDocumentService($serviceMethodName, $parameters);
        } else {
            throw new \Exception("GLExchangeClient: Endpoint is not defined in client class");
        }

        return $response;
    }

    /**
     * request methods of project service
     *
     * @param string $serviceMethodName
     * @param array  $parameters
     *
     * @return mixed
     * @throws Exception
     */
    protected function useProjectService($serviceMethodName, $parameters)
    {
        if ($serviceMethodName == 'getUserProjects') {
            // @var Project[]
            $response = $this->getUserProjects($parameters);
        } elseif ($serviceMethodName == 'findProjectByShortCode') {
            // @var Project
            $response = $this->findProjectByShortCode($parameters);
        } elseif ($serviceMethodName == 'findProjectByName') {
            // @var Project
            $response = $this->findProjectByName($parameters);
        } elseif ($serviceMethodName == 'findByTicket') {
            // @var Project
            $response = $this->findByTicket($parameters);
        } else {
            throw new \Exception("GLExchangeClient: Method is not defined for given Endpoint in client class");
        }

        return $response;
    }

    /**
     * request methods of target service
     *
     * @param string $serviceMethodName
     * @param array  $parameters
     *
     * @return mixed
     * @throws Exception
     */
    protected function useTargetService($serviceMethodName, $parameters)
    {
        if ($serviceMethodName == 'downloadTargetResource') {
            if (empty($parameters['targetId'])) {
                throw new \Exception("GLExchangeClient: Document's Ticket is not given");
            } elseif (!is_string($parameters['targetId'])) {
                throw new \Exception("GLExchangeClient: Document's Ticket must be a string");
            }
            $response = $this->downloadTargetResource($parameters);
        } elseif ($serviceMethodName == 'sendDownloadConfirmation') {
            if (empty($parameters['targetId'])) {
                throw new \Exception("GLExchangeClient: Document's Ticket is not given");
            } elseif (!is_string($parameters['targetId'])) {
                throw new \Exception("GLExchangeClient: Document's Ticket must be a string");
            }
            $response = $this->sendDownloadConfirmation($parameters['targetId']);
        } elseif ($serviceMethodName == 'cancelTargetByDocumentId') {
            if (empty($parameters['documentId'])) {
                throw new \Exception("GLExchangeClient: Document's Ticket is not given");
            } elseif (!is_string($parameters['documentId'])) {
                throw new \Exception("GLExchangeClient: Document's Ticket must be a string");
            }
            if (empty($parameters['targetLocale'])) {
                throw new \Exception("GLExchangeClient: Target Locale is not given");
            } elseif (!is_string($parameters['targetLocale'])) {
                throw new \Exception("GLExchangeClient: Target Locale  must be a string");
            }
            $response = $this->cancelTargetByDocumentId($parameters);
        } elseif ($serviceMethodName == 'getCanceledTargetsBySubmissions') {
            if (empty($parameters['submissionTickets'])) {
                throw new \Exception("GLExchangeClient: Submission's Ticket(s) is not given");
            } elseif (!is_array($parameters['submissionTickets'])) {
                throw new \Exception("GLExchangeClient: Submission's Ticket(s) must be an array");
            }
            $response = $this->getCanceledTargetsBySubmissions($parameters);
        } else {
            throw new \Exception("GLExchangeClient: Method is not defined for given Endpoint in client class");
        }

        return $response;
    }

    /**
     * request methods of document service
     *
     * @param string $serviceMethodName
     * @param array  $parameters
     *
     * @return mixed
     * @throws Exception
     */
    protected function useDocumentService($serviceMethodName, $parameters)
    {
        if ($serviceMethodName == 'cancelDocument') {
            if (empty($parameters['documentTicket'])) {
                throw new \Exception("GLExchangeClient: Document's Ticket is not given");
            } elseif (!is_string($parameters['documentTicket'])) {
                throw new \Exception("GLExchangeClient: Document's Ticket must be a string");
            }
            $response = $this->cancelDocument($parameters);
        } else {
            throw new \Exception("GLExchangeClient: Method is not defined for given Endpoint in client class");
        }

        return $response;
    }

    /**
     * Get library class by classname
     *
     * @param string $classname
     *
     * @return mixed
     * @throws Exception
     */
    protected function getLibraryClass($classname)
    {
        try {
            $request = $this->getConnect()->getClass($classname);
        } catch (\Exception $e) {
            $logData = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                ];
            if (in_array($this::LOGGING_LEVEL_ERROR, $this->enabledLevels)) {
                $this->bgLogger->error($this->bgLogger->bgLogMessage($logData));
            }
            throw $e;
        }
        return $request;
    }

    /**
     * Get project by ticket
     *
     * @param array $parameters
     *
     * @return Project
     * @throws Exception
     */
    protected function findByTicket(array $parameters)
    {
        if (empty($parameters['ticket'])) {
            throw new \Exception("GLExchangeClient: Project's Ticket is not given");
        } elseif (!is_string($parameters['ticket'])) {
            throw new \Exception("GLExchangeClient: Project's Ticket must be a string");
        }
        $request = $this->getLibraryClass('findByTicket');
        $request->ticket = $parameters['ticket'];
        $project = $this->getConnect()->getProjectService()->findByTicket($request)->return;

        return $project;
    }

    /**
     * Get project by name
     *
     * @param array $parameters
     *
     * @return Project
     * @throws Exception
     */
    protected function findProjectByName(array $parameters)
    {
        if (empty($parameters['projectName'])) {
            throw new \Exception("GLExchangeClient: Project's Name is not given");
        } elseif (!is_string($parameters['projectName'])) {
            throw new \Exception("GLExchangeClient: Project's Name must be a string");
        }
        $request = $this->getLibraryClass('findProjectByName');
        $request->projectName = $parameters['projectName'];
        $project = $this->getConnect()->getProjectService()->findProjectByName($request)->return;

        return $project;
    }

    /**
     * Get project by ShortCode
     *
     * @param array $parameters
     *
     * @return Project
     * @throws Exception
     */
    protected function findProjectByShortCode(array $parameters)
    {
        if (empty($parameters['projectShortCode'])) {
            throw new \Exception("GLExchangeClient: Project's ShortCode is not given");
        } elseif (!is_string($parameters['projectShortCode'])) {
            throw new \Exception("GLExchangeClient: Project's ShortCode must be a string");
        }
        $request = $this->getLibraryClass('findProjectByShortCode');
        $request->projectShortCode = $parameters['projectShortCode'];
        $project = $this->getConnect()->getProjectService()->findProjectByShortCode($request)->return;

        return $project;
    }

    /**
     * Get user projects
     *
     * @param array $parameters
     *
     * @return array of Project
     */
    protected function getUserProjects(array $parameters = [])
    {
        $isSubProjectIncluded = true;

        if (!empty($parameters['isSubProjectIncluded'])) {
            $isSubProjectIncluded = (bool) $parameters['isSubProjectIncluded'];
        }

        $request = $this->getLibraryClass('getUserProjects');
        $request->isSubProjectIncluded = $isSubProjectIncluded;
        $projects = $this->getConnect()->getProjectService()->getUserProjects($request)->return;

        if (is_array($projects)) {
            return $projects;
        }
        return [$projects];
    }

    /**
     * Get translated text by doc ticket
     *
     * @param array $parameters
     *
     * @return string
     */
    protected function downloadTargetResource(array $parameters)
    {
        $request = $this->getLibraryClass('downloadTargetResource');
        $request->targetId = $parameters['targetId'];
        $repositoryItem = $this->getConnect()->getTargetService()->downloadTargetResource($request)->return;

        return $repositoryItem->data->_;
    }

    /**
     * Send download confirmation
     *
     * @param string $documentTicket
     *
     * @return string
     */
    protected function sendDownloadConfirmation($documentTicket)
    {
        return $this->getConnect()->sendDownloadConfirmation($documentTicket);
    }

    /**
     * Cancel target by document ticket and locale code
     *
     * @param array $parameters
     *
     * @return bool
     */
    protected function cancelTargetByDocumentId(array $parameters)
    {
        $docTicket = $this->getLibraryClass('DocumentTicket');
        $docTicket->ticketId = $parameters['documentId'];
        $request = $this->getLibraryClass('cancelTargetByDocumentId');
        $request->documentId = $docTicket;
        $request->targetLocale = $parameters['targetLocale'];
        try {
            $result = $this->getConnect()->getTargetService()->cancelTargetByDocumentId($request);
            $result = true;
        } catch (\Exception $e) {
            // can't be canceled
            $result = false;
        }

        return $result;
    }

    /**
     * Cancel document by ticket
     *
     * @param array $parameters
     *
     * @return bool
     */
    protected function cancelDocument(array $parameters)
    {
        $request = $this->getLibraryClass('cancelDocument');
        $request->documentTicket = $parameters['documentTicket'];
        try {
            $result = $this->getConnect()->getDocumentService()->cancelDocument($request)->return;
            $result = true;
        } catch (\Exception $e) {
            // can't be canceled
            $result = false;
        }

        return $result;
    }

    /**
     * get document tickets for remotely cancelled submissions
     *
     * @param array $parameters
     *
     * @return array $cancelled = [
     *      document_ticket => [
     *          target_locale,
     *          target_locale,
     *      ],
     *      document_ticket => [
     *          target_locale,
     *      ],
     *  ]
     */
    protected function getCanceledTargetsBySubmissions(array $parameters)
    {
        $PDTargetObjects = $this->getConnect()->getCancelledTargetsBySubmissions(
            $parameters['submissionTickets'],
            $this->maxCancelledCount
        );

        $cancelled = [];
        foreach ($PDTargetObjects as $obj) {
            $cancelled[$obj->documentTicket][] = $obj->targetLocale;
        }

        return $cancelled;
    }

    /**
     * Init submission
     *
     * @param array $data
     */
    public function initSubmission(array $data)
    {
        $textAttributeFilled = false;
        $comboAttributeFilled = false;
        $pdproject = $this->getConnect()->getProject($data['projectShortCode']);
        $customAttributes = $pdproject->customAttributes;
        $submission = $this->getLibraryClass('PDSubmission');
        $submission->name = $data['submissionName'];
        $submission->project = $pdproject;
        $submission->isUrgent = (bool) $data['submissionPriority'];
        $submission->instructions = $data['submissionNotes'];
        $submission->dueDate = strtotime($data['submissionDueDate'])*1000;
        foreach ($customAttributes as $attribute) {
            if ($data['attribute_text'] != null && $attribute->type == 'TEXT' && $textAttributeFilled == false) {
                $submission->customAttributes[$attribute->name] = $data['attribute_text'];
                $textAttributeFilled = true;
            }
            if ($data['attribute_combo'] != null && $attribute->type == 'COMBO' && $comboAttributeFilled == false) {
                $submission->customAttributes[$attribute->name] = $data['attribute_combo'];
                $comboAttributeFilled = true;
            }
        }
        $client = $this->getConnect();
        $client->initSubmission($submission);
    }

    /**
     * Send document
     *
     * @param array $data
     *
     * @return string Document ticket
     */
    public function sendDocumentForTranslate(array $data)
    {
        $client = $this->getConnect();

        $document = $this->getLibraryClass('PDDocument');
        $document->fileformat = $data['fileformat'];
        $document->name = $data['name'];
        $document->sourceLanguage = $data['sourceLanguage'];
        $document->targetLanguages = $data['targetLanguages'];
        $document->data = $data['data'];
        $targetLanguagesString = implode(",", $document->targetLanguages);
        $documentTicket = $client->uploadTranslatable($document);

        $message = "Document uploaded to GLPD. Document ticket: {$documentTicket}. Item name: {$document->name}, Source language: {$document->sourceLanguage}, Target Language(s): {$targetLanguagesString}. ";
        $debugMessage = "Entity Data: {$document->data}";

        $logData = ['message' => $message];
        if (in_array($this::LOGGING_LEVEL_INFO, $this->enabledLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        if (in_array($this::LOGGING_LEVEL_DEBUG, $this->enabledLevels)) {
            $logData = ['message' => $debugMessage];
            $this->bgLogger->debug($this->bgLogger->bgLogMessage($logData));
            if (!empty($data['logInfo'])) {
                $logData = ['message' => "Additional Info: {$data['logInfo']}"];
                $this->bgLogger->debug($this->bgLogger->bgLogMessage($logData));
            }
        }
        return $documentTicket;
    }

    /**
     * Start submission
     *
     * @return string Submission ticket
     */
    public function startSubmission()
    {
        $client = $this->getConnect();

        $submissionTicket = $client->startSubmission();

        $message = "Submission created. Submission ticket: {$submissionTicket}.";
        $logData = ['message' => $message];
        if (in_array($this::LOGGING_LEVEL_INFO, $this->enabledLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }

        return $submissionTicket;
    }

    /**
     * Receive translations
     *
     * @param array $tickets
     *
     * @return PDTarget[]
     */
    public function receiveTranslationsByTickets($tickets)
    {
        $client = $this->getConnect();

        for ($i=0; $i < 30; $i++) {
            $targetTickets = $client->getCompletedTargetsBySubmission($tickets, $this->maxTargetCount);
            if ($targetTickets != null) {
                return $targetTickets;
            }
        }
        return $client->getCompletedTargetsBySubmission($tickets, $this->maxTargetCount);
    }

    /**
     * Receive translations
     *
     * @param $project
     *
     * @return PDTarget[]
     */
    public function receiveTranslationsByProject($project)
    {
        $client = $this->getConnect();
        $pdproject = $client->getProject($project);
        for ($i=0; $i < 8; $i++) {
            $targetTickets = $client->getCompletedTargetsByProject($pdproject, $this->maxTargetCount);
            if($targetTickets != null)
                return $targetTickets;
        }

        return $targetTickets;
    }

    /**
     * Receive completed targets by submission
     * @param $submissionTicket
     * @return [Target]
     */

    public function getCompletedTargetsBySubmission($submissionTicket)
    {
        $client = $this->getConnect();
        return $client->getCompletedTargetsBySubmission($submissionTicket, $this->maxTargetCount);
    }
}
