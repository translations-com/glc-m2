<?php

namespace TransPerfect\GlobalLink\Model\ResourceModel\Queue;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use TransPerfect\GlobalLink\Helper\Data;

/**
 * Class Item
 *
 * @package TransPerfect\GlobalLink\Model\ResourceModel\Queue
 */
class Item extends AbstractDb
{
    /**
     * @var \TransPerfect\GlobalLink\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        Data $helper,
        \Magento\Backend\Model\Auth\Session $authSession,
        $connectionName = null
    ) {
        $this->helper = $helper;
        $this->authSession = $authSession;
        parent::__construct($context, $connectionName);
    }

    /**
     * Init
     */
    protected function _construct()
    {
        $this->_init('globallink_job_items', 'id');
    }

    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);
        $this->joinAdditionalData($select);
        return $select;
    }

    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterLoad($object);
        return $this;
    }

    public function joinAdditionalData(\Magento\Framework\DB\Select $select)
    {
        $select->join(
            ['queue' => $this->getTable('globallink_job_queue')],
            'main_table.queue_id = queue.id',
            [
                'user_id' => 'magento_admin_user_requested_by',
                'store_id' => 'origin_store_id',
                'submission_name' => 'name',
                'due_date' => 'due_date',
                'request_date' => 'request_date'
            ]
        );
        $select->join(
            ['admin_user' => $this->getTable('admin_user')],
            'queue.magento_admin_user_requested_by = admin_user.user_id',
            ['username']
        );
        $select->join(
            ['store_source' => $this->getTable('store')],
            'queue.origin_store_id = store_source.store_id',
            [
                'source_locale' => 'locale',
                'source_store_id' => 'store_id',
            ]
        );
        return $this;
    }

    /**
     * Map for collection filter fields
     *
     * @param $alias
     *
     * @return mixed
     */
    public function getFieldNameByAlias($alias)
    {
        $aliasesMap = [
            'submission_name' => 'queue.name',
            'username' => 'admin_user.username',
            'status_id' => 'main_table.status_id',
            'entity_id' => 'main_table.entity_id',
            'entity_type_id' => 'main_table.entity_type_id',
            'store_name' => 'store_source.name',
            'source_locale' => 'store_source.locale',
            'source_store_id' => 'store_source.store_id',
            'pd_locale_iso_code' => 'main_table.pd_locale_iso_code',
            'entity_name' => 'main_table.entity_name',
            'id' => 'main_table.id',
            'submission_ticket' => 'main_table.submission_ticket',
            'document_ticket' => 'main_table.document_ticket',
            'target_stores' => 'main_table.target_stores',
            'request_date' => 'queue.request_date',
            'due_date'  => 'queue.due_date'
        ];
        return $aliasesMap[$alias];
    }

    /**
     * Returns distinct Submission tickets for given Queue
     * Queue can be submitted as several submissions b/c of files_per_submission limit
     *
     * @param int $queueId
     *
     * @return array
     */
    public function getDistinctSbmTicketsForQueue($queueId)
    {
        $queueId = (int) $queueId;
        $select = $this->getConnection()->select()
            ->from(['main_table' => $this->getTable('globallink_job_items')])
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns('submission_ticket')
            ->distinct(true)
            ->where('main_table.queue_id IN (?)', [$queueId]);

        $rowset = $this->getConnection()->fetchAll($select);
        $sbmTickets = [];
        foreach ($rowset as $row) {
            $sbmTickets[] = $row['submission_ticket'];
        }

        return $sbmTickets;
    }

    /**
     * Returns length of SQL field
     *
     * @param string $columnName
     * @param int    $entityTypeId
     *
     * @return int
     */
    public function getFieldLength($columnName, $entityTypeId)
    {
        switch ($entityTypeId) {
            case 1:
                // customer
                $tableName = 'customer_entity';
                break;
            case 2:
                // customer address
                $tableName = 'customer_address_entity';
                break;
            case 3:
                // category
                $tableName = 'catalog_category_entity';
                break;
            case 4:
                // product
                $tableName = 'catalog_product_entity';
                break;
        }

        $connection = $this->getConnection();
        $config = $connection->getConfig();
        $dbName = $config['dbname'];

        $select = $connection->select()
            ->from(['main_table' => $this->getTable('information_schema.columns')])
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['column_name', 'character_maximum_length'])
            ->where('table_schema = ?', $dbName)
            ->where('table_name = ?', $tableName)
            ->where('column_name = ?', $columnName)
            ->limit(1);

        $rowset = $this->getConnection()->fetchAll($select);

        foreach ($rowset as $row) {
            return $row['character_maximum_length'];
        }
    }

    /**
     * Get current store_id in ui grid
     *
     * @param string $namespace
     *
     * @return int
     */
    public function getUiGridStoreId($namespace)
    {
        $userId = $this->authSession->getUser()->getId();

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['main_table' => $this->getTable('ui_bookmark')])
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['identifier', 'config'])
            ->where('identifier = current')
            ->where('namespace = ?', $namespace)
            ->where('user_id = ?', $userId)
            ->limit(1);

        $rowset = $this->getConnection()->fetchAll($select);

        foreach ($rowset as $row) {
            $dataObject = json_decode($row['config']);
            $identifier = $row['identifier'];
            if (empty($dataObject->$identifier->filters->applied->store_id)) {
                return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            }
            return $dataObject->$identifier->filters->applied->store_id;
        }
    }
}
