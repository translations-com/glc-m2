<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Eugene Monakov <emonakov@robofirm.com>
 */

namespace TransPerfect\GlobalLink\Helper\Ui;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use TransPerfect\GlobalLink\Api\LoggerInterface;
use Magento\Backend\Model\Auth;
use TransPerfect\GlobalLink\Helper\Data as Helper;
use TransPerfect\GlobalLink\Helper\Data;

/**
 * Class Logger
 *
 * @package TransPerfect\GlobalLink\Helper\Ui
 */
class Logger extends AbstractHelper
{
    /**
     * Severity types
     */
    const CRITICAL = 'critical';
    const ALERT = 'alert';
    const NOTICE = 'notice';
    const DEBUG = 'debug';

    /**
     * Action types
     */
    const FORM_ACTION_TYPE = 'form_action';
    const SEND_ACTION_TYPE = 'send_action';
    const CONFIG_ACTION_TYPE = 'config_action';
    const CONFIG_ADD_ACTION_TYPE = 'config_add_action';
    const CONFIG_DELETE_ACTION_TYPE = 'config_delete_action';

    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Backend\Model\Auth
     */
    protected $auth;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \TransPerfect\GlobalLink\Logger\Logger
     */
    protected $_logger;

    protected $enabledLevels;

    /**
     * Logger constructor.
     *
     * @param \Magento\Framework\App\Helper\Context        $context
     * @param \TransPerfect\GlobalLink\Api\LoggerInterface $logger
     * @param \Magento\Backend\Model\Auth                  $auth
     * @param \TransPerfect\GlobalLink\Helper\Data         $helper
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        Auth $auth,
        Helper $helper
    ) {
        $this->url = $context->getUrlBuilder();
        $this->helper = $helper;
        $this->auth = $auth;
        parent::__construct($context);
        $this->_logger = $logger;
        $this->enabledLevels = $this->scopeConfig->getValue('globallink/general/logging_level', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Prepare data for logging
     *
     * @param $data
     *
     * @return array
     */
    protected function prepareLogData($data)
    {
        $actionData = [
            'severity' => $data['severity'],
            'message' => $data['message'],
            'context' => $data['context']
        ];
        return $actionData;
    }

    /**
     * Add log method lookup and log
     *
     * @param $actionData
     *
     * @return $this
     */
    protected function logSubmissionAction($actionData)
    {
        $levels = explode(',', $this->enabledLevels);
        $logAction = false;
        switch ($actionData['severity']) {
            case self::NOTICE:
                if (in_array(Data::LOGGING_LEVEL_INFO, $levels)) {
                    $logAction = 'addInfo';
                }
                break;
            case self::ALERT:
                if (in_array(Data::LOGGING_LEVEL_ERROR, $levels)) {
                    $logAction = 'addError';
                }
                break;
            case self::CRITICAL:
                if (in_array(Data::LOGGING_LEVEL_ERROR, $levels)) {
                    $logAction = 'addCritical';
                }
                break;
            case self::DEBUG:
                if (in_array(Data::LOGGING_LEVEL_DEBUG, $levels)) {
                    $logAction = 'addDebug';
                }
                break;
        }
        if ($logAction) {
            $this->_logger->$logAction($actionData['message'], $actionData['context']);
        }
        return $this;
    }

    /**
     * Entry point to log ui action
     *
     * @param        $entityId
     * @param        $actionType
     * @param array  $data
     * @param string $severity
     * @param string $message
     *
     * @return $this
     */
    public function logAction($entityId, $actionType, $data = [], $severity = 'debug', $message = '')
    {
        if ($this->enabledLevels !== null) {
            $entityMetaData = $this->helper->mapObjectTypeToModel();
            $data['user'] = $this->auth->getUser()->getName();
            $data['action'] = $this->url->getCurrentUrl();
            $_data['message'] = $entityMetaData[$entityId]['messages'][$actionType];
            if ($message) {
                $_data['message'] .= ' | ' . $message;
            }
            $_data['severity'] = $severity;
            $_data['context'] = $data;
            $actionData = $this->prepareLogData($_data);
            $this->logSubmissionAction($actionData);
        }
        return $this;
    }

    public function isDebugEnabled(){
        $levels = explode(',', $this->enabledLevels);
        if (in_array(Data::LOGGING_LEVEL_DEBUG, $levels)) {
            return true;
        } else{
            return false;
        }
    }
    public function isErrorEnabled(){
        $levels = explode(',', $this->enabledLevels);
        if (in_array(Data::LOGGING_LEVEL_ERROR, $levels)) {
            return true;
        } else{
            return false;
        }
    }
}
