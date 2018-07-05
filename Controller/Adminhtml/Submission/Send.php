<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Eugene Monakov <emonakov@robofirm.com>
 */
namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission;

use \Magento\Backend\App\Action as BackendAction;
use TransPerfect\GlobalLink\Helper\Ui\Logger;
use Magento\Framework\App\ResponseInterface;
use TransPerfect\GlobalLink\Model\Queue\Item;

/**
 * Class Send
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission
 */
class Send extends BackendAction
{
    /**
     * @var bool|\Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory = false;

    /**
     * Queue model factory
     *
     * @var \TransPerfect\GlobalLink\Model\QueueFactory
     */
    protected $_queueFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPage;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \TransPerfect\GlobalLink\Helper\Ui\Logger
     */
    protected $logger;
    
    /**
     * @var \TransPerfect\GlobalLink\Helper\Product
     */
    protected $productHelper;

    /**
     * Send constructor.
     *
     * @param \Magento\Backend\App\Action\Context              $context
     * @param \Magento\Framework\View\Result\PageFactory       $resultPageFactory
     * @param \TransPerfect\GlobalLink\Model\QueueFactory      $queueFactory
     * @param \Magento\Eav\Model\Config                        $config
     * @param \Magento\Framework\Stdlib\DateTime\DateTime      $dateTime
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \TransPerfect\GlobalLink\Helper\Ui\Logger        $logger
     * @param \TransPerfect\GlobalLink\Helper\Product          $productHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \TransPerfect\GlobalLink\Model\QueueFactory $queueFactory,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        Logger $logger,
        \TransPerfect\GlobalLink\Helper\Product $productHelper
    ) {
        parent::__construct($context);
        $this->_dateTime = $dateTime;
        $this->resultPageFactory = $resultPageFactory;
        $this->_queueFactory = $queueFactory;
        $this->_eavConfig = $config;
        $this->categoryRepository = $categoryRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->logger = $logger;
        $this->productHelper = $productHelper;
        $user = $this->_auth->getUser();
        if (!empty($user)) {
            Item::setActor('user: '.$user->getUsername().'('.$user->getId().')');
        }
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        if ($this->getRequest()->getPost()) {
            $queue = $this->_queueFactory->create();

            // TODO change database structure and field names to save data.
            $formData = $this->getRequest()->getParam('submission');
            $queue->setData($formData);

            try {
                $queue->save();
                $this->messageManager->addSuccessMessage(__('Submission has been successfully created.'));
                $this->_redirect('*/*/');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addSuccessMessage($e->getMessage());
            }

            $this->_getSession()->setFormData($formData);
            $this->_redirect('*/*/create');
        }
    }

    /*
     * Check permission via ACL resource
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TransPerfect_GlobalLink::management');
    }
}
