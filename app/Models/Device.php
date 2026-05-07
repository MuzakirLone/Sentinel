<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class Device
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findOrCreate(string $userAgent): array
    {
        $fingerprint = hash('sha256', $userAgent);

        $device = $this->db->queryOne(
            'SELECT * FROM devices WHERE fingerprint = :fp',
            ['fp' => $fingerprint]
        );

        if ($device) {
            $this->db->execute(
                'UPDATE devices SET last_seen_at = NOW() WHERE id = :id',
                ['id' => $device['id']]
            );
            return $device;
        }

        // Parse user agent
        $parsed = $this->parseUserAgent($userAgent);

        $id = $this->db->insert('devices', [
            'fingerprint'     => $fingerprint,
            'user_agent'      => $userAgent,
            'browser'         => $parsed['browser'],
            'browser_version' => $parsed['browser_version'],
            'os'              => $parsed['os'],
            'os_version'      => $parsed['os_version'],
            'device_type'     => $parsed['device_type'],
            'is_bot'          => $parsed['is_bot'] ? 'true' : 'false',
        ]);

        return $this->db->queryOne('SELECT * FROM devices WHERE id = :id', ['id' => $id]);
    }

    public function getUserDeviceCount(int $userId, int $hours = 24): int
    {
        return (int) $this->db->queryScalar(
            "SELECT COUNT(DISTINCT d.id)
             FROM events e
             JOIN devices d ON e.device_id = d.id
             WHERE e.user_id = :user_id
             AND e.created_at >= NOW() - INTERVAL '{$hours} hours'",
            ['user_id' => $userId]
        );
    }

    private function parseUserAgent(string $ua): array
    {
        $result = [
            'browser' => 'Unknown',
            'browser_version' => '',
            'os' => 'Unknown',
            'os_version' => '',
            'device_type' => 'desktop',
            'is_bot' => false,
        ];

        // Bot detection
        $botPatterns = ['bot', 'crawler', 'spider', 'headless', 'phantom', 'selenium', 'puppeteer', 'playwright'];
        foreach ($botPatterns as $pattern) {
            if (stripos($ua, $pattern) !== false) {
                $result['is_bot'] = true;
                $result['device_type'] = 'bot';
                break;
            }
        }

        // Browser detection
        if (preg_match('/Chrome\/([\d.]+)/', $ua, $m)) {
            $result['browser'] = 'Chrome';
            $result['browser_version'] = $m[1];
        } elseif (preg_match('/Firefox\/([\d.]+)/', $ua, $m)) {
            $result['browser'] = 'Firefox';
            $result['browser_version'] = $m[1];
        } elseif (preg_match('/Safari\/([\d.]+)/', $ua, $m) && !str_contains($ua, 'Chrome')) {
            $result['browser'] = 'Safari';
            $result['browser_version'] = $m[1];
        } elseif (preg_match('/Edge\/([\d.]+)/', $ua, $m)) {
            $result['browser'] = 'Edge';
            $result['browser_version'] = $m[1];
        }

        // OS detection
        if (str_contains($ua, 'Windows')) {
            $result['os'] = 'Windows';
            if (preg_match('/Windows NT ([\d.]+)/', $ua, $m)) {
                $result['os_version'] = $m[1];
            }
        } elseif (str_contains($ua, 'Mac OS')) {
            $result['os'] = 'macOS';
        } elseif (str_contains($ua, 'Linux')) {
            $result['os'] = 'Linux';
        } elseif (str_contains($ua, 'Android')) {
            $result['os'] = 'Android';
            $result['device_type'] = 'mobile';
        } elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            $result['os'] = 'iOS';
            $result['device_type'] = str_contains($ua, 'iPad') ? 'tablet' : 'mobile';
        }

        return $result;
    }
}
