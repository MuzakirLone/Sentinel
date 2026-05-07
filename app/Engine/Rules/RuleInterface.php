<?php

namespace Sentinel\Engine\Rules;

/**
 * Interface that all risk rules must implement.
 */
interface RuleInterface
{
    /**
     * Evaluate an event against this rule.
     *
     * @param array $event      The event being evaluated
     * @param array $user       The user associated with the event
     * @param array $context    Additional context (IP history, device history, etc.)
     * @return RuleResult       The evaluation result
     */
    public function evaluate(array $event, array $user, array $context): RuleResult;

    /**
     * Get the rule's default weight multiplier.
     */
    public function getWeight(): float;

    /**
     * Get the rule's unique slug identifier.
     */
    public function getSlug(): string;

    /**
     * Get the risk category (authentication, behavior, identity, geo, etc.)
     */
    public function getCategory(): string;
}
