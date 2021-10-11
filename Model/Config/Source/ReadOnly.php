<?php
namespace TransPerfect\GlobalLink\Model\Config\Source;

use Magento\Framework\Data\Form\Element\AbstractElement;

class ReadOnly extends \Magento\Config\Block\System\Config\Form\Field{

    protected function _getElementHtml(AbstractElement $element){
        $element->setData('readonly', 1);
        return $element->getElementHtml();

    }
}
