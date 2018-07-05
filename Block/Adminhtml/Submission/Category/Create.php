<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Category;

use TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Create as FormContainer;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Category
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
        $this->_mode = 'category_create';
        parent::_construct();
        $this->updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/category') . '\')');
    }

    /**
     * Retrieve URL for save
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('translations/submission_category/send', ['_current' => true, 'back' => null]);
    }
}
