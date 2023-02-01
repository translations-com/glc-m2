<?php
/**
 * Created by PhpStorm.
 * User: jgriffin
 * Date: 10/11/2019
 * Time: 9:34 AM
 */
namespace TransPerfect\GlobalLink\Block\Adminhtml\Submission\Review\Create;

use \TransPerfect\GlobalLink\Block\Adminhtml\Submission\Base\Form as BaseForm;

/**
 * Class Form
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Submission\Review\Create
 */
class Form extends BaseForm
{
    protected $collectionFactory;

    protected $reviewNames;
    /**
     * @var array $attributesToTranslate array of ids
     */
    protected $reviewsToTranslate;

    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                               $context
     * @param \Magento\Framework\Registry                                           $registry
     * @param \Magento\Framework\Data\FormFactory                                   $formFactory
     * @param \TransPerfect\GlobalLink\Helper\Data                                  $helper
     * @param \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory  $collectionFactory
     * @param []                                                                    $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \TransPerfect\GlobalLink\Helper\Data $helper,
        \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory $collectionFactory,
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

        foreach ($this->reviewsToTranslate as $reviewId) {
            $fieldset->addField(
                'id_' . $reviewId,
                'hidden',
                [
                    'name' => "submission[items][$reviewId]",
                    'value' => $this->reviewNames[$reviewId],
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
        $this->_prepareReviews();
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareReviews()
    {
        $this->reviewsToTranslate = explode(",", $this->getRequest()->getParam('reviews'));

        if (empty($this->reviewsToTranslate)) {
            $this->reviewsToTranslate = $this->getItemsFromSession();
        }

        $this->reviewNames = $this->getTitles();
        return $this;
    }

    /**
     * @return array
     */
    public function getTitles()
    {
        $names = [];
        $collection = $this->collectionFactory->create();
        foreach ($this->reviewsToTranslate as $id) {
            $currentRecord = $collection->getItemById($id);
            $names[$id] = $currentRecord->getData('title');
        }
        return $names;
    }
}
