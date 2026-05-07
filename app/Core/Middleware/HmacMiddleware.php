<?php

namespace Sentinel\Core\Middleware;

use Sentinel\Core\Database;
use Sentinel\Core\Request;

/**
 * HMAC-SHA256 Request Signing Middleware
 *
 * Provides cryptographic request verification beyond simple API key authentication.
 * Prevents replay attacks, man-in-the-middle tampering, and stolen key abuse.
 *
 * Signature format:
 *   X-Signature: HMAC-SHA256(api_secret, timestamp + "\n" + method + "\n" + path + "\n" + body_sha256)
 *
 * Required headers for HMAC mode:
 *   X-API-Key:    The API key (sk_...)
 *   X-Timestamp:  Unix timestamp of request
 *   X-Nonce:      Unique cryptographic nonce string
 *   X-Signature:  HMAC-SHA256 signature
 *
 * Falls back to plain API key auth if X-Signature is not present (backward compatibility).
 */
class HmacMiddleware
{
    private Database $db;
    private int $maxDriftSeconds;

    public function __construct(Database $db, int $maxDriftSeconds = 300)
    {
        $this->db = $db;
        $this->maxDriftSeconds = $maxDriftSeconds;
    }

    /**
     * Verify HMAC signature if present.
     * Returns true if HMAC was verified, false if not attempted (fallback to API key).
     *
     * @throws \Exception on signature verification failure
     */
    public function handle(Request $request): bool
    {
        $signature = $request->header('x_signature');
        $timestamp = $request->header('x_timestamp');
        $nonce     = $request->header('x_nonce');

        // No signature header = fall back to plain API key auth
        if (!$signature || !$timestamp || !$nonce) {
            return false;
        }

        // ─── 1. Replay Attack Prevention ───────────────────
        $requestTime = (int) $timestamp;
        $drift = abs(time() - $requestTime);
        if ($drift > $this->maxDriftSeconds) {
            throw new \Exception(
                "Request timestamp drift: {$drift}s exceeds maximum allowed {$this->maxDriftSeconds}s (replay attack prevention).",
                401
            );
        }
        
        try {
            $this->db->execute('INSERT INTO api_nonces (nonce) VALUES (:nonce)', ['nonce' => $nonce]);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'duplicate key') || str_contains($e->getMessage(), '1062')) {
                throw new \Exception('Nonce already used. Replay attack prevented.', 401);
            }
            throw $e;
        }

        // ─── 2. Resolve API Secret ─────────────────────────
        $apiKey = $request->header('x_api_key') ?? $request->bearerToken();
        if (!$apiKey) {
            throw new \Exception('API key required for HMAC verification.', 401);
        }

        $keyHash = hash('sha256', $apiKey);
        $cacheKey = 'api_key:' . $keyHash;
        
        $keyRecord = \Sentinel\Core\Cache::get($cacheKey);

        if (!$keyRecord) {
            $keyRecord = $this->db->queryOne(
                'SELECT id, api_secret, is_active FROM api_keys WHERE key_hash = :hash',
                ['hash' => $keyHash]
            );

            if ($keyRecord) {
                // Cache for 5 minutes. Revocation dynamically clears this.
                \Sentinel\Core\Cache::set($cacheKey, $keyRecord, 300);
            }
        }

        if (!$keyRecord || !$keyRecord['is_active']) {
            throw new \Exception('Invalid or revoked API key.', 403);
        }

        if (empty($keyRecord['api_secret'])) {
            throw new \Exception('HMAC signing is not configured for this API key. Generate a new key with HMAC enabled.', 400);
        }

        // ─── 3. Reconstruct & Verify Signature ─────────────
        $method = strtoupper($request->method());
        $path = $request->uri();
        $body = file_get_contents('php://input') ?: '';
        $bodyHash = hash('sha256', $body);

        $payload = implode("\n", [
            $timestamp,
            $nonce,
            $method,
            $path,
            $bodyHash,
        ]);

        $expectedSignature = hash_hmac('sha256', $payload, $keyRecord['api_secret']);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception(
                'HMAC signature verification failed. Request may have been tampered with.',
                401
            );
        }

        // ─── 4. Update last used ───────────────────────────
        $this->db->execute(
            'UPDATE api_keys SET last_used_at = NOW() WHERE id = :id',
            ['id' => $keyRecord['id']]
        );

        return true;
    }
}
