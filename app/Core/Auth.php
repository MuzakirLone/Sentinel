<?php

namespace Sentinel\Core;

/**
 * Authentication helper for dashboard sessions and API keys.
 */
class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if (strpos($host, ':') !== false) {
                $host = explode(':', $host)[0];
            }
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => $host,
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }

    }

    public static function csrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        self::startSession();
        if (empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    /**
     * Get the currently logged-in admin user ID.
     */
    public static function userId(): ?int
    {
        self::startSession();
        return $_SESSION['admin_user_id'] ?? null;
    }

    /**
     * Check if a user is authenticated.
     */
    public static function check(): bool
    {
        return self::userId() !== null;
    }

    /**
     * Log in as an admin user.
     */
    public static function login(int $userId, string $email, string $displayName): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = $userId;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_name'] = $displayName;
        $_SESSION['login_time'] = time();
    }

    /**
     * Log out.
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Get session data.
     */
    public static function user(): array
    {
        self::startSession();
        return [
            'id'    => $_SESSION['admin_user_id'] ?? null,
            'email' => $_SESSION['admin_email'] ?? null,
            'name'  => $_SESSION['admin_name'] ?? null,
        ];
    }

    /**
     * Hash a password.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify a password against a hash.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate a secure API key.
     */
    public static function generateApiKey(): string
    {
        return 'sk_' . bin2hex(random_bytes(32));
    }

    /**
     * Hash an API key for storage.
     */
    public static function hashApiKey(string $key): string
    {
        return hash('sha256', $key);
    }
}
