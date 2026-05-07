<?php

namespace Sentinel\Tests\Unit\Engine;

use PHPUnit\Framework\TestCase;
use Sentinel\Engine\ScoreCalculator;
use Sentinel\Engine\Rules\RuleResult;

class ScoreCalculatorTest extends TestCase
{
    public function testEmptyResultsReturnsZero()
    {
        $totals = ScoreCalculator::calculate([], ['baseline_event_count_30d' => 100]);
        
        $this->assertEquals(0.0, $totals['overall_score']);
        $this->assertEquals('low', $totals['risk_level']);
        $this->assertEquals(100, $totals['confidence']);
    }

    public function testCategoryWeightsAndMaxAggregation()
    {
        $results = [
            new RuleResult('account_takeover', 100.0, true, 'ATO detected'), // auth category, 0.3 weight -> 30
            new RuleResult('brute_force', 80.0, true, 'Brute force'),       // auth category (max applies, so max is 100)
            new RuleResult('high_risk_region', 50.0, true, 'Tor'),          // geo category, 0.1 weight -> 5
        ];

        $totals = ScoreCalculator::calculate($results, ['baseline_event_count_30d' => 100]);
        
        // Expected max scores per category:
        // Auth = 100
        // Geo = 50
        $this->assertEquals(100.0, $totals['auth_score']);
        $this->assertEquals(50.0, $totals['geo_score']);

        // Overall Score Algorithm:
        // Weighted sum: (100 * 0.3) + (50 * 0.1) = 35.0
        // Max category score: 100.0
        // Blend: 35.0 * 0.6 + 100.0 * 0.4 = 21.0 + 40.0 = 61.0
        $this->assertEquals(61.0, $totals['overall_score']);
        $this->assertEquals('high', $totals['risk_level']);
    }

    public function testConfidenceModifierForNewUsers()
    {
        $results = [
            new RuleResult('account_takeover', 100.0, true, 'ATO detected'),
        ];

        // 0 events in last 30d -> extreme low confidence
        $totals = ScoreCalculator::calculate($results, ['baseline_event_count_30d' => 2]);
        $this->assertEquals(30, $totals['confidence']);

        // < 10 events
        $totals = ScoreCalculator::calculate($results, ['baseline_event_count_30d' => 8]);
        $this->assertEquals(60, $totals['confidence']);
    }

    public function testDeviationAmplifier()
    {
        $results = [
            // Creates a raw overall score of 30.0
            new RuleResult('account_takeover', 100.0, true, 'ATO'),
        ];
        // 100 auth max. Weighted sum: 30. Blend: 18 + 40 = 58.

        // Without deviation
        $totalsNormal = ScoreCalculator::calculate($results, ['baseline_event_count_30d' => 100, 'deviation_score' => 0]);
        $this->assertEquals(58.0, $totalsNormal['overall_score']);

        // With high deviation (e.g. 100 -> 1.5x amplifier)
        $totalsAmplified = ScoreCalculator::calculate($results, ['baseline_event_count_30d' => 100, 'deviation_score' => 100]);
        // 58 * 1.5 = 87.0
        $this->assertEquals(87.0, $totalsAmplified['overall_score']);
        $this->assertEquals('critical', $totalsAmplified['risk_level']);
    }

    public function testRiskLevels()
    {
        $r1 = [new RuleResult('account_takeover', 30.0, true, 'ATO')];
        $t1 = ScoreCalculator::calculate($r1, ['baseline_event_count_30d' => 100]);
        // 30 auth max -> (9 * 0.6) + (30 * 0.4) = 5.4 + 12 = 17.4 -> low
        $this->assertEquals('low', $t1['risk_level']);
    }
}
