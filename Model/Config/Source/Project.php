<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Justin Griffin jgriffin@translations.com
 */

namespace TransPerfect\GlobalLink\Model\Config\Source;

class Project implements \Magento\Framework\Option\ArrayInterface
{
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function toOptionArray()
    {
        $projectOptions = [];
        $shortCodeProperty = $this->scopeConfig->getValue('globallink/general/project_short_codes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(!empty($shortCodeProperty)){
            $shortCodes = array_map('trim', explode(",", $shortCodeProperty));
            if (count($shortCodes) == 0) {
                $projectOptions[0] = ['value' => 0, 'label' => 'No project short codes have been configured in properties.'];
                return $projectOptions;
            } else {
                foreach($shortCodes as $shortCode){
                    $projectOptions[] = ['value' => $shortCode, 'label' => $shortCode];
                }
            }
        } else{
            $projectOptions[0] = ['value' => 0, 'label' => 'No project short codes have been configured in properties.'];
        }

        return $projectOptions;
    }
}
