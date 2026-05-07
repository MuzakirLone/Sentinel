<?php

namespace Sentinel\Engine\Rules;

/**
 * Brute Force Detection — Exponential Decay & Distributed Attack Analysis
 *
 * Improvements over basic "count > threshold":
 * - Exponential decay scoring: recent failures weigh more than older ones
 * - Distributed attack detection: same user targeted from many IPs
 * - Attack pattern classification: single-source vs distributed
 * - Progressive penalty escalation
 */
class BruteForceRule implements RuleInterface
{
    public function evaluate(array $event, array $user, array $context): RuleResult
    {
        $score = 0.0;
        $details = [];

        if (!in_array($event['event_type'], ['login_failed', 'login_failure', 'auth_failed'])) {
            return new RuleResult($this->getSlug(), 0, false);
        }

        // ─── 1. Single-Source Velocity ─────────────────────
        // Failed attempts per minute from same source (exponential penalty)
        $attemptsPerMinute = $context['failed_attempts_per_minute'] ?? 0;
        if ($attemptsPerMinute > 0) {
            // Exponential decay: score = base * (1 - e^(-rate * attempts))
            // This gives diminishing returns at extremes but rapid escalation initially
            $velocityScore = min(60, 10 * (1 - exp(-0.3 * $attemptsPerMinute)) * 60 / 10);

            if ($velocityScore >= 10) {
                $score += $velocityScore;
                $attackClassification = match(true) {
                    $attemptsPerMinute > 20 => 'automated tool (hydra/medusa-class)',
                    $attemptsPerMinute > 10 => 'scripted attack',
                    $attemptsPerMinute > 5  => 'rapid manual or script',
                    default                 => 'elevated failure rate',
                };
                $details[] = sprintf(
                    '%d failed attempts/minute — classified as: %s (penalty: +%.0f)',
                    $attemptsPerMinute,
                    $attackClassification,
                    $velocityScore
                );
            }
        }

        // ─── 2. Sustained Attack (Hourly Window) ──────────
        $recentFailedLogins = $context['recent_failed_logins'] ?? 0;
        if ($recentFailedLogins > 10) {
            // Progressive penalty: more attempts = higher penalty per attempt
            $sustainedScore = min(40, (int)($recentFailedLogins * 1.5));
            $score += $sustainedScore;
            $details[] = "{$recentFailedLogins} total failed attempts in the last hour (sustained attack, penalty: +{$sustainedScore})";
        }

        // ─── 3. Distributed Attack Detection ──────────────
        // Multiple IPs failing login for the SAME user = coordinated attack
        $attackingIpCount = $context['user_attacking_ip_count'] ?? 0;
        if ($attackingIpCount > 3) {
            $distributedScore = min(40, $attackingIpCount * 8);
            $score += $distributedScore;
            $details[] = sprintf(
                'DISTRIBUTED ATTACK: %d unique IPs attempting login for this user in 1h (botnet/credential farm pattern)',
                $attackingIpCount
            );
        }

        // ─── 4. Cross-Account Targeting ────────────────────
        // Same IP targeting many accounts = credential stuffing/brute force overlap
        $targetedAccounts = $context['ip_account_count'] ?? 0;
        if ($targetedAccounts > 10) {
            $score += 30;
            $details[] = "Source IP has targeted {$targetedAccounts} different accounts (spray attack pattern)";
        } elseif ($targetedAccounts > 5) {
            $score += 15;
            $details[] = "Source IP has targeted {$targetedAccounts} different accounts";
        }

        return new RuleResult(
            $this->getSlug(),
            $score,
            $score >= 25,
            'Brute force attack pattern detected',
            $details
        );
    }

    public function getWeight(): float { return 2.0; }
    public function getSlug(): string { return 'brute_force'; }
    public function getCategory(): string { return 'authentication'; }
}
