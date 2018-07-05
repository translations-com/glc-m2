<?php
namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Customer\Attribute;

use TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Send as BaseSubmission;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Send
 */
class Send extends BaseSubmission
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('adminhtml/customer_attribute');
        }

        $data = $this->getRequest()->getPostValue();

        if (!empty($data)) {
            $dueDate = $data['submission']['due_date'];
            $dueDate = $this->_dateTime->gmtTimestamp($dueDate);
            $dueDate = $dueDate + (24*60*60) - 1;

            $formData = $this->getRequest()->getParam('submission');
            foreach ($data['submission']['items'] as $itemId => $itemName) {
                $formData['id_'.$itemId] = $itemName;
            }

            $queue = $this->_queueFactory->create();
            $queueData = [
                'name' => $data['submission']['name'],
                'submission_instructions' => $data['submission']['instructions'],
                'project_shortcode' => $data['submission']['project'],
                'entity_type_id' => \TransPerfect\GlobalLink\Helper\Data::CUSTOMER_ATTRIBUTE_TYPE_ID,
                'magento_admin_user_requested_by' => $this->_auth->getUser()->getId(),
                'request_date' => $this->_dateTime->gmtTimestamp(),
                'due_date' => $dueDate,
                'priority' => $data['submission']['priority'],
                'origin_store_id' => $data['submission']['store'],
                'items' => $data['submission']['items'],
                'localizations' => $data['submission']['localize'],
                'confirmation_email' => $data['submission']['confirmation_email'],
            ];
            $queue->setData($queueData);
            try {
                $queue->save();
                $this->logger->logAction(Data::CUSTOMER_ATTRIBUTE_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData);
            } catch (\Exception $e) {
                $this->_getSession()->setFormData($formData);
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->logAction(Data::CUSTOMER_ATTRIBUTE_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData, Logger::CRITICAL, $e->getMessage());
                return $resultRedirect->setPath('adminhtml/customer_attribute');
            }

            $this->messageManager->addSuccessMessage(__('Customer attributes have been saved to translation queue'));
        }

        return $resultRedirect->setPath('adminhtml/customer_attribute');
    }
}
