<?php

namespace Sentinel\Core\Middleware;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;

/**
 * Validates API key from X-API-Key header or Bearer token.
 */
class ApiKeyMiddleware
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function handle(Request $request): void
    {
        $apiKey = $request->header('x_api_key') ?? $request->bearerToken();

        if (!$apiKey) {
            throw new \Exception('API key required. Provide via X-API-Key header or Bearer token.', 401);
        }

        $keyHash = Auth::hashApiKey($apiKey);
        $cacheKey = 'api_key:' . $keyHash;

        $key = \Sentinel\Core\Cache::get($cacheKey);

        if (!$key) {
            $key = $this->db->queryOne(
                'SELECT id, is_active FROM api_keys WHERE key_hash = :hash',
                ['hash' => $keyHash]
            );

            if ($key) {
                \Sentinel\Core\Cache::set($cacheKey, $key, 300);
            }
        }

        if (!$key || !$key['is_active']) {
            throw new \Exception('Invalid or revoked API key.', 403);
        }

        // Update last used timestamp
        $this->db->execute(
            'UPDATE api_keys SET last_used_at = NOW() WHERE id = :id',
            ['id' => $key['id']]
        );
    }
}
