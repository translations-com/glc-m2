<?php
/**
 * Upgrade Schema
 */
namespace TransPerfect\GlobalLink\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Upgrade schema
 */
class UpgradeSchema implements UpgradeSchemaInterface
{

    protected $installer;
    protected $tableItems;
    protected $tableQueue;
    protected $tableField;
    protected $context;
    protected $cmsBlocks;
    protected $cmsPages;
    protected $attributeTable;
    protected $categoryTable;
    protected $productTable;

    /**
     * upgrade
     *
     * SchemaSetupInterface   $setup
     * ModuleContextInterface $context
     *
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->installer = $setup;
        $this->installer->startSetup();
        $this->context = $context;

        $this->tableItems = $this->installer->getTable('globallink_job_items');
        $this->tableQueue = $this->installer->getTable('globallink_job_queue');
        $this->tableField = $this->installer->getTable('globallink_field');
        $this->cmsBlocks = $this->installer->getTable('cms_block');
        $this->cmsPages = $this->installer->getTable('cms_page');
        $this->attributeTable = $this->installer->getTable('eav_attribute');
        $this->categoryTable = $this->installer->getTable('catalog_category_entity');
        $this->productTable = $this->installer->getTable('catalog_product_entity');

        /**
         * - Add globallink_job_items.entity_type_id
         * - Add globallink_job_items.pd_locale_iso_code
         * - Remove globallink_job_queue.entity_type_id
         * - Remove globallink_job_queue.pd_locale_iso_code
         * - Remove globallink_job_queue.entity_id
         */
        if (version_compare($context->getVersion(), '0.2.0', '<')) {
            $connection = $this->installer->getConnection();

            $connection->truncateTable($this->tableQueue);
            $connection->truncateTable($this->tableItems);

            $connection->addColumn(
                $this->tableItems,
                'entity_type_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'Entity Type ID'
                ]
            );

