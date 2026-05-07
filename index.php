<?php

/**
 * Sentinel — Application Entry Point
 * 
 * All requests are routed through this file via .htaccess rewrite rules.
 */

declare(strict_types=1);

// Error reporting based on environment
$env = getenv('APP_ENV') ?: 'production';
if ($env === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'Sentinel\\';
    $baseDir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
$config = require __DIR__ . '/config/app.php';

// Initialize Logger and Request Tracing
$requestId = bin2hex(random_bytes(16));
\Sentinel\Core\Logger::setRequestId($requestId);

// Validate Environment Settings securely
\Sentinel\Core\EnvValidator::validate($config);

// Initialize Caching layer (disabled - no Redis)
\Sentinel\Core\Cache::init();

\Sentinel\Core\Logger::info("Bootstrapped application", ['method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI', 'uri' => $_SERVER['REQUEST_URI'] ?? '']);

// Initialize core components
$db = \Sentinel\Core\Database::getInstance($config['database']);
$request = new \Sentinel\Core\Request();
$response = new \Sentinel\Core\Response();
$router = new \Sentinel\Core\Router($request, $response);

// Apply central security headers to all responses
(new \Sentinel\Core\Middleware\SecurityHeadersMiddleware())->handle($response);

// ─── API Routes ────────────────────────────────────────────────────

// CORS preflight
$router->options('/api/v1/.*', function() use ($response) {
    $response->cors();
    $response->json(['status' => 'ok']);
});

// Event ingestion
$router->post('/api/v1/events', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\RateLimitMiddleware(600))->handle($request); // Rate limit API ingestion
    // Try HMAC signing first, fall back to plain API key
    $hmac = new \Sentinel\Core\Middleware\HmacMiddleware($db);
    if (!$hmac->handle($request)) {
        (new \Sentinel\Core\Middleware\ApiKeyMiddleware($db))->handle($request);
    }
    (new \Sentinel\Core\Middleware\CorsMiddleware())->handle($request, $response);
    (new \Sentinel\Controllers\Api\EventController($db, $config))->store($request, $response);
});

$router->post('/api/v1/events/batch', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\ApiKeyMiddleware($db))->handle($request);
    (new \Sentinel\Core\Middleware\CorsMiddleware())->handle($request, $response);
    (new \Sentinel\Controllers\Api\EventController($db, $config))->storeBatch($request, $response);
});

// Blacklist check
$router->post('/api/v1/blacklist/check', function() use ($request, $response, $db) {
    (new \Sentinel\Core\Middleware\ApiKeyMiddleware($db))->handle($request);
    (new \Sentinel\Core\Middleware\CorsMiddleware())->handle($request, $response);
    (new \Sentinel\Controllers\Api\BlacklistController($db))->check($request, $response);
});

// ─── Auth Routes ───────────────────────────────────────────────────

$router->get('/login', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Controllers\Dashboard\AuthController($db, $config))->showLogin($request, $response);
});

$router->post('/login', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\RateLimitMiddleware(10))->handle($request);
    (new \Sentinel\Core\Middleware\CsrfMiddleware())->handle($request);
    (new \Sentinel\Controllers\Dashboard\AuthController($db, $config))->login($request, $response);
});

$router->get('/signup', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Controllers\Dashboard\AuthController($db, $config))->showSignup($request, $response);
});

$router->post('/signup', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\RateLimitMiddleware(10))->handle($request);
    (new \Sentinel\Core\Middleware\CsrfMiddleware())->handle($request);
    (new \Sentinel\Controllers\Dashboard\AuthController($db, $config))->signup($request, $response);
});

$router->get('/logout', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Controllers\Dashboard\AuthController($db, $config))->logout($request, $response);
});

$router->get('/forgot-password', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Controllers\Dashboard\AuthController($db, $config))->showForgotPassword($request, $response);
});

$router->post('/forgot-password', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\RateLimitMiddleware(10))->handle($request);
    (new \Sentinel\Core\Middleware\CsrfMiddleware())->handle($request);
    (new \Sentinel\Controllers\Dashboard\AuthController($db, $config))->forgotPassword($request, $response);
});

