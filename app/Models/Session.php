<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class Session
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findOrCreate(string $sessionId, int $userId, ?int $ipId = null, ?int $deviceId = null): array
    {
        $session = $this->db->queryOne(
            'SELECT * FROM sessions WHERE session_id = :sid',
            ['sid' => $sessionId]
        );

        if ($session) {
            $this->db->execute(
                'UPDATE sessions SET last_activity = NOW(), event_count = event_count + 1 WHERE id = :id',
                ['id' => $session['id']]
            );
            $session['event_count']++;
            return $session;
        }

        $id = $this->db->insert('sessions', [
            'session_id'    => $sessionId,
            'user_id'       => $userId,
            'ip_address_id' => $ipId,
            'device_id'     => $deviceId,
            'event_count'   => 1,
        ]);

        return $this->db->queryOne('SELECT * FROM sessions WHERE id = :id', ['id' => $id]);
    }

    public function getUserSessions(int $userId, int $limit = 20): array
    {
        return $this->db->query(
            'SELECT s.*, ip.ip_address, ip.country, d.browser, d.os
             FROM sessions s
             LEFT JOIN ip_addresses ip ON s.ip_address_id = ip.id
             LEFT JOIN devices d ON s.device_id = d.id
             WHERE s.user_id = :user_id
             ORDER BY s.last_activity DESC
             LIMIT :limit',
            ['user_id' => $userId, 'limit' => $limit]
        );
    }

    public function markSuspicious(int $sessionId): void
    {
        $this->db->execute(
            'UPDATE sessions SET is_suspicious = TRUE WHERE id = :id',
            ['id' => $sessionId]
        );
    }
}
