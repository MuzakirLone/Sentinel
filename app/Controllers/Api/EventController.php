<?php

namespace Sentinel\Controllers\Api;

use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Engine\RiskEngine;
use Sentinel\Engine\ScoreCalculator;
use Sentinel\Models\User;
use Sentinel\Models\Event;
use Sentinel\Models\Session;
use Sentinel\Models\IpAddress;
use Sentinel\Models\Device;
use Sentinel\Models\RiskScore;
use Sentinel\Models\Rule;
use Sentinel\Models\ReviewItem;
use Sentinel\Models\AuditEntry;

/**
 * Handles event ingestion from external applications via API.
 */
class EventController
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * POST /api/v1/events — Ingest a single event.
     */
    public function store(Request $request, Response $response): void
    {
        $data = $request->input();

        // Validate required fields
        if (empty($data['event_type'])) {
            $response->json(['error' => 'event_type is required'], 400);
            return;
        }

        // ─── Synchronous Mode: process immediately ─────────
        // Note: Async queue processing (Redis) has been disabled
        try {
            $result = $this->processEvent($data);
            $response->json([
                'status'          => 'accepted',
                'event_id'        => $result['event_id'],
                'risk_score'      => $result['risk_score'],
                'risk_level'      => $result['risk_level'],
                'confidence'      => $result['confidence'] ?? 100,
                'deviation_score' => $result['deviation_score'] ?? 0,
                'risk_factors'    => $result['risk_factors'] ?? [],
            ], 201);
        } catch (\Exception $e) {
            $response->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Process an event from the background worker (no HTTP response needed).
     */
    public function processEventFromWorker(array $data): array
    {
        return $this->processEvent($data);
    }

    /**
     * POST /api/v1/events/batch — Ingest multiple events.
     */
    public function storeBatch(Request $request, Response $response): void
    {
        $data = $request->input();
        $events = $data['events'] ?? [];

        if (empty($events) || !is_array($events)) {
            $response->json(['error' => 'events array is required'], 400);
            return;
        }

        if (count($events) > 100) {
            $response->json(['error' => 'Maximum 100 events per batch'], 400);
            return;
        }

        $results = [];
        $errors = [];

        $this->db->beginTransaction();
        try {
            foreach ($events as $i => $eventData) {
                try {
                    if (empty($eventData['event_type'])) {
                        $errors[] = ['index' => $i, 'error' => 'event_type is required'];
                        continue;
                    }
                    $result = $this->processEvent($eventData);
                    $results[] = $result;
                } catch (\Exception $e) {
                    $errors[] = ['index' => $i, 'error' => $e->getMessage()];
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            $response->json(['error' => 'Batch processing failed: ' . $e->getMessage()], 500);
            return;
        }

        $response->json([
            'status'    => 'accepted',
            'processed' => count($results),
            'errors'    => $errors,
            'results'   => $results,
        ], 201);
    }

    /**
     * Process a single event through the pipeline.
     */
    private function processEvent(array $data): array
    {
        $service = new \Sentinel\Services\EventProcessingService($this->db, $this->config);
        return $service->processEvent($data);
    }
}
