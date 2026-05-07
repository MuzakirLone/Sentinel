<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class ReviewItem
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(int $userId, string $reason, float $riskScore, array $triggeredRules = [], string $priority = 'medium'): int
    {
        // Check if there's already a pending review for this user
        $existing = $this->db->queryOne(
            "SELECT id FROM review_queue WHERE user_id = :user_id AND status = 'pending'",
            ['user_id' => $userId]
        );

        if ($existing) {
            // Update existing
            $this->db->update('review_queue', [
                'reason'          => $reason,
                'risk_score'      => $riskScore,
                'triggered_rules' => '{' . implode(',', $triggeredRules) . '}',
                'priority'        => $priority,
                'updated_at'      => date('c'),
            ], ['id' => $existing['id']]);
            return $existing['id'];
        }

        return $this->db->insert('review_queue', [
            'user_id'         => $userId,
            'reason'          => $reason,
            'risk_score'      => $riskScore,
            'status'          => 'pending',
            'priority'        => $priority,
            'triggered_rules' => '{' . implode(',', $triggeredRules) . '}',
        ]);
    }

    public function getAll(string $status = null, int $limit = 50, int $offset = 0): array
    {
        $where = '';
        $params = ['limit' => $limit, 'offset' => $offset];

        if ($status) {
            $where = 'WHERE rq.status = :status';
            $params['status'] = $status;
        }

        return $this->db->query(
            "SELECT rq.*, u.external_id, u.email, u.risk_score as current_risk_score, u.status as user_status
             FROM review_queue rq
             JOIN users u ON rq.user_id = u.id
             {$where}
             ORDER BY
                CASE rq.priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                rq.created_at DESC
             LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function countByStatus(string $status = 'pending'): int
    {
        return (int) $this->db->queryScalar(
            'SELECT COUNT(*) FROM review_queue WHERE status = :status',
            ['status' => $status]
        );
    }

    public function takeAction(int $id, int $adminUserId, string $action, string $notes = ''): void
    {
        $this->db->update('review_queue', [
            'status'       => $action === 'dismiss' ? 'dismissed' : 'resolved',
            'reviewed_by'  => $adminUserId,
            'reviewed_at'  => date('c'),
            'action_taken' => $action,
            'notes'        => $notes,
            'updated_at'   => date('c'),
        ], ['id' => $id]);
    }
}
