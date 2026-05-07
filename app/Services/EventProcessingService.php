<?php

namespace Sentinel\Services;

use Sentinel\Core\Database;
use Sentinel\Engine\RiskEngine;
use Sentinel\Engine\ScoreCalculator;
use Sentinel\Models\User;
use Sentinel\Models\Event;
use Sentinel\Models\Session;
use Sentinel\Models\IpAddress;
use Sentinel\Models\Device;
use Sentinel\Models\RiskScore;
use Sentinel\Models\Rule;
use Sentinel\Models\ReviewItem;
use Sentinel\Models\AuditEntry;

class EventProcessingService
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Process a single event through the pipeline.
     */
    public function processEvent(array $data): array
    {
        $userModel = new User($this->db);
        $eventModel = new Event($this->db);
        $sessionModel = new Session($this->db);
        $ipModel = new IpAddress($this->db);
        $deviceModel = new Device($this->db);
        $riskScoreModel = new RiskScore($this->db);
        $ruleModel = new Rule($this->db);
        $reviewModel = new ReviewItem($this->db);

        // 0. Idempotency Check
        $idempotencyKey = $data['idempotency_key'] ?? hash('sha256', json_encode($data));
        $data['idempotency_key'] = $idempotencyKey;

        $existingEvent = $eventModel->findByIdempotencyKey($idempotencyKey);
        if ($existingEvent) {
            return [
                'event_id'        => $existingEvent['id'],
                'risk_score'      => (float) $existingEvent['risk_score'],
                'risk_level'      => 'cached',
                'confidence'      => 100,
                'deviation_score' => 0,
                'risk_factors'    => [],
                'rules_triggered' => [],
                'status'          => 'already_processed',
            ];
        }

        // 1. Resolve or create user
        $user = null;
        if (!empty($data['user_id'])) {
            $user = $userModel->findOrCreate($data['user_id'], [
                'email'    => $data['email'] ?? null,
                'username' => $data['username'] ?? null,
                'phone'    => $data['phone'] ?? null,
            ]);
        }

        // 2. Resolve or create IP address
        $ip = null;
        if (!empty($data['ip'])) {
            $ip = $ipModel->findOrCreate($data['ip']);
        }

        // 3. Resolve or create device
        $device = null;
        $userAgent = $data['user_agent'] ?? $data['userAgent'] ?? '';
        if ($userAgent) {
            $device = $deviceModel->findOrCreate($userAgent);
        }

        // 4. Resolve or create session
        $session = null;
        if ($user && !empty($data['session_id'])) {
            $session = $sessionModel->findOrCreate(
                $data['session_id'],
                $user['id'],
                $ip['id'] ?? null,
                $device['id'] ?? null
            );
        }

        // 5. Create event record
        $eventId = $eventModel->create([
            'event_type'    => $data['event_type'],
            'user_id'       => $user['id'] ?? null,
            'session_id'    => $session['id'] ?? null,
            'ip_address_id' => $ip['id'] ?? null,
            'device_id'     => $device['id'] ?? null,
            'url'           => $data['url'] ?? null,
            'http_method'   => $data['http_method'] ?? null,
            'metadata'      => $data['metadata'] ?? [],
        ]);

        // 6. Run risk engine
        $riskScore = 0.0;
        $riskLevel = 'low';
        $triggeredRules = [];

        if ($user) {
            $event = $eventModel->findById($eventId);
            $engine = new RiskEngine($this->db);
            $ruleResults = $engine->evaluate($event, $user);

            // Build context for confidence/deviation scoring
            $scoreContext = [
                'baseline_event_count_30d' => (int) $this->db->queryScalar(
                    "SELECT COUNT(*) FROM events WHERE user_id = :uid AND created_at >= NOW() - INTERVAL '30 days'",
                    ['uid' => $user['id']]
                ),
                'deviation_score' => 0,
            ];
            // Retrieve deviation score from risk engine context if available
            foreach ($ruleResults as $rr) {
                if ($rr->triggered) break; // just need the context, not results
            }
            $scores = ScoreCalculator::calculate($ruleResults, $scoreContext);

            $riskScore = $scores['overall_score'];
            $riskLevel = $scores['risk_level'];
            $triggeredRules = $scores['triggered_rules'];

            // Save rule results
            $dbRules = $ruleModel->getEnabled();
            $ruleMap = [];
            foreach ($dbRules as $r) {
                $ruleMap[$r['slug']] = $r['id'];
            }

            foreach ($ruleResults as $result) {
                if (isset($ruleMap[$result->ruleSlug])) {
                    $ruleModel->saveResult(
                        $eventId,
                        $ruleMap[$result->ruleSlug],
                        $user['id'],
                        $result->score,
                        $result->triggered,
                        $result->details
                    );
                }
            }

            // Mark event as processed
            $eventModel->markProcessed($eventId, $riskScore, $triggeredRules);

            // Update user risk score
            $riskScoreModel->upsert($user['id'], $scores);
            $userModel->updateRiskScore($user['id'], $riskScore, $riskLevel);

            // Auto-flag/suspend if thresholds are met
            $flagThreshold = $this->config['risk']['threshold_flag'] ?? 60;
            $suspendThreshold = $this->config['risk']['threshold_suspend'] ?? 85;

            if ($riskScore >= $suspendThreshold) {
                $userModel->updateStatus($user['id'], 'suspended');
                \Sentinel\Core\Logger::critical('User suspended by risk engine', [
                    'user_id' => $user['id'], 
                    'risk_score' => $riskScore, 
                    'triggered_rules' => $triggeredRules,
                    'event_type' => $data['event_type']
                ]);
                $reviewModel->create(
                    $user['id'],
                    "Risk score {$riskScore} exceeds suspend threshold ({$suspendThreshold})",
                    $riskScore,
                    $triggeredRules,
                    'critical'
                );
            } elseif ($riskScore >= $flagThreshold) {
                $userModel->updateStatus($user['id'], 'flagged');
                \Sentinel\Core\Logger::warn('User flagged by risk engine', [
                    'user_id' => $user['id'], 
                    'risk_score' => $riskScore, 
                    'triggered_rules' => $triggeredRules,
                    'event_type' => $data['event_type']
                ]);
                $reviewModel->create(
                    $user['id'],
                    "Risk score {$riskScore} exceeds flag threshold ({$flagThreshold})",
                    $riskScore,
                    $triggeredRules,
                    'high'
                );
            }

            // Create audit entries for field changes
            if (!empty($data['field_changes'])) {
                $auditModel = new AuditEntry($this->db);
                foreach ($data['field_changes'] as $change) {
                    $auditModel->create([
                        'user_id'     => $user['id'],
                        'entity_type' => $change['entity_type'] ?? 'user',
                        'entity_id'   => $change['entity_id'] ?? $user['id'],
                        'field_name'  => $change['field'] ?? 'unknown',
                        'old_value'   => $change['old_value'] ?? null,
                        'new_value'   => $change['new_value'] ?? null,
                        'changed_by'  => $data['user_id'] ?? 'system',
                        'ip_address'  => $data['ip'] ?? null,
                    ]);
                }
            }
        }

        // Build risk factors breakdown for "Why Flagged?" explanation
        $riskFactors = [];
        if (isset($scores['factors'])) {
            foreach ($scores['factors'] as $factor) {
                $riskFactors[] = [
                    'rule'    => $factor['rule'] ?? 'unknown',
                    'score'   => $factor['score'] ?? 0,
                    'reason'  => $factor['description'] ?? '',
                    'details' => $factor['details'] ?? [],
                ];
            }
        }

        return [
            'event_id'        => $eventId,
            'risk_score'      => $riskScore,
            'risk_level'      => $riskLevel,
            'confidence'      => $scores['confidence'] ?? 100,
            'deviation_score' => $scores['deviation_score'] ?? 0,
            'risk_factors'    => $riskFactors,
            'rules_triggered' => $triggeredRules,
        ];
    }
}
