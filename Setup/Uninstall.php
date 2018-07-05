<?php
namespace TransPerfect\GlobalLink\Setup;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Class Uninstall
 *
 * @package TransPerfect\GlobalLink\Setup
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var \Magento\Eav\Setup\EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * Uninstall constructor.
     *
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();


        /** @var \Magento\Eav\Setup\EavSetup  $eavSetup */
        //This is commented out as we are no longer using any active_submission columns
        //$eavSetup = $this->eavSetupFactory->create();

        $setup->getConnection()->dropTable($setup->getTable('globallink_job_queue'));
        $setup->getConnection()->dropTable($setup->getTable('globallink_job_items'));
        $setup->getConnection()->dropTable($setup->getTable('globallink_job_item_status'));
        $setup->getConnection()->dropTable($setup->getTable('globallink_field'));
        $setup->getConnection()->dropTable($setup->getTable('globallink_entity_translation_status'));
        $setup->getConnection()->dropTable($setup->getTable('globallink_field_product_category'));
        $setup->getConnection()->dropTable($setup->getTable('globallink_job_item_status_history'));
        $setup->getConnection()->dropColumn($setup->getTable('store'), 'locale');
        //$setup->getConnection()->dropColumn($setup->getTable('eav_entity_attribute'), 'include_in_translation');
        //$setup->getConnection()->dropColumn($setup->getTable('cms_block'), 'active_submission');
        //$setup->getConnection()->dropColumn($setup->getTable('cms_page'), 'active_submission');
        //$setup->getConnection()->dropColumn($setup->getTable('eav_attribute'), 'active_submission');
        //$setup->getConnection()->dropColumn($setup->getTable('catalog_category_entity'), 'active_submission');
        //$setup->getConnection()->dropColumn($setup->getTable('catalog_product_entity'), 'active_submission');

        /**
         * Removing active submission fields from entities' tables
         */
        //$setup->getConnection()->dropColumn($setup->getTable('cms_block'), 'active_submission');
        //$setup->getConnection()->dropColumn($setup->getTable('cms_page'), 'active_submission');
        //$setup->getConnection()->dropColumn($setup->getTable('eav_attribute'), 'active_submission');
        //$setup->getConnection()->dropColumn($setup->getTable('catalog_category_entity'), 'active_submission');
        //$setup->getConnection()->dropColumn($setup->getTable('catalog_product_entity'), 'active_submission');

        /**
         * Removing static attributes from products and categories
         */
        //$eavSetup->removeAttribute(Product::ENTITY, 'active_submission');
        //$eavSetup->removeAttribute(Category::ENTITY, 'active_submission');
        /**
         * Removing generated rows in the core_config_data table
         */
        $sql = "DELETE FROM `core_config_data` WHERE `path` LIKE '%globallink%';";
        $setup->getConnection()->query($sql);
        /**
         * End setup
         */
        $setup->endSetup();
    }
}
