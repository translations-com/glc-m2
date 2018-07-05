<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Block;

use Magento\Backend\Block\Widget\Form\Container as FormContainer;

/**
 * Class Add
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Block
 */
class Add extends FormContainer
{
    /**
     * Initialize edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'TransPerfect_GlobalLink';
        $this->_controller = 'adminhtml_config';
        $this->_mode = 'cms_block_add';
        parent::_construct();
        $this->updateButton('save', 'label', __('Save New Field'));
        $this->removeButton('delete');
        $this->updateButton('reset', 'onclick', 'location.reload(true)');
        $this->updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/config_cms_block/field') . '\')');
    }

    /**
     * Retrieve URL for save
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/config_cms_block_field/save', ['_current' => true, 'back' => null]);
    }
}
