<?php

/**
 * Sentinel — Background Event Processing Worker
 *
 * Long-running process that pulls events from the Redis queue
 * and processes them through the risk engine.
 *
 * Usage:
 *   php worker.php
 *
 * In production, this is run as a Docker container (see docker-compose.yml).
 */

declare(strict_types=1);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'Sentinel\\';
    $baseDir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
$config = require __DIR__ . '/config/app.php';

\Sentinel\Core\Logger::setRequestId('worker-' . getmypid() . '-' . uniqid());

// Prevent booting if environment secrets are omitted
\Sentinel\Core\EnvValidator::validate($config);

// Initialize Caching layer
\Sentinel\Core\Cache::init($config['redis'] ?? []);

\Sentinel\Core\Logger::info("Starting Sentinel Worker");

// Initialize
$db = \Sentinel\Core\Database::getInstance($config['database']);
$queue = new \Sentinel\Core\QueueManager($config['redis'] ?? []);

if (!$queue->isAvailable()) {
    \Sentinel\Core\Logger::critical("Redis is not available. Worker cannot start.");
    exit(1);
}

\Sentinel\Core\Logger::info("Connected to Redis. Waiting for events...");

$processed = 0;
$errors = 0;

// 0. Recover any jobs left in "processing" state from a previous unexpected shutdown
$recovered = $queue->recoverStalledJobs();
if ($recovered > 0) {
    \Sentinel\Core\Logger::info("Restored stalled jobs from processing list", ['count' => $recovered]);
}

// ─── Main processing loop ──────────────────────────────
while (true) {
    // 1. Check and promote delayed jobs ready for execution
    $promoted = $queue->promoteDelayed();
    if ($promoted > 0) {
        \Sentinel\Core\Logger::info("Promoted mature jobs from delayed queue to active", ['count' => $promoted]);
    }
    try {
        $payload = $queue->pop(5); // Block for 5s waiting for events

        if ($payload === null) {
            continue; // Timeout, loop back
        }

        $data = $payload['data'] ?? null;
        if (!$data || empty($data['event_type'])) {
            \Sentinel\Core\Logger::warn("Invalid payload — missing event_type");
            $queue->acknowledge($payload);
            continue;
        }

        $startTime = microtime(true);

        // Process the event through the full pipeline
        $service = new \Sentinel\Services\EventProcessingService($db, $config);
        $result = $service->processEvent($data);
        
        // Successfully processed. Un-stash from processing holding queue.
        $queue->acknowledge($payload);

        $elapsed = round((microtime(true) - $startTime) * 1000, 1);
        $processed++;

        // Identify idempotent hits silently
        $isIdempotent = ($result['status'] ?? '') === 'already_processed';

        \Sentinel\Core\Logger::info(
            $isIdempotent ? "Event successfully identified as idempotent" : "Successfully processed event",
            [
                'event_id'   => $result['event_id'] ?? 0,
                'type'       => $data['event_type'],
                'risk_score' => $result['risk_score'] ?? 0,
                'risk_level' => $result['risk_level'] ?? 'low',
                'elapsed_ms' => $elapsed,
                'total_runs' => $processed
            ]
        );

    } catch (\Exception $e) {
        $errors++;
        
        $attempt = ($payload['attempt'] ?? 0) + 1;
        $maxRetries = 4;

        if ($attempt < $maxRetries) {
            // Exponential backoff: Base delay 5s * (attempt ^ 2)
            $delay = 5 * ($attempt ** 2);
            \Sentinel\Core\Logger::warn("Event failed. Pushing to delayed queue.", [
                'delay_seconds' => $delay, 
                'attempt' => $attempt, 
                'error' => $e->getMessage()
            ]);
            
            $payload['attempt'] = $attempt;
            $queue->pushDelayed($payload, $delay);
        } else {
            \Sentinel\Core\Logger::error("Event permanently failed. Pushing to PostgreSQL DLQ.", ['error' => $e->getMessage()]);
            $queue->pushToDeadLetter($db, $payload, $e->getMessage());
        }

        // Cool down on extreme persistent systemic errors
        if ($errors > 10) {
            \Sentinel\Core\Logger::warn("Too many systemic errors in loop. Heavy cooldown... (5s)");
            sleep(5);
            $errors = 0;
        }
    }
}
