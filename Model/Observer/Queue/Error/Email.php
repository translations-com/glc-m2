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

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $recipient = explode(',', $this->scopeConfig->getValue(self::ERROR_RECIPIENT_EMAIL_XPATH));
        $enabled = (bool)$this->scopeConfig->getValue(self::ERROR_EMAIL_ENABLE_XPATH);
        $firstRecipient = $recipient[count($recipient)-1];
        if ($enabled && !empty($firstRecipient)) {
            /** @var \TransPerfect\GlobalLink\Model\Queue[] $queues */
            $queues = $observer->getQueues();
            foreach ($queues as $queue) {
                if ($queue->hasQueueErrors()) {
                    $messages = implode(PHP_EOL, $queue->getQueueErrors());
                    /*if (empty($messages)) {
                        continue;
                    }*/
                    try {
                        $exception_file_path = $this->directoryList->getPath('log') . '/exception.log';
                        if (file_exists($exception_file_path)) {
                            $exception_file = file_get_contents($exception_file_path);
                        } else {
                            $exception_file = null;
                        }

                        $sub_name = $queue->getName();
                        $request_date = $queue->getData('request_date');
                        $due_date = $queue->getData('due_date');
                        $source_locale = $queue->getData('source_locale');
                        $userId = $queue->getData('magento_admin_user_requested_by');
                        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
                        $sender = [
                            'name'  => $this->scopeConfig->getValue(self::STORE_NAME_XPATH, $storeScope),
                            'email' => $this->scopeConfig->getValue(self::STORE_MAIL_XPATH, $storeScope),
                        ];
                        /** @var \Magento\Framework\Mail\Transport $transport */
                        $transport = $this->transportBuilder
                            ->setTemplateIdentifier('translations_email_error_translation')
                            ->setTemplateOptions([
                                    'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                                ])->setTemplateVars(['messages' => $messages, 'sub_name' => $sub_name, 'request_date' => $request_date, 'due_date' => $due_date, 'source_locale' => $source_locale, 'userId' => $userId])
                            ->setFrom($sender)
                            ->addTo($recipient)
                            ->addAttachment($exception_file, \Zend_Mime::TYPE_OCTETSTREAM, \Zend_Mime::DISPOSITION_ATTACHMENT, \Zend_Mime::ENCODING_BASE64, 'exception.log')
                            ->getTransport();
                        $transport->sendMessage();
                    } catch (\Exception $e) {
                        $this->bgLogger->error($e->getMessage());
                        throw $e;
                    }
                }
            }
        }
    }
}
