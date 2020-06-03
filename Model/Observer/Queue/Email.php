<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Eugene Monakov <emonakov@robofirm.com>
 */

namespace TransPerfect\GlobalLink\Model\Observer\Queue;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use TransPerfect\GlobalLink\Logger\BgTask\Logger;
use \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory as ItemCollectionFactory;

/**
 * Class Email
 *
 * @package TransPerfect\GlobalLink\Model\Observer\Queue
 */
class Email implements ObserverInterface
{
    const STORE_NAME_XPATH = 'trans_email/ident_general/name';
    const STORE_MAIL_XPATH = 'trans_email/ident_general/email';
    const PD_USERNAME = 'globallink/connection/username';

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \TransPerfect\GlobalLink\Logger\BgTask\Logger
     */
    protected $bgLogger;

    
    protected $directoryList;

    protected $itemCollectionFactory;
    protected $messageManager;
    /**
     * Email constructor.
     *
     * @param \Magento\Framework\Mail\Template\TransportBuilder  $transportBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param \Magento\Framework\Escaper                         $escaper
     * @param \TransPerfect\GlobalLink\Logger\BgTask\Logger      $bgLogger
     */
    public function __construct(
        \TransPerfect\GlobalLink\Magento\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Escaper $escaper,
        Logger $bgLogger,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list,
        ItemCollectionFactory $itemCollectionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->escaper = $escaper;
        $this->bgLogger = $bgLogger;
        $this->directoryList = $directory_list;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * {@inheritdoc}
     */

    /**
     * Note to self Justin 2/1/18: Use DI to include ItemCollectionFactory then populate item data that way in order to send more correct emails.
     */
    public function execute(Observer $observer)
    {
        /** @var \TransPerfect\GlobalLink\Model\Queue[] $queues */
        $queues = array_filter($observer->getQueues(), function ($queue) {
            return $queue->getProcessed() && $queue->hasConfirmationEmail();
        });
        $username = $this->scopeConfig->getValue(self::PD_USERNAME);
        $itemCollection = $observer->getItems();
        foreach ($queues as $queue) {
            if(!$queue->hasQueueErrors()) {
                $targetLocales = [];
                $submission_ticket = null;
                $document_tickets = [];
                $request_date = $queue->getData('request_date');
                $receive_date = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
                $source_locale = $queue->getData('source_locale');
                $recipient = explode(',', $queue->getConfirmationEmail());
                $queueItems = $queue->getItems();
                $items = $this->itemCollectionFactory->create();
                $items->addFieldToFilter(
                    'id',
                    ['in' => $queueItems]
                );
                if ($items != null) {
                    foreach ($items as $item) {
                        $targetLocale = $item->getPdLocaleIsoCode();
                        $submission_ticket = $item->getSubmissionTicket();
                        $currentDocTicket = $item->getDocumentTicket();
                        if (!in_array($targetLocale, $targetLocales)) {
                            $targetLocales[] = $targetLocale;
                        }
                        if (!in_array($currentDocTicket, $document_tickets)) {
                            $document_tickets[] = $currentDocTicket;
                        }
                    }
                    $target_locale = implode(', ', $targetLocales);
                    $document_tickets = implode(', ', $document_tickets);
                }
                $firstRecipient = $recipient[count($recipient) - 1];
                if (empty($firstRecipient)) {
                    continue;
                }
                try {
                    $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
                    $sender = [
                        'name' => $this->scopeConfig->getValue(self::STORE_NAME_XPATH, $storeScope),
                        'email' => $this->scopeConfig->getValue(self::STORE_MAIL_XPATH, $storeScope),
                    ];
                    /** @var \Magento\Framework\Mail\Transport $transport */

                    $transport = $this->transportBuilder
                        ->setTemplateIdentifier('translations_email_receive_translation')
                        ->setTemplateOptions([
                            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                            'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,])
                        ->setTemplateVars(['queue' => $queue, 'submission_ticket' => $submission_ticket, 'username' => $username, 'document_tickets' => $document_tickets, 'source_locale' => $source_locale, 'target_locale' => $target_locale, 'request_date' => $request_date, 'receive_date' => $receive_date])
                        ->setFrom($sender)
                        ->addTo($recipient)
                        ->getTransport()
                        ->sendMessage();
                }
                catch (\Exception $e) {
                    $this->bgLogger->error($e->getMessage(), $e->getTrace());
                }
            }
        }
    }
}
