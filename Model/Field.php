<?php

namespace TransPerfect\GlobalLink\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Fielde
 *
 * @package TransPerfect\GlobalLink\Model
 */
class Field extends AbstractModel
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('TransPerfect\GlobalLink\Model\ResourceModel\Field');
    }

    /**
     * Check if field already exist in field configuration
     *
     * @param $type
     * @param $fieldname
     *
     * @return int
     */
    public function isFieldExist($type, $fieldname)
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('object_type', $type);
        $collection->addFieldToFilter('field_name', trim($fieldname));

        return $collection->getSize();
    }
}
