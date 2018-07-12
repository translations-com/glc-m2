<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Grid\Renderer;

use \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

/**
 * Class DueDate
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Category\Grid\Renderer
 */
class DueDate extends AbstractRenderer
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * constructor
     *
     * @param \Magento\Backend\Block\Context              $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param array                                       $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        array $data = []
    ) {
        $this->dateTime = $dateTime;
        parent::__construct($context, $data);
    }

    /**
     * Render action
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $date = $row->getData($this->getColumn()->getIndex());
        $timestamp = $this->dateTime->gmtTimestamp($date);
        $date = date('M j, Y', $timestamp);
        return $date;
    }
}
