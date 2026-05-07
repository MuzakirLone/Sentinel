<?php

namespace Sentinel\Engine\Rules;

class PromoAbuseRule implements RuleInterface
{
    public function evaluate(array $event, array $user, array $context): RuleResult
    {
        $score = 0.0;
        $details = [];

        $promoActions = ['promo_apply', 'coupon_use', 'referral_claim', 'discount_apply'];

        if (!in_array($event['event_type'], $promoActions)) {
            return new RuleResult($this->getSlug(), 0, false);
        }

        // Multiple promo usages
        $promoCount = $context['user_promo_count'] ?? 0;
        if ($promoCount > 5) {
            $score += 40;
            $details[] = "User has used {$promoCount} promotional codes";
        } elseif ($promoCount > 3) {
            $score += 20;
            $details[] = "User has used {$promoCount} promotional codes";
        }

        // New account using promo immediately
        $userAge = $context['user_age_hours'] ?? 999;
        if ($userAge < 1) {
            $score += 20;
            $details[] = "Promotional code used within first hour of account creation";
        }

        // Same promo from same IP used by different accounts
        $ipPromoAccounts = $context['ip_promo_account_count'] ?? 0;
        if ($ipPromoAccounts > 2) {
            $score += 30;
            $details[] = "{$ipPromoAccounts} accounts from same IP using promotions";
        }

        return new RuleResult(
            $this->getSlug(),
            $score,
            $score >= 20,
            'Promotional abuse pattern detected',
            $details
        );
    }

    public function getWeight(): float { return 1.0; }
    public function getSlug(): string { return 'promo_abuse'; }
    public function getCategory(): string { return 'fraud'; }
}
