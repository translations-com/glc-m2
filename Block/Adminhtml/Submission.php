<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Submission
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml
 */
class Submission extends Container
{
    /**
     * Initialize object state with incoming parameters
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_blockGroup = 'TransPerfect_GlobalLink';
        $this->_controller = 'adminhtml_submission';
        $this->_headerText = __('Submissions');
        $this->removeButton('add');
        $this->addButton(
            'remove_all_cancelled',
            [
                'label' => __('Remove all cancelled items'),
                'onclick' => 'deleteConfirm("'.__('Are you sure you want to remove all cancelled items?').'", \'' . $this->getUrl('*/*/remove', ['ids' => 'all']) . '\')',
                'class' => 'add primary',
            ]
        );
    }
}
