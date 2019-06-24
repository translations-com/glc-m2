<?php

namespace TransPerfect\GlobalLink\Block\Widget\Grid;

use Magento\Framework\DataObject;

class CustomRenderer extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Item statuses
     */
    const STATUS_NEW = 0;               // item has not been sent for translation yet
    const STATUS_INPROGRESS = 1;        // item been sent but has not been translated yet
    const STATUS_FINISHED = 2;          // item has been translated (xml downloaded)
    const STATUS_ERROR_DOWNLOAD = 3;    // service error while trying to get translation by submission ticket
    const STATUS_APPLIED = 4;           // user applied translation to site
    const STATUS_FOR_CANCEL = 5;        // translation canceled locally, waiting for cancelation cron job run to cancel it on remote
    const STATUS_FOR_DELETE = 6;        // related entity has been deleted or task has been successfully cancelled. Item can be removed.
    const STATUS_CANCEL_FAILED = 7;     // last cancellation request failed
    const STATUS_ERROR_UPLOAD = 8;      // something went wrong while documend uploading (item has not been sent)
    const STATUS_CANCELLED = 9;         // item is cancelled
    const STATUS_MAXLENGTH = 10;        // item has one or more fields that failed the max length test and cannot be imported
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;
    /**
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * get category name
     * @param  DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        if ($this->getColumn()->getEditable()) {
            $result = '<div class="admin__grid-control">';
            $result .= $this->getColumn()->getEditOnly() ? ''
                : '<span class="admin__grid-control-value">' . $this->_getValue($row) . '</span>';

            return $result . $this->_getInputValueElement($row) . '</div>' ;
        }
        $statusId = $this->_getValue($row);
        return $this->getHtmlOutput($statusId);
    }

    /**
     * take status id and return the correct html output
     */
    public function getHtmlOutput($statusId)
    {
        switch($statusId){
            case $this::STATUS_APPLIED:
                return '<span class="admin__grid-control-value" style="background-color: #1E90FF; border-style: solid; color: #ffffff; border-color: black; font-size: 14px; float:left; width:125px; text-align: center; font-family:Verdana, Arial, sans-serif; font-weight: bold; border-radius: 5px;">Completed</span>';
            case $this::STATUS_CANCEL_FAILED:
                return '<span class="admin__grid-control-value" style="background-color: #A9A9A9; border-style: solid; color: #ffffff; border-color: black; font-size: 14px; float:left; width:125px; text-align: center; font-family:Verdana, Arial, sans-serif; font-weight: bold; border-radius: 5px;">Cancel Failed, Will Retry</span>';
            case $this::STATUS_ERROR_DOWNLOAD:
                return '<span class="admin__grid-control-value" style="background-color: #A9A9A9; border-style: solid; color: #ffffff; border-color: black; font-size: 14px; float:left; width:125px; text-align: center; font-family:Verdana, Arial, sans-serif; font-weight: bold; border-radius: 5px;">Cancelled, Source Page Deleted</span>';
            case $this::STATUS_FINISHED:
                return '<span class="admin__grid-control-value" style="background-color: #33cc33; border-style: solid; color: #ffffff; border-color: black; font-size: 14px; float:left; width:125px; text-align: center; font-family:Verdana, Arial, sans-serif; font-weight: bold; border-radius: 5px;">Ready to Import</span>';
            case $this::STATUS_ERROR_UPLOAD:
                return '<span class="admin__grid-control-value" style="background-color: #FF0000; border-style: solid; color: #ffffff; border-color: black; font-size: 14px; float:left; width:125px; text-align: center; font-family:Verdana, Arial, sans-serif; font-weight: bold; border-radius: 5px;">Uploading Failed</span>';
            case $this::STATUS_FOR_CANCEL:
                return '<span class="admin__grid-control-value" style="background-color: #A9A9A9; border-style: solid; color: #ffffff; border-color: black; font-size: 14px; float:left; width:125px; text-align: center; font-family:Verdana, Arial, sans-serif; font-weight: bold; border-radius: 5px;">Waiting to be Cancelled</span>';
            case $this::STATUS_FOR_DELETE:
                return '<span class="admin__grid-control-value" style="background-color: #A9A9A9; border-style: solid; color: #ffffff; border-color: black; font-size: 14px; float:left; width:125px; text-align: center; font-family:Verdana, Arial, sans-serif; font-weight: bold; border-radius: 5px;">Cancelled</span>';
            case $this::STATUS_INPROGRESS:
                return '<span class="admin__grid-control-value" style="background-color: #ff9933; border-style: solid; color: #ffffff; border-color: black; font-size: 14px; float:left; width:125px; text-align: center; font-family:Verdana, Arial, sans-serif; font-weight: bold; border-radius: 5px;">In Progress</span>';
            case $this::STATUS_MAXLENGTH:
                return '<span class="admin__grid-control-value" style="background-color: #FF0000; border-style: solid; color: #ffffff; border-color: black; font-size: 14px; float:left; width:125px; text-align: center; font-family:Verdana, Arial, sans-serif; font-weight: bold; border-radius: 5px;">Max Length Error</span>';
            case $this::STATUS_NEW:
                return '<span class="admin__grid-control-value" style="background-color: #ff9933; border-style: solid; color: #ffffff; border-color: black; font-size: 14px; float:left; width:125px; text-align: center; font-family:Verdana, Arial, sans-serif; font-weight: bold; border-radius: 5px;">Queued</span>';
        }
    }
}