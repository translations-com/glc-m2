<?php
namespace TransPerfect\GlobalLink\Model\Entity;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Attribute
 *
 * @package TransPerfect\GlobalLink\Model\Entity\Attribute
 */
class Attribute extends AbstractModel
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('TransPerfect\GlobalLink\Model\ResourceModel\Entity\Attribute');
    }

    public function getRecord($id)
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('entity_attribute_id', $id);
        return $collection->getFirstItem();
    }
}
