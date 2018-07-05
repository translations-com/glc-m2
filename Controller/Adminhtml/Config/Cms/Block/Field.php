<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config\Cms\Block;

use TransPerfect\GlobalLink\Controller\Adminhtml\Config\Action;

/**
 * Class Field
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Config\Cms\Block
 */
class Field extends Action
{
    public function execute()
    {
        $resultPage = parent::execute();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::cms_block_fields');
        $resultPage->getConfig()->getTitle()->prepend(__('Fields Configuration - CMS Block Fields'));
        return $resultPage;
    }
}
