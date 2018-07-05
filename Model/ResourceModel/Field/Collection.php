<?php

namespace TransPerfect\GlobalLink\Model\ResourceModel\Field;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package TransPerfect\GlobalLink\Model\ResourceModel\Field
 */
class Collection extends AbstractCollection
{
    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init(
            'TransPerfect\GlobalLink\Model\Field',
            'TransPerfect\GlobalLink\Model\ResourceModel\Field'
        );
    }
}
