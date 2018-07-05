<?php

namespace TransPerfect\GlobalLink\Controller\Adminhtml\Config\Category;

use TransPerfect\GlobalLink\Controller\Adminhtml\Config\Action;

/**
 * Class Attribute
 *
 * @package TransPerfect\GlobalLink\Controller\Config\Category
 */
class Attribute extends Action
{
    public function execute()
    {
        $resultPage = parent::execute();
        $resultPage->setActiveMenu('TransPerfect_GlobalLink::category_fields');
        $resultPage->getConfig()->getTitle()->prepend(__('Fields Configuration - Category Attributes'));
        return $resultPage;
    }
}
