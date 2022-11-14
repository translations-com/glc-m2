<?php
/**
 * User: jgriffin
 * Date: 10/9/2019
 * Time: 4:34 PM
 */
namespace TransPerfect\GlobalLink\Block\Adminhtml\Review;

use \Magento\Review\Block\Adminhtml\Grid as ReviewGrid;

class Grid extends ReviewGrid
{
    /**
     * @var \TransPerfect\GlobalLink\Model\Entity\TranslationStatus
     */
    protected $translationStatus;
    /**
     * Review action pager
     *
     * @var \Magento\Review\Helper\Action\Pager
     */
    protected $_reviewActionPager = null;

    /**
     * Review data
     *
     * @var \Magento\Review\Helper\Data
     */
    protected $_reviewData = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Review collection model factory
     *
     * @var \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory
     */
    protected $_productsFactory;

    /**
     * Review model factory
     *
     * @var \Magento\Review\Model\ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     * @param \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory $productsFactory
     * @param \Magento\Review\Helper\Data $reviewData
     * @param \Magento\Review\Helper\Action\Pager $reviewActionPager
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \TransPerfect\GlobalLink\Model\Entity\TranslationStatus $translationStatus
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory $productsFactory,
        \Magento\Review\Helper\Data $reviewData,
        \Magento\Review\Helper\Action\Pager $reviewActionPager,
        \Magento\Framework\Registry $coreRegistry,
        \TransPerfect\GlobalLink\Model\Entity\TranslationStatus $translationStatus,
        array $data = []
    ) {
        $this->_productsFactory = $productsFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_reviewData = $reviewData;
        $this->_reviewActionPager = $reviewActionPager;
        $this->_reviewFactory = $reviewFactory;
        $this->translationStatus = $translationStatus;
        parent::__construct($context, $backendHelper, $reviewFactory, $productsFactory, $reviewData, $reviewActionPager, $coreRegistry, $data);
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Review\Block\Adminhtml\Grid
     */
    protected function _prepareCollection()
    {
        return parent::_prepareCollection();
    }
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        return $this;
    }

    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('review_id');
        $this->setMassactionIdFilter('rt.review_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);
        $this->getMassactionBlock()->setFormFieldName('reviews');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl(
                    '*/*/massDelete',
                    ['ret' => $this->_coreRegistry->registry('usePendingFilter') ? 'pending' : 'index']
                ),
                'confirm' => __('Are you sure?')
            ]
        );

        $statuses = $this->_reviewData->getReviewStatusesOptionArray();
        array_unshift($statuses, ['label' => '', 'value' => '']);
        $this->getMassactionBlock()->addItem(
            'update_status',
            [
                'label' => __('Update Status'),
                'url' => $this->getUrl(
                    '*/*/massUpdateStatus',
                    ['ret' => $this->_coreRegistry->registry('usePendingFilter') ? 'pending' : 'index']
                ),
                'additional' => [
                    'status' => [
                        'name' => 'status',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => __('Status'),
                        'values' => $statuses,
                    ],
                ]
            ]
        );
        $this->getMassactionBlock()->addItem(
            'translate',
            [
                'label' => __('Send for Translation'),
                'url' => $this->getUrl('translations/submission_review/create')
            ]
        );
    }
}
