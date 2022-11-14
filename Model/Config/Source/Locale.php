<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Justin Griffin jgriffin@translations.com
 */

namespace TransPerfect\GlobalLink\Model\Config\Source;

class Locale implements \Magento\Framework\Option\ArrayInterface
{
    protected $helper;

    public function __construct(
        \TransPerfect\GlobalLink\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    public function toOptionArray()
    {
        $locales = $this->helper->getLocales(false, true);
        $localeOptions = [];
        if ($locales == null || $locales == '' || count($locales) == 0) {
            $localeOptions[0] = ['value' => 0, 'label' => 'No locales are available. Check your connection to PD.'];
            return $localeOptions;
        }
        foreach ($locales as $key => $value) {
            $localeOptions[] = ['value' => $key, 'label' => $value];
        }
        return $localeOptions;
    }
}
