<?php
/**
 * Created by PhpStorm.
 * User: jgriffin
 * Date: 10/9/2019
 * Time: 10:00 AM
 */

namespace TransPerfect\GlobalLink\Block\Adminhtml\Config\Review;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Field
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Config\Review
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
        $this->_controller = 'adminhtml_config_review';
        $this->_blockGroup = 'TransPerfect_GlobalLink';
        $this->_headerText = __('Product Review Configuration');
    }
    /**
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/config_review_field/add');
    }
}
