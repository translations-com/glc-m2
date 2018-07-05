<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Page\Add;

use Magento\Backend\Block\Widget\Form\Generic as GenericForm;

/**
 * Class Form
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\Config\Cms\Page\Add
 */
class Form extends GenericForm
{
    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context     $context
     * @param \Magento\Framework\Registry                 $registry
     * @param \Magento\Framework\Data\FormFactory         $formFactory
     * @param []                                       $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
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

        $fieldset = $form->addFieldset('translation_fieldset', ['legend' => __('Add New Field')]);
        $fieldset->addField(
            'field_name',
            'text',
            [
                'name' => 'field_name',
                'label' => __('Field Name'),
                'title' => __('Field Name'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'field_label',
            'text',
            [
                'name' => 'field_label',
                'label' => __('Field Label'),
                'title' => __('Field Label'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'include_in_translation',
            'select',
            [
                'label' => __('Translate'),
                'name' => 'include_in_translation',
                'values' => [
                    ['label' => 'Yes', 'value' => '1'],
                    ['label' => 'No', 'value' => '0'],
                ],
                'default' => '1'
            ]
        );

        $fieldset->addField(
            'object_type',
            'hidden',
            [
                'name' => 'object_type',
                'required' => true,
                'value' => \TransPerfect\GlobalLink\Helper\Data::CMS_PAGE_TYPE_ID
            ]
        );

        $fieldset->addField(
            'user_submitted',
            'hidden',
            [
                'name' => 'user_submitted',
                'required' => true,
                'value' => 1
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
