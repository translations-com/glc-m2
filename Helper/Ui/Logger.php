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

/**
 * Class Logger
 *
 * @package TransPerfect\GlobalLink\Helper\Ui
 */
class Logger extends AbstractHelper
{
    const LOGGING_LEVEL_DEBUG = 0;
    const LOGGING_LEVEL_INFO = 1;
    const LOGGING_LEVEL_ERROR = 2;
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
     * Object types
     */
    const CATALOG_CATEGORY_TYPE_ID = 3;
    const CATALOG_PRODUCT_TYPE_ID = 4;
    const PRODUCT_ATTRIBUTE_TYPE_ID = 11;
    const CMS_PAGE_TYPE_ID = 12;
    const CMS_BLOCK_TYPE_ID = 13;
    const CUSTOMER_ATTRIBUTE_TYPE_ID = 14;
    const PRODUCT_REVIEW_ID = 15;

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
        Auth $auth
    ) {
        $this->url = $context->getUrlBuilder();
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
                if (in_array(self::LOGGING_LEVEL_INFO, $levels)) {
                    $logAction = 'info';
                }
                break;
            case self::ALERT:
                if (in_array(self::LOGGING_LEVEL_ERROR, $levels)) {
                    $logAction = 'error';
                }
                break;
            case self::CRITICAL:
                if (in_array(self::LOGGING_LEVEL_ERROR, $levels)) {
                    $logAction = 'critical';
                }
                break;
            case self::DEBUG:
                if (in_array(self::LOGGING_LEVEL_DEBUG, $levels)) {
                    $logAction = 'debug';
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
            $entityMetaData = $this->mapObjectTypeToModel();
            $data['user'] = $this->auth->getUser()->getName();
            $data['action'] = $this->url->getCurrentUrl();
            if(isset($entityMetaData[$entityId]['messages'][$actionType])){
                $data['message'] = $entityMetaData[$entityId]['messages'][$actionType];
            } else{
                $data['message'] = '';
            }
            if ($message) {
                $data['message'] .= ' | ' . $message;
            }
            $data['severity'] = $severity;
            $data['context'] = $data;
            $actionData = $this->prepareLogData($data);
            $this->logSubmissionAction($actionData);
        }
        return $this;
    }

    public function isDebugEnabled(){
        $levels = explode(',', $this->enabledLevels);
        if (in_array(self::LOGGING_LEVEL_DEBUG, $levels)) {
            return true;
        } else{
            return false;
        }
    }
    public function isErrorEnabled(){
        $levels = explode(',', $this->enabledLevels);
        if (in_array(self::LOGGING_LEVEL_ERROR, $levels)) {
            return true;
        } else{
            return false;
        }
    }
    public function isInfoEnabled(){
        $levels = explode(',', $this->enabledLevels);
        if (in_array(self::LOGGING_LEVEL_INFO, $levels)) {
            return true;
        } else{
            return false;
        }
    }
    /**
     * @return array
     */
    public function mapObjectTypeToModel()
    {
        return [
            self::CATALOG_CATEGORY_TYPE_ID => [
                'class' => \Magento\Catalog\Model\Category::class,
                'messages' => [
                    'form_action' => __('Send for Translation Form - Categories'),
                    'send_action' => __('Send for Translation - Categories'),
                    'config_action' => __('Field Configuration - Categories'),
                ]
            ],
            self::CATALOG_PRODUCT_TYPE_ID => [
                'class' => \Magento\Catalog\Model\Product::class,
                'messages' => [
                    'form_action' => __('Send for Translation Form - Products'),
                    'send_action' => __('Send for Translation - Products'),
                    'config_action' => __('Field Configuration - Products'),
                ]
            ],
            self::PRODUCT_ATTRIBUTE_TYPE_ID => [
                'class' => \Magento\Eav\Model\Attribute::class,
                'entity' => \Magento\Catalog\Model\Product::ENTITY,
                'messages' => [
                    'form_action' => __('Send for Translation Form - Product Attributes'),
                    'send_action' => __('Send for Translation - Product Attributes'),
                    'config_action' => __('Field Configuration - Product Attributes'),
                ]
            ],
            self::CMS_PAGE_TYPE_ID => [
                'class' => \Magento\Cms\Model\Page::class,
                'messages' => [
                    'form_action' => __('Send for Translation Form - CMS Pages'),
                    'send_action' => __('Send for Translation - CMS Pages'),
                    'config_action' => __('Field Configuration - CMS Pages'),
                    'config_add_action' => __('Field Configuration Add - CMS Pages'),
                    'config_delete_action' => __('Field Configuration Delete - CMS Pages')
                ]
            ],
            self::CMS_BLOCK_TYPE_ID => [
                'class' =>\Magento\Cms\Model\Block::class,
                'messages' => [
                    'form_action' => __('Send for Translation Form - CMS Blocks'),
                    'send_action' => __('Send for Translation - CMS Blocks'),
                    'config_action' => __('Field Configuration - CMS Blocks'),
                    'config_add_action' => __('Field Configuration Add - CMS Blocks'),
                    'config_delete_action' => __('Field Configuration Delete - CMS Blocks')
                ]
            ],
            self::CUSTOMER_ATTRIBUTE_TYPE_ID => [
                'class' => \Magento\Eav\Model\Attribute::class,
                'entity' => \Magento\Customer\Model\Customer::ENTITY,
                'messages' => [
                    'form_action' => __('Send for Translation Form - Customer Attributes'),
                    'send_action' => __('Send for Translation - Customer Attributes'),
                    'config_action' => __('Field Configuration - Customer Attributes'),
                ]
            ],
            self::PRODUCT_REVIEW_ID => [
                'class' => \Magento\Review\Model\Review::class,
                'messages' => [
                    'form_action' => __('Send for Translation Form - Product Reviews'),
                    'send_action' => __('Send for Translation - Product Reviews'),
                    'config_action' => __('Field Configuration - Product Reviews'),
                ]
            ],
        ];
    }

}
