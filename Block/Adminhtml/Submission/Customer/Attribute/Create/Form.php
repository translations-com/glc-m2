<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Customer\Attribute\Create;

use \TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Form as BaseForm;

/**
 * Class Form
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Customer\Attribute\Create
 */
class Form extends BaseForm
{
    protected $collectionFactory;

    protected $attributeNames;
    /**
     * @var array $attributesToTranslate array of ids
     */
    protected $attributesToTranslate;

    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                           $context
     * @param \Magento\Framework\Registry                                       $registry
     * @param \Magento\Framework\Data\FormFactory                               $formFactory
     * @param \TransPerfect\GlobalLink\Helper\Data                              $helper
     * @param \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $collectionFactory
     * @param []                                                                $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $collectionFactory,
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
        $this->_prepareCustomerAttributes();
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareCustomerAttributes()
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
