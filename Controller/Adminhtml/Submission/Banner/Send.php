<?php


namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Banner;

use TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Send as BaseSubmission;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Send
 */
class Send extends BaseSubmission
{
    private $submitError = false;
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('adminhtml/banner/index');
        }

        $data = $this->getRequest()->getPostValue();

        if (!empty($data)) {
            $dueDate = $data['submission']['due_date'];
            $dueDate = $this->_dateTime->gmtTimestamp($dueDate);
            $dueDate = $dueDate + (24*60*60) - 1;
            $project = $data['submission']['project'];
            $formData = $this->getRequest()->getParam('submission');
            foreach ($data['submission']['items'] as $itemId => $itemName) {
                $completedSubmissionExists = $this->checkForCompletedSubmission($itemId, $data['submission']['localize'], Data::BANNER_ID);
                if ($completedSubmissionExists && !$this->allowDuplicateSubmissions) {
                    $this->messageManager->addErrorMessage(__('Cannot create submission, a complete submission for this entity with a duplicate locale exists in PD. Please import that submission first.'));
                    return $resultRedirect->setPath('adminhtml/banner/index');
                }
                $formData['id_' . $itemId] = $itemName;
            }
            $customAttributes = $this->helper->getCustomAttributes($formData['project']);
            foreach ($customAttributes as $attribute) {
                if ($attribute->type == 'TEXT') {
                    if ($attribute->mandatory && ($formData['attribute_text'][$project] == "" || empty($formData['attribute_text'][$project]))) {
                        $this->messageManager->addErrorMessage(__('Cannot create submission, one or more mandatory custom attributes was not filled out.'));
                        return $resultRedirect->setPath('adminhtml/banner/index');
                    }
                }
                if ($attribute->type == 'COMBO') {
                    if ($attribute->mandatory && !isset($formData['attribute_combo'])) {
                        $this->messageManager->addErrorMessage(__('Cannot create submission, one or more mandatory custom attributes was not filled out.'));
                        return $resultRedirect->setPath('adminhtml/banner/index');
                    }
                }
            }
            $queue = $this->_queueFactory->create();
            $queueData = [
                'name' => $data['submission']['name'],
                'submission_instructions' => $data['submission']['instructions'],
                'project_shortcode' => $data['submission']['project'],
                'entity_type_id' => \TransPerfect\GlobalLink\Helper\Data::BANNER_ID,
                'magento_admin_user_requested_by' => $this->_auth->getUser()->getId(),
                'request_date' => $this->_dateTime->gmtTimestamp(),
                'due_date' => $dueDate,
                'priority' => $data['submission']['priority'],
                'origin_store_id' => $data['submission']['store'],
                'items' => $data['submission']['items'],
                'localizations' => $data['submission']['localize'],
                'confirmation_email' => $data['submission']['confirmation_email'],
            ];
            $project = $data['submission']['project'];
            if (isset($data['submission']['attribute_text'][$project])) {
                $queueData['attribute_text'] = $data['submission']['attribute_text'][$project];
            }
            if (isset($data['submission']['attribute_combo'][$project])) {
                $queueData['attribute_combo'] = $data['submission']['attribute_combo'][$project];
            }
            $queue->setData($queueData);
            try {
                $queue->save();
                if ($this->logger->isDebugEnabled()) {
                    $this->logger->logAction(Data::BANNER_ID, Logger::SEND_ACTION_TYPE, $queueData);
                }
            } catch (\Exception $e) {
                $this->_getSession()->setFormData($formData);
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->logAction(Data::BANNER_ID, Logger::SEND_ACTION_TYPE, $queueData, Logger::CRITICAL, $e->getMessage());
                return $resultRedirect->setPath('adminhtml/banner/index');
            }
            if ($this->submitTranslations->isJobLocked() && $this->isAutomaticMode) {
                $this->submitError = true;
                $message = "Items saved to translate queue, but could not send to PD. Please run the unlock command and then submit through the CLI.";
                $this->messageManager->addErrorMessage($message);
                if ($this->logger->isErrorEnabled()) {
                    $this->logger->logAction(Data::BANNER_ID, Logger::SEND_ACTION_TYPE, $queueData, Logger::CRITICAL, $message);
                }
            } elseif ($this->isAutomaticMode) {
                $this->submitTranslations->executeAutomatic($queue);
            }
            if (!$this->submitError) {
                $this->messageManager->addSuccessMessage(__('Dynamic blocks have been saved to translation queue'));
            }
        }

        return $resultRedirect->setPath('adminhtml/banner/index');
    }
}
