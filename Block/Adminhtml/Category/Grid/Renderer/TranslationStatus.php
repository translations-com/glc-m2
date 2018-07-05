<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Category\Grid\Renderer;

use \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use \TransPerfect\GlobalLink\Model\Entity\TranslationStatus as TranslationStatusModel;
use \Magento\Store\Model\StoreManagerInterface;

class TranslationStatus extends AbstractRenderer
{
    /**
     * @var \TransPerfect\GlobalLink\Model\Entity\TranslationStatus
     */
    protected $translationStatus;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    /**
     * constructor
     *
     * @param \Magento\Backend\Block\Context             $context
     * @param array                                      $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \TransPerfect\GlobalLink\Model\Entity\TranslationStatus $translationStatus,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->translationStatus = $translationStatus;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }
    /**
     * Render action
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $adminStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        $defaultStoreId = $this->storeManager->getDefaultStoreView()->getId();
        $storeId = $this->getRequest()->getParam('store');

        $status = $row->getData($this->getColumn()->getIndex());
        if (empty($status)) {
            if (!empty($storeId) && !in_array($storeId, [$adminStoreId, $defaultStoreId])) {
                $status = TranslationStatusModel::STATUS_ENTITY_TRANSLATION_REQUIRED;
            }
        }
        return $this->translationStatus->getOptionLabel($status);
    }
}
