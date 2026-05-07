<?php

namespace Sentinel\Engine\Rules;

/**
 * Immutable result from a rule evaluation.
 */
class RuleResult
{
    public readonly string $ruleSlug;
    public readonly float $score;
    public readonly bool $triggered;
    public readonly string $description;
    public readonly array $details;

    public function __construct(
        string $ruleSlug,
        float $score,
        bool $triggered,
        string $description = '',
        array $details = []
    ) {
        $this->ruleSlug = $ruleSlug;
        $this->score = min(100.0, max(0.0, $score));
        $this->triggered = $triggered;
        $this->description = $description;
        $this->details = $details;
    }

    public function toArray(): array
    {
        return [
            'rule'        => $this->ruleSlug,
            'score'       => $this->score,
            'triggered'   => $this->triggered,
            'description' => $this->description,
            'details'     => $this->details,
        ];
    }
}
