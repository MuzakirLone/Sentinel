<?php

namespace Sentinel\Core;

/**
 * Sentinel structured JSON Logger.
 * Ensures all emitted application logs are natively parseable by SIEMs.
 */
class Logger
{
    private static ?string $requestId = null;
    private static string $logFile = __DIR__ . '/../../logs/sentinel.log';
    private static bool $toStdout = false;

    public static function setRequestId(string $id): void
    {
        self::$requestId = $id;
    }

    public static function getRequestId(): ?string
    {
        return self::$requestId;
    }

    public static function setLogFile(string $path): void
    {
        self::$logFile = $path;
    }

    public static function enableStdout(bool $enabled = true): void
    {
        self::$toStdout = $enabled;
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warn(string $message, array $context = []): void
    {
        self::log('WARN', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }

    private static function log(string $level, string $message, array $context): void
    {
        $payload = [
            'timestamp'  => gmdate('Y-m-d\TH:i:s.v\Z'),
            'level'      => $level,
            'message'    => $message,
            'request_id' => self::$requestId,
        ];

        if (!empty($context)) {
            $payload['context'] = $context;
        }

        $jsonLog = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        if (self::$toStdout || PHP_SAPI === 'cli') {
            echo $jsonLog;
        } else {
            $logDir = dirname(self::$logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }
            @file_put_contents(self::$logFile, $jsonLog, FILE_APPEND | LOCK_EX);
        }
    }
}
