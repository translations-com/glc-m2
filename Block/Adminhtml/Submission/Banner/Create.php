<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Banner;

use TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Create as FormContainer;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Banner
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
        $this->_mode = 'banner_create';
        parent::_construct();
        $this->updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('adminhtml/banner/grid') . '\')');
    }

    /**
     * Retrieve URL for save
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('translations/submission_banner/send', ['_current' => true, 'back' => null]);
    }
}
