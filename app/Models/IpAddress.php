<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class IpAddress
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findOrCreate(string $ipAddress): array
    {
        $ip = $this->db->queryOne(
            'SELECT * FROM ip_addresses WHERE ip_address = :ip',
            ['ip' => $ipAddress]
        );

        if ($ip) {
            $this->db->execute(
                'UPDATE ip_addresses SET last_seen_at = NOW() WHERE id = :id',
                ['id' => $ip['id']]
            );
            return $ip;
        }

        // Basic detection for private/special IPs
        $isTor = false;
        $isVpn = false;
        $isProxy = false;
        $isDatacenter = false;

        $id = $this->db->insert('ip_addresses', [
            'ip_address'   => $ipAddress,
            'is_tor'       => $isTor ? 'true' : 'false',
            'is_vpn'       => $isVpn ? 'true' : 'false',
            'is_proxy'     => $isProxy ? 'true' : 'false',
            'is_datacenter' => $isDatacenter ? 'true' : 'false',
        ]);

        return $this->db->queryOne('SELECT * FROM ip_addresses WHERE id = :id', ['id' => $id]);
    }

    public function getUserIpCount(int $userId, int $hours = 24): int
    {
        return (int) $this->db->queryScalar(
            "SELECT COUNT(DISTINCT ip.id)
             FROM events e
             JOIN ip_addresses ip ON e.ip_address_id = ip.id
             WHERE e.user_id = :user_id
             AND e.created_at >= NOW() - INTERVAL '{$hours} hours'",
            ['user_id' => $userId]
        );
    }

    public function getIpUserCount(string $ipAddress): int
    {
        return (int) $this->db->queryScalar(
            "SELECT COUNT(DISTINCT e.user_id)
             FROM events e
             JOIN ip_addresses ip ON e.ip_address_id = ip.id
             WHERE ip.ip_address = :ip",
            ['ip' => $ipAddress]
        );
    }
}
