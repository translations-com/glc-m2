<?php

namespace TransPerfect\GlobalLink\Model;

use Magento\Framework\Model\AbstractModel;

class FieldProductCategory extends AbstractModel
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('TransPerfect\GlobalLink\Model\ResourceModel\FieldProductCategory');
    }
    /**
     * Check if field already exist in field configuration
     *
     * @param $type
     * @param $fieldname
     *
     * @return int
     */
    public function isFieldExist($id)
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('entity_attribute_id', $id);

        return $collection->getSize();
    }
    public function getRecord($id)
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('entity_attribute_id', $id);

        return $collection->getFirstItem();
    }
}
