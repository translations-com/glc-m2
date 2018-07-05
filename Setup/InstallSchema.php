<?php
namespace TransPerfect\GlobalLink\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 *
 * @package TransPerfect\GlobalLink\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /**
         * Create table 'globallink_job_queue'
         */
        $setup->getConnection()->dropTable($setup->getTable('globallink_job_queue'));
        $table = $setup->getConnection()
            ->newTable($setup->getTable('globallink_job_queue'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                128,
                ['nullable' => false, 'default' => ''],
                'Job name is auto-populated eg. CAT-CSO-yyyymmddHHmmss'
            )
            ->addColumn(
                'entity_type_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Entity Type ID'
            )
            ->addColumn(
                'pd_locale_iso_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'ID of locale from language mapping'
            )
            ->addColumn(
                'magento_admin_user_requested_by',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'User who requested'
            )
            ->addColumn(
                'request_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Time when request was sent'
            )
            ->addColumn(
                'due_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Time when the request is due'
            )
            ->addColumn(
                'project_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false],
                'Project ID'
            )
            ->addColumn(
                'origin_store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                ['nullable' => false],
                'FK to store table\'s store_id field'
            )
            ->addColumn(
                'priority',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                ['nullable' => false],
                'Priority: High, Medium, Low'
            )
            ->addColumn(
                'progress',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                128,
                ['unsigned' => true, 'nullable' => false],
                'Indicator (0-100)'
            )
            ->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default' => '0'],
                '0 = Archived, 1 = Active'
            )
            ->addColumn(
                'include_subcategories',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => true, 'default' => '0'],
                'Only applicable to the category entity type.'
            )
            ->addColumn(
                'include_associated_and_parent_categories',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => true, 'default' => '0'],
                'Only applicable to the category entity type.'
            )
            ->addColumn(
                'confirmation_email',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                ['nullable' => false, 'default' => ''],
                'List of emails that will be alerted when the translation is completed'
            )
            ->addColumn(
                'submission_instructions',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                ['nullable' => false, 'default' => ''],
                'Additional Instructions'
            )
            ->addIndex(
                $setup->getIdxName('globallink_job_queue', ['id']),
                ['id']
            );
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'globallink_job_items'
         */
        $setup->getConnection()->dropTable($setup->getTable('globallink_job_items'));
        $table = $setup->getConnection()
            ->newTable($setup->getTable('globallink_job_items'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'queue_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'FK to id column in globallink_job_queue'
            )
            ->addColumn(
                'status_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'FK to id column in globallink_job_item_status table'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Entity ID'
            )
            ->addIndex(
                $setup->getIdxName('globallink_job_items', ['id']),
                ['id']
            );
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'globallink_job_item_status'
         * TODO Add 1-Queued 2-In Progress 3-Completed
         */
        $setup->getConnection()->dropTable($setup->getTable('globallink_job_item_status'));
        $table = $setup->getConnection()
            ->newTable($setup->getTable('globallink_job_item_status'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Status Name'
            )
            ->addIndex(
                $setup->getIdxName('globallink_job_item_status', ['id']),
                ['id']
            );
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'globallink_field'
         */
        $setup->getConnection()->dropTable($setup->getTable('globallink_field'));
        $table = $setup->getConnection()
            ->newTable($setup->getTable('globallink_field'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                11,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'GlobalLink Field ID'
            )
            ->addColumn(
                'object_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                5,
                ['nullable' => false],
                'Object Type'
            )
            ->addColumn(
                'object_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Field Code'
            )
            ->addColumn(
                'field_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Field Name'
            )
            ->addColumn(
                'include_in_translation',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                1,
                ['nullable' => false, 'default' => '0'],
                'Include in Translation'
            )
            ->addColumn(
                'user_submitted',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                1,
                ['nullable' => false, 'default' => '0'],
                'User Submitted'
            )
            ->addIndex(
                $setup->getIdxName('globallink_field', ['id']),
                ['id']
            );
        $setup->getConnection()->createTable($table);

        $setup->getConnection()->addColumn(
            $setup->getTable('store'),
            'locale',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Locale'
            ]
        );

        $setup->endSetup();
    }
}
