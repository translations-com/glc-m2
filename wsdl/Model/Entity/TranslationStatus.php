<?php
namespace TransPerfect\GlobalLink\Model\Entity;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class TranslationStatus
 *
 * @package TransPerfect\GlobalLink\Model
 */
class TranslationStatus extends AbstractModel implements OptionSourceInterface
{
    /**
     * statuses
     */
    const STATUS_ENTITY_NULL = null;              // Null status. No such row in table
    const STATUS_ENTITY_NONE = 0;                 // no status. 0 or no such row in table
    const STATUS_ENTITY_IN_PROGRESS = 1;          // translation task for this entity-to-store was sent
    const STATUS_ENTITY_TRANSLATION_REQUIRED = 2; // entity was edited in source language
    const STATUS_ENTITY_TRANSLATED = 3;           // translation for entity-to-store is up to date

    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('TransPerfect\GlobalLink\Model\ResourceModel\Entity\TranslationStatus');
    }

    /**
     * prepare options array
     */
    public function toOptionArray()
    {
        $options[] = [
            'label' => '',
            'value' => self::STATUS_ENTITY_NONE,
        ];
        $options[] = [
            'label' => __('Processing'),
            'value' => self::STATUS_ENTITY_IN_PROGRESS,
        ];
        $options[] = [
            'label' => __('Translation Required'),
            'value' => self::STATUS_ENTITY_TRANSLATION_REQUIRED,
        ];
        $options[] = [
            'label' => __('Translated'),
            'value' => self::STATUS_ENTITY_TRANSLATED,
        ];
        return $options;
    }

    /**
     * prepare options array
     */
    public function toProductAttributeOptionArray()
    {
        $options[] = [
            'label' => __(''),
            'value' => self::STATUS_ENTITY_NONE,
        ];
        $options[] = [
            'label' => __('Processing'),
            'value' => self::STATUS_ENTITY_IN_PROGRESS,
        ];
        $options[] = [
            'label' => __('Entity Has Been Modified - Translation Required'),
            'value' => self::STATUS_ENTITY_TRANSLATION_REQUIRED,
        ];
        $options[] = [
            'label' => __('Translated'),
            'value' => self::STATUS_ENTITY_TRANSLATED,
        ];
        $options[] = [
            'label' => __('New Entity - Translation Required'),
            'value' => self::STATUS_ENTITY_NULL,
        ];
        return $options;
    }

    /**
     * prepare options array
     */
    public function optionsToArray()
    {
        $optionArray = $this->toOptionArray();
        $options = [];
        foreach ($optionArray as $item) {
            $options[$item['value']] = $item['label'];
        }
        return $options;
    }

    /**
     * prepare product attribute options array
     */
    public function productAttributeOptionsToArray()
    {
        $optionArray = $this->toProductAttributeOptionArray();
        $options = [];
        foreach ($optionArray as $item) {
            $options[$item['value']] = $item['label'];
        }
        return $options;
    }

    /**
     * get title by value
     *
     * @param int $value
     *
     * @return string
     */
    public function getOptionLabel($value)
    {
        $optionArray = $this->toOptionArray();
        foreach ($optionArray as $item) {
            if ($item['value'] == $value) {
                return $item['label'];
            }
        }
        return '';
    }
}
