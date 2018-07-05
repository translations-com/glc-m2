<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Category\Grid\Renderer;

use \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

/**
 * Class Website
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Category\Grid\Renderer
 */
class Website extends AbstractRenderer
{

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

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
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
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
        $row->setData($this->getColumn()->getIndex(), $this->_storeManager->getStore($row->getStoreId())->getWebsiteId());
        if ((int)$row->getData($this->getColumn()->getIndex()) === 0) {
            return __('All Websites');
        }
        $website = $this->_storeManager->getWebsite($row->getData($this->getColumn()->getIndex()));
        $websiteName = $website->getName();
        return $websiteName;
    }
}
