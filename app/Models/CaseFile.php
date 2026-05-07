<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class CaseFile
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getAll(?string $status = null, int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        $where = [];
        $params = ['limit' => $limit, 'offset' => $offset];

        if ($status) {
            $where[] = 'c.status = :status';
            $params['status'] = $status;
        }

        if ($search) {
            $where[] = '(c.title ILIKE :search OR u.external_id ILIKE :search OR u.email ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->db->query(
            "SELECT c.*, u.external_id, u.email, au.display_name as created_by_name, assignee.display_name as assigned_to_name
             FROM cases c
             LEFT JOIN users u ON c.user_id = u.id
             LEFT JOIN admin_users au ON c.created_by = au.id
             LEFT JOIN admin_users assignee ON c.assigned_to = assignee.id
             {$whereClause}
             ORDER BY
                CASE c.priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                c.created_at DESC
             LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function count(?string $status = null, ?string $search = null): int
    {
        $where = [];
        $params = [];

        if ($status) {
            $where[] = 'c.status = :status';
            $params['status'] = $status;
        }

        if ($search) {
            $where[] = '(c.title ILIKE :search OR u.external_id ILIKE :search OR u.email ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return (int) $this->db->queryScalar(
            "SELECT COUNT(*)
             FROM cases c
             LEFT JOIN users u ON c.user_id = u.id
             {$whereClause}",
            $params
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT c.*, u.external_id, u.email, au.display_name as created_by_name, assignee.display_name as assigned_to_name
             FROM cases c
             LEFT JOIN users u ON c.user_id = u.id
             LEFT JOIN admin_users au ON c.created_by = au.id
             LEFT JOIN admin_users assignee ON c.assigned_to = assignee.id
             WHERE c.id = :id",
            ['id' => $id]
        );
    }

    public function createFromReview(int $reviewId, int $adminUserId): ?int
    {
        $existing = $this->db->queryOne(
            'SELECT id FROM cases WHERE review_item_id = :review_item_id',
            ['review_item_id' => $reviewId]
        );

        if ($existing) {
            return (int) $existing['id'];
        }

        $review = $this->db->queryOne(
            'SELECT rq.*, u.external_id, u.email FROM review_queue rq LEFT JOIN users u ON rq.user_id = u.id WHERE rq.id = :id',
            ['id' => $reviewId]
        );

        if (!$review) {
            return null;
        }

        $priority = $review['priority'] ?? 'medium';
        $slaDueAt = $this->calculateSlaDueAt($priority);

        $title = 'Alert: ' . ($review['reason'] ?? 'Investigation required');
        $summary = sprintf(
            'Escalated from alert queue for user %s (%s).',
            $review['external_id'] ?? 'unknown',
            $review['email'] ?? 'no-email'
        );

        $caseId = $this->db->insert('cases', [
            'title'          => $title,
            'summary'        => $summary,
            'status'         => 'open',
            'priority'       => $priority,
            'user_id'        => $review['user_id'],
            'review_item_id' => $review['id'],
            'created_by'     => $adminUserId,
            'assigned_to'    => $adminUserId,
            'sla_due_at'     => $slaDueAt,
        ]);

        $this->db->update('review_queue', [
            'status'     => 'in_case',
            'updated_at' => date('c'),
        ], ['id' => $review['id']]);

        return $caseId;
    }

    public function resolve(int $id, int $adminUserId, string $notes = ''): void
    {
        $this->db->update('cases', [
            'status'           => 'resolved',
            'resolved_at'      => date('c'),
            'resolution_notes' => $notes,
            'assigned_to'      => $adminUserId,
            'updated_at'       => date('c'),
        ], ['id' => $id]);
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->db->queryScalar(
            'SELECT COUNT(*) FROM cases WHERE status = :status',
            ['status' => $status]
        );
    }

    public function countOpen(): int
    {
        return (int) $this->db->queryScalar(
            "SELECT COUNT(*) FROM cases WHERE status IN ('open', 'in_progress')"
        );
    }

    public function countSlaBreaches(): int
    {
        return (int) $this->db->queryScalar(
            "SELECT COUNT(*) FROM cases WHERE status IN ('open', 'in_progress') AND sla_due_at IS NOT NULL AND sla_due_at < NOW()"
        );
    }

    public function getAverageMttrHours(int $days = 30): float
    {
        $value = $this->db->queryScalar(
            "SELECT AVG(EXTRACT(EPOCH FROM (resolved_at - created_at)) / 3600)
             FROM cases
             WHERE resolved_at IS NOT NULL
               AND resolved_at >= NOW() - INTERVAL '{$days} days'"
        );

        return $value ? (float) $value : 0.0;
    }

    private function calculateSlaDueAt(string $priority): ?string
    {
        $hours = match ($priority) {
            'critical' => 4,
            'high' => 8,
            'low' => 48,
            default => 24,
        };

        return date('c', strtotime("+{$hours} hours"));
    }
}