            $connection->addColumn(
                $this->tableItems,
                'pd_locale_iso_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'ID of locale from language mapping'
                ]
            );

            $connection->dropColumn(
                $this->tableQueue,
                'entity_type_id'
            );
            $connection->dropColumn(
                $this->tableQueue,
                'pd_locale_iso_code'
            );
            $connection->dropColumn(
                $this->tableQueue,
                'entity_id'
            );
            $connection->addColumn(
                $this->tableQueue,
                'include_cms_block_widgets',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'unsigned' => true,
                    'nullable' => true,
                    'comment' => 'Only applicable to the CMS Page entity type.'
                ]
            );
        }

        /**
         * - Add eav_attribute.include_in_translation
         */
        if (version_compare($context->getVersion(), '0.2.2', '<')) {
            $this->installer->getConnection()->addColumn(
                $this->installer->getTable('eav_entity_attribute'),
                'include_in_translation',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'length' => 1,
                    'nullable' => true,
                    'comment' => 'Include in Translation'
                ]
            );
        }


        if (version_compare($context->getVersion(), '0.2.3', '<')) {
            $connection = $this->installer->getConnection();
            $connection->modifyColumn(
                $this->tableField,
                'object_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'Object Type',
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.2.4', '<')) {
            $connection = $this->installer->getConnection();
            $this->tableItems = $this->installer->getTable('globallink_job_items');
            $connection->addColumn(
                $this->tableItems,
                'entity_name',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Entity Name',
                    'after' => 'entity_id'
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.2.5', '<')) {
            $connection = $this->installer->getConnection();
            $connection->addColumn(
                $this->tableField,
                'field_label',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Field Label',
                    'after' => 'field_name',
                    'length' => 255
                ]
            );
            $connection->delete($this->tableField);
        }

        $this->upgradeToVersion('0.3.0');
        $this->upgradeToVersion('0.3.1');
        $this->upgradeToVersion('0.4.0');
        $this->upgradeToVersion('0.5.2');
        $this->upgradeToVersion('0.6.0');
        $this->upgradeToVersion('0.7.0');
        $this->upgradeToVersion('0.7.1');
        $this->upgradeToVersion('0.7.2');
        $this->upgradeToVersion('0.8.0');
        $this->upgradeToVersion('0.9.0');
        $this->upgradeToVersion('0.9.1');
        $this->upgradeToVersion('0.9.5');
        $this->upgradeToVersion('0.9.7');
        $this->installer->endSetup();
    }

    protected function upgradeToVersion($versionNumber)
    {
        if (version_compare($this->context->getVersion(), $versionNumber, '<')) {
            $methodName = 'upgradeTo_'.str_replace('.', '', $versionNumber);
            $this->$methodName();
        }
    }

    /**
     * - add globallink_job_queue.project_shortcode
     * - remove globallink_job_queue.project_id
     */
    protected function upgradeTo_030()
    {
        $connection = $this->installer->getConnection();
        $connection->addColumn(
            $this->tableQueue,
            'project_shortcode',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 32,
                'after' => 'due_date',
                'comment' => 'Project Shortcode'
            ]
        );
        $connection->dropColumn(
            $this->tableQueue,
            'project_id'
        );
    }

    /**
     * - add globallink_job_item.submission_ticket
     */
    protected function upgradeTo_031()
    {
        $connection = $this->installer->getConnection();
        $connection->addColumn(
            $this->tableItems,
            'submission_ticket',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 64,
                'after' => 'pd_locale_iso_code',
                'comment' => 'Submission Ticket'
            ]
        );
    }

    /**
     * - add globallink_job_item.document_ticket
     */
    protected function upgradeTo_040()
    {
        $connection = $this->installer->getConnection();
        $connection->addColumn(
            $this->tableItems,
            'document_ticket',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 64,
                'after' => 'submission_ticket',
                'comment' => 'Document Ticket'
            ]
        );
    }

    /**
     * - modify globallink_job_items.pd_locale_iso_code
     */
    protected function upgradeTo_052()
    {
        $connection = $this->installer->getConnection();
        $connection->modifyColumn(
            $this->tableItems,
            'pd_locale_iso_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 64,
            ]
        );
    }

    /**
     * - add globallink_job_item.target_stores
     */
    protected function upgradeTo_060()
    {
        $connection = $this->installer->getConnection();
        $connection->addColumn(
            $this->tableItems,
            'target_stores',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 64,
                'after' => 'pd_locale_iso_code',
                'comment' => 'Target Store Ids'
            ]
        );
    }

    /**
     * - drop active_submission columns from all tables (were added in 0.4.1, 0.4.2, 0.4.3)
     */
    protected function upgradeTo_070()
    {
        $connection = $this->installer->getConnection();
        foreach ([
                $this->cmsPages,
                $this->cmsBlocks,
                $this->attributeTable,
                $this->categoryTable,
                $this->productTable,
            ] as $table) {
                $connection->dropColumn($table, 'active_submission');
        }
    }

    /**
     * Create table 'globallink_entity_translation_status'
     */
    protected function upgradeTo_071()
    {
        $connection = $this->installer->getConnection();
        $connection->dropTable($this->installer->getTable('globallink_entity_translation_status'));
        $table = $connection
            ->newTable($this->installer->getTable('globallink_entity_translation_status'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'entity_type_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Entity Type Id'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Entity Id'
            )
            ->addColumn(
                'store_view_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Store View Id'
            )
            ->addColumn(
                'translation_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Translation Status'
            )
            ->addIndex(
                $this->installer->getIdxName('globallink_entity_translation_status', ['entity_type_id']),
                ['entity_type_id']
            )
            ->addIndex(
                $this->installer->getIdxName('globallink_entity_translation_status', ['entity_id']),
                ['entity_id']
            );
        $connection->createTable($table);
    }

    /**
     * Clear tables (needed b/c of LIKE condition was changed in prev commit)
     */
    protected function upgradeTo_072()
    {
        $connection = $this->installer->getConnection();
        $connection->truncateTable($this->tableQueue);
        $connection->truncateTable($this->tableItems);
    }

    /**
     * Create table 'globallink_job_item_status_history'
     */
    protected function upgradeTo_080()
    {
        $connection = $this->installer->getConnection();
        $connection->dropTable($this->installer->getTable('globallink_job_item_status_history'));
        $table = $connection
            ->newTable($this->installer->getTable('globallink_job_item_status_history'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'entity_type_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Entity Type Id'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Entity Id'
            )
            ->addColumn(
                'source_store_view_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Source store View Id'
            )
            ->addColumn(
                'target_store_view_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Target store View Id'
            )
            ->addColumn(
                'changed_by',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                128,
                ['nullable' => false, 'default' => ''],
                'User/System responsible for changing the status'
            )
            ->addColumn(
                'status_change_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Date and time of change'
            )
            ->addColumn(
                'status_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Status Id'
            )
            ->addIndex(
                $this->installer->getIdxName('globallink_job_item_status_history', ['entity_type_id']),
                ['entity_type_id']
            )
            ->addIndex(
                $this->installer->getIdxName('globallink_job_item_status_history', ['entity_id']),
                ['entity_id']
            );
        $connection->createTable($table);
    }

    /**
     * - add globallink_job_item.target_ticket
     */
    protected function upgradeTo_090()
    {
        $connection = $this->installer->getConnection();
        $connection->addColumn(
            $this->tableItems,
            'target_ticket',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 64,
                'after' => 'document_ticket',
                'comment' => 'Target Ticket'
            ]
        );
    }
    protected function upgradeTo_091()
    {
        $connection = $this->installer->getConnection();
        $connection->addColumn(
            $this->tableField,
            'attribute_set_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'after' => 'field_label',
                'nullable' => true,
                'comment' => 'Product Attribute Set ID'
            ]
        );
    }

    /**
     * - drop active_submission columns from all tables (were added in 0.4.1, 0.4.2, 0.4.3)
     */
    protected function upgradeTo_095()
    {
        $connection = $this->installer->getConnection();
        $connection->dropColumn($this->tableField, 'attribute_set_id');
        $connection = $this->installer->getConnection();
        $connection->dropTable($this->installer->getTable('globallink_field_product_category'));
        $table = $connection
            ->newTable($this->installer->getTable('globallink_field_product_category'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
                'Entity Attribute ID'
            )
            ->addColumn(
                'entity_attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Entity Attribute ID'
            )
            ->addColumn(
                'entity_type_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Entity Type Id'
            )
            ->addColumn(
                'attribute_set_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Attribute Set Id'
            )
            ->addColumn(
                'attribute_group_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Attribute Group Id'
            )
            ->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Attribute Id'
            )
            ->addColumn(
                'include_in_translation',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                [
                        'length' => 1,
                        'nullable' => true,
                        'comment' => 'Include in Translation'
                    ]
            );
        $connection->createTable($table);
    }

    protected function upgradeTo_097()
    {
        $connection = $this->installer->getConnection();
        //$connection->dropColumn($this->attributeTable, 'include_in_translation');
        $connection->dropColumn($this->installer->getTable('eav_entity_attribute'), 'include_in_translation');
    }
}
