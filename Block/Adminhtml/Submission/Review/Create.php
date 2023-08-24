<?php
/**
 * Created by PhpStorm.
 * User: jgriffin
 * Date: 10/11/2019
 * Time: 9:31 AM
 */
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Review;

use TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Create as FormContainer;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Review
 */
class Create extends FormContainer
{
    protected $_mode;
    /**
     * Initialize blog post edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_mode = 'review_create';
        parent::_construct();
        $this->updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('review/product/index') . '\')');
    }

    /**
     * Retrieve URL for save
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('translations/submission_review/send', ['_current' => true, 'back' => null]);
    }
}
