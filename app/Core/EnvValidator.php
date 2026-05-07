<?php

namespace Sentinel\Core;

/**
 * Validates critical environment configurations before the application boots.
 * Protects against DevOps oversights in production deployments.
 */
class EnvValidator
{
    private static array $requiredKeys = [
        'DB_HOST',
        'DB_NAME',
        'DB_USER',
        'DB_PASS',
        'APP_SECRET',
    ];

    public static function validate(array $config = []): void
    {
        $missing = [];

        // Check explicit config array (like config/app.php) or Env
        // For Sentinel, most vital config falls back to getenv() inside config/app.php
        foreach (self::$requiredKeys as $key) {
            $val = getenv($key);
            if ($val === false || trim($val) === '') {
                // If it's APP_SECRET, let's also check the nested config array just in case
                if ($key === 'APP_SECRET' && isset($config['app']['secret']) && !empty($config['app']['secret'])) {
                    if ($config['app']['secret'] === 'change_this_to_a_random_64_char_string' && (getenv('APP_ENV') === 'production')) {
                        $missing[] = "APP_SECRET (must not be default in production)";
                    }
                    continue;
                }
                
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            $errorMsg = "FATAL BOOT ERROR: Missing critical environment variables: " . implode(', ', $missing);
            
            // If Logger is initialized, log it strictly. Otherwise error_log.
            if (class_exists(Logger::class)) {
                Logger::critical($errorMsg);
            } else {
                error_log($errorMsg);
            }

            // Immediately halt processing.
            if (PHP_SAPI !== 'cli') {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(["error" => "System configuration error. Check logs."]);
            } else {
                echo "[FATAL] $errorMsg\n";
            }
            exit(1);
        }
    }
}
