<?php
namespace TransPerfect\GlobalLink\Model\Config\Source;

use Magento\Framework\Exception\StateException;

/**
 * Class OpenSourceClassifiers
 *
 * @package TransPerfect\GlobalLink\Model\Config\Source
 */
class OpenSourceClassifiers implements \Magento\Framework\Option\ArrayInterface
{
    protected $translationService;
    protected $scopeConfig;
    protected $testService;
    protected $helper;

    public function __construct(
        \TransPerfect\GlobalLink\Model\TranslationService $translationService,
        \TransPerfect\Globallink\Model\SoapClient\GLExchangeClient $testService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \TransPerfect\GlobalLink\Helper\Data $helper
    ) {

        $this->translationService = $translationService;
        $this->testService = $testService;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }
    /**
     * Option getter
     * TODO need to use data from API
     *
     * @return array
     */

    public function toOptionArray()
    {
        $glExchange = $this->testService->getConnect();
        $fileFormats = [];
        try {
            $shortCodeString = $this->scopeConfig->getValue('globallink/general/project_short_codes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == null ? '' : $this->scopeConfig->getValue('globallink/general/project_short_codes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            if (!$this->helper->isEnterprise()) {
                $fileFormats[0] = ['value' => 0, 'label' => 'This feature is not available outside of Commerce Edition'];
                return $fileFormats;
            }
            $shortCodes = array_map('trim', explode(",", $shortCodeString));
            if ($glExchange->healthcheck()) {
                //DO NOTHING
            } else {
                $fileFormats[0] = ['value' => 0, 'label' => 'No available File Formats, could not connect to PD'];
                return $fileFormats;
            }
        } catch (StateException $ex) {
            $response = [];
        }
        $i=1;
        $response = $glExchange->getProjects();
        if (!empty($response)) {
            foreach ($response as $project) {
                $currentShortCode = trim($project->shortCode);
                if (!in_array($currentShortCode, $shortCodes)) {
                    //DO NOTHING
                } else {
                    $currentFormats = $glExchange->getProjectFileFormats($project->projectId);
                    if (is_array($currentFormats)) {
                        $formatExists = false;
                        foreach ($currentFormats as $format) {
                            $currentProfileName = $format->name;
                            if (!empty($fileFormats)) {
                                foreach ($fileFormats as $format) {
                                    if ($currentProfileName == $format['value']) {
                                        $formatExists = true;
                                    }
                                }
                            }
                            if (!$formatExists) {
                                $fileFormats[$i - 1] = ['value' => $currentProfileName, 'label' => $currentProfileName];
                                $i++;
                            }
                            $formatExists = false;
                        }
                    } else {
                        $currentProfileName = $currentFormats->name;
                        $formatExists = false;
                        if (!empty($fileFormats)) {
                            foreach ($fileFormats as $format) {
                                if ($currentProfileName == $format['value']) {
                                    $formatExists = true;
                                }
                            }
                        }
                        if (!$formatExists) {
                            $fileFormats[$i - 1] = ['value' => $currentProfileName, 'label' => $currentProfileName];
                            $i++;
                        }
                        $formatExists = false;
                    }
                }
            }
        } else {
            $fileFormats[$i - 1] = ['value' => $i, 'label' => 'No available File Formats, could not connect to PD'];
        }
        return $fileFormats;
    }
}
