<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Justin Griffin jgriffin@translations.com
 */

namespace TransPerfect\GlobalLink\Model\Observer;

use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

class TranslateNewAttributes implements \Magento\Framework\Event\ObserverInterface
{
    protected $logger;
    protected $productRepository;
    protected $helper;
    protected $storeRepository;
    protected $queueFactory;
    private $_auth;
    private $_dateTime;
    protected $scopeConfig;
    protected $messageManager;

    public function __construct(
        \TransPerfect\GlobalLink\Helper\Ui\Logger $logger,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Store\Api\StoreRepositoryInterface $repository,
        \TransPerfect\GlobalLink\Model\QueueFactory $queueFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->helper = $helper;
        $this->storeRepository = $repository;
        $this->queueFactory = $queueFactory;
        $this->_auth = $authSession;
        $this->_dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $useAutomation = $this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_product_attributes/translate_new_product_attributes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $attribute = $observer->getEvent()->getAttribute();
        if ($attribute->isObjectNew() && $useAutomation) {

            $stores = $this->storeRepository->getList();
            $targetStores = [];
            $sourceLocale = $this->helper->getStoreIdFromLocale($this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_product_attributes/tnpa_source_locale', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))[0];
            foreach ($stores as $store) {
                if ($store->getData("store_id") != $sourceLocale && $store->getData('locale') != '') {
                    $targetStores[] = $store->getId();
                }
            }
            $shortCode = $this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_product_attributes/tnpa_project', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $confirmationEmail = $this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_product_attributes/tnpa_confirmation_email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $includeOptions = $this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_product_attributes/tnpa_include_options', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $numberOfDays = $this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_product_attributes/tnpa_number_of_days', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $queue = $this->queueFactory->create();
            $queueData = [
                'name' => 'Auto Submission: ' .  $attribute->getName(),
                'submission_instructions' => '',
                'project_shortcode' => $shortCode,
                'entity_type_id' => $this->helper::PRODUCT_ATTRIBUTE_TYPE_ID,
                'magento_admin_user_requested_by' => $this->_auth->getUser()->getId(),
                'request_date' => $this->_dateTime->gmtTimestamp(),
                'due_date' => ($this->_dateTime->gmtTimestamp() + (24*60*60*$numberOfDays) - 1),
                'include_associated_and_parent_categories' => 0,
                'priority' => "0",
                'origin_store_id' => $sourceLocale,
                'items' => [$attribute->getId() => $attribute->getName()],
                'localizations' => $targetStores,
                'confirmation_email' => $confirmationEmail,
                'refresh_nontranslatable_fields' => 0,
                'include_options' => $includeOptions
            ];
            $queue->setData($queueData);
            try {
                $queue->getResource()->save($queue);
                $this->messageManager->addSuccessMessage(__('Product attribute has been saved to the translate queue'));
                if ($this->logger->isDebugEnabled()) {
                    $this->logger->logAction(Data::PRODUCT_ATTRIBUTE_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData);
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                if ($this->logger->isErrorEnabled()) {
                    $this->logger->logAction(Data::PRODUCT_ATTRIBUTE_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData, Logger::CRITICAL, $e->getMessage());
                }
            }
        }


    }
}
