<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Customer\Attribute\Edit\Button;


class Submission extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_customerAttribute;
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    /**
     * App Emulator
     *
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $_emulation;
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Store\Model\App\Emulation $emulation
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Attribute $customerAttribute,
        \Magento\Store\Model\App\Emulation $emulation,
        array $data = []
    )
    {
        $this->_coreRegistry = $registry;
        $this->_customerAttribute = $customerAttribute;
        $this->_request = $context->getRequest();
        $this->_emulation = $emulation;
        parent::__construct($context, $data);
    }
    /**
     * Block constructor adds buttons
     *
     */
    protected function _construct()
    {
        $this->addButton(
            'send_for_translation',
            $this->getButtonData()
        );
        parent::_construct();
    }
    /**
     * Return button attributes array
     */
    public function getButtonData()
    {
        $attributeId = $this->_coreRegistry->registry('entity_attribute')->getData('attribute_id');
        $url = $this->getUrl('translations/submission_customer_attribute/create', ['id' => $attributeId]);

        /*if ($this->context->getRequestParam('store')) {
            $url = $this->_urlBuilder->getUrl('translations/submission_product/create', ['id' => $product->getId(), 'store' => $this->context->getRequestParam('store')]);
        } else {
            $url = $this->_urlBuilder->getUrl('translations/submission_product/create', ['id' => $product->getId()]);
        }*/

        return [
            'label' => __('Send for Translation'),
            'class' => 'action-secondary',
            'on_click' => 'location.href = "'.$url.'"',
            'sort_order' => 10
        ];
    }
}