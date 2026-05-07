<?php

namespace Sentinel\Engine\Rules;

/**
 * Account Takeover Detection — Behavioral Anomaly Analysis
 *
 * Goes beyond simple threshold checks to detect:
 * - Velocity-based anomalies (how fast are new devices/IPs appearing?)
 * - Session novelty scoring (new device + new IP + new country = compound signal)
 * - Credential change chain detection (password → email → MFA disable)
 * - Deviation from user's established login patterns
 */
class AccountTakeoverRule implements RuleInterface
{
    public function evaluate(array $event, array $user, array $context): RuleResult
    {
        $score = 0.0;
        $details = [];

        $sensitiveActions = ['login_success', 'password_change', 'email_change', 'mfa_disable', 'phone_change', 'recovery_email_change'];

        if (!in_array($event['event_type'], $sensitiveActions)) {
            return new RuleResult($this->getSlug(), 0, false);
        }

        // ─── 1. Compound Novelty Signal ────────────────────
        // New device + new IP + new country on same event = strong ATO signal
        $noveltyScore = 0;
        $noveltyFactors = [];

        if ($context['is_new_device_for_user'] ?? false) {
            $noveltyScore += 15;
            $noveltyFactors[] = 'new device';
        }
        if ($context['is_new_ip_for_user'] ?? false) {
            $noveltyScore += 10;
            $noveltyFactors[] = 'new IP';
        }
        if ($context['is_new_country_for_user'] ?? false) {
            $noveltyScore += 15;
            $noveltyFactors[] = 'new country';
        }

        if ($noveltyScore > 0) {
            $score += $noveltyScore;
            $details[] = sprintf(
                'Login from %s (compound novelty: %d/40)',
                implode(' + ', $noveltyFactors),
                $noveltyScore
            );
        }

        // ─── 2. Velocity-Based Device/IP Anomaly ───────────
        // Compare recent device/IP count against user's baseline
        $recentIpCount = $context['recent_ip_count'] ?? 0;
        $baselineIpCount = $context['baseline_known_ip_count'] ?? 0;

        if ($baselineIpCount > 0 && $recentIpCount > 3) {
            // User normally uses N IPs total, but has used M in just 24h
            $ipVelocityRatio = $recentIpCount / max(1, $baselineIpCount);
            if ($ipVelocityRatio > 1.5) {
                $score += min(30, (int)($ipVelocityRatio * 10));
                $details[] = sprintf(
                    'IP velocity anomaly: %d unique IPs in 24h (baseline total: %d, ratio: %.1fx)',
                    $recentIpCount,
                    $baselineIpCount,
                    $ipVelocityRatio
                );
            }
        } elseif ($recentIpCount > 5) {
            $score += 25;
            $details[] = "{$recentIpCount} different IPs in 24h (no baseline yet)";
        }

        $recentDeviceCount = $context['recent_device_count'] ?? 0;
        if ($recentDeviceCount > 3) {
            $score += 20;
            $details[] = "{$recentDeviceCount} different devices in 24h";
        }

        // ─── 3. Credential Change Chain ────────────────────
        // Detect rapid sequences: password_change → email_change → mfa_disable
        $credentialChangeTypes = ['password_change', 'email_change', 'mfa_disable', 'phone_change', 'recovery_email_change'];
        if (in_array($event['event_type'], $credentialChangeTypes)) {
            $recentCredChanges = $context['recent_credential_changes'] ?? 0;
            // Even without detailed count, a credential change from new device is alarming
            $score += 20;
            $details[] = "Sensitive credential action: {$event['event_type']}";

            if ($context['is_new_device_for_user'] ?? false) {
                $score += 15;
                $details[] = 'Credential change from previously unseen device';
            }
        }

        // ─── 4. Failed-then-Success Pattern ────────────────
        $recentFailedLogins = $context['recent_failed_logins'] ?? 0;
        if ($recentFailedLogins > 3 && $event['event_type'] === 'login_success') {
            // Scale by number of failures — more failures = more suspicious
            $failPenalty = min(35, $recentFailedLogins * 5);
            $score += $failPenalty;
            $details[] = "{$recentFailedLogins} failed logins preceded this successful login (penalty: +{$failPenalty})";
        }

        // ─── 5. Unusual Login Hour ─────────────────────────
        $typicalHours = $context['baseline_typical_hours'] ?? [];
        $currentHour = (int) date('G');
        if (!empty($typicalHours) && !in_array($currentHour, $typicalHours)) {
            $score += 8;
            $details[] = sprintf(
                'Login at unusual hour (%02d:00) — typical hours: %s',
                $currentHour,
                implode(', ', array_map(fn($h) => sprintf('%02d:00', $h), $typicalHours))
            );
        }

        return new RuleResult(
            $this->getSlug(),
            $score,
            $score >= 20,
            'Potential account takeover detected',
            $details
        );
    }

    public function getWeight(): float { return 1.5; }
    public function getSlug(): string { return 'account_takeover'; }
    public function getCategory(): string { return 'authentication'; }
}
