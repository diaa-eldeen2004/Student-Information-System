<?php
namespace core;

class Router
{
    private array $routes = [];
    private string $basePath = '';

    public function __construct(array $routes = [], string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');

        foreach ($routes as $route) {
            [$method, $path, $handler] = $route;
            $this->add($method, $path, $handler);
        }
    }

    public function add(string $method, string $path, string $handler): void
    {
        $normalizedPath = $this->normalizePath($path);
        $this->routes[strtoupper($method)][] = [
            'path' => $normalizedPath,
            'handler' => $handler,
        ];
    }

    public function resolve(string $uri, string $method): array
    {
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?: '/');

        // Trim base path (for apps hosted under a subdirectory)
        if (!empty($this->basePath) && strpos($path, $this->basePath) === 0) {
            $path = $this->normalizePath(substr($path, strlen($this->basePath)));
        }
        $method = strtoupper($method);

        foreach ($this->routes[$method] ?? [] as $route) {
            if ($route['path'] === $path) {
                [$controller, $action] = explode('@', $route['handler']);
                return [$controller, $action, []];
            }
        }

        return [null, null, []];
    }

    private function normalizePath(string $path): string
    {
        $trimmed = rtrim($path, '/');
        return $trimmed === '' ? '/' : $trimmed;
    }
}

