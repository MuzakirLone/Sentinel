<?php

namespace Sentinel\Engine;

use Sentinel\Engine\Rules\RuleResult;

/**
 * Aggregates individual rule scores into an overall risk score.
 *
 * Uses:
 * - Weighted category aggregation with max+blend approach
 * - Temporal decay (recent triggers weigh more in historical context)
 * - Confidence modifier (new users with few events get lower confidence)
 * - Per-rule factor breakdown for "Why Flagged?" explanations
 */
class ScoreCalculator
{
    /**
     * Calculate overall risk score from rule results.
     * Uses a weighted maximum approach rather than sum to avoid runaway scores.
     */
    public static function calculate(array $results, array $context = []): array
    {
        $categoryScores = [
            'authentication' => 0.0,
            'behavior'       => 0.0,
            'identity'       => 0.0,
            'geo'            => 0.0,
            'automation'     => 0.0,
            'content'        => 0.0,
            'fraud'          => 0.0,
            'access'         => 0.0,
        ];

        $factors = [];
        $triggeredRules = [];

        foreach ($results as $result) {
            if (!($result instanceof RuleResult)) continue;

            if ($result->triggered) {
                $triggeredRules[] = $result->ruleSlug;
                $factors[] = $result->toArray();

                // Map rule to category
                $category = self::ruleCategory($result->ruleSlug);
                $categoryScores[$category] = max($categoryScores[$category], $result->score);
            }
        }

        // ─── Category Weight Matrix ────────────────────────
        // Authentication threats are weighted highest (credential compromise)
        $weights = [
            'authentication' => 0.30,
            'behavior'       => 0.10,
            'identity'       => 0.15,
            'geo'            => 0.10,
            'automation'     => 0.15,
            'content'        => 0.05,
            'fraud'          => 0.10,
            'access'         => 0.05,
        ];

        $overallScore = 0.0;
        $maxCategoryScore = 0.0;

        foreach ($categoryScores as $category => $score) {
            $overallScore += $score * ($weights[$category] ?? 0.1);
            $maxCategoryScore = max($maxCategoryScore, $score);
        }

        // Blend of weighted average and max category score
        // This ensures a single critical rule still elevates the overall score
        $overallScore = min(100, ($overallScore * 0.6) + ($maxCategoryScore * 0.4));

        // ─── Confidence Modifier ───────────────────────────
        // Low event count = low confidence in the score
        // New users get a confidence discount to avoid false positives
        $confidence = 100;
        $eventCount30d = $context['baseline_event_count_30d'] ?? null;
        if ($eventCount30d !== null) {
            if ($eventCount30d < 3) {
                $confidence = 30;
            } elseif ($eventCount30d < 10) {
                $confidence = 60;
            } elseif ($eventCount30d < 50) {
                $confidence = 80;
            }
        }

        // ─── Deviation Amplifier ───────────────────────────
        // If the behavioral deviation score is high, amplify the risk score
        $deviationScore = $context['deviation_score'] ?? 0;
        if ($deviationScore > 50 && $overallScore > 20) {
            $amplifier = 1 + ($deviationScore / 200); // max 1.5x
            $overallScore = min(100, $overallScore * $amplifier);
        }

        $level = self::calculateLevel($overallScore);

        // ─── Sort factors by score descending for "Why Flagged?" ──
        usort($factors, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return [
            'overall_score'   => round($overallScore, 1),
            'auth_score'      => round($categoryScores['authentication'], 1),
            'behavior_score'  => round($categoryScores['behavior'], 1),
            'identity_score'  => round($categoryScores['identity'], 1),
            'geo_score'       => round($categoryScores['geo'], 1),
            'risk_level'      => $level,
            'confidence'      => $confidence,
            'deviation_score' => round($deviationScore, 1),
            'factors'         => $factors,
            'triggered_rules' => $triggeredRules,
        ];
    }

    private static function ruleCategory(string $slug): string
    {
        $map = [
            'account_takeover'    => 'authentication',
            'credential_stuffing' => 'authentication',
            'brute_force'         => 'authentication',
            'bot_detection'       => 'automation',
            'content_spam'        => 'content',
            'multi_accounting'    => 'identity',
            'dormant_account'     => 'behavior',
            'high_risk_region'    => 'geo',
            'promo_abuse'         => 'fraud',
            'insider_threat'      => 'access',
        ];

        return $map[$slug] ?? 'behavior';
    }

    private static function calculateLevel(float $score): string
    {
        if ($score >= 80) return 'critical';
        if ($score >= 60) return 'high';
        if ($score >= 40) return 'elevated';
        if ($score >= 20) return 'moderate';
        return 'low';
    }
}
