<?php
namespace TransPerfect\GlobalLink\Model\ResourceModel\Product\Attribute;

use \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;

/**
 * Custom Product attribute collection
 */
class CollectionCustom extends Collection
{
    /**
     * Join eav_entity_attribute table
     *
     * @return $this
     */
    public function appendData()
    {
        //$this->getSelect()->distinct(true);
        $this->join(
            $this->getTable('eav_entity_attribute'),
            'eav_entity_attribute.attribute_id = main_table.attribute_id',
            'include_in_translation'
        );

        return $this;
    }

    public function appendFieldData()
    {
        //$this->getSelect()->distinct(true);
        $this->join(
            $this->getTable('globallink_field_product_category'),
            'globallink_field_product_category.attribute_id = main_table.attribute_id',
            'include_in_translation'
        );

        return $this;
    }
}
