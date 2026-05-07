<?php

namespace Sentinel\Core\Middleware;

use Sentinel\Core\Request;
use Sentinel\Core\Response;

/**
 * Adds CORS headers for API endpoints.
 */
class CorsMiddleware
{
    public function handle(Request $request, Response $response = null): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
        header('Access-Control-Max-Age: 86400');
    }
}
