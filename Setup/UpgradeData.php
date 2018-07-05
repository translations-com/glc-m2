<?php

namespace TransPerfect\GlobalLink\Setup;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use TransPerfect\GlobalLink\Model\ResourceModel\Field\CollectionFactory as FieldCollectionFactory;
use TransPerfect\GlobalLink\Model\FieldFactory;
use Magento\Catalog\Setup\CategorySetupFactory;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $installer;

    /**
     * @var ModuleContextInterface
     */
    protected $context;

    /**
     * @var \TransPerfect\GlobalLink\Model\FieldFactory
     */
    protected $fieldFactory;

    /**
     * @var \Magento\Catalog\Setup\CategorySetupFactory
     */
    protected $catalogSetupFactory;

    /**
     * Field collection factory
     *
     * @var \TransPerfect\GlobalLink\Model\ResourceModel\field\CollectionFactory
     */
    protected $fieldCollectionFactory;

    public function __construct(
        FieldFactory $fieldFactory,
        FieldCollectionFactory $fieldCollectionFactory,
        CategorySetupFactory $catalogSetupFactory
    ) {
        $this->catalogSetupFactory = $catalogSetupFactory;
        $this->fieldFactory = $fieldFactory;
        $this->fieldCollectionFactory = $fieldCollectionFactory;
    }

    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $this->installer = $setup;
        $this->context = $context;
        $setup->startSetup();
        /**
         * Fill in fields gonfig data
         */
        if (version_compare($context->getVersion(), '0.3.4', '<')) {
            $fields = [
                \TransPerfect\GlobalLink\Helper\Data::CMS_PAGE_TYPE_ID => [
                    'title' => 'Page title',
                    'content_heading' => 'Content Heading',
                    'content' => 'Page Content',
                    'meta_keywords' => 'Meta Keywords',
                    'meta_description' => 'Meta Description'
                ],
                \TransPerfect\GlobalLink\Helper\Data::CMS_BLOCK_TYPE_ID => [
                    'title' => 'Block title',
                    'content' => 'Block Content'
                ],
            ];


            $fieldCollection = $this->fieldCollectionFactory->create();

            $fieldCollection->walk('delete');

            foreach ($fields as $objectType => $field) {
                foreach ($field as $fieldName => $fieldLabel) {
                    $fieldData = [
                        'field_name' => $fieldName,
                        'field_label' => $fieldLabel,
                        'object_type' => $objectType,
                        'include_in_translation' => 1,
                    ];
                    $field = $this->fieldFactory->create();
                    $field->setData($fieldData);
                    $fieldCollection->addItem($field);
                }
            }
            $fieldCollection->save();
        }

        $this->upgradeToVersion('0.6.1');
        $this->upgradeToVersion('0.7.0');

        $setup->endSetup();
    }

    protected function upgradeToVersion($versionNumber)
    {
        if (version_compare($this->context->getVersion(), $versionNumber, '<')) {
            $methodName = 'upgradeTo_'.str_replace('.', '', $versionNumber);
            $this->$methodName();
        }
    }

    /**
     * - add 'title' in blocks config table
     */
    protected function upgradeTo_061()
    {
        $fieldCollection = $this->fieldCollectionFactory->create();
        $fieldCollection->addFieldToFilter('field_name', 'title');
        $fieldCollection->addFieldToFilter('object_type', 13);
        $fieldsTotal = count($fieldCollection);
        if (!$fieldsTotal) {
            $field = $this->fieldFactory->create();
            $fieldData = [
                'field_name' => 'title',
                'field_label' => 'Block title',
                'object_type' => \TransPerfect\GlobalLink\Helper\Data::CMS_BLOCK_TYPE_ID,
                'include_in_translation' => 1,
            ];
            $field->setData($fieldData);
            $field->save();
        }
    }

    /**
     * - remove active_submission attributes from products and categories (were added in 0.4.4)
     */
    protected function upgradeTo_070()
    {
        /** @var \Magento\Catalog\Setup\CategorySetup $catalogSetup */
        $catalogSetup = $this->catalogSetupFactory->create(['setup' => $this->installer]);
        foreach ([
                $catalogSetup->getEntityTypeId(Product::ENTITY),
                $catalogSetup->getEntityTypeId(Category::ENTITY),
            ] as $entityTypeId) {
                $catalogSetup->removeAttribute($entityTypeId, 'active_submission');
        }
    }
}
