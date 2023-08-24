<?php
namespace TransPerfect\GlobalLink\Model\SoapClient\GLExchange;

use GLExchange;
use TransPerfect\GlobalLink\Model\CustomReflectionClassFactory;

/**
 * Extend library class
 */
class GLExchangeLocal extends GLExchange
{
    /**
     * @var array Reflections for private properties of parent class
     */
    protected $privates;

    /**
     * @var \getUserProjects
     */
    protected $getUserProjects;

    /**
     * @var \findProjectByShortCode
     */
    protected $findProjectByShortCode;

    /**
     * @var \findProjectByName
     */
    protected $findProjectByName;

    /**
     * @var \findByTicket
     */
    protected $findByTicket;

    /**
     * @var \PDSubmission
     */
    protected $PDSubmission;

    /**
     * @var \PDDocument
     */
    protected $PDDocument;

    /**
     * @var \downloadTargetResource
     */
    protected $downloadTargetResource;

    /**
     * @var \findByProjectName
     */
    protected $findByProjectName;

    /**
     * @var \cancelTargetByDocumentId
     */
    protected $cancelTargetByDocumentId;

    /**
     * @var \DocumentTicket
     */
    protected $DocumentTicket;

    /**
     * @var \cancelDocument
     */
    protected $cancelDocument;

    /**
     * Initialize Project Director connector
     *
     * @param \PDConfig                         $connectionConfig
     * @param \CustomReflectionClassFactory     $reflectionFactory
     * @param \ProductMetadataInterface         $productMetadata
     */
    public function __construct(
        \PDConfig $connectionConfig,
        CustomReflectionClassFactory $reflectionFactory,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        parent::__construct($connectionConfig);
        $version = $productMetadata->getVersion();
        //$reflection = new ReflectionClass($this);
        if(substr($version, 0, 5) === '2.4.4' || substr($version, 0, 5) === '2.4.5' || substr($version, 0, 5) === '2.4.6' || substr($version, 0, 3) === '2.5') {
            $reflection = $reflectionFactory->create(['objectOrClass' => $this]);
        } else{
            $reflection = $reflectionFactory->create(['argument' => $this]);
        }
        $reflection = $reflection->getParentClass();
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $this->privates[$property->getName()] = $property;
        }

        $this->getUserProjects = new \getUserProjects();
        $this->findProjectByShortCode = new \findProjectByShortCode();
        $this->findByTicket = new \findByTicket();
        $this->findProjectByName = new \findProjectByName();
        $this->PDSubmission = new \PDSubmission();
        $this->PDDocument = new \PDDocument();
        $this->downloadTargetResource = new \downloadTargetResource();
        $this->cancelTargetByDocumentId = new \cancelTargetByDocumentId();
        $this->DocumentTicket = new \DocumentTicket();
        $this->cancelDocument = new \cancelDocument();
    }

    /**
     * TRAN-67: Need this getters to make required requests from GLExchangeClient class
     */
    public function getProjectService()
    {
        return $this->privates['projectService']->getValue($this);
    }
    public function getSubmissionService()
    {
        return $this->privates['submissionService']->getValue($this);
    }
    public function getWorkflowService()
    {
        return $this->privates['workflowService']->getValue($this);
    }
    public function getTargetService()
    {
        return $this->privates['targetService']->getValue($this);
    }
    public function getDocumentService()
    {
        return $this->privates['documentService']->getValue($this);
    }
    public function getUserProfileService()
    {
        return $this->privates['userProfileService']->getValue($this);
    }

    /**
     * Return internal library class
     *
     * @param string $classname
     */
    public function getClass($classname)
    {
        if (empty($this->$classname)) {
            throw new \Exception('Class '.$classname.' seems have not been created in GLExchangeLocal Constructor');
        } elseif (get_class($this->$classname) != $classname) {
            throw new \Exception('Expected class '.$classname.' into field '.$classname.' but found'.get_class($this->$classname));
        }
        return $this->$classname;
    }
}
