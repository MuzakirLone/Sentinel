<?php

namespace Sentinel\Core\Middleware;

use Sentinel\Core\Auth;
use Sentinel\Core\Request;

/**
 * Ensures CSRF tokens are valid for state-changing dashboard requests.
 */
class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        $method = $request->method();
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $request->input('csrf_token') ?? $request->header('x_csrf_token');
            if (!$token || !Auth::verifyCsrfToken($token)) {
                http_response_code(403);
                header('Content-Type: text/html; charset=utf-8');
                echo "403 Forbidden: Invalid or missing CSRF token.";
                exit;
            }
        }
    }
}
