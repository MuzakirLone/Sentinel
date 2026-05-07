<?php

namespace Sentinel\Services;

use Sentinel\Core\Database;
use Sentinel\Models\Event;
use Sentinel\Models\User;
use Sentinel\Models\Session;
use Sentinel\Models\RiskScore;

/**
 * Service orchestrating complex single-user data relationships.
 * Aggregates analytical datasets out of Controllers gracefully protecting separation of concerns.
 */
class UserTimelineService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Resolves all historical and relational data required for displaying a User Timeline natively.
     * 
     * @return array|null Null if user isn't physically found.
     */
    public function getFullTimeline(int $userId): ?array
    {
        $userModel      = new User($this->db);
        $eventModel     = new Event($this->db);
        $sessionModel   = new Session($this->db);
        $riskScoreModel = new RiskScore($this->db);

        $user = $userModel->findById($userId);
        if (!$user) {
            return null; // Signals 404 cleanly upstream
        }

        $timeline    = $eventModel->getUserTimeline($userId, 100);
        $sessions    = $sessionModel->getUserSessions($userId, 20);
        $riskProfile = $riskScoreModel->getByUserId($userId);

        // Calculate explicit flag-origins explaining the behavior 
        $riskFactors = $this->db->query(
            "SELECT rr.score, rr.triggered, rr.details, r.name, r.slug, r.category
             FROM rule_results rr
             JOIN rules r ON rr.rule_id = r.id
             WHERE rr.user_id = :user_id AND rr.triggered = TRUE
             ORDER BY rr.score DESC, rr.created_at DESC
             LIMIT 20",
            ['user_id' => $userId]
        );

        return [
            'user'          => $user,
            'timeline'      => $timeline,
            'sessions'      => $sessions,
            'risk_profile'  => $riskProfile,
            'risk_factors'  => $riskFactors,
        ];
    }
}
