<?php
namespace TransPerfect\GlobalLink\Model\Config\Source;

use TransPerfect\GlobalLink\Helper\Data as HelperGlobalLink;

/**
 * Class Logging
 *
 * @package TransPerfect\GlobalLink\Model\Config\Source
 */
class Logging implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Option getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => HelperGlobalLink::LOGGING_LEVEL_DEBUG, 'label' => __('Debug')],
            ['value' => HelperGlobalLink::LOGGING_LEVEL_INFO, 'label' => __('Info')],
            ['value' => HelperGlobalLink::LOGGING_LEVEL_ERROR, 'label' => __('Error')],
        ];
    }
}
