<?php
/**
 * Created by PhpStorm.
 * User: jgriffin
 * Date: 10/9/2019
 * Time: 11:35 AM
 */

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config\Review\Field;

use Magento\Framework\View\Result\PageFactory;
use TransPerfect\GlobalLink\Controller\Adminhtml\Config\Review\Field as Action;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Save
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Config\Review\Field
 */
class Save extends Action
{
    protected $fieldFactory;

    protected $resource;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context         $context
     * @param \Magento\Framework\View\Result\PageFactory  $pageFactory
     * @param \TransPerfect\GlobalLink\Helper\Data        $helper
     * @param \TransPerfect\GlobalLink\Helper\Ui\Logger   $logger
     * @param \TransPerfect\GlobalLink\Model\FieldFactory $fieldFactory
     * @param \Magento\Framework\App\ResourceConnection   $resource
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \TransPerfect\GlobalLink\Helper\Ui\Logger $logger,
        \TransPerfect\GlobalLink\Model\FieldFactory $fieldFactory,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct($context, $pageFactory, $helper, $logger);
        $this->fieldFactory = $fieldFactory;
        $this->resource = $resource;
    }

    public function execute()
    {
        if ($this->_formKeyValidator->validate($this->getRequest())) {
            $fieldName = $this->getRequest()->getParam('field_name');
            $table = $this->resource->getTableName('review_detail');
            $fields = $this->resource->getConnection()->describeTable($table);
            try {
                if (!in_array($fieldName, array_keys($fields))) {
                    throw new \Exception(__('Field is missing from database. New field cannot be added.'));
                }

                $field = $this->fieldFactory->create();
                if ($field->isFieldExist(Data::PRODUCT_REVIEW_ID, $fieldName)) {
                    throw new \Exception(__('Configuration field already exist'));
                }

                $field->setData($this->getRequest()->getParams());
                $field->getResource()->save($field);
                $this->messageManager->addSuccessMessage(__('Configurations saved successfully.'));
                if($this->logger->isDebugEnabled()) {
                    $this->logger->logAction(Data::PRODUCT_REVIEW_ID, Logger::CONFIG_ADD_ACTION_TYPE, $this->getRequest()->getParams());
                }
            } catch (\Exception $e) {
                if($this->logger->isErrorEnabled()) {
                    $this->logger->logAction(Data::PRODUCT_REVIEW_ID, Logger::CONFIG_ADD_ACTION_TYPE, $this->getRequest()->getParams(), Logger::CRITICAL, $e->getMessage());
                    $this->messageManager->addErrorMessage($e->getMessage());
                }
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/config_review/field');
        return $resultRedirect;
    }
}
