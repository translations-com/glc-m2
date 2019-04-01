<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Eugene Monakov <emonakov@robofirm.com>
 */

namespace TransPerfect\GlobalLink\Model\Observer\Queue\Error;

use Magento\Framework\Event\Observer;
use TransPerfect\GlobalLink\Model\Observer\Queue\Email as BaseEmail;

/**
 * Class Email
 *
 * @package TransPerfect\GlobalLink\Model\Observer\Queue\Error
 */
class Email extends BaseEmail
{
    const ERROR_RECIPIENT_EMAIL_XPATH = 'globallink/general/email_address';
    const ERROR_EMAIL_ENABLE_XPATH = 'globallink/general/email_errors';
    const PD_USERNAME = 'globallink/connection/username';

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $recipient = array_map('trim', explode(',', $this->scopeConfig->getValue(self::ERROR_RECIPIENT_EMAIL_XPATH)));
        $username = $this->scopeConfig->getValue(self::PD_USERNAME);
        $enabled = (bool)$this->scopeConfig->getValue(self::ERROR_EMAIL_ENABLE_XPATH);
        $firstRecipient = $recipient[count($recipient)-1];
        if ($enabled && !empty($firstRecipient)) {
            /** @var \TransPerfect\GlobalLink\Model\Queue[] $queues */
            $queues = $observer->getQueues();
            foreach ($queues as $queue) {
                if ($queue->hasQueueErrors()) {
                    $itemIds = array();
                    $itemCollection =null;
                    foreach($queue->getItems() as $currentItem){
                        $itemIds[] = $currentItem;
                    }
                    $itemCollection = $this->itemCollectionFactory->create();
                    $itemCollection->addFieldToFilter(
                        'id',
                        ['in' => $itemIds]
                    );
                    $targetLocales = [];
                    $submission_ticket = "Not available";
                    $document_tickets = "Not available";
                    $target_locale = "Not available";
                    if ($itemCollection != null) {
                        $document_tickets = [];
                        $submission_tickets = [];
                        foreach ($itemCollection as $item) {
                            $targetLocale = $item->getPdLocaleIsoCode();
                            $currentSubmissionTicket = $item->getSubmissionTicket();
                            $currentDocTicket = $item->getDocumentTicket();
                            if (!in_array($targetLocale, $targetLocales)) {
                                $targetLocales[] = $targetLocale;
                            }
                            if (!in_array($currentDocTicket, $document_tickets)) {
                                $document_tickets[] = $currentDocTicket;
                            }
                            if (!in_array($currentSubmissionTicket, $submission_tickets)) {
                                $submission_tickets[] = $currentSubmissionTicket;
                            }
                        }
                        $target_locale = implode(', ', $targetLocales);
                        $document_tickets = implode(', ', $document_tickets);
                        $submission_ticket = implode(', ', $submission_tickets);
                    }
                    $messages = implode(PHP_EOL, $queue->getQueueErrors());
                    /*if (empty($messages)) {
                        continue;
                    }*/
                    try {
                        $exception_file_path = $this->directoryList->getPath('log') . '/globallink_api_request.log';
                        if (file_exists($exception_file_path)) {
                            $exception_file = file_get_contents($exception_file_path);
                        } else {
                            $exception_file = null;
                        }

                        $sub_name = $queue->getName();
                        $request_date = $queue->getData('request_date');
                        $receive_date = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
                        $source_locale = $queue->getData('source_locale');
                        $userId = $queue->getData('magento_admin_user_requested_by');
                        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
                        $sender = [
                            'name'  => $this->scopeConfig->getValue(self::STORE_NAME_XPATH, $storeScope),
                            'email' => $this->scopeConfig->getValue(self::STORE_MAIL_XPATH, $storeScope),
                        ];
                        $this->transportBuilder
                            ->setTemplateIdentifier('translations_email_error_translation')
                            ->setTemplateOptions([
                                'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                                'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                            ])
                            ->setTemplateVars(['messages' => $messages, 'queue' => $queue, 'submission_ticket' => $submission_ticket,  'username' => $username, 'document_tickets' => $document_tickets, 'source_locale' => $source_locale, 'target_locale' => $target_locale, 'request_date' => $request_date, 'receive_date' => $receive_date])
                            ->addAttachment($exception_file, \Zend_Mime::TYPE_OCTETSTREAM, \Zend_Mime::DISPOSITION_ATTACHMENT, \Zend_Mime::ENCODING_BASE64, 'globallink_api_request.log')
                            ->setFrom($sender)
                            ->addTo($recipient)
                            ->getTransport()
                            ->sendMessage();
                    } catch (\Exception $e) {
                        $this->bgLogger->error($e->getMessage());
                        throw $e;
                    }
                }
            }
        }
    }
}
