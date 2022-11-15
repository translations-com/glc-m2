<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Banner\Create;

use \TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Form as BaseForm;

/**
 * Class Form
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Banner\Create
 */
class Form extends BaseForm
{
    protected $collectionFactory;

    protected $bannerNames;
    /**
     * @var array $attributesToTranslate array of ids
     */
    protected $bannersToTranslate;

    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                               $context
     * @param \Magento\Framework\Registry                                           $registry
     * @param \Magento\Framework\Data\FormFactory                                   $formFactory
     * @param \TransPerfect\GlobalLink\Helper\Data                                  $helper
     * @param \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory          $collectionFactory
     * @param []                                                                    $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $collectionFactory,
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

        foreach ($this->bannersToTranslate as $bannerId => $bannerValue) {
            $fieldset->addField(
                'id_' . $bannerId,
                'hidden',
                [
                    'name' => "submission[items][$bannerId]",
                    'value' => $bannerValue,
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
        $this->_prepareBanners();
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareBanners()
    {
        $this->bannersToTranslate = $this->getRequest()->getParam('banner');

        if (empty($this->bannersToTranslate)) {
            $this->bannersToTranslate = $this->getItemsFromSession();
        }

        $this->bannersToTranslate = $this->getTitles();
        return $this;
    }

    /**
     * @return array
     */
    public function getTitles()
    {
        $names = [];
        $collection = $this->collectionFactory->create();
        $bannersToTranslate = (is_array($this->bannersToTranslate)) ? explode(',', $this->bannersToTranslate) : $this->bannersToTranslate;
        foreach ($bannersToTranslate as $id) {
            $currentRecord = $collection->getItemById($id);
            $names[$id] = $currentRecord->getData('name');
        }
        return $names;
    }
}
