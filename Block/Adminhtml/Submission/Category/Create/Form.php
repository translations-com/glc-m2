<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Category\Create;

use \TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Form as BaseForm;

/**
 * Class Form
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Category\Create
 */
class Form extends BaseForm
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var array $categoriesToTranslate array of ids
     */
    protected $categoriesToTranslate;

    protected $categoryNames = [];

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $collectionFactory,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        \TransPerfect\GlobalLink\Model\TranslationService $translationService,
        array $data = []
    ) {
        $this->categoryCollectionFactory = $collectionFactory;
        $this->yesNo = $yesno;
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
        $yesno = $this->yesNo->toOptionArray();
        foreach ($this->categoriesToTranslate as $categoryId) {
            $fieldset->addField(
                'id_' . $categoryId,
                'hidden',
                [
                    'name' => "submission[items][$categoryId]",
                    'value' => $this->categoryNames[$categoryId],
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

        $fieldset->addField(
            'send_with_subcategories',
            'select',
            [
                'label' => __('Also Translate Subcategories'),
                'name' => 'submission[send_with_subcategories]',
                'values' => $yesno,
                'value' => 0,
                'required' => 1
            ]
        );

        if ($this->session->getFormData()) {
            $form->setValues($this->session->getFormData());
            $this->session->setFormData(null);
        }

        $this->setForm($form);
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareData()
    {
        parent::_prepareData();
        $this->_prepareCategories();
        return $this;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareCategories()
    {
        $itemsToTranslate = $this->_coreRegistry->registry('itemsToTranslate');
        $this->categoriesToTranslate = $itemsToTranslate['ids'];
        $this->categoryNames = $itemsToTranslate['names'];

        return $this;
    }
}
