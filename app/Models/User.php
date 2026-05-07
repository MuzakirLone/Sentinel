<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class User
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        return $this->db->queryOne('SELECT * FROM users WHERE id = :id', ['id' => $id]);
    }

    public function findByExternalId(string $externalId): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM users WHERE external_id = :eid',
            ['eid' => $externalId]
        );
    }

    public function findOrCreate(string $externalId, array $data = []): array
    {
        $user = $this->findByExternalId($externalId);

        if ($user) {
            // Update last seen
            $updates = ['last_seen_at' => date('c'), 'total_events' => $user['total_events'] + 1];
            if (!empty($data['email']) && $data['email'] !== $user['email']) {
                $updates['email'] = $data['email'];
            }
            if (!empty($data['country'])) {
                $updates['country'] = $data['country'];
            }
            $this->db->update('users', $updates, ['id' => $user['id']]);
            $user = array_merge($user, $updates);
            return $user;
        }

        $id = $this->db->insert('users', [
            'external_id' => $externalId,
            'email'       => $data['email'] ?? null,
            'username'    => $data['username'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'country'     => $data['country'] ?? null,
            'total_events' => 1,
            'metadata'    => json_encode($data['metadata'] ?? []),
        ]);

        return $this->findById($id);
    }

    public function getAll(int $limit = 50, int $offset = 0, string $sort = 'risk_score', string $order = 'DESC', ?string $search = null): array
    {
        $allowedSorts = ['risk_score', 'total_events', 'last_seen_at', 'first_seen_at', 'email'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'risk_score';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE email ILIKE :search OR external_id ILIKE :search OR username ILIKE :search";
            $params['search'] = "%{$search}%";
        }

        $sql = "SELECT * FROM users {$where} ORDER BY {$sort} {$order} LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->db->query($sql, $params);
    }

    public function count(?string $search = null): int
    {
        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE email ILIKE :search OR external_id ILIKE :search OR username ILIKE :search";
            $params['search'] = "%{$search}%";
        }

        return (int) $this->db->queryScalar("SELECT COUNT(*) FROM users {$where}", $params);
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->db->queryScalar(
            'SELECT COUNT(*) FROM users WHERE status = :status',
            ['status' => $status]
        );
    }

    public function countHighRisk(float $threshold = 60.0): int
    {
        return (int) $this->db->queryScalar(
            'SELECT COUNT(*) FROM users WHERE risk_score >= :threshold',
            ['threshold' => $threshold]
        );
    }

    public function countActiveRecent(int $hours = 24): int
    {
        return (int) $this->db->queryScalar(
            "SELECT COUNT(*) FROM users WHERE last_seen_at >= NOW() - INTERVAL '{$hours} hours'"
        );
    }

    public function updateRiskScore(int $userId, float $score, string $level): void
    {
        $this->db->update('users', [
            'risk_score' => $score,
            'risk_level' => $level,
            'updated_at' => date('c'),
        ], ['id' => $userId]);
    }

    public function updateStatus(int $userId, string $status): void
    {
        $this->db->update('users', [
            'status' => $status,
            'updated_at' => date('c'),
        ], ['id' => $userId]);
    }

    public function getTopRisky(int $limit = 10): array
    {
        return $this->db->query(
            'SELECT * FROM users WHERE risk_score > 0 ORDER BY risk_score DESC LIMIT :limit',
            ['limit' => $limit]
        );
    }
}
