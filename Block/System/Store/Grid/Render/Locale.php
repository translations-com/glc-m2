<?php
namespace TransPerfect\GlobalLink\Block\System\Store\Grid\Render;

use Magento\Catalog\Model\Product\Exception;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Locale
 *
 * @package TransPerfect\GlobalLink\Block\System\Store\Grid\Render
 */
class Locale extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    protected $_helperGlobalLink;

    protected $storeManager;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \TransPerfect\GlobalLink\Helper\Data $helperGlobalLink,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->_helperGlobalLink = $helperGlobalLink;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $currentStore = $this->storeManager->getStore($row->getStoreId());
        if ($currentStore == null) {
            return null;
        }
        return '<a title="' . __(
            'Edit Locale'
        ) . '"
            href="' .
        $this->getUrl('adminhtml/*/editStore', ['store_id' => $row->getStoreId()]) .
        '">' .
        $this->getLocaleLabel($currentStore->getLocale())
        . '</a>';
    }

    /**
     * Get locale label
     *
     * @param $id
     *
     * @return string
     */
    protected function getLocaleLabel($id)
    {
        $locales = $this->_helperGlobalLink->getLocales(false, true, true);

        if (isset($locales[$id])) {
            return $locales[$id];
        }

        return '';
    }
}
