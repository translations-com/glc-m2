<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config\Review;

use TransPerfect\GlobalLink\Controller\Adminhtml\Config\Action;

/**
 * Class Field
 *
 * @package TransPerfect\GlobalLink\Controller\Adminhtml\Config\Review
 */
class Field extends Action
{
    public function execute()
    {
        $resultPage = parent::execute();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::review_fields');
        $resultPage->getConfig()->getTitle()->prepend(__('Fields Configuration - Product Review Fields'));
        return $resultPage;
    }
}
