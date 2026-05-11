<?php

namespace Sentinel\Tests\Unit\Engine;

use Sentinel\Tests\TestCase;
use Sentinel\Engine\RiskEngine;

class RiskEngineTest extends TestCase
{
    public function testHaversineDistanceMath()
    {
        // New York to London
        $lat1 = 40.7128;
        $lon1 = -74.0060;
        $lat2 = 51.5074;
        $lon2 = -0.1278;

        $distance = RiskEngine::haversineDistance($lat1, $lon1, $lat2, $lon2);

        // Distance should be approx 5570 km
        $this->assertGreaterThan(5000, $distance);
        $this->assertLessThan(6000, $distance);
        $this->assertEqualsWithDelta(5570, $distance, 50); // allow 50km margin
    }

    public function testEvaluateReturnsNoResultsForEmptyRules()
    {
        $db = $this->createMockDatabase();
        // Mock query to return no enabled rules
        $db->method('query')->willReturn([]);

        $engine = new RiskEngine($db);
        $results = $engine->evaluate(['id' => 1], ['id' => 1]);

        $this->assertEmpty($results);
    }

    public function testBuildContextUsesIpIdParameterForKnownIpQuery()
    {
        $db = $this->createMockDatabase();

        $db->method('query')->willReturn([]);
        $db->method('queryOne')->willReturnCallback(function (string $sql, array $params = []) {
            if (str_contains($sql, 'FROM ip_addresses WHERE id = :id')) {
                return ['id' => $params['id'] ?? 0, 'country' => 'US'];
            }
            return null;
        });
        $db->method('queryScalar')->willReturnCallback(function (string $sql, array $params = []) {
            if (str_contains($sql, 'ip_address_id = :ip_id') && str_contains($sql, 'id != :event_id')) {
                if (!array_key_exists('ip_id', $params)) {
                    throw new \InvalidArgumentException('Missing ip_id bind parameter');
                }
            }
            return 0;
        });

        $engine = new RiskEngine($db);

        $ref = new \ReflectionClass($engine);
        $method = $ref->getMethod('buildContext');
        $method->setAccessible(true);

        $context = $method->invoke($engine, ['id' => 10, 'ip_address_id' => 99], ['id' => 1]);

        $this->assertIsArray($context);
    }
}
