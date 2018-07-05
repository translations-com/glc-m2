<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config\Cms\Page\Field;

use Magento\Framework\View\Result\PageFactory;
use TransPerfect\GlobalLink\Controller\Adminhtml\Config\Cms\Page\Field as Action;
use TransPerfect\GlobalLink\Helper\Data;
use TransPerfect\GlobalLink\Helper\Ui\Logger;

/**
 * Class Save
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Config\Cms\Page\Field
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

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Exception
     */
    public function execute()
    {
        if ($this->_formKeyValidator->validate($this->getRequest())) {
            $fieldName = $this->getRequest()->getParam('field_name');
            $table = $this->resource->getTableName('cms_page');
            $fields = $this->resource->getConnection()->describeTable($table);

            try {
                if (!in_array($fieldName, array_keys($fields))) {
                    throw new \Exception(__('Field is missing from database. New field cannot be added.'));
                }

                $field = $this->fieldFactory->create();
                if ($field->isFieldExist(Data::CMS_PAGE_TYPE_ID, $fieldName)) {
                    throw new \Exception(__('Configuration field already exist'));
                }

                $field->setData($this->getRequest()->getParams());
                $field->save();
                $this->messageManager->addSuccessMessage(__('Configurations saved successfully.'));
                $this->logger->logAction(Data::CMS_PAGE_TYPE_ID, Logger::CONFIG_ADD_ACTION_TYPE, $this->getRequest()->getParams());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->logAction(Data::CMS_PAGE_TYPE_ID, Logger::CONFIG_ADD_ACTION_TYPE, $this->getRequest()->getParams(), Logger::CRITICAL, $e->getMessage());
            }
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/config_cms_page/field');

        return $resultRedirect;
    }
}
