<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Product\Create;

use \TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Form as BaseForm;

/**
 * Class Form
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Product\Create
 */
class Form extends BaseForm
{
    protected $collectionFactory;

    protected $productNames = [];
    /**
     * @var array $productsToTranslate Array of product IDs
     */
    protected $productsToTranslate;

    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                        $context
     * @param \Magento\Framework\Registry                                    $registry
     * @param \Magento\Framework\Data\FormFactory                            $formFactory
     * @param \TransPerfect\GlobalLink\Helper\Data                           $helper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param []                                                          $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
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

        foreach ($this->productsToTranslate as $productId) {
            $fieldset->addField(
                'id_' . $productId,
                'hidden',
                [
                    'name' => "submission[items][$productId]",
                    'value' => $this->productNames[$productId],
                    'required' => 1,
                ]
            );
        }

        $fieldset->addField(
            'include_associated_and_parent_categories',
            'select',
            [
                'label' => __('Translate associated and parent categories'),
                'name' => 'submission[include_associated_and_parent_categories]',
                'values' => [
                    ['label' => 'Yes', 'value' => '1'],
                    ['label' => 'No', 'value' => '0'],
                ],
                'default' => '1'
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
        $this->_prepareProducts();
        return $this;
    }

    /**
     * Prepare products for translation
     *
     * @return $this
     */
    protected function _prepareProducts()
    {
        $itemsToTranslate = $this->_coreRegistry->registry('itemsToTranslate');
        $this->productsToTranslate = $itemsToTranslate['ids'];
        $this->productNames = $itemsToTranslate['names'];

        return $this;
    }
}
