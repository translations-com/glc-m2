<?php
namespace TransPerfect\GlobalLink\Controller\Adminhtml\Submission\Category;

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
            return $resultRedirect->setPath('*/category');
        }

        $data = $this->getRequest()->getPostValue();

        if (!empty($data)) {
            $items = $data['submission']['items'];
            $includeSubcategories = $data['submission']['send_with_subcategories'];
            if (!empty($includeSubcategories)) {
                $items = $this->addSubcategories($items);
            }

            $dueDate = $data['submission']['due_date'];
            $dueDate = $this->_dateTime->gmtTimestamp($dueDate);
            $dueDate = $dueDate + (24*60*60) - 1;

            $formData = $this->getRequest()->getParam('submission');
            foreach ($items as $itemId => $itemName) {
                $formData['id_'.$itemId] = $itemName;
            }

            $queue = $this->_queueFactory->create();
            $queueData = [
                'name' => $data['submission']['name'],
                'submission_instructions' => $data['submission']['instructions'],
                'project_shortcode' => $data['submission']['project'],
                'entity_type_id' => \TransPerfect\GlobalLink\Helper\Data::CATALOG_CATEGORY_TYPE_ID,
                'magento_admin_user_requested_by' => $this->_auth->getUser()->getId(),
                'request_date' => $this->_dateTime->gmtTimestamp(),
                'due_date' => $dueDate,
                'priority' => $data['submission']['priority'],
                'origin_store_id' => $data['submission']['store'],
                'items' => $items,
                'localizations' => $data['submission']['localize'],
                'confirmation_email' => $data['submission']['confirmation_email'],
                'include_subcategories' => $includeSubcategories,
            ];
            $queue->setData($queueData);

            try {
                $queue->getResource()->save($queue);
                $this->logger->logAction(Data::CATALOG_CATEGORY_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData);
            } catch (\Exception $e) {
                $this->_session->setFormData($formData);
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->logAction(Data::CATALOG_CATEGORY_TYPE_ID, Logger::SEND_ACTION_TYPE, $queueData, Logger::CRITICAL, $e->getMessage());
                return $resultRedirect->setPath('*/*/create');
            }

            $this->messageManager->addSuccessMessage(__('Categories have been saved to translation queue'));
        }

        return $resultRedirect->setPath('*/category');
    }

    /**
     * Recursively add all subcategories id=>name into given array
     *
     * @param array $allItems
     *
     * @return array
     */
    protected function addSubcategories(array $allItems)
    {
        if (!empty($allItems) && is_array($allItems)) {
            foreach ($allItems as $categoryId => $categoryName) {
                // @var \Magento\Catalog\Api\Data\CategoryInterface
                $category = $this->categoryRepository->get($categoryId);
                $recursive = true;
                $subCatIds = $category->getResource()->getChildren($category, $recursive);

                $categories = $this->categoryCollectionFactory->create();
                $categories->addFieldToFilter(
                    'entity_id',
                    ['in' => $subCatIds]
                );
                $categories->addFieldToSelect('name');

                foreach ($categories as $cat) {
                    $allItems[$cat->getId()] = $cat->getName();
                }
            }
        }
        return $allItems;
    }
}
