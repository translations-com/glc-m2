<?php
namespace TransPerfect\GlobalLink\Model\ResourceModel\Entity\TranslationStatus;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package TransPerfect\GlobalLink\Model\ResourceModel\Entity\Attribute
 */
class Collection extends AbstractCollection
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init(
            'TransPerfect\GlobalLink\Model\Entity\TranslationStatus',
            'TransPerfect\GlobalLink\Model\ResourceModel\Entity\TranslationStatus'
        );
    }
}
