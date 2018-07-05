<?php
namespace TransPerfect\GlobalLink\Block\Adminhtml\AttributeSet;

use Magento\Backend\Block\Store\Switcher as BaseSwitcher;
use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\WebsiteFactory;
use Magento\Store\Model\StoreFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Eav\Model\Config;

/**
 * Class Switcher
 *
 * @package TransPerfect\GlobalLink\Block\Adminhtml\AttributeSet
 */
class Switcher extends BaseSwitcher
{
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory
     */
    protected $attributeSetFactory;

    /**
     * @var \Magento\Eav\Api\AttributeSetRepositoryInterface
     */
    protected $attributeSetRepository;

    /**
     * @var
     */
    protected $productEntityTypeId;

    public function __construct(
        Context $context,
        WebsiteFactory $websiteFactory,
        GroupFactory $storeGroupFactory,
        StoreFactory $storeFactory,
        CollectionFactory $attributeSetFactory,
        AttributeSetRepositoryInterface $attributeSetRepository,
        Config $eavConfig,
        array $data = []
    ) {
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->productEntityTypeId = $eavConfig->getEntityType(\Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE)
            ->getEntityTypeId();

        parent::__construct(
            $context,
            $websiteFactory,
            $storeGroupFactory,
            $storeFactory,
            $data
        );
    }

    /**
     * Get Product Attribute Set collection for dropdown
     *
     * @return mixed
     */
    public function getAttributeSets()
    {
        $attributeSetCollection = $this->attributeSetFactory->create();
        $attributeSetCollection->addFieldToFilter('entity_type_id', ['eq' => $this->productEntityTypeId]);

        return $attributeSetCollection;
    }

    /**
     * Get current attribute set object
     *
     * @return $this|bool
     */
    public function getCurrentAttributeSet()
    {
        if ($attributeSetSelected = $this->getCurrentAttributeSetId()) {
            return $this->attributeSetRepository->get($attributeSetSelected);
        }

        return false;
    }

    /**
     * Check if Attribute Set is already selected and return ID
     *
     * @return mixed
     */
    public function getCurrentAttributeSetId()
    {
        return $this->getRequest()->getParam('attribute_set');
    }

    /**
     * @return string
     */
    public function getSwitchUrl()
    {
        if ($url = $this->getData('switch_url')) {
            return $url;
        }
        return $this->getUrl(
            '*/*/*',
            [
                '_current' => true,
                'attribute_set' => null
            ]
        );
    }
}
