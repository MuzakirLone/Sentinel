<?php

namespace Sentinel\Core;

/**
 * Redis-based event queue for async event processing.
 *
 * Flow: SDK → API → Redis Queue → Worker → DB + Risk Engine
 *
 * Uses Redis RPUSH/BLPOP for reliable queue semantics.
 * Supports batch dequeue for throughput optimization.
 */
class QueueManager
{
    private ?\Redis $redis = null;
    private string $queueKey = 'sentinel:events:pending';
    private string $processingKey = 'sentinel:events:processing';
    private string $delayedKey = 'sentinel:events:delayed';
    private string $statsKey = 'sentinel:queue:stats';

    public function __construct(array $config = [])
    {
        $host = $config['host'] ?? (getenv('REDIS_HOST') ?: '127.0.0.1');
        $port = (int) ($config['port'] ?? (getenv('REDIS_PORT') ?: 6379));

        try {
            $this->redis = new \Redis();
            $this->redis->connect($host, $port, 5.0);
            $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_JSON);
        } catch (\Exception $e) {
            error_log("Redis connection failed: {$e->getMessage()}");
            $this->redis = null;
        }
    }

    /**
     * Check if queue system is available.
     */
    public function isAvailable(): bool
    {
        if ($this->redis === null) return false;
        try {
            return $this->redis->ping() === true || $this->redis->ping() === '+PONG';
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Push an event to the queue for async processing.
     *
     * @return int Queue position
     */
    public function push(array $eventData): int
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('Queue system unavailable');
        }

        $payload = [
            'data'       => $eventData,
            'queued_at'  => microtime(true),
            'attempt'    => 0,
        ];

        $position = $this->redis->rPush($this->queueKey, $payload);
        $this->redis->hIncrBy($this->statsKey, 'total_enqueued', 1);

        return (int) $position;
    }

    /**
     * Pop the next event from the queue using Reliable Queue pattern (BRPOPLPUSH).
     *
     * @param int $timeout Seconds to wait for an event
     * @return array|null The event payload or null on timeout
     */
    public function pop(int $timeout = 5): ?array
    {
        if (!$this->isAvailable()) return null;

        try {
            // bRPopLPush safely pops from tail of pending, and pushes to head of processing (blocking)
            $result = $this->redis->bRPopLPush($this->queueKey, $this->processingKey, $timeout);
            
            if ($result) {
                $this->redis->hIncrBy($this->statsKey, 'total_dequeued', 1);
                return json_decode($result, true);
            }
        } catch (\Exception $e) {
            error_log("Queue pop error: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Acknowledge that a job is complete, safely removing it from the processing queue.
     */
    public function acknowledge(array $payload): void
    {
        if (!$this->isAvailable()) return;
        $this->redis->lRem($this->processingKey, json_encode($payload), 1);
    }

    /**
     * Pop multiple events at once for batch processing.
     *
     * @param int $count Max events to dequeue
     * @return array Array of event payloads
     */
    public function popBatch(int $count = 10): array
    {
        if (!$this->isAvailable()) return [];

        $events = [];
        $pipe = $this->redis->multi(\Redis::PIPELINE);

        for ($i = 0; $i < $count; $i++) {
            $pipe->lPop($this->queueKey);
        }

        $results = $pipe->exec();

        foreach ($results as $result) {
            if ($result !== false && $result !== null) {
                $events[] = $result;
            }
        }

        if (count($events) > 0) {
            $this->redis->hIncrBy($this->statsKey, 'total_dequeued', count($events));
        }

        return $events;
    }

    /**
     * Get queue statistics.
     */
    public function getStats(): array
    {
        if (!$this->isAvailable()) {
            return ['available' => false];
        }

        return [
            'available'       => true,
            'queue_length'    => (int) $this->redis->lLen($this->queueKey),
            'total_enqueued'  => (int) ($this->redis->hGet($this->statsKey, 'total_enqueued') ?: 0),
            'total_dequeued'  => (int) ($this->redis->hGet($this->statsKey, 'total_dequeued') ?: 0),
            'total_failed'    => (int) ($this->redis->hGet($this->statsKey, 'total_failed') ?: 0),
        ];
    }

    /**
     * Get current queue length.
     */
    public function getQueueLength(): int
    {
        if (!$this->isAvailable()) return 0;
        return (int) $this->redis->lLen($this->queueKey);
    }

    /**
     * Record a processing failure.
     */
    public function recordFailure(): void
    {
        if ($this->isAvailable()) {
            $this->redis->hIncrBy($this->statsKey, 'total_failed', 1);
        }
    }

    /**
     * Push a permanently failed job to the Dead Letter Queue (PostgreSQL).
     */
    public function pushToDeadLetter(\Sentinel\Core\Database $db, array $payload, string $exceptionMessage): void
    {
        $this->recordFailure();
        try {
            $db->execute(
                'INSERT INTO failed_jobs (payload, exception_message, failed_at) VALUES (:payload, :message, NOW())',
                [
                    'payload' => json_encode($payload),
                    'message' => substr($exceptionMessage, 0, 5000)
                ]
            );
            $this->acknowledge($payload);
        } catch (\Exception $e) {
            error_log('FATAL: Could not write to Dead Letter Queue. ' . $e->getMessage());
        }
    }

    /**
     * Push an event to the delayed queue (Sorted Set) for exponential backoff retries.
     */
    public function pushDelayed(array $payload, int $delaySeconds): void
    {
        if (!$this->isAvailable()) return;
        
        $executeAt = time() + $delaySeconds;
        $this->redis->zAdd($this->delayedKey, $executeAt, json_encode($payload));
        // Acknowledge the current failed attempt so it no longer lives in the processing queue
        $this->acknowledge($payload);
    }

    /**
     * Move jobs from the delayed queue whose execution time has arrived, back to pending.
     */
    public function promoteDelayed(): int
    {
        if (!$this->isAvailable()) return 0;

        $now = time();
        $promoted = 0;

        // Get all jobs ready to process using a Lua transaction or basic loop
        $jobs = $this->redis->zRangeByScore($this->delayedKey, '-inf', (string) $now);
        
        if (!empty($jobs)) {
            // Remove from ZSET and PUSH to MAIN natively without losing items
            foreach ($jobs as $jobJson) {
                if ($this->redis->zRem($this->delayedKey, $jobJson)) {
                    $this->redis->lPush($this->queueKey, $jobJson);
                    $promoted++;
                }
            }
        }

        return $promoted;
    }

    /**
     * Sweep stalled jobs directly from the processing queue back to pending (Orphan Recovery).
     * WARNING: Only call this cautiously if there's only one master worker or with a lock.
     */
    public function recoverStalledJobs(): int
    {
        if (!$this->isAvailable()) return 0;
        
        $recovered = 0;
        // In this basic version, we migrate all elements from processing back to pending
        // A more advanced system uses ZSETs for processing queues too to track start times.
        while ($job = $this->redis->rPopLPush($this->processingKey, $this->queueKey)) {
            $recovered++;
        }
        
        return $recovered;
    }
}
