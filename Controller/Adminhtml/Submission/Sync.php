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
 * Class Sync
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Submission
 */
class Sync extends Submission
{
    /**
     * controller main method
     *
     * @return \Magento\Framework\Controller\ResultInterface)
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->receiveTranslations->executeAutomatic();
        $this->messageManager->addSuccessMessage(__('Updated status of submissions successfully.'));
        return $resultRedirect->setPath('*/*/index');
    }
}
