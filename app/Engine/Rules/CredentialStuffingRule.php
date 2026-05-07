<?php

namespace Sentinel\Engine\Rules;

/**
 * Credential Stuffing Detection — Statistical Pattern Analysis
 *
 * Improvements over basic threshold checks:
 * - Success-to-failure ratio analysis: low ratio from single IP = stuffing
 * - Cross-account velocity: how many unique usernames per minute
 * - Attack fingerprinting: combine ratio + velocity + account spread
 * - Confidence scoring based on sample size
 */
class CredentialStuffingRule implements RuleInterface
{
    public function evaluate(array $event, array $user, array $context): RuleResult
    {
        $score = 0.0;
        $details = [];

        if (!in_array($event['event_type'], ['login_failed', 'login_failure', 'login_success'])) {
            return new RuleResult($this->getSlug(), 0, false);
        }

        // ─── 1. Success-to-Failure Ratio Analysis ─────────
        // Credential stuffing: try many stolen credentials → very low success rate
        $successCount = $context['ip_success_count'] ?? 0;
        $failureCount = $context['ip_failure_count'] ?? 0;
        $ratio = $context['ip_success_failure_ratio'] ?? 1.0;
        $totalAttempts = $successCount + $failureCount;

        if ($totalAttempts >= 5 && $ratio < 0.15) {
            // Less than 15% success rate with sufficient sample = classic stuffing
            $confidenceBonus = min(20, $totalAttempts - 5); // more data = more confidence
            $ratioScore = 35 + $confidenceBonus;
            $score += $ratioScore;
            $details[] = sprintf(
                'Success/failure ratio: %.1f%% (%d success / %d failure) — classic credential stuffing pattern (confidence: %d)',
                $ratio * 100,
                $successCount,
                $failureCount,
                min(100, $totalAttempts * 5)
            );
        } elseif ($totalAttempts >= 3 && $ratio < 0.3) {
            $score += 15;
            $details[] = sprintf(
                'Suspicious success/failure ratio: %.1f%% (%d/%d) from same IP',
                $ratio * 100,
                $successCount,
                $failureCount
            );
        }

        // ─── 2. Cross-Account Velocity ────────────────────
        // Many different usernames attempted per minute = automated tool
        $uniqueUsernamesPerMin = $context['ip_unique_usernames_per_min'] ?? 0;
        if ($uniqueUsernamesPerMin > 5) {
            $velocityScore = min(40, $uniqueUsernamesPerMin * 6);
            $score += $velocityScore;
            $details[] = sprintf(
                '%d unique accounts targeted per minute from this IP (automated credential rotation)',
                $uniqueUsernamesPerMin
            );
        }

        // ─── 3. Multi-Account Spread ──────────────────────
        $accountsFromIp = $context['ip_account_count'] ?? 0;
        $failedLoginsFromIp = $context['ip_failed_logins'] ?? 0;

        if ($failedLoginsFromIp > 10 && $accountsFromIp > 5) {
            $spreadScore = min(30, ($failedLoginsFromIp / 2) + ($accountsFromIp * 3));
            $score += $spreadScore;
            $details[] = sprintf(
                '%d failed logins across %d accounts from same IP (credential database attack)',
                $failedLoginsFromIp,
                $accountsFromIp
            );
        }

        // ─── 4. Rapid Fire Pattern ────────────────────────
        $recentFailedLogins = $context['recent_failed_logins'] ?? 0;
        if ($recentFailedLogins > 15) {
            $score += 20;
            $details[] = "{$recentFailedLogins} failed login attempts in the last hour (sustained credential testing)";
        }

        return new RuleResult(
            $this->getSlug(),
            $score,
            $score >= 25,
            'Credential stuffing pattern detected',
            $details
        );
    }

    public function getWeight(): float { return 2.0; }
    public function getSlug(): string { return 'credential_stuffing'; }
    public function getCategory(): string { return 'authentication'; }
}
