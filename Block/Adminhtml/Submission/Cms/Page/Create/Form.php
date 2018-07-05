<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Cms\Page\Create;

use \TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Form as BaseForm;

/**
 * Class Form
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Cms\Page\Create
 */
class Form extends BaseForm
{
    protected $collectionFactory;

    protected $pageNames = [];
    /**
     * @var array $pagesToTranslate array of ids
     */
    protected $pagesToTranslate;

    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                 $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\Data\FormFactory                     $formFactory
     * @param \TransPerfect\GlobalLink\Helper\Data                    $helper
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory
     * @param []                                                      $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory,
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

        foreach ($this->pagesToTranslate as $pageId) {
            $fieldset->addField(
                'id_' . $pageId,
                'hidden',
                [
                    'name' => "submission[items][$pageId]",
                    'value' => $this->pageNames[$pageId],
                    'required' => 1,
                ]
            );
        }

        $fieldset->addField(
            'include_cms_block_widgets',
            'select',
            [
                'label' => __('Also Translate CMS Block Widgets'),
                'name' => 'submission[include_cms_block_widgets]',
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
        $this->_preparePages();
        return $this;
    }

    /**
     * @return $this
     */
    protected function _preparePages()
    {
        $itemsToTranslate = $this->_coreRegistry->registry('itemsToTranslate');
        $this->pagesToTranslate = $itemsToTranslate['ids'];
        $this->pageNames = $itemsToTranslate['names'];

        return $this;
    }
}
