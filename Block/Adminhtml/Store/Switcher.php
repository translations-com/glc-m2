<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Store;

use Magento\Backend\Block\Store\Switcher as BaseSwitcher;
use TransPerfect\GlobalLink;
use Magento\Store\Model\Store;
use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\WebsiteFactory;
use Magento\Store\Model\StoreFactory;
use TransPerfect\GlobalLink\Helper\Data as TranHelper;

class Switcher extends BaseSwitcher
{
    protected $helper;

    protected $_template = 'TransPerfect_GlobalLink::store/switcher.phtml';

    public function __construct(
        Context $context,
        WebsiteFactory $websiteFactory,
        GroupFactory $storeGroupFactory,
        StoreFactory $storeFactory,
        TranHelper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct(
            $context,
            $websiteFactory,
            $storeGroupFactory,
            $storeFactory,
            $data
        );
    }

    public function getLocalizeLabel(Store $store)
    {
        return $this->helper->getLocaleLabel($store->getLocale());
    }
}
