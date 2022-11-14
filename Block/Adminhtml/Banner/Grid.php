<?php
/**
 * User: jgriffin
 * Date: 10/9/2019
 * Time: 4:34 PM
 */
namespace TransPerfect\GlobalLink\Block\Adminhtml\Banner;

use \Magento\Banner\Block\Adminhtml\Banner\Grid as BannerGrid;

class Grid extends BannerGrid
{
    /**
     * Banner resource collection factory
     *
     * @var \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory
     */
    protected $_bannerColFactory = null;

    /**
     * Banner config
     *
     * @var \Magento\Banner\Model\Config
     */
    protected $_bannerConfig = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory
     * @param \Magento\Banner\Model\Config $bannerConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory,
        \Magento\Banner\Model\Config $bannerConfig,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $bannerColFactory, $bannerConfig, $data);
        $this->_bannerColFactory = $bannerColFactory;
        $this->_bannerConfig = $bannerConfig;
    }
    /**
     * Prepare mass action options for this grid
     *
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('banner_id');
        $this->getMassactionBlock()->setFormFieldName('banner');


        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl('adminhtml/*/massDelete'),
                'confirm' => __('Are you sure you want to delete these dynamic blocks?')
            ]
        );

        $this->getMassactionBlock()->addItem(
            'translate',
            [
                'label' => __('Send for Translation'),
                'url' => $this->getUrl('translations/submission_banner/create')
            ]
        );
        return $this;
    }
}
