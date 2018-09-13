<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base;

use \Magento\Backend\Block\Widget\Form\Container as FormContainer;

class Create extends FormContainer
{
    protected function _construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $registry = $objectManager->get('Magento\Framework\Registry');
        $nonDefaultDifferentStoresSelected = $registry->registry('differentStoresSelected');
        $this->_blockGroup = 'TransPerfect_GlobalLink';
        $this->_controller = 'adminhtml_submission';
        parent::_construct();
        $this->updateButton('save', 'label', __('Send for Translation'));
        if($nonDefaultDifferentStoresSelected == true){
            $this->updateButton('save', 'disabled', true);
        }
        $this->removeButton('delete');
        $this->updateButton('reset', 'onclick', 'location.reload(true)');
    }
}
