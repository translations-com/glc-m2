<?php
namespace TransPerfect\GlobalLink\Model\Config\Source;

use Magento\Framework\Exception\StateException;
use TransPerfect\GlobalLink\Model\SoapClient\GLExchangeClient;

/**
 * Class Classifiers
 *
 * @package TransPerfect\GlobalLink\Model\Config\Source
 */
class Classifiers implements \Magento\Framework\Option\ArrayInterface
{
    protected $translationService;
    protected $scopeConfig;
    protected $testService;

    public function __construct(
        \TransPerfect\GlobalLink\Model\TranslationService $translationService,
        \TransPerfect\Globallink\Model\SoapClient\GLExchangeClient $testService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {

        $this->translationService = $translationService;
        $this->testService = $testService;
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * Option getter
     * TODO need to use data from API
     *
     * @return array
     */

    public function toOptionArray()
    {
        $fileFormats = [];
        try {
            $connectionUrl = $this->scopeConfig->getValue('globallink/connection/url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $username = $this->scopeConfig->getValue('globallink/connection/username', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $password = $this->scopeConfig->getValue('globallink/connection/password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $shortCodes = array_map('trim', explode(",", $this->scopeConfig->getValue('globallink/general/project_short_codes',  \Magento\Store\Model\ScopeInterface::SCOPE_STORE )));
            if ($connectionUrl == null || $username == null || $password == null) {
                $fileFormats[0] = ['value' => 0, 'label' => 'No available File Formats, could not connect to PD'];
                return $fileFormats;
            }
            $error = $this->testService->testConnectError($username, $password, $connectionUrl);
            if ($error == '') {
                $response = $this->translationService->requestGLExchange(
                    '/services/ProjectService',
                    'getUserProjects',
                    [
                        'isSubProjectIncluded' => true,
                    ]
                );
            } else {
                $response = [];
            }
        } catch (StateException $ex) {
            $response = [];
        }
        $i=1;
        if (!empty($response)) {
            foreach ($response as $project) {
                if ($project == null) {
                    $fileFormats[$i - 1] = ['value' => $i, 'label' => 'No available File Formats, could not connect to PD'];
                    break;
                }
                $currentShortCode = trim($project->projectInfo->shortCode);
                if(!in_array($currentShortCode, $shortCodes)){
                    //DO NOTHING
                } else {
                    $currentFormats = $project->fileFormatProfiles;
                    if (is_array($currentFormats)) {
                        $formatExists = false;
                        foreach ($currentFormats as $format) {
                            $currentProfileName = $format->profileName;
                            if(!empty($fileFormats)){
                                foreach($fileFormats as $format){
                                    if($currentProfileName == $format['value']){
                                        $formatExists = true;
                                    }
                                }
                            }
                            if(!$formatExists) {
                                $fileFormats[$i - 1] = ['value' => $currentProfileName, 'label' => $currentProfileName];
                                $i++;
                                $formatExists = false;
                            }
                        }
                    } else {
                        $currentProfileName = $currentFormats->profileName;
                        $formatExists = false;
                        if (!empty($fileFormats)) {
                            foreach ($fileFormats as $format) {
                                if ($currentProfileName == $format['value']) {
                                    $formatExists = true;
                                }
                            }
                        }
                        if(!$formatExists) {
                            $fileFormats[$i - 1] = ['value' => $currentProfileName, 'label' => $currentProfileName];
                            $i++;
                            $formatExists = false;
                        }
                    }
                }
            }
        } else {
            $fileFormats[$i - 1] = ['value' => $i, 'label' => 'No available File Formats, could not connect to PD'];
        }
        return $fileFormats;
    }
}
