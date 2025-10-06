<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Audit Log Resource Model
 */
class AuditLogResourceModel extends AbstractDb
{
    /**
     * Initialize connection and table
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('logiscenter_immutable_quote_audit', 'id');
    }

    /**
     * Insert audit record
     *
     * @param array $data
     * @return void
     */
    public function insertAuditRecord(array $data): void
    {
        $connection = $this->getConnection();
        $connection->insert($this->getMainTable(), $data);
    }

    /**
     * Get audit records for quote
     *
     * @param int $quoteId
     * @param int $limit
     * @return array
     */
    public function getAuditRecordsForQuote(int $quoteId, int $limit = 100): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('quote_id = ?', $quoteId)
            ->order('created_at DESC')
            ->limit($limit);

        return $connection->fetchAll($select);
    }

    /**
     * Get suspicious activity records
     *
     * @param int $hours
     * @param int $minAttempts
     * @return array
     */
    public function getSuspiciousActivity(int $hours = 24, int $minAttempts = 5): array
    {
        $connection = $this->getConnection();
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        $select = $connection->select()
            ->from($this->getMainTable(), [
                'customer_id',
                'ip_address',
                'attempt_count' => 'COUNT(*)',
                'first_attempt' => 'MIN(created_at)',
                'last_attempt' => 'MAX(created_at)'
            ])
            ->where('action = ?', 'modification_attempted')
            ->where('created_at >= ?', $since)
            ->group(['customer_id', 'ip_address'])
            ->having('COUNT(*) >= ?', $minAttempts)
            ->order('attempt_count DESC');

        return $connection->fetchAll($select);
    }

    /**
     * Clean old audit records
     *
     * @param int $days
     * @return int Number of deleted records
     */
    public function cleanOldRecords(int $days): int
    {
        if ($days <= 0) {
            return 0; // Don't delete if days is 0 or negative
        }

        $connection = $this->getConnection();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $connection->delete(
            $this->getMainTable(),
            ['created_at < ?' => $cutoffDate]
        );
    }
}
