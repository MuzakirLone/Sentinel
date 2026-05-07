<?php

namespace Sentinel\Core\Middleware;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;

/**
 * Ensures the user is logged in via session for dashboard routes.
 */
class AuthMiddleware
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            if (str_starts_with($request->uri(), '/api/')) {
                throw new \Exception('Unauthorized', 401);
            }
            header('Location: /login');
            exit;
        }
    }
}
