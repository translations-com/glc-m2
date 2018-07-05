<?php
namespace TransPerfect\GlobalLink\Block\System\Store\Grid\Render;

use Magento\Catalog\Model\Product\Exception;

/**
 * Class Locale
 *
 * @package TransPerfect\GlobalLink\Block\System\Store\Grid\Render
 */
class Locale extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    protected $_helperGlobalLink;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \TransPerfect\GlobalLink\Helper\Data $helperGlobalLink,
        array $data = []
    ) {
        $this->_helperGlobalLink = $helperGlobalLink;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if (!$row->getData($this->getColumn()->getIndex())) {
            return null;
        }
        return '<a title="' . __(
            'Edit Locale'
        ) . '"
            href="' .
        $this->getUrl('adminhtml/*/editStore', ['store_id' => $row->getStoreId()]) .
        '">' .
        $this->getLocaleLabel($row->getLocale())
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
        $locales = $this->_helperGlobalLink->getLocales();

        if (isset($locales[$id])) {
            return $locales[$id];
        }

        return '';
    }
}
