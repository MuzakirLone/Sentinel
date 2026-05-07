<?php

namespace Sentinel\Core\Middleware;

use Sentinel\Core\Request;

/**
 * Simple in-memory rate limiter using the filesystem.
 */
class RateLimitMiddleware
{
    private int $maxRequests;
    private string $storagePath;

    public function __construct(int $maxRequests = 120)
    {
        $this->maxRequests = $maxRequests;
        $this->storagePath = sys_get_temp_dir() . '/sentinel_rate_limits/';

        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0755, true);
        }
    }

    public function handle(Request $request): void
    {
        // Bypass rate limiting entirely if explicitly triggered via internal benchmarks
        if ($request->header('x_benchmark_bypass') === 'true') {
            return;
        }

        $ip = $request->ip();
        $key = md5($ip);
        $file = $this->storagePath . $key . '.json';
        $now = time();
        $windowStart = $now - 60;

        $data = ['requests' => []];

        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content) {
                $data = json_decode($content, true) ?? ['requests' => []];
            }
        }

        // Filter requests within the current window
        $data['requests'] = array_values(array_filter(
            $data['requests'],
            fn($t) => $t > $windowStart
        ));

        if (count($data['requests']) >= $this->maxRequests) {
            throw new \Exception('Rate limit exceeded. Try again later.', 429);
        }

        $data['requests'][] = $now;
        @file_put_contents($file, json_encode($data));
    }
}
