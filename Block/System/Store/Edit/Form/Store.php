<?php
namespace TransPerfect\GlobalLink\Block\System\Store\Edit\Form;

use Magento\Backend\Block\System\Store\Edit\Form\Store as MagentoStore;

/**
 * Class Store
 *
 * @package TransPerfect\GlobalLink\Block\System\Store\Edit\Form
 */
class Store extends MagentoStore
{
    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $_helperGlobalLink;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\GroupFactory $groupFactory
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \TransPerfect\GlobalLink\Helper\Data $helperGlobalLink
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\GroupFactory $groupFactory,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \TransPerfect\GlobalLink\Helper\Data $helperGlobalLink,
        array $data = []
    ) {
        $this->_helperGlobalLink = $helperGlobalLink;
        parent::__construct($context, $registry, $formFactory, $groupFactory, $websiteFactory, $data);
    }

    /**
     * Prepare store specific fieldset
     *
     * @param \Magento\Framework\Data\Form $form
     * @return void
     */
    protected function _prepareStoreFieldset(\Magento\Framework\Data\Form $form)
    {
        $storeModel = $this->_coreRegistry->registry('store_data');
        $postData = $this->_coreRegistry->registry('store_post_data');
        if ($postData) {
            $storeModel->setData($postData['store']);
        }
        $fieldset = $form->addFieldset('store_fieldset', ['legend' => __('Store View Information')]);

        $storeAction = $this->_coreRegistry->registry('store_action');
        if ($storeAction == 'edit' || $storeAction == 'add') {
            $fieldset->addField(
                'store_group_id',
                'select',
                [
                    'name' => 'store[group_id]',
                    'label' => __('Store'),
                    'value' => $storeModel->getGroupId(),
                    'values' => $this->_getStoreGroups(),
                    'required' => true,
                    'disabled' => $storeModel->isReadOnly()
                ]
            );
            $fieldset = $this->prepareGroupIdField($form, $storeModel, $fieldset);
        }

        $fieldset->addField(
            'store_name',
            'text',
            [
                'name' => 'store[name]',
                'label' => __('Name'),
                'value' => $storeModel->getName(),
                'required' => true,
                'disabled' => $storeModel->isReadOnly()
            ]
        );
        $fieldset->addField(
            'store_code',
            'text',
            [
                'name' => 'store[code]',
                'label' => __('Code'),
                'value' => $storeModel->getCode(),
                'required' => true,
                'disabled' => $storeModel->isReadOnly()
            ]
        );

        $fieldset->addField(
            'locale',
            'select',
            [
                'name' => 'store[locale]',
                'label' => __('GlobalLink Locale'),
                'value' => $storeModel->getLocale(),
                'required' => false,
                'default' => 0,
                'values' => $this->_helperGlobalLink->getLocaleOptions()
            ]
        );

        $fieldset->addField(
            'store_is_active',
            'select',
            [
                'name' => 'store[is_active]',
                'label' => __('Status'),
                'value' => $storeModel->isActive(),
                'options' => [0 => __('Disabled'), 1 => __('Enabled')],
                'required' => true,
                'disabled' => $storeModel->isReadOnly()
                    || ($storeModel->getId() && $storeModel->getIsDefault() && $storeModel->isActive())
            ]
        );

        $fieldset->addField(
            'store_sort_order',
            'text',
            [
                'name' => 'store[sort_order]',
                'label' => __('Sort Order'),
                'value' => $storeModel->getSortOrder(),
                'required' => false,
                'disabled' => $storeModel->isReadOnly()
            ]
        );

        $fieldset->addField(
            'store_is_default',
            'hidden',
            ['name' => 'store[is_default]', 'no_span' => true, 'value' => $storeModel->getIsDefault()]
        );

        $fieldset->addField(
            'store_store_id',
            'hidden',
            [
                'name' => 'store[store_id]',
                'no_span' => true,
                'value' => $storeModel->getId(),
                'disabled' => $storeModel->isReadOnly()
            ]
        );
    }

    /**
     * Prepare group id field in the fieldset
     *
     * @param \Magento\Framework\Data\Form $form
     * @param \Magento\Store\Model\Store $storeModel
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     * @return \Magento\Framework\Data\Form\Element\Fieldset
     */
    private function prepareGroupIdField(
        \Magento\Framework\Data\Form $form,
        \Magento\Store\Model\Store $storeModel,
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset
    ) {
        if ($storeModel->getId() && $storeModel->getGroup()->getDefaultStoreId() == $storeModel->getId()) {
            if ($storeModel->getGroup() && $storeModel->getGroup()->getStoresCount() > 1) {
                $form->getElement('store_group_id')->setDisabled(true);

                $fieldset->addField(
                    'store_hidden_group_id',
                    'hidden',
                    ['name' => 'store[group_id]', 'no_span' => true, 'value' => $storeModel->getGroupId()]
                );
            } else {
                $fieldset->addField(
                    'store_original_group_id',
                    'hidden',
                    [
                        'name' => 'store[original_group_id]',
                        'no_span' => true,
                        'value' => $storeModel->getGroupId()
                    ]
                );
            }
        }
        return $fieldset;
    }
}
