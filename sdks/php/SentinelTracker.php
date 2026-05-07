<?php

/**
 * Sentinel PHP Tracker SDK
 *
 * Lightweight SDK for sending security events to your Sentinel instance.
 *
 * Usage:
 *   $tracker = new SentinelTracker('http://localhost:8585', 'sk_your_api_key');
 *   $tracker->track('login_success', [
 *       'user_id'    => 'usr_12345',
 *       'email'      => 'user@example.com',
 *       'ip'         => $_SERVER['REMOTE_ADDR'],
 *       'user_agent' => $_SERVER['HTTP_USER_AGENT'],
 *   ]);
 */
class SentinelTracker
{
    private string $baseUrl;
    private string $apiKey;
    private ?string $apiSecret;
    private int $timeout;
    private array $queue = [];
    private int $batchSize;

    public function __construct(string $baseUrl, string $apiKey, ?string $apiSecret = null, int $timeout = 5, int $batchSize = 50)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->timeout = $timeout;
        $this->batchSize = $batchSize;
    }

    /**
     * Track a single event.
     */
    public function track(string $eventType, array $data = []): ?array
    {
        $payload = array_merge($data, ['event_type' => $eventType]);

        // Auto-detect IP and user agent if not provided
        if (empty($payload['ip']) && isset($_SERVER['REMOTE_ADDR'])) {
            $payload['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        }
        if (empty($payload['user_agent']) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $payload['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        if (empty($payload['url']) && isset($_SERVER['REQUEST_URI'])) {
            $payload['url'] = $_SERVER['REQUEST_URI'];
        }

        return $this->post('/api/v1/events', $payload);
    }

    /**
     * Add event to batch queue. Call flush() to send.
     */
    public function queue(string $eventType, array $data = []): void
    {
        $this->queue[] = array_merge($data, ['event_type' => $eventType]);

        if (count($this->queue) >= $this->batchSize) {
            $this->flush();
        }
    }

    /**
     * Send all queued events in a batch.
     */
    public function flush(): ?array
    {
        if (empty($this->queue)) {
            return null;
        }

        $payload = ['events' => $this->queue];
        $this->queue = [];

        return $this->post('/api/v1/events/batch', $payload);
    }

    /**
     * Check if a user/IP is blacklisted.
     */
    public function checkBlacklist(array $params): ?array
    {
        return $this->post('/api/v1/blacklist/check', $params);
    }

    /**
     * Track a login event.
     */
    public function trackLogin(string $userId, bool $success, array $extra = []): ?array
    {
        return $this->track($success ? 'login_success' : 'login_failed', array_merge($extra, [
            'user_id' => $userId,
        ]));
    }

    /**
     * Track a signup event.
     */
    public function trackSignup(string $userId, string $email, array $extra = []): ?array
    {
        return $this->track('signup', array_merge($extra, [
            'user_id' => $userId,
            'email'   => $email,
        ]));
    }

    /**
     * Track a field change for audit trail.
     */
    public function trackFieldChange(string $userId, string $entityType, int $entityId, string $field, $oldValue, $newValue, array $extra = []): ?array
    {
        return $this->track('field_change', array_merge($extra, [
            'user_id'       => $userId,
            'field_changes' => [[
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'field'       => $field,
                'old_value'   => (string) $oldValue,
                'new_value'   => (string) $newValue,
            ]],
        ]));
    }

    private function post(string $endpoint, array $data): ?array
    {
        $url = $this->baseUrl . $endpoint;
        $json = json_encode($data);
        $timestamp = (string) time();

        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey,
        ];

        // HMAC-SHA256 request signing
        if ($this->apiSecret) {
            $bodyHash = hash('sha256', $json);
            $payload = implode("\n", [$timestamp, 'POST', $endpoint, $bodyHash]);
            $signature = hash_hmac('sha256', $payload, $this->apiSecret);

            $headers[] = 'X-Timestamp: ' . $timestamp;
            $headers[] = 'X-Signature: ' . $signature;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Sentinel SDK Error: {$error}");
            return null;
        }

        if ($httpCode >= 400) {
            error_log("Sentinel SDK HTTP {$httpCode}: {$response}");
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Flush remaining events on destruction.
     */
    public function __destruct()
    {
        $this->flush();
    }
}
