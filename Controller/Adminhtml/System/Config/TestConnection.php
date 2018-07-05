<?php
namespace TransPerfect\GlobalLink\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use TransPerfect\GlobalLink\Model\TranslationService;

/**
 * Class TestConnection
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\System\Config
 */
class TestConnection extends Action
{

    protected $resultJsonFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $loggerInterface;

    /**
     * @var \TransPerfect\GlobalLink\Model\TranslationService
     */
    protected $translationService;

    /**
     * TestConnection constructor.
     *
     * @param \Magento\Backend\App\Action\Context               $context
     * @param \Magento\Framework\Controller\Result\JsonFactory  $resultJsonFactory
     * @param \Psr\Log\LoggerInterface                          $loggerInterface
     * @param \TransPerfect\GlobalLink\Model\TranslationService $translationService
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LoggerInterface $loggerInterface,
        TranslationService $translationService
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->loggerInterface = $loggerInterface;
        $this->translationService = $translationService;
        parent::__construct($context);
    }

    /**
     * Test connection
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $response = $this->resultJsonFactory->create();

        try {
            $result = $this->_testConnection();

            if ($result === true) {
                $response->setData(['result' => __('Connected successfully')]);
            } else {
                $response->setData(['result' => $result]);
            }
        } catch (\Exception $e) {
            $this->loggerInterface->critical($e);
            $response->setData(['result' => false, 'message' => $e->getMessage()]);
        }

        return $response;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TransPerfect_GlobalLink::config');
    }

    /**
     * @return bool
     */
    protected function _testConnection()
    {
        $result = true;
        try {
            $response = $this->translationService->requestGLExchange(
                '/services/ProjectService',
                'getUserProjects',
                [
                        'isSubProjectIncluded' => true,
                    ]
            );
            if (empty($response)) {
                $result = __('Empty response');
            }
            if (is_array($response)) {
                if ($response[0] == null) {
                    $result = __('Connection Failed');
                }
            }
        } catch (\Exception $e) {
            $result = __('Connection Failed');
        }
        return $result;
    }
}
