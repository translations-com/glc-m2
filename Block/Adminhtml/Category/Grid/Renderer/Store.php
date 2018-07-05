<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Category\Grid\Renderer;

use \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

class Store extends AbstractRenderer
{

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \TransPerfect\GlobalLink\Helper\Data */
    protected $_helperGlobalLink;

    /**
     * Website constructor.
     *
     * @param \Magento\Backend\Block\Context             $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array                                      $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \TransPerfect\GlobalLink\Helper\Data $helperGlobalLink,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_helperGlobalLink = $helperGlobalLink;
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
        $row->setData($this->getColumn()->getIndex(), $this->getStoreId());
        return $this->_helperGlobalLink->getLocaleLabel(
            $this->_storeManager
                ->getStore($row->getData($this->getColumn()->getIndex()))
                ->getLocale()
        );
    }
}
