<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Customer\Attribute;

use TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Create as FormContainer;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Customer\Attribute
 */
class Create extends FormContainer
{
    /**
     * Initialize blog post edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_mode = 'customer_attribute_create';
        parent::_construct();
        $this->updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('adminhtml/customer_attribute') . '\')');
    }

    /**
     * Retrieve URL for save
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('translations/submission_customer_attribute/send', ['_current' => true, 'back' => null]);
    }
}
