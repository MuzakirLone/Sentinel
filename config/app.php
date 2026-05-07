<?php

/**
 * Sentinel — Application Configuration
 * 
 * Loads from environment variables with sensible defaults.
 */

return [
    'app' => [
        'name'    => getenv('APP_NAME') ?: 'Sentinel',
        'env'     => getenv('APP_ENV') ?: 'production',
        'url'     => getenv('APP_URL') ?: 'http://localhost:8585',
        'secret'  => getenv('APP_SECRET') ?: 'change_this_to_a_random_64_char_string',
        'version' => '1.0.0',
    ],

    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '5432',
        'name' => getenv('DB_NAME') ?: 'sentinel',
        'user' => getenv('DB_USER') ?: 'sentinel',
        'pass' => getenv('DB_PASS') ?: '',
    ],

    'queue' => [
        'enabled' => false, // Redis queue disabled
    ],

    'risk' => [
        'threshold_flag'    => (int)(getenv('RISK_THRESHOLD_FLAG') ?: 60),
        'threshold_suspend' => (int)(getenv('RISK_THRESHOLD_SUSPEND') ?: 85),
    ],

    'session' => [
        'lifetime' => (int)(getenv('SESSION_LIFETIME') ?: 3600),
    ],

    'rate_limit' => [
        'per_minute' => (int)(getenv('RATE_LIMIT_PER_MINUTE') ?: 120),
    ],
];
