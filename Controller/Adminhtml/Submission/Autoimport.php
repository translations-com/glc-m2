<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Justin Griffin <jgriffin@translations.com>
 */

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission;

use TransPerfect\GlobalLink\Controller\Adminhtml\Submission;

/**
 * Class Autoimport
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission
 */
class Autoimport extends Submission
{

    const STATUS_FINISHED = 2;
    /**
     * controller main method
     *
     * @return \Magento\Framework\Controller\ResultInterface)
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        //Runs receive translations command to import
        $this->receiveTranslations->executeAutomatic();
        $automaticItemIds = $this->receiveTranslations->getAutomaticItemIds();
        //Then imports any translations that are ready to go
        $items = $this->itemCollectionFactory->create();
        $items->addFieldToFilter(
            'id',
            ['in' => $automaticItemIds]
        );
        $items->addFieldToFilter(
            'status_id',
            ['in' => [$this::STATUS_FINISHED]]
        );
        $itemsTotal = count($items);

        if (!$itemsTotal) {
            $this->messageManager->addError(__('No submissions were ready to be applied.'));
            return $this->resultRedirect->setPath('*/*/index');
        }
        $this->registry->register('queues', []);
        try {
            $items->walk('applyTranslation');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $start = microtime(true);
        $this->helper->reIndexing();
        $this->_eventManager->dispatch('transperfect_globallink_apply_translation_after', ['queues' => $this->registry->registry('queues')]);
        if(in_array($this->helper::LOGGING_LEVEL_INFO, $this->helper->loggingLevels)) {
            $logData = [
                'message' => "Reindex and redirect duration: " . (microtime(true) - $start) . " seconds",
            ];
            $this->bgLogger->info($this->bgLogger->bgLogMessage($logData));
        }
        $this->messageManager->addSuccessMessage(__('Submissions were successfully received and applied to target stores.'));
        return $resultRedirect->setPath('*/*/index');
    }
}