// ─── Dashboard Routes ──────────────────────────────────────────────

$router->get('/', function() use ($request, $response) {
    (new \Sentinel\Controllers\LandingController())->index($request, $response);
});

$router->get('/dashboard', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\DashboardController($db, $config))->index($request, $response);
});

$router->get('/dashboard/stats', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\DashboardController($db, $config))->stats($request, $response);
});

// Users
$router->get('/users', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\UsersController($db, $config))->index($request, $response);
});

$router->get('/users/data', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\UsersController($db, $config))->data($request, $response);
});

$router->get('/users/(\d+)', function(int $id) use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\UsersController($db, $config))->show($request, $response, $id);
});

$router->get('/users/(\d+)/timeline', function(int $id) use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\UsersController($db, $config))->timeline($request, $response, $id);
});

// Events
$router->get('/events', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\EventsController($db, $config))->index($request, $response);
});

$router->get('/events/data', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\EventsController($db, $config))->data($request, $response);
});

// Review queue
$router->get('/review', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\ReviewController($db, $config))->index($request, $response);
});

$router->get('/review/data', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\ReviewController($db, $config))->data($request, $response);
});

$router->post('/review/(\d+)/action', function(int $id) use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Core\Middleware\CsrfMiddleware())->handle($request);
    (new \Sentinel\Controllers\Dashboard\ReviewController($db, $config))->action($request, $response, $id);
});

// Rules
$router->get('/rules', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\RulesController($db, $config))->index($request, $response);
});

$router->post('/rules/(\d+)/toggle', function(int $id) use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Core\Middleware\CsrfMiddleware())->handle($request);
    (new \Sentinel\Controllers\Dashboard\RulesController($db, $config))->toggle($request, $response, $id);
});

$router->post('/rules/(\d+)/weight', function(int $id) use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Core\Middleware\CsrfMiddleware())->handle($request);
    (new \Sentinel\Controllers\Dashboard\RulesController($db, $config))->updateWeight($request, $response, $id);
});

// Settings
$router->get('/settings', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\SettingsController($db, $config))->index($request, $response);
});

$router->post('/settings/api-keys', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Core\Middleware\CsrfMiddleware())->handle($request);
    (new \Sentinel\Controllers\Dashboard\SettingsController($db, $config))->createApiKey($request, $response);
});

$router->post('/settings/api-keys/(\d+)/revoke', function(int $id) use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Core\Middleware\CsrfMiddleware())->handle($request);
    (new \Sentinel\Controllers\Dashboard\SettingsController($db, $config))->revokeApiKey($request, $response, $id);
});

// Audit trail
$router->get('/audit', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\AuditController($db, $config))->index($request, $response);
});

$router->get('/audit/data', function() use ($request, $response, $db, $config) {
    (new \Sentinel\Core\Middleware\AuthMiddleware($db))->handle($request);
    (new \Sentinel\Controllers\Dashboard\AuditController($db, $config))->data($request, $response);
});

// ─── Cron ──────────────────────────────────────────────────────────

$router->get('/cron', function() use ($db, $config) {
    // Allow CLI or localhost only
    if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])) {
        http_response_code(403);
        exit('Forbidden');
    }
    (new \Sentinel\Cron\CronRunner($db, $config))->run();
    echo "Cron completed.\n";
});

// ─── Static Assets ─────────────────────────────────────────────────

$router->get('/public/css/(.*)', function(string $file) {
    $path = __DIR__ . '/public/css/' . basename($file);
    if (file_exists($path)) {
        header('Content-Type: text/css');
        readfile($path);
    } else {
        http_response_code(404);
    }
    exit;
});

$router->get('/public/js/(.*)', function(string $file) {
    $path = __DIR__ . '/public/js/' . basename($file);
    if (file_exists($path)) {
        header('Content-Type: application/javascript');
        readfile($path);
    } else {
        http_response_code(404);
    }
    exit;
});

// ─── Dispatch ──────────────────────────────────────────────────────

$router->dispatch();
