<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Block;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Field
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Block
 */
class Field extends Container
{
    protected $_controller;
    protected $_blockGroup;
    protected $_headerText;
    /**
     * Initialize object state with incoming parameters
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_controller = 'adminhtml_config_cms_block';
        $this->_blockGroup = 'TransPerfect_GlobalLink';
        $this->_headerText = __('CMS Block Configuration');
    }
    /**
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/config_cms_block_field/add');
    }
}
