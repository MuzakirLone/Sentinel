<?php

namespace Sentinel\Engine\Rules;

class MultiAccountingRule implements RuleInterface
{
    public function evaluate(array $event, array $user, array $context): RuleResult
    {
        $score = 0.0;
        $details = [];

        if (!in_array($event['event_type'], ['signup', 'register', 'account_create', 'login_success'])) {
            return new RuleResult($this->getSlug(), 0, false);
        }

        // Multiple accounts from same IP
        $accountsFromIp = $context['ip_account_count'] ?? 0;
        if ($accountsFromIp > 5) {
            $score += 40;
            $details[] = "{$accountsFromIp} accounts registered from the same IP address";
        } elseif ($accountsFromIp > 3) {
            $score += 20;
            $details[] = "{$accountsFromIp} accounts registered from the same IP address";
        }

        // Multiple accounts from same device
        $accountsFromDevice = $context['device_account_count'] ?? 0;
        if ($accountsFromDevice > 3) {
            $score += 35;
            $details[] = "{$accountsFromDevice} accounts from the same device fingerprint";
        } elseif ($accountsFromDevice > 2) {
            $score += 15;
            $details[] = "{$accountsFromDevice} accounts from the same device fingerprint";
        }

        // Similar email patterns (e.g., user1@, user2@, user3@)
        $email = $user['email'] ?? '';
        if ($email && preg_match('/\d+@/', $email)) {
            $score += 10;
            $details[] = "Email contains numeric pattern suggesting generated address";
        }

        return new RuleResult(
            $this->getSlug(),
            $score,
            $score >= 20,
            'Multi-accounting pattern detected',
            $details
        );
    }

    public function getWeight(): float { return 1.0; }
    public function getSlug(): string { return 'multi_accounting'; }
    public function getCategory(): string { return 'identity'; }
}
