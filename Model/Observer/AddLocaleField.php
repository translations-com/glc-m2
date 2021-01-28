<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Justin Griffin jgriffin@translations.com
 */

namespace TransPerfect\GlobalLink\Model\Observer;

class AddLocaleField implements \Magento\Framework\Event\ObserverInterface
{

    protected $_helperGlobalLink;
    protected $registry;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \TransPerfect\GlobalLink\Helper\Data $helperGlobalLink
    ){
        $this->_helperGlobalLink = $helperGlobalLink;
        $this->registry = $registry;
    }

    /**
     * execute observer
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();

        $form       = $block->getForm();
        $fieldset   = $form->getElement('store_fieldset');
        $storeModel = $this->registry->registry('store_data');
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
    }
}