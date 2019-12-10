<?php
/**
 * Created by PhpStorm.
 * User: jgriffin
 * Date: 10/22/2019
 * Time: 10:34 AM
 */

namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Grid\Renderer;

use Magento\Framework\DataObject;
use TransPerfect\GlobalLink\Helper\Data as Helper;
use TransPerfect\GlobalLink\Model\Queue\Item as Item;

class EntityLinker extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    protected $helper;
    protected $backendHelper;

    public function __construct(
        Helper $helper,
        \Magento\Backend\Helper\Data $backendHelper
    )
    {
        $this->helper = $helper;
        $this->backendHelper = $backendHelper;
    }

    public function render(DataObject $row)
    {
        if ($this->getColumn()->getEditable()) {
            $result = '<div class="admin__grid-control">';
            $result .= $this->getColumn()->getEditOnly() ? ''
                : '<span class="admin__grid-control-value">' . $this->_getValue($row) . '</span>';

            return $result . $this->_getInputValueElement($row) . '</div>' ;
        }
        $columnName = $this->getColumn()->getData('id');
        $value = $this->_getValue($row);
        $entityType = $row['entity_type_id'];
        $submissionStatus = $row['status_id'];
        $newEntityId = $row['new_entity_id'];
        $originalEntityId = $row['entity_id'];
        //$storeId = str_replace(",", "", $row['target_stores']);
        $outputValue = $this->helper->getLocaleColumnLabel($value, false, true);
        $storeIdArray = $this->helper->getStoreIdFromLocale($value);
        $storeId = $storeIdArray[0];
        if($submissionStatus != Item::STATUS_APPLIED && $columnName == 'pd_locale_iso_code'){
            return $outputValue;
        } else {
            switch ($entityType) {
                case $this->helper::CATALOG_PRODUCT_TYPE_ID:
                    return "<a href='{$this->backendHelper->getUrl('catalog/product/edit',
                    ['id' => $row['entity_id'],
                     'store' => $storeId
                    ])}' style=\"font-weight:bold\">" . $outputValue . "</a>";
                case $this->helper::CATALOG_CATEGORY_TYPE_ID:
                    return "<a href='{$this->backendHelper->getUrl('catalog/category/edit',
                    ['id' => $row['entity_id'],
                     'store' => $storeId
                    ])}' style=\"font-weight:bold\">" . $outputValue . "</a>";
                case $this->helper::CMS_PAGE_TYPE_ID:
                    if($columnName == 'source_locale') {
                        return "<a href='{$this->backendHelper->getUrl('cms/page/edit',
                        ['page_id' => $originalEntityId
                        ])}' style=\"font-weight:bold\">" . $outputValue . "</a>";
                    } else{
                        return "<a href='{$this->backendHelper->getUrl('cms/page/edit',
                        ['page_id' => $newEntityId
                        ])}' style=\"font-weight:bold\">" . $outputValue . "</a>";
                    }
                case $this->helper::CMS_BLOCK_TYPE_ID:
                    if($columnName == 'source_locale') {
                        return "<a href='{$this->backendHelper->getUrl('cms/block/edit',
                        ['block_id' => $originalEntityId
                        ])}' style=\"font-weight:bold\">" . $outputValue . "</a>";
                    } else{
                        return "<a href='{$this->backendHelper->getUrl('cms/block/edit',
                        ['block_id' => $newEntityId
                        ])}' style=\"font-weight:bold\">" . $outputValue . "</a>";
                    }
                case $this->helper::PRODUCT_ATTRIBUTE_TYPE_ID:
                    return "<a href='{$this->backendHelper->getUrl('catalog/product_attribute/edit',
                    ['attribute_id' => $row['entity_id']
                    ])}' style=\"font-weight:bold\">" . $outputValue . "</a>";
                case $this->helper::CUSTOMER_ATTRIBUTE_TYPE_ID:
                    return "<a href='{$this->backendHelper->getUrl('adminhtml/customer_attribute/edit',
                    ['attribute_id' => $row['entity_id']
                    ])}' style=\"font-weight:bold\">" . $outputValue . "</a>";
                case $this->helper::PRODUCT_REVIEW_ID:
                    if($columnName == 'source_locale') {
                        return "<a href='{$this->backendHelper->getUrl('review/product/edit',
                        ['id' => $originalEntityId
                        ])}' style=\"font-weight:bold\">" . $outputValue . "</a>";
                    } else{
                        return "<a href='{$this->backendHelper->getUrl('review/product/edit',
                        ['id' => $newEntityId
                        ])}' style=\"font-weight:bold\">" . $outputValue . "</a>";
                    }
            }
        }
    }



}