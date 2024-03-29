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

class TranslateNewProducts implements \Magento\Framework\Event\ObserverInterface
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
    protected $isAutomaticMode;
    protected $submitTranslations;

    public function __construct(
        \TransPerfect\GlobalLink\Helper\Ui\Logger $logger,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Store\Api\StoreRepositoryInterface $repository,
        \TransPerfect\GlobalLink\Model\QueueFactory $queueFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \TransPerfect\GlobalLink\Cron\SubmitTranslations $submitTranslations
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
        $this->submitTranslations = $submitTranslations;
        if ($this->scopeConfig->getValue('globallink/general/automation') == 1) {
            $this->isAutomaticMode = true;
        } else {
            $this->isAutomaticMode = false;
        }
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $useAutomation = $this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_products/translate_new_products', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $product = $observer->getEvent()->getProduct();
        if ($product->isObjectNew() && $useAutomation) {

            $stores = $this->storeRepository->getList();
            $targetStores = [];
            $sourceLocale = $this->helper->getStoreIdFromLocale($this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_products/tnp_source_locale', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))[0];
            $shortCode = $this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_products/tnp_project', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $confirmationEmail = $this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_products/tnp_confirmation_email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $includeOptions = $this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_products/tnp_include_options', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $numberOfDays = $this->scopeConfig->getValue('globallink_translate_new_entities/translate_new_products/tnp_number_of_days', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $targetLocales = $this->helper->getConfiguredTargetLanguagesBySource($shortCode, $this->helper->getPdLocaleIsoCodeByStoreId($sourceLocale)[0]);
            foreach ($stores as $store) {
                if ($store->getData("store_id") != $sourceLocale && in_array($store->getData('locale'), $targetLocales)) {
                    $targetStores[] = $store->getId();
                }
            }
            $queue = $this->queueFactory->create();
            $queueData = [
                'name' => 'Auto Submission: ' .  $product->getName(),
                'submission_instructions' => '',
                'project_shortcode' => $shortCode,
                'entity_type_id' => $this->helper::CATALOG_PRODUCT_TYPE_ID,
                'magento_admin_user_requested_by' => $this->_auth->getUser()->getId(),
                'request_date' => $this->_dateTime->gmtTimestamp(),
                'due_date' => ($this->_dateTime->gmtTimestamp() + (24*60*60*$numberOfDays) - 1),
                'include_associated_and_parent_categories' => 0,
                'priority' => "0",
                'origin_store_id' => $sourceLocale,
                'items' => [$product->getId() => $product->getName()],
                'localizations' => $targetStores,
                'confirmation_email' => $confirmationEmail,
                'refresh_nontranslatable_fields' => 0,
                'include_options' => $includeOptions
            ];
            $queue->setData($queueData);
            if(empty($targetLocales)){
                $message = 'Cannot create translation job, no language directions are configured for this source locale.';
                $this->messageManager->addErrorMessage($message);
                if ($this->logger->isErrorEnabled()) {
                    $this->logger->logAction(Data::CATALOG_PRODUCT_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData, Logger::CRITICAL, $message);
                }
            } else {
                try {
                    $queue->getResource()->save($queue);
                    if ($this->logger->isDebugEnabled()) {
                        $this->logger->logAction(Data::CATALOG_PRODUCT_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData);
                    }
                    if ($this->submitTranslations->isJobLocked() && $this->isAutomaticMode) {
                        $message = "Items saved to translate queue, but could not send to PD. Please run the unlock command and then submit through the CLI.";
                        $this->messageManager->addErrorMessage($message);
                        if ($this->logger->isErrorEnabled()) {
                            $this->logger->logAction(Data::CATALOG_PRODUCT_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData, Logger::CRITICAL, $message);
                        }
                    } elseif ($this->isAutomaticMode) {
                        $this->messageManager->addSuccessMessage(__('Product has been sent for translation.'));
                        $this->submitTranslations->executeAutomatic($queue);
                    } else{
                        $this->messageManager->addSuccessMessage(__('Product has been saved to the translate queue'));
                    }
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                    if ($this->logger->isErrorEnabled()) {
                        $this->logger->logAction(Data::CATALOG_PRODUCT_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData, Logger::CRITICAL, $e->getMessage());
                    }
                }
            }
        }


    }
}
