<?php
namespace TransPerfect\GlobalLink\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use TransPerfect\GlobalLink\Model\ResourceModel\Field\CollectionFactory as FieldCollectionFactory;
use TransPerfect\GlobalLink\Helper\Data as HelperData;

/**
 * Class CheckFields
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\System\Config
 */
class CheckFields extends Action
{

    protected $resultJsonFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $loggerInterface;

    /**
     * Field collection factory
     *
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\field\CollectionFactory
     */
    protected $fieldCollectionFactory;

    /**
     * constructor
     *
     * @param \Magento\Backend\App\Action\Context                                  $context
     * @param \Magento\Framework\Controller\Result\JsonFactory                     $resultJsonFactory
     * @param \Psr\Log\LoggerInterface                                             $loggerInterface
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Field\CollectionFactory $fieldCollectionFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LoggerInterface $loggerInterface,
        FieldCollectionFactory $fieldCollectionFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->loggerInterface = $loggerInterface;
        $this->fieldCollectionFactory = $fieldCollectionFactory;
        parent::__construct($context);
    }

    /**
     * check if entity has fields set as enabled for translation
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = [
            'success' => false,
            'fieldsCount' => 0,
            'errorMessage' => 'Something went wrong',
        ];
        $entityTypeId = $this->getRequest()->getParam('entityTypeId');
        try {
            if (in_array(
                $entityTypeId,
                [HelperData::CMS_BLOCK_TYPE_ID, HelperData::CMS_PAGE_TYPE_ID]
            )) {
                $fields = $this->fieldCollectionFactory->create();
                $fields->addFieldToFilter('object_type', $entityTypeId);
                $fields->addFieldToFilter('include_in_translation', 1);
                $result['success'] = true;
                $result['fieldsCount'] = count($fields);
            } elseif (in_array(
                $entityTypeId,
                [HelperData::CATALOG_PRODUCT_TYPE_ID]
            )) {
            }
        } catch (\Exception $e) {
            $this->loggerInterface->critical($e);
            $result['errorMessage'] = $e->getMessage();
            $this->logger->logAction($entityTypeId, Logger::FORM_ACTION_TYPE, $result, Logger::CRITICAL, $e->getMessage());
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }

    /*
     * Check permission via ACL resource
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TransPerfect_GlobalLink::management');
    }
}
