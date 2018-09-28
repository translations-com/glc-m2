<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Cms\Block\Create;

use \TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Form as BaseForm;

/**
 * Class Form
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Cms\Block\Create
 */
class Form extends BaseForm
{
    protected $collectionFactory;

    protected $blockNames = [];

    protected $blockStoreId;
    /**
     * @var array $blocksToTranslate array of ids
     */
    protected $blocksToTranslate;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param \TransPerfect\GlobalLink\Helper\Data    $helper
     * @param \Magento\Cms\Model\ResourceModel\Block\CollectionFactory $collectionFactory
     * @param []                                      $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Cms\Model\ResourceModel\Block\CollectionFactory $collectionFactory,
        \TransPerfect\GlobalLink\Model\TranslationService $translationService,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $registry, $formFactory, $helper, $translationService, $data);
        $this->blockStoreId = $registry->registry('objectStoreId');
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

        foreach ($this->blocksToTranslate as $blockId) {
            $fieldset->addField(
                'id_' . $blockId,
                'hidden',
                [
                    'name' => "submission[items][$blockId]",
                    'value' => $this->blockNames[$blockId],
                    'required' => 1,
                ]
            );
        }
        if($this->blockStoreId == null){
            $fieldset->addField(
                'store',
                'hidden',
                [
                    'name' => 'submission[store]',
                    'value' => $this->selectedStore->getId(),
                ]
            );
        }
        else{
            $fieldset->addField(
                'store',
                'hidden',
                [
                    'name' => 'submission[store]',
                    'value' => $this->blockStoreId,
                ]
            );
        }


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
        $this->_prepareBlocks();
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareBlocks()
    {
        $itemsToTranslate = $this->_coreRegistry->registry('itemsToTranslate');
        $this->blocksToTranslate = $itemsToTranslate['ids'];
        $this->blockNames = $itemsToTranslate['names'];

        return $this;
    }
}
