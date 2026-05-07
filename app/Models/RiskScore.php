<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class RiskScore
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function upsert(int $userId, array $scores): void
    {
        $existing = $this->db->queryOne(
            'SELECT id FROM risk_scores WHERE user_id = :user_id',
            ['user_id' => $userId]
        );

        $level = $this->calculateLevel($scores['overall_score'] ?? 0);

        if ($existing) {
            $this->db->update('risk_scores', [
                'overall_score'  => $scores['overall_score'] ?? 0,
                'auth_score'     => $scores['auth_score'] ?? 0,
                'behavior_score' => $scores['behavior_score'] ?? 0,
                'identity_score' => $scores['identity_score'] ?? 0,
                'geo_score'      => $scores['geo_score'] ?? 0,
                'risk_level'     => $level,
                'factors'        => json_encode($scores['factors'] ?? []),
                'calculated_at'  => date('c'),
                'updated_at'     => date('c'),
            ], ['id' => $existing['id']]);
        } else {
            $this->db->insert('risk_scores', [
                'user_id'        => $userId,
                'overall_score'  => $scores['overall_score'] ?? 0,
                'auth_score'     => $scores['auth_score'] ?? 0,
                'behavior_score' => $scores['behavior_score'] ?? 0,
                'identity_score' => $scores['identity_score'] ?? 0,
                'geo_score'      => $scores['geo_score'] ?? 0,
                'risk_level'     => $level,
                'factors'        => json_encode($scores['factors'] ?? []),
                'calculated_at'  => date('c'),
            ]);
        }
    }

    public function getByUserId(int $userId): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM risk_scores WHERE user_id = :user_id',
            ['user_id' => $userId]
        );
    }

    public function getAverageScore(): float
    {
        return (float) ($this->db->queryScalar('SELECT AVG(overall_score) FROM risk_scores') ?? 0);
    }

    private function calculateLevel(float $score): string
    {
        if ($score >= 80) return 'critical';
        if ($score >= 60) return 'high';
        if ($score >= 40) return 'elevated';
        if ($score >= 20) return 'moderate';
        return 'low';
    }
}
