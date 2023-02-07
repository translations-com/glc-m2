<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base;

use Magento\Backend\Block\Widget\Form\Generic as GenericForm;

/**
 * Class Form
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base
 */
class Form extends GenericForm
{
    protected $currentLocale;

    protected $selectedStore;

    protected $localize;

    protected $objectStoreId;

    protected $nonDefaultDifferentStoresSelected;

    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;

    /**
     * @var \TransPerfect\GlobalLink\Model\TranslationService
     */
    protected $translationService;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;
    /**
     * @var boolean
     */
    protected $differentStoresSelected = false;
    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \TransPerfect\GlobalLink\Helper\Data $helper
     * @param \TransPerfect\GlobalLink\Model\TranslationService $translationService
     * @param array $data
     */

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \TransPerfect\GlobalLink\Model\TranslationService $translationService,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->translationService = $translationService;
        $this->session = $context->getBackendSession();
        parent::__construct($context, $registry, $formFactory, $data);
        $this->differentStoresSelected = $registry->registry('differentStoresSelected');
        $this->objectStoreId = $registry->registry('objectStoreId');
        $this->_prepareData();
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post']
            ]
        );

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $fieldset = $form->addFieldset('translation_fieldset', ['legend' => __('Translation information')]);
        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'submission[name]',
                'label' => __('Submission Name'),
                'title' => __('Submission Name'),
                'required' => true,
            ]
        );

        $fieldset->addType(
            'futuredate',
            '\TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Renderer\Form\Element\FutureDate'
        );
        $fieldset->addField(
            'due_date',
            'futuredate',
            [
                'name' => 'submission[due_date]',
                'label' => __('Due Date'),
                'title' => __('Due Date'),
                'required' => true,
                'date_format' => $dateFormat,
                'readonly' => 'readonly',
            ]
        );
        if ($this->differentStoresSelected) {
            $fieldset->addField(
                'submission[source_language]',
                'note',
                [
                    'label' => __('Source language'),
                    'text' => __('Cannot translate, multiple languages selected.'),
                ]
            );
        } else {
            $fieldset->addField(
                'submission[source_language]',
                'note',
                [
                    'label' => __('Source language'),
                    'text' => $this->helper->getCountrybyLocaleCode($this->currentLocale),
                ]
            );
        }

        $serviceProjectsData = $this->getServiceProjectsData();
        $fieldset->addField(
            'project_id',
            'select',
            [
                'label' => __('Project'),
                'name' => 'submission[project]',
                'required' => true,
                'values' => $serviceProjectsData['projects'],
                'after_element_html' => $this->getProjectsScripts()
                    . $serviceProjectsData['projectsMessage'],
                'onchange' => 'projectChange(this)',
            ]
        );

        foreach ($serviceProjectsData['targetlocales'] as $projectShortcode => $locales) {
            $customAttributes = null;
            foreach ($serviceProjectsData['projects'] as $currentProject) {
                if ($projectShortcode == $currentProject['value']) {
                    if (isset($currentProject['custom_attributes'])) {
                        $customAttributes = $currentProject['custom_attributes'];
                    }
                }
            }
            /**
             * TRAN-53: Instead of sending locales, send target store ids.
             * While saving a Queue save both locales and store ids.
             */
            $newLocales = [];
            $tempLocales = [];
            foreach ($locales as $localeData) {
                $tempLocales[$localeData['value']] = $localeData['label'];
            }
            if (!empty($tempLocales) && is_array($tempLocales)) {
                foreach ($tempLocales as $localeCode => $localeName) {
                    $stores = $this->helper->getStoresByLocaleCode($localeCode);
                    foreach ($stores as $store) {
                        $newLocales[] = ['value' => $store->getId(), 'label' => $store->getName() . ': ' . $localeName];
                    }
                }
            }
            asort($newLocales);
            $locales = $newLocales;
            /** end: TRAN-53 **/

            $fieldId = 'localize_' . $projectShortcode;
            if (!empty($locales)) {
                $fieldset->addField(
                    $fieldId,
                    'checkboxes',
                    [
                        'label' => __('Target Store(s)'),
                        'name' => 'submission[localize][]',
                        'options' => $locales,
                        'required' => true,
                        'value' => '',
                        'onchange' => 'validatecheckboxes(\'' . $fieldId . '\')',
                        'after_element_html' => $this->getCheckboxesAfterHtml($fieldId),
                        'display' => 'none',
                    ]
                );
            } else {
                $fieldset->addField(
                    $fieldId,
                    'note',
                    [
                        'label' => __('Target Language(s)'),
                        'required' => true,
                        'text' => '<label class="mage-error">'
                            . __("This project doesn't have any translate directions with current source language")
                            . '</label>',
                        'after_element_html' => $this->getNotesAfterHtml($fieldId),
                    ]
                );
            }
            if ($customAttributes != null) {
                $textAttributePopulated = false;
                $comboAttributePopulated = false;
                if(is_array($customAttributes)) {
                    foreach ($customAttributes as $attribute) {
                        if ($attribute->type == 'TEXT' && $textAttributePopulated == false) {
                            $attributeFieldId = 'attribute_' . $projectShortcode . '_text';
                            if ($attribute->mandatory == true) {
                                $fieldset->addField(
                                    $attributeFieldId,
                                    'text',
                                    [
                                        'name' => 'submission[attribute_text]['.$projectShortcode.']',
                                        'label' => __($attribute->name) . ' (Required)',
                                        'title' => __($attribute->name),
                                        'required' => false,
                                        'display' => 'none',
                                        'after_element_html' => $this->getAttributesAfterHtml($attributeFieldId)
                                    ]
                                );
                            } else {
                                $fieldset->addField(
                                    $attributeFieldId,
                                    'text',
                                    [
                                        'name' => 'submission[attribute_text]['.$projectShortcode.']',
                                        'label' => __($attribute->name),
                                        'title' => __($attribute->name),
                                        'required' => false,
                                        'display' => 'none',
                                        'after_element_html' => $this->getAttributesAfterHtml($attributeFieldId)
                                    ]
                                );
                            }
                            $textAttributePopulated = true;
                        } elseif ($attribute->type == 'COMBO' && $comboAttributePopulated == false) {
                            $newValues = [];
                            $attributeFieldId = 'attribute_' . $projectShortcode . '_combo';
                            $values = explode(',', $attribute->values);
                            if ($attribute->mandatory == false) {
                                $newValues[] = ['value' => '', 'label' => ''];
                            }
                            foreach ($values as $value) {
                                $newValues[] = ['value' => $value, 'label' => $value];
                            }
                            $values = $newValues;
                            $fieldset->addField(
                                $attributeFieldId,
                                'select',
                                [
                                    'name' => 'submission[attribute_combo]['.$projectShortcode.']',
                                    'label' => __($attribute->name),
                                    'title' => __($attribute->name),
                                    'values' => $values,
                                    'required' => false,
                                    'display' => 'none',
                                    'after_element_html' => $this->getAttributesAfterHtml($attributeFieldId)
                                ]
                            );
                            $comboAttributePopulated = true;
                        }
                    }
                } else if(is_object($customAttributes)){
                    $attribute = $customAttributes;
                    if ($attribute->type == 'TEXT' && $textAttributePopulated == false) {
                        $attributeFieldId = 'attribute_' . $projectShortcode . '_text';
                        if ($attribute->mandatory == true) {
                            $fieldset->addField(
                                $attributeFieldId,
                                'text',
                                [
                                    'name' => 'submission[attribute_text]['.$projectShortcode.']',
                                    'label' => __($attribute->name) . ' (Required)',
                                    'title' => __($attribute->name),
                                    'required' => false,
                                    'display' => 'none',
                                    'after_element_html' => $this->getAttributesAfterHtml($attributeFieldId)
                                ]
                            );
                        } else {
                            $fieldset->addField(
                                $attributeFieldId,
                                'text',
                                [
                                    'name' => 'submission[attribute_text]['.$projectShortcode.']',
                                    'label' => __($attribute->name),
                                    'title' => __($attribute->name),
                                    'required' => false,
                                    'display' => 'none',
                                    'after_element_html' => $this->getAttributesAfterHtml($attributeFieldId)
                                ]
                            );
                        }
                        $textAttributePopulated = true;
                    } elseif ($attribute->type == 'COMBO' && $comboAttributePopulated == false) {
                        $newValues = [];
                        $attributeFieldId = 'attribute_' . $projectShortcode . '_combo';
                        $values = explode(',', $attribute->values);
                        if ($attribute->mandatory == false) {
                            $newValues[] = ['value' => '', 'label' => ''];
                        }
                        foreach ($values as $value) {
                            $newValues[] = ['value' => $value, 'label' => $value];
                        }
                        $values = $newValues;
                        $fieldset->addField(
                            $attributeFieldId,
                            'select',
                            [
                                'name' => 'submission[attribute_combo]['.$projectShortcode.']',
                                'label' => __($attribute->name),
                                'title' => __($attribute->name),
                                'values' => $values,
                                'required' => false,
                                'display' => 'none',
                                'after_element_html' => $this->getAttributesAfterHtml($attributeFieldId)
                            ]
                        );
                        $comboAttributePopulated = true;
                    }
                }
            }
        }

        $fieldset->addField(
            'priority',
            'select',
            [
                'label' => __('Priority'),
                'name' => 'submission[priority]',
                'values' => [
                    ['label' => 'High', 'value' => '1'],
                    ['label' => 'Normal', 'value' => '0']
                ],
                'value' => '0'
            ]
        );

        $fieldset->addField(
            'confirmation_email',
            'text',
            [
                'label' => __('Alert Email'),
                'name' => 'submission[confirmation_email]',
                'class' => 'validate-emails'
            ]
        );

        $fieldset->addField(
            'instructions',
            'textarea',
            [
                'label' => __('Submission Instructions'),
                'name' => 'submission[instructions]',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * @return $this
     */
    protected function _prepareData()
    {
        if ($this->objectStoreId == null) {
            $storeId = $this->getSourceStoreId();
        } else {
            $storeId = $this->objectStoreId;
        }
        $this->selectedStore = $this->_storeManager->getStore($storeId);

        $selectedStoreLocale = $this->getSourceStoreLocaleCode();
        $this->currentLocale = $selectedStoreLocale;

        $locales = $this->helper->getLocales(true);
        unset($locales[$selectedStoreLocale]);

        $this->localize = $locales;

        return $this;
    }

    /**
     * Return source store id
     *
     * @return int
     */
    protected function getSourceStoreId()
    {
        if (!empty($this->getRequest()->getParam('store'))) {
            $storeId = $this->getRequest()->getParam('store');
        } else {
            // get current store id
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }

        return $storeId;
    }

    protected function getSourceStoreLocaleCode()
    {
        if (!empty($this->selectedStore->getLocale())) {
            $storeLocaleCode = $this->selectedStore->getLocale();
        } else {
            // if current store has no assigned globallink's locale
            // set magento's locale to it and use it
            $storeLocaleCode = str_replace(
                '_',
                '-',
                $this->_scopeConfig->getValue(
                    'general/locale/code',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $this->selectedStore->getId()
                )
            );
            $this->selectedStore->setLocale($storeLocaleCode)->save();
        }

        return $storeLocaleCode;
    }

    /**
     * Returns additional html for checkboxes
     *
     * @return string
     */
    protected function getProjectsScripts()
    {
        return
            "<script type='text/javascript'>
        function projectChange(el){
            jQuery(\"input[name='submission[localize][]']\").prop('checked', false);

            jQuery('.checkboxes-error-message').hide();

            var selectedShortcode = jQuery(el).val();

            var fieldId = 'localize_'+selectedShortcode;
            var attributeFieldIdText = 'attribute_'+selectedShortcode+'_text';
            var attributeFieldIdCombo = 'attribute_'+selectedShortcode+'_combo';
            //hide all but selected
            jQuery(\"div[class^='field-localize_'],div[class*=' field-localize_']\").hide();
            jQuery(\"div[class^='field-attribute_'],div[class*=' field-attribute_']\").hide();
            jQuery(\".field-\"+fieldId).show();
            jQuery(\".field-\"+attributeFieldIdText).show();
            jQuery(\".field-\"+attributeFieldIdCombo).show();
            //jQuery(\"div[class^='field-\"+attributeFieldId+\"'],div[class*=' field-\"+attributeFieldId+\"']\").show();

            validatecheckboxes(fieldId);
        }

        function validatecheckboxes(fieldId){
            var isChecked = jQuery(\".field-\"+fieldId+\" input[name='submission[localize][]']:checked\").length;
            var saveButton = jQuery('button#save');
            if(!isChecked) {
                jQuery('#error-message-'+fieldId).show();
                saveButton.prop('disabled', true);
                return false;
            }
            jQuery('#error-message-'+fieldId).hide();
            saveButton.prop('disabled', false);
            return true;
        }
        </script>
        ";
    }
    /**
     * Returns additional html for attributes
     *
     * @param string $fieldId
     */
    protected function getAttributesAfterHtml($fieldId)
    {
        $html = "<style type='text/css'>.field-" . $fieldId . "{display:none;}</style>";
        return $html;
    }
    /**
     * Returns additional html for Checkboxes
     *
     * @param string $fieldId
     * @param bool   $isFirst
     *
     * @return string
     */
    protected function getCheckboxesAfterHtml($fieldId)
    {
        $html = "<label style='display:none' for='" . $fieldId . "' generated='false' class='mage-error checkboxes-error-message' id='error-message-" . $fieldId . "'>This is a required field.</label>";
        $html .= "<style type='text/css'>.field-" . $fieldId . "{display:none;}</style>";

        return $html;
    }

    /**
     * Returns additional html for Notes
     *
     * @param string $fieldId
     * @param bool   $isFirst
     *
     * @return string
     */
    protected function getNotesAfterHtml($fieldId)
    {
        $html = "<style type='text/css'>.field-" . $fieldId . "{display:none;}</style>";

        return $html;
    }

    /**
     * Get projects data from service
     *
     * @return array
     *  [
     *      'projects' => [
     *          ['label'=>'Select Project', 'value'=>''],
     *          ['label'=>'project 1', 'value'=>'shortcode1'],
     *          ['label'=>'project 2', 'value'=>'shortcode2'],
     *      ],
     *      'projectsMessage' => '',
     *      'targetlocales' => [
     *          'shortcode1' => [
     *              ['value'=>'de-DE', 'label'=>'German (Germany)'],
     *              ['value'=>'fr-FR', 'label'=>'French (France)'],
     *          ],
     *          'shortcode2' => [
     *              ['value'=>'jp-JP', 'label'=>'Japanese (Japan)'],
     *              ['value'=>'es-ES', 'label'=>'Spanish (Spain)'],
     *          ],
     *      ]
     *  ]
     */
    protected function getServiceProjectsData()
    {
        $projectsArray = [['label'=>__('Select Project'), 'value'=>'']];
        $projectsMessage = '';
        $targetlocales = [];
        $shortCodes = $this->helper->getProjectShortCodes();

        $locallyAvailableLocales = $this->helper->getLocales(true);

        try {
            $response = $this->translationService->requestGLExchange(
                '/services/ProjectService',
                'getUserProjects',
                [
                    'isSubProjectIncluded' => true,
                ]
            );
            foreach ($response as $project) {
                if (in_array($project->projectInfo->shortCode, $shortCodes)) {
                    $projectsArray[] = [
                        'label' => $project->projectInfo->name,
                        'value' => $project->projectInfo->shortCode,
                        'custom_attributes' => $project->projectCustomFieldConfiguration
                    ];
                }

                $targetlocales[$project->projectInfo->shortCode] = [];
                if(isset($project->projectLanguageDirections->sourceLanguage) && isset($project->projectLanguageDirections->targetLanguage)){
                    if (array_key_exists($project->projectLanguageDirections->targetLanguage->locale, $this->localize)) {
                        $serviceLocaleLabel = $project->projectLanguageDirections->targetLanguage->value;
                        $targetlocales[$project->projectInfo->shortCode][] = [
                            'value' => $project->projectLanguageDirections->targetLanguage->locale,
                            'label' => $serviceLocaleLabel,
                        ];
                    }
                } else{
                    foreach ($project->projectLanguageDirections as $direction) {
                        if ($direction->sourceLanguage->locale == $this->currentLocale) {
                            // limit by stores locales
                            if (array_key_exists($direction->targetLanguage->locale, $this->localize)) {
                                $serviceLocaleLabel = $direction->targetLanguage->value;
                                $targetlocales[$project->projectInfo->shortCode][] = [
                                    'value' => $direction->targetLanguage->locale,
                                    'label' => $serviceLocaleLabel,
                                ];
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $projectsArray = [
                ['label' => 'None', 'value' => 'error']
            ];
            $projectsMessage = '<label class="mage-error">'
                . __("Can't get projects from GLPD Service") . '<br>' . $e->getMessage()
                . '</label>';
        }

        return [
            'projects' => $projectsArray,
            'projectsMessage' => $projectsMessage,
            'targetlocales' => $targetlocales,
        ];
    }

    /**
     * get items which were submitted to translation but due to fail were stored
     * to session
     *
     * @return array
     */
    protected function getItemsFromSession()
    {
        $items = [];
        $sessionData = $this->session->getFormData();
        if (!empty($sessionData)) {
            $items = array_keys($sessionData['items']);
        }
        return $items;
    }
}
