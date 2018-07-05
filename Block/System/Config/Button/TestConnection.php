<?php
namespace TransPerfect\GlobalLink\Block\System\Config\Button;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class TestConnection
 *
 * @package TransPerfect\GlobalLink\Block\System\Config\Button
 */
class TestConnection extends Field
{
    /**
     * @var string
     */
    protected $_template = 'TransPerfect_GlobalLink::system/config/button/test_connection.phtml';

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for test of connection
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('translations/system_config/testConnection');
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'test_connection',
                'label' => __('Test Connection'),
            ]
        );

        return $button->toHtml();
    }
}
