<?php
namespace TransPerfect\GlobalLink\Model\Plugin\Website;

/**
 * Websites collection
 */
class CollectionPlugin
{
    /**
     * Add `locale` field to collection
     *
     * @param \Magento\Store\Model\ResourceModel\Website\Collection $subject
     *
     * @return $this
     * @throws \Zend_Db_Select_Exception
     */
    public function afterJoinGroupAndStore(\Magento\Store\Model\ResourceModel\Website\Collection $subject)
    {
        $part = $subject->getSelect()->getPart('columns');
        $part[] = ['store_table', 'locale', 'locale'];
        $subject->getSelect()->setPart('columns', $part);

        return $this;
    }
}
