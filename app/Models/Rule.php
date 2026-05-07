<?php

namespace Sentinel\Models;

use Sentinel\Core\Database;

class Rule
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        return $this->db->query('SELECT * FROM rules ORDER BY category, name');
    }

    public function getEnabled(): array
    {
        $cacheKey = 'sentinel:rules:enabled';
        $rules = \Sentinel\Core\Cache::get($cacheKey);

        if (!$rules) {
            $rules = $this->db->query('SELECT * FROM rules WHERE is_enabled = TRUE ORDER BY category, name');
            \Sentinel\Core\Cache::set($cacheKey, $rules, 3600); // 1-hour cache
        }

        return $rules;
    }

    public function findById(int $id): ?array
    {
        return $this->db->queryOne('SELECT * FROM rules WHERE id = :id', ['id' => $id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->queryOne('SELECT * FROM rules WHERE slug = :slug', ['slug' => $slug]);
    }

    public function toggle(int $id): bool
    {
        $rule = $this->findById($id);
        if (!$rule) return false;

        $newState = !$rule['is_enabled'];
        $this->db->update('rules', [
            'is_enabled' => $newState ? 'true' : 'false',
            'updated_at' => date('c'),
        ], ['id' => $id]);

        \Sentinel\Core\Cache::delete('sentinel:rules:enabled');
        return $newState;
    }

    public function updateWeight(int $id, float $weight): void
    {
        $this->db->update('rules', [
            'weight'     => max(0.1, min(5.0, $weight)),
            'updated_at' => date('c'),
        ], ['id' => $id]);
        
        \Sentinel\Core\Cache::delete('sentinel:rules:enabled');
    }

    public function saveResult(int $eventId, int $ruleId, ?int $userId, float $score, bool $triggered, array $details = []): void
    {
        $this->db->insert('rule_results', [
            'event_id'  => $eventId,
            'rule_id'   => $ruleId,
            'user_id'   => $userId,
            'score'     => $score,
            'triggered' => $triggered ? 'true' : 'false',
            'details'   => json_encode($details),
        ]);
    }

    public function getTriggeredCountByRule(): array
    {
        return $this->db->query(
            'SELECT r.name, r.slug, r.category, COUNT(rr.id) as trigger_count
             FROM rules r
             LEFT JOIN rule_results rr ON r.id = rr.rule_id AND rr.triggered = TRUE
             GROUP BY r.id, r.name, r.slug, r.category
             ORDER BY trigger_count DESC'
        );
    }
}
