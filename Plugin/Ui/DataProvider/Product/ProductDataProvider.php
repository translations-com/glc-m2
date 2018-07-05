<?php
/**
 * Plugin for products grid collection
 */
namespace TransPerfect\GlobalLink\Plugin\Ui\DataProvider\Product;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider as Subject;
use Magento\Store\Model\StoreManagerInterface;
use \TransPerfect\GlobalLink\Model\Entity\TranslationStatus as TranslationStatusModel;

/**
 * Plugin class
 */
class ProductDataProvider
{
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $_storeManager;

    /**
     * @var int
     */
    protected $currentStore = null;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
    }

    /**
     * If store filter is in action - save it to use later in join
     *
     * @param \Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider $subject
     * @param \Magento\Framework\Api\Filter                                $filter
     *
     * @return [\Magento\Framework\Api\Filter]
     */
    public function beforeAddFilter(Subject $subject, \Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() == 'store_id') {
            $this->currentStore = $filter->getValue();
        }

        return [$filter];
    }
    public function aroundAddFilter(
        Subject $subject,
        \Closure $proceed,
        \Magento\Framework\Api\Filter $filter
    ) {
        if ($filter->getField() == 'translation_status') {
            $adminStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            $defaultStoreId = $this->_storeManager->getDefaultStoreView()->getId();
            $storeId = $this->currentStore;

            $status = $filter->getValue();
            $collection = $subject->getCollection();
            $collection->getSelect()->where('gets.translation_status = ?', $status);
            if ($status == TranslationStatusModel::STATUS_ENTITY_TRANSLATION_REQUIRED) {
                if (!empty($storeId) && !in_array($storeId, [$adminStoreId, $defaultStoreId])) {
                    $collection->getSelect()->orwhere('gets.translation_status IS NULL');
                }
            }
        } else {
            return $proceed($filter);
        }
    }

    /**
     * Join translation status to collection
     * If no store filter is in action default store will be used for joining
     *
     * @param \Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider $subject
     *
     * @return void
     */
    public function beforeGetData(Subject $subject)
    {
        if (empty($this->currentStore)) {
            $this->currentStore = $this->_storeManager->getDefaultStoreView()->getId();
        }
        $collection = $subject->getCollection();
        $collection->getSelect()->joinLeft(
            ['gets' => $collection->getTable('globallink_entity_translation_status')],
            'gets.entity_type_id = '.\TransPerfect\GlobalLink\Helper\Data::CATALOG_PRODUCT_TYPE_ID.
            ' AND '.
            'gets.store_view_id = '.$this->currentStore.
            ' AND '.
            'gets.entity_id = e.entity_id',
            ['translation_status']
        );
    }
}
