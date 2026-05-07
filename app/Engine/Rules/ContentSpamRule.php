<?php

namespace Sentinel\Engine\Rules;

class ContentSpamRule implements RuleInterface
{
    public function evaluate(array $event, array $user, array $context): RuleResult
    {
        $score = 0.0;
        $details = [];

        $contentActions = ['post_create', 'comment_create', 'message_send', 'review_create', 'content_submit'];

        if (!in_array($event['event_type'], $contentActions)) {
            return new RuleResult($this->getSlug(), 0, false);
        }

        // High frequency posting
        $postsPerHour = $context['posts_per_hour'] ?? 0;
        if ($postsPerHour > 20) {
            $score += 40;
            $details[] = "{$postsPerHour} content submissions in the last hour";
        } elseif ($postsPerHour > 10) {
            $score += 20;
            $details[] = "{$postsPerHour} content submissions in the last hour";
        }

        // New account posting rapidly
        $userAge = $context['user_age_hours'] ?? 999;
        if ($userAge < 1 && $postsPerHour > 5) {
            $score += 30;
            $details[] = "New account (< 1 hour old) posting at high frequency";
        }

        // Check content metadata for spam indicators
        $metadata = $event['metadata'] ?? [];
        if (isset($metadata['contains_urls']) && $metadata['contains_urls'] > 3) {
            $score += 15;
            $details[] = "Content contains {$metadata['contains_urls']} URLs";
        }

        return new RuleResult(
            $this->getSlug(),
            $score,
            $score >= 20,
            'Content spam pattern detected',
            $details
        );
    }

    public function getWeight(): float { return 1.0; }
    public function getSlug(): string { return 'content_spam'; }
    public function getCategory(): string { return 'content'; }
}
