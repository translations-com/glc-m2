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
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        if($scopeConfig->getValue('globallink/general/automation') == 1 && $scopeConfig->getValue('globallink/general/auto_import') == 0) {
            $this->addButton(
                'sync_with_pd',
                [
                    'label' => __('Check for Updates'),
                    'onclick' => 'confirmSetLocation("' . __('Are you sure you want to check for updates?') . '", \'' . $this->getUrl('*/*/sync') . '\')',
                    'class' => 'add primary',
                ]
            );
        }
        elseif($scopeConfig->getValue('globallink/general/automation') == 1 && $scopeConfig->getValue('globallink/general/auto_import') == 1) {
            $this->addButton(
                'sync_and_import',
                [
                    'label' => __('Check for Updates & Import Automatically'),
                    'onclick' => 'confirmSetLocation("' . __('Are you sure you want to check for updates and import any received content?') . '", \'' . $this->getUrl('*/*/autoimport') . '\')',
                    'class' => 'add primary',
                ]
            );
        }
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
