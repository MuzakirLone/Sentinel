<?php

namespace Sentinel\Engine\Rules;

class DormantAccountRule implements RuleInterface
{
    public function evaluate(array $event, array $user, array $context): RuleResult
    {
        $score = 0.0;
        $details = [];

        $lastSeen = $user['last_seen_at'] ?? null;
        $firstSeen = $user['first_seen_at'] ?? null;

        if (!$lastSeen || !$firstSeen) {
            return new RuleResult($this->getSlug(), 0, false);
        }

        // Calculate dormancy period
        $lastSeenTs = strtotime($lastSeen);
        $nowTs = time();
        $dormantDays = ($nowTs - $lastSeenTs) / 86400;

        if ($dormantDays > 180) {
            $score += 35;
            $details[] = "Account dormant for " . round($dormantDays) . " days, now suddenly active";
        } elseif ($dormantDays > 90) {
            $score += 20;
            $details[] = "Account dormant for " . round($dormantDays) . " days, now suddenly active";
        } elseif ($dormantDays > 30) {
            $score += 10;
            $details[] = "Account inactive for " . round($dormantDays) . " days";
        }

        // Dormant account + sensitive action
        if ($dormantDays > 30 && in_array($event['event_type'], ['password_change', 'email_change', 'data_export'])) {
            $score += 25;
            $details[] = "Sensitive action after long dormancy period";
        }

        return new RuleResult(
            $this->getSlug(),
            $score,
            $score >= 20,
            'Dormant account reactivation detected',
            $details
        );
    }

    public function getWeight(): float { return 0.8; }
    public function getSlug(): string { return 'dormant_account'; }
    public function getCategory(): string { return 'behavior'; }
}
