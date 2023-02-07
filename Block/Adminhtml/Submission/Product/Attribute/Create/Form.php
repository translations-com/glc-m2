<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Product\Attribute\Create;

use \TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Form as BaseForm;

/**
 * Class Form
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Product\Attribute\Create
 */
class Form extends BaseForm
{
    protected $collectionFactory;

    protected $attributeNames = [];
    /**
     * @var array $attributesToTranslate array of ids
     */
    protected $attributesToTranslate;

    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                                   $context
     * @param \Magento\Framework\Registry                                               $registry
     * @param \Magento\Framework\Data\FormFactory                                       $formFactory
     * @param \TransPerfect\GlobalLink\Helper\Data                                      $helper
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory  $collectionFactory
     * @param []                                                                        $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory,
        \TransPerfect\GlobalLink\Model\TranslationService $translationService,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $registry, $formFactory, $helper, $translationService, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();
        $form = $this->getForm();
        $fieldset = $form->getElement('translation_fieldset');
        if(is_array($this->attributesToTranslate)) {
            foreach ($this->attributesToTranslate as $attributeId) {
                $fieldset->addField(
                    'id_' . $attributeId,
                    'hidden',
                    [
                        'name' => "submission[items][$attributeId]",
                        'value' => $this->attributeNames[$attributeId],
                        'required' => 1,
                    ]
                );
            }
        } else{
            $fieldset->addField(
                'id_' . $this->attributesToTranslate,
                'hidden',
                [
                    'name' => "submission[items][$this->attributesToTranslate]",
                    'value' => $this->attributeNames[$this->attributesToTranslate],
                    'required' => 1,
                ]
            );
        }
        $fieldset->addField(
            'include_options',
            'select',
            [
                'label' => __('Also Translate Attribute Options'),
                'name' => 'submission[include_options]',
                'values' => [
                    ['label' => 'Yes', 'value' => '1'],
                    ['label' => 'No', 'value' => '0'],
                ],
                'default' => '0'
            ]
        );
        $fieldset->addField(
            'store',
            'hidden',
            [
                'name' => 'submission[store]',
                'value' => $this->selectedStore->getId(),
            ]
        );

        if ($this->session->getFormData()) {
            $form->setValues($this->session->getFormData());
            $this->session->setFormData(null);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareData()
    {
        parent::_prepareData();
        $this->_prepareAttributes();
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareAttributes()
    {
        $this->attributesToTranslate = $this->getRequest()->getParam('selected');

        if (empty($this->attributesToTranslate)) {
            $this->attributesToTranslate = $this->getItemsFromSession();
        }

        $this->attributeNames = $this->helper->getOtherEntityNames(
            $this->collectionFactory,
            $this->getRequest()->getParam('store'),
            $this->attributesToTranslate,
            'attribute',
            'frontend_label'
        );

        return $this;
    }
}
