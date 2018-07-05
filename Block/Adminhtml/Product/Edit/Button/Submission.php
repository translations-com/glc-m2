<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Product\Edit\Button;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;

/**
 * Class Submission
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Product\Edit\Button
 */
class Submission extends Generic
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\View\Element\UiComponent\Context
     */
    protected $context;

    /**
     * Submission constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\Context $context
     * @param \Magento\Framework\Registry                         $registry
     * @param \Magento\Framework\UrlInterface                     $urlBuilder
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->context = $context;

        parent::__construct($context, $registry);
    }

    /**
     * Add `Send for Translation` button to product edit page
     *
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->_isProductNew()) {
            $product = $this->getProduct();

            if ($this->context->getRequestParam('store')) {
                $url = $this->_urlBuilder->getUrl('translations/submission_product/create', ['id' => $product->getId(), 'store' => $this->context->getRequestParam('store')]);
            } else {
                $url = $this->_urlBuilder->getUrl('translations/submission_product/create', ['id' => $product->getId()]);
            }

            return [
                'label' => __('Send for Translation'),
                'class' => 'action-secondary',
                'on_click' => 'location.href = "'.$url.'"',
                'sort_order' => 10
            ];
        }

        return [];
    }

    /**
     * Check whether new product is being created
     *
     * @return bool
     */
    protected function _isProductNew()
    {
        $product = $this->getProduct();

        return !$product || !$product->getId();
    }
}
