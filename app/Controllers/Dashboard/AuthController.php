<?php

namespace Sentinel\Controllers\Dashboard;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Models\AdminUser;

class AuthController
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function showLogin(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $response->redirect('/dashboard');
            return;
        }
        $response->view('auth/login', ['config' => $this->config]);
    }

    public function login(Request $request, Response $response): void
    {
        $email = $request->input('email', '');
        $password = $request->input('password', '');

        if (empty($email) || empty($password)) {
            $response->view('auth/login', [
                'error' => 'Email and password are required.',
                'config' => $this->config,
            ]);
            return;
        }

        $adminModel = new AdminUser($this->db);
        $user = $adminModel->findByEmail($email);

        if (!$user || !Auth::verifyPassword($password, $user['password_hash'])) {
            \Sentinel\Core\Logger::warn('Failed login attempt', ['email' => $email, 'ip' => $request->ip()]);
            $response->view('auth/login', [
                'error' => 'Invalid email or password.',
                'config' => $this->config,
            ]);
            return;
        }

        \Sentinel\Core\Logger::info('Successful dashboard login', ['email' => $user['email'], 'user_id' => $user['id'], 'ip' => $request->ip()]);
        Auth::login($user['id'], $user['email'], $user['display_name']);
        $adminModel->updateLastLogin($user['id']);

        $response->redirect('/dashboard');
    }

    public function showSignup(Request $request, Response $response): void
    {
        // Only allow signup if no admin users exist
        $adminModel = new AdminUser($this->db);
        if ($adminModel->count() > 0 && !Auth::check()) {
            $response->redirect('/login');
            return;
        }

        $response->view('auth/signup', ['config' => $this->config]);
    }

    public function signup(Request $request, Response $response): void
    {
        $adminModel = new AdminUser($this->db);

        // Only allow first signup without auth
        if ($adminModel->count() > 0 && !Auth::check()) {
            $response->redirect('/login');
            return;
        }

        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');
        $name = trim($request->input('display_name', ''));

        $errors = [];
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (empty($name)) {
            $errors[] = 'Display name is required.';
        }
        if ($adminModel->findByEmail($email)) {
            $errors[] = 'An account with this email already exists.';
        }

        if (!empty($errors)) {
            $response->view('auth/signup', [
                'errors' => $errors,
                'config' => $this->config,
            ]);
            return;
        }

        $userId = $adminModel->create($email, $password, $name);
        Auth::login($userId, $email, $name);

        $response->redirect('/dashboard');
    }

    public function logout(Request $request, Response $response): void
    {
        Auth::logout();
        $response->redirect('/login');
    }

    public function showForgotPassword(Request $request, Response $response): void
    {
        $response->view('auth/forgot-password', ['config' => $this->config]);
    }

    public function forgotPassword(Request $request, Response $response): void
    {
        $email = trim($request->input('email', ''));
        $masterKey = trim($request->input('master_key', ''));
        $newPassword = $request->input('new_password', '');
        $confirmPassword = $request->input('confirm_password', '');

        $errors = [];

        // Validate master key (APP_SECRET from config/.env)
        $expectedKey = $this->config['app']['secret'] ?? '';
        if (empty($masterKey) || $masterKey !== $expectedKey) {
            $errors[] = 'Invalid master key. Check your APP_SECRET in .env or docker-compose.yml.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required.';
        }

        if (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            $response->view('auth/forgot-password', [
                'errors' => $errors,
                'config' => $this->config,
            ]);
            return;
        }

        $adminModel = new AdminUser($this->db);
        $updated = $adminModel->updatePassword($email, $newPassword);

        if (!$updated) {
            $response->view('auth/forgot-password', [
                'errors' => ['No active account found with that email address.'],
                'config' => $this->config,
            ]);
            return;
        }

        $response->view('auth/login', [
            'success' => 'Password reset successfully. You can now sign in.',
            'config'  => $this->config,
        ]);
    }
}
