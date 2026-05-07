<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class Event
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        return $this->db->insert('events', [
            'event_type'      => $data['event_type'],
            'user_id'         => $data['user_id'] ?? null,
            'session_id'      => $data['session_id'] ?? null,
            'ip_address_id'   => $data['ip_address_id'] ?? null,
            'device_id'       => $data['device_id'] ?? null,
            'url'             => $data['url'] ?? null,
            'http_method'     => $data['http_method'] ?? null,
            'risk_score'      => $data['risk_score'] ?? 0.0,
            'idempotency_key' => $data['idempotency_key'] ?? null,
            'risk_flags'      => '{' . implode(',', $data['risk_flags'] ?? []) . '}',
            'metadata'        => json_encode($data['metadata'] ?? []),
        ]);
    }

    public function findById(int $id): ?array
    {
        return $this->db->queryOne(
            'SELECT e.*, u.external_id, u.email as user_email, ip.ip_address, d.browser, d.os
             FROM events e
             LEFT JOIN users u ON e.user_id = u.id
             LEFT JOIN ip_addresses ip ON e.ip_address_id = ip.id
             LEFT JOIN devices d ON e.device_id = d.id
             WHERE e.id = :id',
            ['id' => $id]
        );
    }

    public function getAll(
        int $limit = 50,
        int $offset = 0,
        ?string $eventType = null,
        ?int $userId = null,
        ?string $riskLevel = null,
        ?string $search = null
    ): array
    {
        $where = [];
        $params = ['limit' => $limit, 'offset' => $offset];

        if ($eventType) {
            $where[] = 'e.event_type = :event_type';
            $params['event_type'] = $eventType;
        }

        if ($userId) {
            $where[] = 'e.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $riskClause = $this->riskLevelClause($riskLevel, $params, 'e');
        if ($riskClause) {
            $where[] = $riskClause;
        }

        if ($search) {
            $where[] = '(e.event_type ILIKE :search OR u.external_id ILIKE :search OR u.email ILIKE :search OR ip.ip_address ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->db->query(
            "SELECT e.*, u.external_id, u.email as user_email, ip.ip_address, ip.country as ip_country,
                    d.browser, d.os, d.is_bot
             FROM events e
             LEFT JOIN users u ON e.user_id = u.id
             LEFT JOIN ip_addresses ip ON e.ip_address_id = ip.id
             LEFT JOIN devices d ON e.device_id = d.id
             {$whereClause}
             ORDER BY e.created_at DESC
             LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function count(
        ?string $eventType = null,
        ?int $userId = null,
        ?string $riskLevel = null,
        ?string $search = null
    ): int
    {
        $where = [];
        $params = [];

        if ($eventType) {
            $where[] = 'event_type = :event_type';
            $params['event_type'] = $eventType;
        }

        if ($userId) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $riskClause = $this->riskLevelClause($riskLevel, $params, 'events');
        if ($riskClause) {
            $where[] = $riskClause;
        }

        if ($search) {
            $where[] = '(event_type ILIKE :search OR EXISTS (SELECT 1 FROM users u WHERE u.id = events.user_id AND (u.external_id ILIKE :search OR u.email ILIKE :search)) OR EXISTS (SELECT 1 FROM ip_addresses ip WHERE ip.id = events.ip_address_id AND ip.ip_address ILIKE :search))';
            $params['search'] = '%' . $search . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return (int) $this->db->queryScalar(
            "SELECT COUNT(*) FROM events {$whereClause}",
            $params
        );
    }

    private function riskLevelClause(?string $riskLevel, array &$params, string $alias): ?string
    {
        if (!$riskLevel) {
            return null;
        }

        $ranges = [
            'low' => [0, 20],
            'moderate' => [20, 40],
            'elevated' => [40, 60],
            'high' => [60, 80],
            'critical' => [80, null],
        ];

        if (!isset($ranges[$riskLevel])) {
            return null;
        }

        [$min, $max] = $ranges[$riskLevel];
        $params['risk_min'] = $min;

        if ($max === null) {
            return $alias . '.risk_score >= :risk_min';
        }

        $params['risk_max'] = $max;
        return $alias . '.risk_score >= :risk_min AND ' . $alias . '.risk_score < :risk_max';
    }

    public function countRecent(int $hours = 24): int
    {
        return (int) $this->db->queryScalar(
            "SELECT COUNT(*) FROM events WHERE created_at >= NOW() - INTERVAL '{$hours} hours'"
        );
    }

    public function getUnprocessed(int $limit = 100): array
    {
        return $this->db->query(
            'SELECT * FROM events WHERE processed = FALSE ORDER BY created_at ASC LIMIT :limit',
            ['limit' => $limit]
        );
    }

    public function markProcessed(int $eventId, float $riskScore = 0.0, array $flags = []): void
    {
        $this->db->execute(
            'UPDATE events SET processed = TRUE, risk_score = :score, risk_flags = :flags WHERE id = :id',
            [
                'id'    => $eventId,
                'score' => $riskScore,
                'flags' => '{' . implode(',', $flags) . '}',
            ]
        );
    }

    public function getUserTimeline(int $userId, int $limit = 100): array
    {
        return $this->db->query(
            "SELECT e.*, ip.ip_address, ip.country as ip_country, d.browser, d.os
             FROM events e
             LEFT JOIN ip_addresses ip ON e.ip_address_id = ip.id
             LEFT JOIN devices d ON e.device_id = d.id
             WHERE e.user_id = :user_id
             ORDER BY e.created_at DESC
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }

    public function getEventsByHour(int $hours = 24): array
    {
        return $this->db->query(
            "SELECT date_trunc('hour', created_at) as hour, COUNT(*) as count, event_type
             FROM events
             WHERE created_at >= NOW() - INTERVAL '{$hours} hours'
             GROUP BY hour, event_type
             ORDER BY hour ASC"
        );
    }

    public function getTopEventTypes(int $limit = 10): array
    {
        return $this->db->query(
            'SELECT event_type, COUNT(*) as count FROM events GROUP BY event_type ORDER BY count DESC LIMIT :limit',
            ['limit' => $limit]
        );
    }

    public function getRiskDistribution(): array
    {
        return $this->db->query(
            "SELECT
                CASE
                    WHEN risk_score < 20 THEN 'low'
                    WHEN risk_score < 40 THEN 'moderate'
                    WHEN risk_score < 60 THEN 'elevated'
                    WHEN risk_score < 80 THEN 'high'
                    ELSE 'critical'
                END as level,
                COUNT(*) as count
             FROM events
             WHERE created_at >= NOW() - INTERVAL '7 days'
             GROUP BY level
             ORDER BY count DESC"
        );
    }

    public function getRecentFailedLogins(string $ip = null, int $userId = null, int $minutes = 60): int
    {
        $where = ["event_type IN ('login_failed', 'login_failure')"];
        $where[] = "created_at >= NOW() - INTERVAL '{$minutes} minutes'";
        $params = [];

        if ($ip !== null) {
            $where[] = "ip_address_id = (SELECT id FROM ip_addresses WHERE ip_address = :ip LIMIT 1)";
            $params['ip'] = $ip;
        }

        if ($userId !== null) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = $userId;
        }

        return (int) $this->db->queryScalar(
            "SELECT COUNT(*) FROM events WHERE " . implode(' AND ', $where),
            $params
        );
    }

    public function findByIdempotencyKey(string $key): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM events WHERE idempotency_key = :key LIMIT 1',
            ['key' => $key]
        );
    }
}
