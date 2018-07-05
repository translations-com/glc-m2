<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Page;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Field
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Page
 */
class Field extends Container
{
    /**
     * Initialize object state with incoming parameters
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_controller = 'adminhtml_config_cms_page';
        $this->_blockGroup = 'TransPerfect_GlobalLink';
        $this->_headerText = __('CMS Page Configuration');
    }

    /**
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/config_cms_page_field/add');
    }
}
