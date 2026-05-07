<?php

namespace Sentinel\Core;

/**
 * HTTP Response helper.
 */
class Response
{
    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        if ($rid = \Sentinel\Core\Logger::getRequestId()) header("X-Request-Id: {$rid}");
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function status(int $code): self
    {
        http_response_code($code);
        return $this;
    }

    public function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    public function cors(): self
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
        header('Access-Control-Max-Age: 86400');
        return $this;
    }

    public function html(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        if ($rid = \Sentinel\Core\Logger::getRequestId()) header("X-Request-Id: {$rid}");
        echo $content;
        exit;
    }

    /**
     * Render a PHP view template.
     */
    public function view(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        if ($rid = \Sentinel\Core\Logger::getRequestId()) header("X-Request-Id: {$rid}");

        if (!function_exists('e')) {
            function e(?string $str): string {
                return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }
        
        $data['csrf_token'] = \Sentinel\Core\Auth::csrfToken();
        extract($data);
        $viewPath = __DIR__ . '/../Views/' . $template . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo "View not found: " . e($template);
            exit;
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // If the template doesn't include its own layout, wrap it
        if (!str_contains($content, '<!DOCTYPE html>')) {
            $pageContent = $content;
            require __DIR__ . '/../Views/layouts/main.php';
        } else {
            echo $content;
        }

        exit;
    }
}
