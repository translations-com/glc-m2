<?php
namespace TransPerfect\GlobalLink\Model\ResourceModel\Entity;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Attribute
 *
 * @package TransPerfect\GlobalLink\Model\ResourceModel\Entity
 */
class Attribute extends AbstractDb
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('eav_entity_attribute', 'entity_attribute_id');
    }
}
