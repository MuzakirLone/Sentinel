<?php

namespace Sentinel\Controllers\Api;

use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;

/**
 * Blacklist check API — lets external apps query if a user/IP is blocked.
 */
class BlacklistController
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * POST /api/v1/blacklist/check
     */
    public function check(Request $request, Response $response): void
    {
        $data = $request->input();

        $result = [
            'blocked'    => false,
            'risk_score' => 0,
            'risk_level' => 'low',
            'reasons'    => [],
        ];

        // Check by user_id
        if (!empty($data['user_id'])) {
            $user = $this->db->queryOne(
                'SELECT status, risk_score, risk_level FROM users WHERE external_id = :eid',
                ['eid' => $data['user_id']]
            );

            if ($user) {
                $result['risk_score'] = (float) $user['risk_score'];
                $result['risk_level'] = $user['risk_level'];

                if ($user['status'] === 'suspended' || $user['status'] === 'blocked') {
                    $result['blocked'] = true;
                    $result['reasons'][] = "User status: {$user['status']}";
                }
            }
        }

        // Check by IP
        if (!empty($data['ip'])) {
            $ip = $this->db->queryOne(
                'SELECT * FROM ip_addresses WHERE ip_address = :ip',
                ['ip' => $data['ip']]
            );

            if ($ip) {
                if ($ip['is_tor']) {
                    $result['reasons'][] = 'TOR exit node';
                }
                if ($ip['threat_score'] > 80) {
                    $result['blocked'] = true;
                    $result['reasons'][] = "High threat score IP: {$ip['threat_score']}";
                }
            }
        }

        // Check by email
        if (!empty($data['email'])) {
            $domain = substr($data['email'], strpos($data['email'], '@') + 1);
            $emailDomain = $this->db->queryOne(
                'SELECT * FROM email_domains WHERE domain = :domain',
                ['domain' => $domain]
            );

            if ($emailDomain && $emailDomain['is_disposable']) {
                $result['reasons'][] = 'Disposable email domain';
            }
        }

        $response->json($result);
    }
}
