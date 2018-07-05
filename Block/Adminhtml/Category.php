<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Category
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml
 */
class Category extends Container
{
    /**
     * Initialize object state with incoming parameters
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_controller = 'adminhtml_category';
        $this->_blockGroup = 'TransPerfect_GlobalLink';
        $this->_headerText = __('Categories');
        $this->removeButton('add');
    }
}
