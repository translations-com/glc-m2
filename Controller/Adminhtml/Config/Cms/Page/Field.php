<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config\Cms\Page;

use TransPerfect\GlobalLink\Controller\Adminhtml\Config\Action;

/**
 * Class Field
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Config\Cms\Page
 */
class Field extends Action
{
    public function execute()
    {
        $resultPage = parent::execute();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::cms_page_fields');
        $resultPage->getConfig()->getTitle()->prepend(__('Fields Configuration - CMS Page Fields'));

        return $resultPage;
    }
}
