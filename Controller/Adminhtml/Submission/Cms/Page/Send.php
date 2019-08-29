<?php
namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Cms\Page;

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
            return $resultRedirect->setPath('cms/page');
        }

        $data = $this->getRequest()->getPostValue();

        if (!empty($data)) {
            $dueDate = $data['submission']['due_date'];
            $dueDate = $this->_dateTime->gmtTimestamp($dueDate);
            $dueDate = $dueDate + (24*60*60) - 1;

            $formData = $this->getRequest()->getParam('submission');
            foreach ($data['submission']['items'] as $itemId => $itemName) {
                $completedSubmissionExists = $this->checkForCompletedSubmission($itemId, $data['submission']['localize'], Data::CMS_PAGE_TYPE_ID);
                if($completedSubmissionExists){
                    $this->messageManager->addErrorMessage(__('Cannot create submission, a complete submission for this entity with a duplicate locale exists in PD. Please import that submission first.'));
                    return $resultRedirect->setPath('cms/page');
                }
                $formData['id_'.$itemId] = $itemName;
            }

            $queue = $this->_queueFactory->create();
            $queueData = [
                'name' => $data['submission']['name'],
                'submission_instructions' => $data['submission']['instructions'],
                'project_shortcode' => $data['submission']['project'],
                'entity_type_id' => \TransPerfect\GlobalLink\Helper\Data::CMS_PAGE_TYPE_ID,
                'magento_admin_user_requested_by' => $this->_auth->getUser()->getId(),
                'request_date' => $this->_dateTime->gmtTimestamp(),
                'due_date' => $dueDate,
                'priority' => $data['submission']['priority'],
                'include_cms_block_widgets' => $data['submission']['include_cms_block_widgets'],
                'origin_store_id' => $data['submission']['store'],
                'items' => $data['submission']['items'],
                'localizations' => $data['submission']['localize'],
                'confirmation_email' => $data['submission']['confirmation_email'],
            ];
            $queue->setData($queueData);

            try {
                $queue->getResource()->save($queue);
                if($this->logger->isDebugEnabled()) {
                    $this->logger->logAction(Data::CMS_PAGE_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData);
                }
            } catch (\Exception $e) {
                $this->_getSession()->setFormData($formData);
                $this->messageManager->addErrorMessage($e->getMessage());
                if($this->logger->isErrorEnabled()) {
                    $this->logger->logAction(Data::CMS_PAGE_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData, Logger::CRITICAL, $e->getMessage());
                }
                return $resultRedirect->setPath('*/*/create');
            }
            if($this->submitTranslations->isJobLocked() && $this->isAutomaticMode){
                $this->submitError = true;
                $message = "Items saved to translate queue, but could not send to PD. Please run the unlock command and then submit through the CLI.";
                $this->messageManager->addErrorMessage($message);
                if($this->logger->isErrorEnabled()) {
                    $this->logger->logAction(Data::CATALOG_PRODUCT_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData, Logger::CRITICAL, $message);
                }
            }
            else if($this->isAutomaticMode){
                $this->submitTranslations->executeAutomatic($queue);
            }
            if(!$this->submitError) {
                $this->messageManager->addSuccessMessage(__('CMS Pages have been saved to translation queue'));
            }
        }

        return $resultRedirect->setPath('cms/page');
    }
}
