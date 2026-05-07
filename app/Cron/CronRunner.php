<?php

namespace Sentinel\Cron;

use Sentinel\Core\Database;
use Sentinel\Engine\RiskEngine;
use Sentinel\Engine\ScoreCalculator;
use Sentinel\Models\Event;
use Sentinel\Models\User;
use Sentinel\Models\RiskScore;
use Sentinel\Models\ReviewItem;
use Sentinel\Models\Rule;

/**
 * Cron runner — processes unprocessed events and recalculates risk scores.
 */
class CronRunner
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function run(): void
    {
        $this->processUnprocessedEvents();
        $this->cleanupOldSessions();

        echo date('[Y-m-d H:i:s]') . " Cron tasks completed.\n";
    }

    private function processUnprocessedEvents(): void
    {
        $eventModel = new Event($this->db);
        $userModel = new User($this->db);
        $riskScoreModel = new RiskScore($this->db);
        $reviewModel = new ReviewItem($this->db);
        $ruleModel = new Rule($this->db);

        $events = $eventModel->getUnprocessed(200);
        $processedCount = 0;

        $engine = new RiskEngine($this->db);

        $dbRules = $ruleModel->getEnabled();
        $ruleMap = [];
        foreach ($dbRules as $r) {
            $ruleMap[$r['slug']] = $r['id'];
        }

        foreach ($events as $event) {
            try {
                $user = $event['user_id'] ? $userModel->findById($event['user_id']) : null;

                if (!$user) {
                    $eventModel->markProcessed($event['id']);
                    continue;
                }

                $ruleResults = $engine->evaluate($event, $user);
                $scores = ScoreCalculator::calculate($ruleResults);

                // Save rule results
                foreach ($ruleResults as $result) {
                    if (isset($ruleMap[$result->ruleSlug])) {
                        $ruleModel->saveResult(
                            $event['id'],
                            $ruleMap[$result->ruleSlug],
                            $user['id'],
                            $result->score,
                            $result->triggered,
                            $result->details
                        );
                    }
                }

                $eventModel->markProcessed($event['id'], $scores['overall_score'], $scores['triggered_rules']);
                $riskScoreModel->upsert($user['id'], $scores);
                $userModel->updateRiskScore($user['id'], $scores['overall_score'], $scores['risk_level']);

                // Auto-flag/suspend
                $flagThreshold = $this->config['risk']['threshold_flag'] ?? 60;
                $suspendThreshold = $this->config['risk']['threshold_suspend'] ?? 85;

                if ($scores['overall_score'] >= $suspendThreshold) {
                    $userModel->updateStatus($user['id'], 'suspended');
                    $reviewModel->create(
                        $user['id'],
                        "Risk score {$scores['overall_score']} exceeds suspend threshold",
                        $scores['overall_score'],
                        $scores['triggered_rules'],
                        'critical'
                    );
                } elseif ($scores['overall_score'] >= $flagThreshold) {
                    $userModel->updateStatus($user['id'], 'flagged');
                    $reviewModel->create(
                        $user['id'],
                        "Risk score {$scores['overall_score']} exceeds flag threshold",
                        $scores['overall_score'],
                        $scores['triggered_rules'],
                        'high'
                    );
                }

                $processedCount++;
            } catch (\Exception $e) {
                error_log("Cron error processing event {$event['id']}: {$e->getMessage()}");
            }
        }

        if ($processedCount > 0) {
            echo date('[Y-m-d H:i:s]') . " Processed {$processedCount} events.\n";
        }
    }

    private function cleanupOldSessions(): void
    {
        $deleted = $this->db->execute(
            "DELETE FROM sessions WHERE last_activity < NOW() - INTERVAL '7 days'"
        );

        if ($deleted > 0) {
            echo date('[Y-m-d H:i:s]') . " Cleaned up {$deleted} old sessions.\n";
        }
    }
}
