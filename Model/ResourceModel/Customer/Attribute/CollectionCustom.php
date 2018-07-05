<?php
namespace TransPerfect\GlobalLink\Model\ResourceModel\Customer\Attribute;

use \Magento\Customer\Model\ResourceModel\Attribute\Collection;

/**
 * Custom Category attribute collection
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
}
