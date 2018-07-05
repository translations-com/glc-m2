<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base;

use \Magento\Backend\Block\Widget\Form\Container as FormContainer;

class Create extends FormContainer
{
    protected function _construct()
    {
        $this->_blockGroup = 'TransPerfect_GlobalLink';
        $this->_controller = 'adminhtml_submission';
        parent::_construct();
        $this->updateButton('save', 'label', __('Send for Translation'));
        $this->removeButton('delete');
        $this->updateButton('reset', 'onclick', 'location.reload(true)');
    }
}
