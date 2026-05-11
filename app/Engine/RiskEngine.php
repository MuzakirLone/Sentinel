<?php

namespace Sentinel\Engine;

use Sentinel\Core\Database;
use Sentinel\Engine\Rules\RuleInterface;
use Sentinel\Engine\Rules\RuleResult;
use Sentinel\Models\Event;
use Sentinel\Models\IpAddress;
use Sentinel\Models\Device;

/**
 * Core risk engine — evaluates all enabled rules against events.
 * 
 * Uses behavioral baselines, velocity analysis, and statistical deviation
 * to produce risk scores that reflect genuine anomaly detection rather than
 * simple threshold checks.
 */
class RiskEngine
{
    private Database $db;
    private array $rules = [];

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->loadRules();
    }

    private function loadRules(): void
    {
        $ruleClasses = [
            new Rules\AccountTakeoverRule(),
            new Rules\CredentialStuffingRule(),
            new Rules\BotDetectionRule(),
            new Rules\ContentSpamRule(),
            new Rules\MultiAccountingRule(),
            new Rules\DormantAccountRule(),
            new Rules\HighRiskRegionRule(),
            new Rules\PromoAbuseRule(),
            new Rules\InsiderThreatRule(),
            new Rules\BruteForceRule(),
        ];

        // Load enabled rules from DB and match with implementations
        $enabledRules = $this->db->query('SELECT * FROM rules WHERE is_enabled = TRUE');
        $enabledSlugs = array_column($enabledRules, 'slug');
        $ruleWeights = [];
        foreach ($enabledRules as $r) {
            $ruleWeights[$r['slug']] = (float) $r['weight'];
        }

        foreach ($ruleClasses as $rule) {
            if (in_array($rule->getSlug(), $enabledSlugs)) {
                $this->rules[] = [
                    'instance' => $rule,
                    'weight'   => $ruleWeights[$rule->getSlug()] ?? $rule->getWeight(),
                ];
            }
        }
    }

    /**
     * Evaluate an event and return all rule results.
     */
    public function evaluate(array $event, array $user): array
    {
        $context = $this->buildContext($event, $user);
        $results = [];

        foreach ($this->rules as $ruleData) {
            /** @var RuleInterface $rule */
            $rule = $ruleData['instance'];
            $weight = $ruleData['weight'];

            try {
                $result = $rule->evaluate($event, $user, $context);

                // Apply weight
                if ($result->triggered) {
                    $weightedScore = min(100, $result->score * $weight);
                    $results[] = new RuleResult(
                        $result->ruleSlug,
                        $weightedScore,
                        $result->triggered,
                        $result->description,
                        $result->details
                    );
                } else {
                    $results[] = $result;
                }
            } catch (\Exception $e) {
                // Log rule error but continue
                error_log("Rule {$rule->getSlug()} error: {$e->getMessage()}");
            }
        }

        return $results;
    }

    /**
     * Build evaluation context with historical data and behavioral baselines.
     *
     * Context provides rules with rich data for statistical/behavioral analysis
     * rather than simple threshold checks.
     */
    private function buildContext(array $event, array $user): array
    {
        $userId = $user['id'] ?? null;
        $ipModel = new IpAddress($this->db);
        $deviceModel = new Device($this->db);
        $eventModel = new Event($this->db);

        $context = [
            // ─── IP Intelligence ───────────────────────────
            'user_agent'        => '',
            'is_bot'            => false,
            'is_tor'            => false,
            'is_vpn'            => false,
            'is_proxy'          => false,
            'is_datacenter'     => false,
            'ip_country'        => '',
            'ip_city'           => '',
            'ip_latitude'       => null,
            'ip_longitude'      => null,
            'previous_country'  => $user['country'] ?? '',

            // ─── Activity Counts ───────────────────────────
            'recent_ip_count'          => 0,
            'recent_device_count'      => 0,
            'recent_failed_logins'     => 0,
            'ip_failed_logins'         => 0,
            'ip_account_count'         => 0,
            'device_account_count'     => 0,
            'events_per_minute'        => 0,
            'posts_per_hour'           => 0,
            'user_promo_count'         => 0,
            'ip_promo_account_count'   => 0,
            'recent_sensitive_actions' => 0,
            'failed_attempts_per_minute' => 0,

            // ─── Temporal Data ─────────────────────────────
            'user_age_hours'           => 999,
            'hours_since_last_seen'    => 0,

            // ─── Behavioral Baselines ──────────────────────
            'baseline_avg_events_per_day'   => 0,
            'baseline_typical_hours'        => [],  // e.g. [9,10,11,14,15,16]
            'baseline_known_countries'      => [],  // e.g. ['IN','US']
            'baseline_known_device_count'   => 0,
            'baseline_known_ip_count'       => 0,
            'baseline_avg_session_duration' => 0,
            'baseline_event_count_7d'       => 0,
            'baseline_event_count_30d'      => 0,

            // ─── Deviation Indicators ──────────────────────
            'is_new_device_for_user'  => false,
            'is_new_ip_for_user'      => false,
            'is_new_country_for_user' => false,
            'deviation_score'         => 0.0,  // 0-100 how abnormal this event is

            // ─── Geo / Travel Data ─────────────────────────
            'previous_ip_latitude'    => null,
            'previous_ip_longitude'   => null,
            'previous_ip_country'     => '',
            'previous_event_time'     => null,
            'travel_speed_kmh'        => null,  // calculated impossible travel speed
            'travel_distance_km'      => null,

            // ─── Credential Stuffing Intel ──────────────────
            'ip_success_count'            => 0,
            'ip_failure_count'            => 0,
            'ip_success_failure_ratio'    => 0.0,
            'ip_unique_usernames_per_min' => 0,

            // ─── Bot Detection Intel ───────────────────────
            'request_interval_stddev'     => 0.0,  // low stddev = bot-like regularity
            'session_unique_pages'        => 0,
            'session_event_count'         => 0,

            // ─── Distributed Attack Intel ──────────────────
            'user_attacking_ip_count'     => 0,  // IPs that failed login for this user recently
        ];

        // ─── Get IP info ───────────────────────────────────
        if (isset($event['ip_address_id'])) {
            $ip = $this->db->queryOne('SELECT * FROM ip_addresses WHERE id = :id', ['id' => $event['ip_address_id']]);
            if ($ip) {
                $context['is_tor'] = (bool) ($ip['is_tor'] ?? false);
                $context['is_vpn'] = (bool) ($ip['is_vpn'] ?? false);
                $context['is_proxy'] = (bool) ($ip['is_proxy'] ?? false);
                $context['is_datacenter'] = (bool) ($ip['is_datacenter'] ?? false);
                $context['ip_country'] = $ip['country'] ?? '';
                $context['ip_city'] = $ip['city'] ?? '';
                $context['ip_latitude'] = isset($ip['latitude']) ? (float) $ip['latitude'] : null;
                $context['ip_longitude'] = isset($ip['longitude']) ? (float) $ip['longitude'] : null;

                // Count of users from this IP
                $context['ip_account_count'] = (int) $this->db->queryScalar(
                    'SELECT COUNT(DISTINCT user_id) FROM events WHERE ip_address_id = :ip_id',
                    ['ip_id' => $ip['id']]
                );

                // Failed logins from this IP
                $context['ip_failed_logins'] = (int) $this->db->queryScalar(
                    "SELECT COUNT(*) FROM events WHERE ip_address_id = :ip_id AND event_type IN ('login_failed','login_failure') AND created_at >= NOW() - INTERVAL '1 hour'",
                    ['ip_id' => $ip['id']]
                );

                // Credential stuffing intel — success vs failure from this IP
                $context['ip_success_count'] = (int) $this->db->queryScalar(
                    "SELECT COUNT(*) FROM events WHERE ip_address_id = :ip_id AND event_type = 'login_success' AND created_at >= NOW() - INTERVAL '1 hour'",
                    ['ip_id' => $ip['id']]
                );
                $context['ip_failure_count'] = $context['ip_failed_logins'];
                $totalAttempts = $context['ip_success_count'] + $context['ip_failure_count'];
                $context['ip_success_failure_ratio'] = $totalAttempts > 0
                    ? $context['ip_success_count'] / $totalAttempts
                    : 1.0;

                // Unique usernames attempted from this IP in last minute
                $context['ip_unique_usernames_per_min'] = (int) $this->db->queryScalar(
                    "SELECT COUNT(DISTINCT user_id) FROM events WHERE ip_address_id = :ip_id AND event_type IN ('login_failed','login_failure','login_success') AND created_at >= NOW() - INTERVAL '1 minute'",
                    ['ip_id' => $ip['id']]
                );
            }
        }

        // ─── Get device info ───────────────────────────────
        if (isset($event['device_id'])) {
            $device = $this->db->queryOne('SELECT * FROM devices WHERE id = :id', ['id' => $event['device_id']]);
            if ($device) {
                $context['user_agent'] = $device['user_agent'] ?? '';
                $context['is_bot'] = (bool) ($device['is_bot'] ?? false);

                $context['device_account_count'] = (int) $this->db->queryScalar(
                    'SELECT COUNT(DISTINCT user_id) FROM events WHERE device_id = :device_id',
                    ['device_id' => $device['id']]
                );
            }
        }

        // ─── User-specific context + behavioral baselines ──
        if ($userId) {
            $context['recent_ip_count'] = $ipModel->getUserIpCount($userId, 24);
            $context['recent_device_count'] = $deviceModel->getUserDeviceCount($userId, 24);
            $context['recent_failed_logins'] = $eventModel->getRecentFailedLogins(null, $userId, 60);

            // User age
            if (isset($user['first_seen_at'])) {
                $context['user_age_hours'] = (time() - strtotime($user['first_seen_at'])) / 3600;
            }

            // Hours since last seen
            if (isset($user['last_seen_at'])) {
                $context['hours_since_last_seen'] = (time() - strtotime($user['last_seen_at'])) / 3600;
            }

            // Events per minute (last 5 minutes)
            $recentEventCount = (int) $this->db->queryScalar(
                "SELECT COUNT(*) FROM events WHERE user_id = :user_id AND created_at >= NOW() - INTERVAL '5 minutes'",
                ['user_id' => $userId]
            );
            $context['events_per_minute'] = $recentEventCount / 5.0;

            // Posts per hour
            $context['posts_per_hour'] = (int) $this->db->queryScalar(
                "SELECT COUNT(*) FROM events WHERE user_id = :user_id AND event_type IN ('post_create','comment_create','message_send','content_submit') AND created_at >= NOW() - INTERVAL '1 hour'",
                ['user_id' => $userId]
            );

            // Promo usage count
            $context['user_promo_count'] = (int) $this->db->queryScalar(
                "SELECT COUNT(*) FROM events WHERE user_id = :user_id AND event_type IN ('promo_apply','coupon_use','referral_claim','discount_apply')",
                ['user_id' => $userId]
            );

            // Recent sensitive actions
            $context['recent_sensitive_actions'] = (int) $this->db->queryScalar(
                "SELECT COUNT(*) FROM events WHERE user_id = :user_id AND event_type IN ('admin_login','data_export','user_delete','bulk_delete','config_change','permission_change','role_change') AND created_at >= NOW() - INTERVAL '1 hour'",
                ['user_id' => $userId]
            );

            // Failed attempts per minute
            $context['failed_attempts_per_minute'] = (int) $this->db->queryScalar(
                "SELECT COUNT(*) FROM events WHERE user_id = :user_id AND event_type IN ('login_failed','login_failure','auth_failed') AND created_at >= NOW() - INTERVAL '1 minute'",
                ['user_id' => $userId]
            );

            // ─── Behavioral Baselines ──────────────────────

            // Average events per day (over last 30 days)
            $context['baseline_event_count_30d'] = (int) $this->db->queryScalar(
                "SELECT COUNT(*) FROM events WHERE user_id = :user_id AND created_at >= NOW() - INTERVAL '30 days'",
                ['user_id' => $userId]
            );
            $context['baseline_event_count_7d'] = (int) $this->db->queryScalar(
                "SELECT COUNT(*) FROM events WHERE user_id = :user_id AND created_at >= NOW() - INTERVAL '7 days'",
                ['user_id' => $userId]
            );
            $accountAgeDays = max(1, $context['user_age_hours'] / 24);
            $context['baseline_avg_events_per_day'] = $context['baseline_event_count_30d'] / min(30, $accountAgeDays);

            // Typical active hours (most common hours the user is active)
            $hourRows = $this->db->query(
                "SELECT EXTRACT(HOUR FROM created_at) AS h, COUNT(*) AS c FROM events WHERE user_id = :user_id AND created_at >= NOW() - INTERVAL '30 days' GROUP BY h ORDER BY c DESC LIMIT 6",
                ['user_id' => $userId]
            );
            $context['baseline_typical_hours'] = array_map(fn($r) => (int) $r['h'], $hourRows);

            // Known countries
            $countryRows = $this->db->query(
                "SELECT DISTINCT ip.country FROM events e JOIN ip_addresses ip ON e.ip_address_id = ip.id WHERE e.user_id = :user_id AND ip.country IS NOT NULL",
                ['user_id' => $userId]
            );
            $context['baseline_known_countries'] = array_map(fn($r) => $r['country'], $countryRows);

            // Known device & IP counts (lifetime)
            $context['baseline_known_device_count'] = (int) $this->db->queryScalar(
                'SELECT COUNT(DISTINCT device_id) FROM events WHERE user_id = :user_id AND device_id IS NOT NULL',
                ['user_id' => $userId]
            );
            $context['baseline_known_ip_count'] = (int) $this->db->queryScalar(
                'SELECT COUNT(DISTINCT ip_address_id) FROM events WHERE user_id = :user_id AND ip_address_id IS NOT NULL',
                ['user_id' => $userId]
            );

            // ─── Novelty Detection ─────────────────────────

            // Is this a device the user has never used before?
            if (isset($event['device_id'])) {
                $knownDevice = $this->db->queryScalar(
                    'SELECT COUNT(*) FROM events WHERE user_id = :user_id AND device_id = :device_id AND id != :event_id',
                    ['user_id' => $userId, 'device_id' => $event['device_id'], 'event_id' => $event['id'] ?? 0]
                );
                $context['is_new_device_for_user'] = ((int) $knownDevice === 0);
            }

            // Is this an IP the user has never used before?
            if (isset($event['ip_address_id'])) {
                $knownIp = $this->db->queryScalar(
                    'SELECT COUNT(*) FROM events WHERE user_id = :user_id AND ip_address_id = :ip_id AND id != :event_id',
                    ['user_id' => $userId, 'ip_id' => $event['ip_address_id'], 'event_id' => $event['id'] ?? 0]
                );
                $context['is_new_ip_for_user'] = ((int) $knownIp === 0);
            }

            // Is this a country the user has never logged in from?
            if ($context['ip_country'] && !empty($context['baseline_known_countries'])) {
                $context['is_new_country_for_user'] = !in_array(
                    $context['ip_country'],
                    $context['baseline_known_countries']
                );
            }

            // ─── Impossible Travel Detection ───────────────

            // Get the user's previous event with geo data
            $prevGeoEvent = $this->db->queryOne(
                "SELECT e.created_at, ip.country, ip.latitude, ip.longitude
                 FROM events e
                 JOIN ip_addresses ip ON e.ip_address_id = ip.id
                 WHERE e.user_id = :user_id
                   AND e.id != :event_id
                   AND ip.latitude IS NOT NULL
                 ORDER BY e.created_at DESC
                 LIMIT 1",
                ['user_id' => $userId, 'event_id' => $event['id'] ?? 0]
            );

            if ($prevGeoEvent) {
                $context['previous_ip_latitude'] = (float) $prevGeoEvent['latitude'];
                $context['previous_ip_longitude'] = (float) $prevGeoEvent['longitude'];
                $context['previous_ip_country'] = $prevGeoEvent['country'] ?? '';
                $context['previous_event_time'] = $prevGeoEvent['created_at'];

                // Calculate travel distance (Haversine) and speed
                if ($context['ip_latitude'] !== null && $context['ip_longitude'] !== null) {
                    $distance = self::haversineDistance(
                        (float) $context['previous_ip_latitude'],
                        (float) $context['previous_ip_longitude'],
                        (float) $context['ip_latitude'],
                        (float) $context['ip_longitude']
                    );
                    $context['travel_distance_km'] = round($distance, 1);

                    $timeDiffHours = (time() - strtotime($prevGeoEvent['created_at'])) / 3600;
                    if ($timeDiffHours > 0 && $distance > 0) {
                        $context['travel_speed_kmh'] = round($distance / $timeDiffHours, 1);
                    }
                }
            }

            // ─── Distributed Attack Detection ──────────────
            $context['user_attacking_ip_count'] = (int) $this->db->queryScalar(
                "SELECT COUNT(DISTINCT ip_address_id) FROM events WHERE user_id = :user_id AND event_type IN ('login_failed','login_failure') AND created_at >= NOW() - INTERVAL '1 hour'",
                ['user_id' => $userId]
            );

            // ─── Request Timing Entropy (Bot Detection) ────
            $recentTimestamps = $this->db->query(
                "SELECT EXTRACT(EPOCH FROM created_at) AS ts FROM events WHERE user_id = :user_id AND created_at >= NOW() - INTERVAL '5 minutes' ORDER BY created_at",
                ['user_id' => $userId]
            );
            if (count($recentTimestamps) >= 3) {
                $intervals = [];
                for ($i = 1; $i < count($recentTimestamps); $i++) {
                    $intervals[] = (float) $recentTimestamps[$i]['ts'] - (float) $recentTimestamps[$i - 1]['ts'];
                }
                $context['request_interval_stddev'] = self::standardDeviation($intervals);
            }

            // ─── Session Depth (Bot Detection) ─────────────
            if (isset($event['session_id'])) {
                $sessionStats = $this->db->queryOne(
                    "SELECT COUNT(*) AS event_count, COUNT(DISTINCT url) AS unique_pages FROM events WHERE session_id = :sid",
                    ['sid' => $event['session_id']]
                );
                if ($sessionStats) {
                    $context['session_event_count'] = (int) $sessionStats['event_count'];
                    $context['session_unique_pages'] = (int) $sessionStats['unique_pages'];
                }
            }

            // ─── Compute Overall Deviation Score ───────────
            $context['deviation_score'] = $this->computeDeviationScore($context);
        }

        return $context;
    }

    /**
     * Compute a 0-100 behavioral deviation score.
     * Higher = more abnormal compared to user's baseline.
     */
    private function computeDeviationScore(array $context): float
    {
        $score = 0.0;

        // New device = +15
        if ($context['is_new_device_for_user']) $score += 15;

        // New IP = +10
        if ($context['is_new_ip_for_user']) $score += 10;

        // New country = +20
        if ($context['is_new_country_for_user']) $score += 20;

        // Unusual hour = +10
        $currentHour = (int) date('G');
        if (!empty($context['baseline_typical_hours']) && !in_array($currentHour, $context['baseline_typical_hours'])) {
            $score += 10;
        }

        // Activity spike (>3x average) = +15
        if ($context['baseline_avg_events_per_day'] > 0) {
            $todayEvents = $context['events_per_minute'] * 60 * 24; // projected
            $ratio = $todayEvents / $context['baseline_avg_events_per_day'];
            if ($ratio > 5) $score += 25;
            elseif ($ratio > 3) $score += 15;
            elseif ($ratio > 2) $score += 8;
        }

        // Impossible travel speed (>900 km/h ≈ commercial flight) = +20
        if ($context['travel_speed_kmh'] !== null && $context['travel_speed_kmh'] > 900) {
            $score += 20;
        }

        return min(100, $score);
    }

    /**
     * Haversine formula — calculate distance in km between two lat/lng points.
     */
    public static function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    /**
     * Calculate standard deviation of an array of numbers.
     */
    private static function standardDeviation(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0.0;

        $mean = array_sum($values) / $n;
        $sumSquaredDiffs = 0.0;
        foreach ($values as $v) {
            $sumSquaredDiffs += ($v - $mean) ** 2;
        }

        return sqrt($sumSquaredDiffs / ($n - 1));
    }
}
