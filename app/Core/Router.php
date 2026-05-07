<?php

namespace Sentinel\Core;

/**
 * Lightweight router with regex pattern matching and HTTP method support.
 */
class Router
{
    private array $routes = [];
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $pattern, callable $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->addRoute('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }

    public function options(string $pattern, callable $handler): void
    {
        $this->addRoute('OPTIONS', $pattern, $handler);
    }

    private function addRoute(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $method = $this->request->method();
        $uri = $this->request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = '#^' . $route['pattern'] . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match
                
                try {
                    call_user_func_array($route['handler'], $matches);
                } catch (\Exception $e) {
                    $this->handleError($e);
                }
                return;
            }
        }

        // No route matched
        $this->response->status(404);
        
        if (str_starts_with($uri, '/api/')) {
            $this->response->json([
                'error' => 'Not Found',
                'message' => 'The requested endpoint does not exist.',
            ], 404);
        } else {
            http_response_code(404);
            echo '<h1>404 — Not Found</h1><p>The page you requested does not exist.</p>';
        }
    }

    private function handleError(\Exception $e): void
    {
        $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
        
        if (str_starts_with($this->request->uri(), '/api/')) {
            $this->response->json([
                'error' => $e->getMessage(),
            ], $code);
        } else {
            if ($code === 401 || $code === 403) {
                $this->response->redirect('/login');
            } else {
                http_response_code($code);
                echo '<h1>Error ' . $code . '</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        }
    }
}
