<?php
namespace TransPerfect\GlobalLink\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use \TransPerfect\GlobalLink\Model\Entity\TranslationStatus as TranslationStatusModel;

class TranslationStatus extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $storeManager;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->storeManager = $storeManager;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $dataSource = parent::prepareDataSource($dataSource);

        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $adminStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        $defaultStoreId = $this->storeManager->getDefaultStoreView()->getId();
        $storeId = $this->context->getFilterParam('store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID);

        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item[$fieldName])) {
                if (!empty($storeId) && !in_array($storeId, [$adminStoreId, $defaultStoreId])) {
                    $item[$fieldName] = TranslationStatusModel::STATUS_ENTITY_TRANSLATION_REQUIRED;
                }
            }
        }

        return $dataSource;
    }
}
