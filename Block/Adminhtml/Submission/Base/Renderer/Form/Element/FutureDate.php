<?php

namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Renderer\Form\Element;

use Magento\Framework\Data\Form\Element\Date;

class FutureDate extends Date
{
    /**
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param TimezoneInterface $localeDate
     * @param array $data
     */
    /*public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        $data = []
    ) {
        $this->localeDate = $localeDate;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->setType('text');
        $this->setExtType('textfield');
        if (isset($data['value'])) {
            $this->setValue($data['value']);
        }
    }*/

    /**
     * Output the input field and assign calendar instance to it.
     * In order to output the date:
     * - the value must be instantiated (\DateTime)
     * - output format must be set (compatible with \DateTime)
     *
     * @throws \Exception
     * @return string
     */
    public function getElementHtml()
    {
        $this->addClass('admin__control-text  input-text');
        $dateFormat = $this->getDateFormat() ?: $this->getFormat();
        $timeFormat = $this->getTimeFormat();
        if (empty($dateFormat)) {
            throw new \Exception(
                'Output format is not specified. ' .
                'Please specify "format" key in constructor, or set it using setFormat().'
            );
        }

        $date = $this->localeDate->date(new \DateTime('now + 1day'))->format('m/d/y');

        $dataInit = 'data-mage-init="' . $this->_escape(
            json_encode(
                [
                    'calendar' => [
                        'dateFormat' => $dateFormat,
                        'showsTime' => !empty($timeFormat),
                        'timeFormat' => $timeFormat,
                        'buttonImage' => $this->getImage(),
                        'buttonText' => 'Select Date',
                        'disabled' => $this->getDisabled(),
                        'minDate' => $date,
                    ],
                ]
            )
        ) . '"';

        $html = sprintf(
            '<input name="%s" id="%s" value="%s" %s %s />',
            $this->getName(),
            $this->getHtmlId(),
            $this->_escape($this->getValue()),
            $this->serialize($this->getHtmlAttributes()),
            $dataInit
        );
        $html .= $this->getAfterElementHtml();
        return $html;
    }
}
