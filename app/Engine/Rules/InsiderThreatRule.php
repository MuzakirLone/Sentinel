<?php

namespace Sentinel\Engine\Rules;

class InsiderThreatRule implements RuleInterface
{
    private array $sensitiveActions = [
        'admin_login', 'data_export', 'user_delete', 'bulk_delete',
        'config_change', 'permission_change', 'role_change', 'api_key_create',
    ];

    public function evaluate(array $event, array $user, array $context): RuleResult
    {
        $score = 0.0;
        $details = [];

        if (!in_array($event['event_type'], $this->sensitiveActions)) {
            return new RuleResult($this->getSlug(), 0, false);
        }

        // Sensitive action outside business hours
        $hour = (int) date('G');
        if ($hour < 6 || $hour > 22) {
            $score += 20;
            $details[] = "Sensitive action performed at unusual hour ({$hour}:00)";
        }

        // Data export or bulk operations
        if (in_array($event['event_type'], ['data_export', 'bulk_delete'])) {
            $score += 30;
            $details[] = "High-risk action: {$event['event_type']}";
        }

        // Permission/role changes
        if (in_array($event['event_type'], ['permission_change', 'role_change'])) {
            $score += 25;
            $details[] = "Privilege modification: {$event['event_type']}";
        }

        // Multiple sensitive actions in short period
        $recentSensitiveActions = $context['recent_sensitive_actions'] ?? 0;
        if ($recentSensitiveActions > 5) {
            $score += 25;
            $details[] = "{$recentSensitiveActions} sensitive actions in the last hour";
        }

        return new RuleResult(
            $this->getSlug(),
            $score,
            $score >= 20,
            'Potential insider threat activity detected',
            $details
        );
    }

    public function getWeight(): float { return 1.8; }
    public function getSlug(): string { return 'insider_threat'; }
    public function getCategory(): string { return 'access'; }
}
