<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Config\Category;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Attribute
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Config\Category
 */
class Attribute extends Container
{

    /**
     * Initialize object state with incoming parameters
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_controller = 'adminhtml_config_category';
        $this->_blockGroup = 'TransPerfect_GlobalLink';
        $this->_headerText = __('Customer Attributes Configuration');
        $this->removeButton('add');
    }
}
