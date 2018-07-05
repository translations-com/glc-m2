<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Product;

use TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Create as FormContainer;

/**
 * Class Create
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Product
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
        $this->_mode = 'product_create';
        parent::_construct();
        $this->updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('catalog/product') . '\')');
    }

    /**
     * Retrieve URL for save
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('translations/submission_product/send', ['_current' => true, 'back' => null]);
    }
}
