<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TransPerfect\GlobalLink\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use TransPerfect\GlobalLink\Model\ResourceModel\Field\CollectionFactory as FieldCollectionFactory;
use TransPerfect\GlobalLink\Model\FieldFactory;
use Magento\Catalog\Setup\CategorySetupFactory;

/**
* Patch is mechanism, that allows to do atomic upgrade data changes
*/
class InstallFieldConfig implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $installer;

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

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        FieldFactory $fieldFactory,
        FieldCollectionFactory $fieldCollectionFactory,
        CategorySetupFactory $catalogSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup
    )
    {
        $this->installer = $moduleDataSetup;
        $this->fieldCollectionFactory = $fieldCollectionFactory;
        $this->fieldFactory = $fieldFactory;
        $this->catalogSetupFactory = $catalogSetupFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->installer->startSetup();
        /**
         * Fill in fields gonfig data
         */
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
            \TransPerfect\GlobalLink\Helper\Data::PRODUCT_REVIEW_ID => [
                'title' => 'Title',
                'detail' => 'Detail Description'
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

        $this->installer->endSetup();

    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
