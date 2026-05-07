<?php

namespace Sentinel\Services;

use Sentinel\Core\Database;
use Sentinel\Models\Event;
use Sentinel\Models\User;
use Sentinel\Models\ReviewItem;
use Sentinel\Models\RiskScore;

/**
 * Service handling statistical aggregations and complex data fetching exclusively for Dashboard visual indicators.
 * Abstracts multiple repository inquiries isolating business logic out of HTTP Endpoints cleanly.
 */
class DashboardMetricsService
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Gathers the holistic snapshot of application operations natively.
     */
    public function getSnapshotStats(): array
    {
        $eventModel     = new Event($this->db);
        $userModel      = new User($this->db);
        $reviewModel    = new ReviewItem($this->db);
        $riskScoreModel = new RiskScore($this->db);

        $stats = [
            'total_events_24h'  => $eventModel->countRecent(24),
            'active_users_24h'  => $userModel->countActiveRecent(24),
            'high_risk_users'   => $userModel->countHighRisk($this->config['risk']['threshold_flag']),
            'pending_reviews'   => $reviewModel->countByStatus('pending'),
            'blocked_users'     => $userModel->countByStatus('suspended'),
            'avg_risk_score'    => round($riskScoreModel->getAverageScore(), 1),
            
            'events_by_hour'    => $eventModel->getEventsByHour(24),
            'risk_distribution' => $eventModel->getRiskDistribution(),
            'top_event_types'   => $eventModel->getTopEventTypes(8),
            'top_risky_users'   => $userModel->getTopRisky(5),
            'recent_events'     => $eventModel->getAll(10, 0),
        ];

        return $stats;
    }
}
