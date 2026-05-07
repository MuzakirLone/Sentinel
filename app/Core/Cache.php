<?php

namespace Sentinel\Core;

/**
 * Lightweight Caching Wrapper (No-op implementation)
 * Redis dependency removed - caching disabled for simplicity
 */
class Cache
{
    public static function init(array $config = []): void
    {
        // No-op: Caching disabled
    }

    public static function get(string $key)
    {
        return null;
    }

    public static function set(string $key, $value, int $ttlSeconds = 300): void
    {
        // No-op: Caching disabled
    }

    public static function delete(string $key): void
    {
        // No-op: Caching disabled
    }
}
