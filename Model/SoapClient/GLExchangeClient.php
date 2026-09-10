<?php

namespace TransPerfect\GlobalLink\Model\SoapClient;

use GlobalLink\RestClient\GlobalLinkClient;
use GlobalLink\RestClient\Model\BatchInfo;
use GlobalLink\RestClient\Model\CreateSubmissionTargetLanguageInfo;
use GlobalLink\RestClient\Model\Target;
use GlobalLink\RestClient\Model\TechTracking;
use GlobalLink\RestClient\Request\CreateSubmissionRequest;
use GlobalLink\RestClient\Request\GetTargetsRequest;
use GlobalLink\RestClient\Request\ListProjectsRequest;
use GlobalLink\RestClient\Request\SaveSubmissionRequest;
use GlobalLink\RestClient\Request\UploadSourceFileRequest;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use mysql_xdevapi\Exception;

use function PHPUnit\Framework\isEmpty;

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
    protected $maxCancelledCount;
    protected $glExchangeLocalFactory;
    protected $productMetadata;
    protected $moduleResource;
    protected $oauthclient;
    protected $oauthsecret;

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
     * @var GlobalLinkClient
     */
    protected $connect;

    /**
     * @var \TransPerfect\GlobalLink\Logger\BgTask\Logger
     */
    protected $bgLogger;
    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;

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
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ResourceInterface $moduleResource
    ) {
        $this->connectionUrl = (string)$scopeConfig->getValue('globallink/connection/url', ScopeInterface::SCOPE_STORE);
        $this->username = (string)$scopeConfig->getValue('globallink/connection/username', ScopeInterface::SCOPE_STORE);
        $this->password = (string)$scopeConfig->getValue('globallink/connection/password', ScopeInterface::SCOPE_STORE);
        $this->oauthclient = (string)$scopeConfig->getValue('globallink/connection/oauthclient', ScopeInterface::SCOPE_STORE);
        $this->oauthsecret = (string)$scopeConfig->getValue('globallink/connection/oauthsecret', ScopeInterface::SCOPE_STORE);
        $this->enabledLevels = $scopeConfig->getValue('globallink/general/logging_level', ScopeInterface::SCOPE_STORE) == null ? [''] : explode(',', $scopeConfig->getValue('globallink/general/logging_level', ScopeInterface::SCOPE_STORE));
        $this->userAgent = $request->getServerValue('HTTP_USER_AGENT');
        $this->productMetadata = $productMetadata;
        $this->moduleResource = $moduleResource;
        if (empty($this->userAgent)) {
            $this->userAgent = 'GLExchangeClient';
        }
        $this->maxCancelledCount = self::DEFAULT_MAX_CANCELLED;
        $this->bgLogger = $bgLogger;
    }

    /**
     * get connection to service
     *
     * @return GlobalLinkClient
     */
    public function getConnect()
    {
        if ($this->connect instanceof GlobalLinkClient) {
            //DO NOTHING
        } else {
            $this->connect = new GlobalLinkClient($this->connectionUrl, $this->username, $this->password, $this->oauthclient, $this->oauthsecret, null, "TEST", null);

        }

        return $this->connect;
    }

    /**
     * Init submission
     *
     * @param array $data
     */
    public function initSubmission(array $data)
    {
        $client = $this->getConnect();

        $textAttributeFilled = false;
        $comboAttributeFilled = false;
        $customAttributes = [];
        foreach ($customAttributes as $attribute) {
            if ($data['attribute_text'] != null && $attribute->type == 'TEXT' && $textAttributeFilled == false) {
                $customAttributes[$attribute->name] = $data['attribute_text'];
                $textAttributeFilled = true;
            }
            if ($data['attribute_combo'] != null && $attribute->type == 'COMBO' && $comboAttributeFilled == false) {
                $customAttributes[$attribute->name] = $data['attribute_combo'];
                $comboAttributeFilled = true;
            }
        }
        $batch = new BatchInfo(
            name: 'Batch 1',
            targetFormat: 'TXLF',
            targetLanguages: $data['targetLanguages']
        );
        if (strlen($data['submissionNotes']) > 0 && !isEmpty($customAttributes)) {
            $request = new CreateSubmissionRequest(
                name: $data['submissionName'],
                dueDate: strtotime($data['submissionDueDate'])*1000,
                projectId: $data['projectShortCode'],
                sourceLanguage: $data['sourceLanguage'],
                batchInfos: [$batch],
                claimScope: 'LANGUAGE',
                instructions: $data['submissionNotes'],
                customAttributes: $customAttributes,
            );
        } elseif (strlen($data['submissionNotes']) > 0 && isEmpty($customAttributes)) {
            $request = new CreateSubmissionRequest(
                name: $data['submissionName'],
                dueDate: strtotime($data['submissionDueDate'])*1000,
                projectId: $data['projectShortCode'],
                sourceLanguage: $data['sourceLanguage'],
                batchInfos: [$batch],
                claimScope: 'LANGUAGE',
                instructions: $data['submissionNotes']
            );
        } elseif (strlen($data['submissionNotes']) == 0 && !isEmpty($customAttributes)) {
            $request = new CreateSubmissionRequest(
                name: $data['submissionName'],
                dueDate: strtotime($data['submissionDueDate'])*1000,
                projectId: $data['projectShortCode'],
                sourceLanguage: $data['sourceLanguage'],
                batchInfos: [$batch],
                claimScope: 'LANGUAGE',
                customAttributes: $customAttributes
            );
        } else {
            $request = new CreateSubmissionRequest(
                name: $data['submissionName'],
                dueDate: strtotime($data['submissionDueDate'])*1000,
                projectId: $data['projectShortCode'],
                sourceLanguage: $data['sourceLanguage'],
                batchInfos: [$batch],
                claimScope: 'LANGUAGE'
            );
        }
        try {
            $createResponse = $client->createSubmission($request);
        } catch (\Magento\Framework\Webapi\Exception $e) {
            $errorMessage = $e->getMessage();
            echo $errorMessage;
        }

        return $createResponse->submissionId;
    }

    /**
     * Get all project IDs configured
     *
     * @var array $shortCodes
     * @return array $projectIds
     */
    public function getProjectIds($shortCodes)
    {
        $projectIds = [];
        foreach ($shortCodes as $shortCode) {
            $request = new ListProjectsRequest(
                shortCode: $shortCode
            );
            $projectIds[] = $this->getConnect()->getProjects($request)[0]->projectId;
        }
        return $projectIds;
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

        $request = new UploadSourceFileRequest(
            batchName: 'Batch 1',
            fileContents: $data['data'],
            fileName: $data['name'],
            fileFormatName: $data['fileformat'],
            targetLanguages: $data['targetLanguages']
        );

        $response = $client->uploadSubmissionSourceFile($data['submission_id'], $request);
        $targetLanguagesString = implode(",", $data['targetLanguages']);
        /*$document = $this->getLibraryClass('PDDocument');
        $document->fileformat = $data['fileformat'];
        $document->name = $data['name'];
        $document->sourceLanguage = $data['sourceLanguage'];
        $document->targetLanguages = $data['targetLanguages'];
        $document->data = $data['data'];
        $targetLanguagesString = implode(",", $document->targetLanguages);
        $documentTicket = $client->uploadTranslatable($document);*/

        $documentID = $response->documentIds[0]->documentId;
        $fileName = $response->documentIds[0]->name;
        $message = "Document uploaded to GLPD. Document ID: {$documentID}. Item name: {$fileName}, Source language: {$data['sourceLanguage']}, Target Language(s): {$targetLanguagesString}. ";

        $logData = ['message' => $message];
        if (in_array($this::LOGGING_LEVEL_INFO, $this->enabledLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }

        return $response->documentIds[0]->documentId;
    }

    /**
     * Start submission
     *
     * @var int $submissionID
     */
    public function startSubmission($submissionID)
    {
        $client = $this->getConnect();
        $techTracking = new TechTracking(
            adaptorName: 'GlobalLink Magento Integration',
            adaptorVersion: $this->moduleResource->getDbVersion('TransPerfect_GlobalLink'),
            clientVersion: $this->productMetadata->getVersion(),
            technologyProduct: 'GLE'
        );
        //$client->putSubmissionTechTracking($submissionID, $techTracking);
        $result = $client->saveSubmission($submissionID, new SaveSubmissionRequest(autoStart: true));

        $message = "Submission started. Submission ID: {$submissionID}.";
        $logData = ['message' => $message];
        if (in_array($this::LOGGING_LEVEL_INFO, $this->enabledLevels)) {
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
    }


    /**
     * Receive translations
     *
     * @param $project
     *
     * @return list<Target>
     */
    public function receiveTranslationsByProject($project)
    {
        $client = $this->getConnect();
        $page = 1;
        $returnTickets = [];
        $targetTickets = null;
        while ($targetTickets == null || count($targetTickets) != 0) {
            $request = new GetTargetsRequest(
                targetStatus: 'PROCESSED',
                projectIds: $project,
                pageSize: 200,
                pageNumber: $page
            );
            $targetTickets = $client->getTargets($request);
            $returnTickets = array_merge($returnTickets, $targetTickets);
            if ($targetTickets == null || count($targetTickets) == 0) {
                return $returnTickets;
            }
            $page++;
        }

        return null;
    }

    /**
     * Receive canclled tickets by submission
     *
     * @param array $submissionIds
     *
     * @return list<Target>
     */
    public function getCancelledTargetsBySubmissions($submissionIds)
    {
        $client = $this->getConnect();
        $page = 1;
        $returnTickets = [];
        $targetTickets = null;
        while ($targetTickets == null || count($targetTickets) != 0) {
            $request = new GetTargetsRequest(
                targetStatus: 'CANCELLED',
                submissionIds: $submissionIds,
                pageSize: 200,
                pageNumber: $page
            );
            $targetTickets = $client->getTargets($request);
            $returnTickets = array_merge($returnTickets, $targetTickets);
            if ($targetTickets == null || count($targetTickets) == 0) {
                return $returnTickets;
            }
            $page++;
        }

        return null;
    }

    /**
     * Receive completed targets by submission
     * @param $submissionTicket
     * @return [Target]
     */

    public function getCompletedTargetsBySubmission($documentID)
    {
        $client = $this->getConnect();
        $targetsRequest = new GetTargetsRequest('PROCESSED', documentIds: [$documentID]);
        $targets = $client->getTargets($targetsRequest);
        return $targets;
    }
}
