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
        $rawPath = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $rawPath; // Don't normalize yet, we need the raw path for base path stripping

        // Trim base path (for apps hosted under a subdirectory)
        if (!empty($this->basePath)) {
            $basePathRaw = rtrim($this->basePath, '/');
            
            // Debug: Log what we're comparing
            // error_log("Router Debug - Raw Path: {$path}, Base Path: {$basePathRaw}");
            
            // Check if path starts with base path
            if ($path === $basePathRaw || $path === $basePathRaw . '/') {
                // If path is exactly the base path, it's the root
                $path = '/';
            } elseif (strpos($path, $basePathRaw) === 0) {
                // Remove base path from the beginning
                $remaining = substr($path, strlen($basePathRaw));
                // If remaining is empty or just '/', make it '/'
                if ($remaining === '' || $remaining === '/') {
                    $path = '/';
                } else {
                    // Normalize the remaining path
                    $path = $this->normalizePath($remaining);
                }
            }
        } else {
            // No base path, just normalize
            $path = $this->normalizePath($path);
        }
        
        $method = strtoupper($method);
        
        // Debug: Log the final path being matched (only if not in test environment)
        if (!defined('TESTING')) {
        error_log("Router Debug - Raw Path: {$rawPath}, Base Path: {$this->basePath}, Final Path: {$path}, Method: {$method}");
        error_log("Router Debug - Available routes for {$method}: " . json_encode(array_column($this->routes[$method] ?? [], 'path')));
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            // Debug: Log each route being checked (only if not in test environment)
            if (!defined('TESTING')) {
            error_log("Router Debug - Checking route: '{$route['path']}' (type: " . gettype($route['path']) . ") against '{$path}' (type: " . gettype($path) . ")");
            }
            if ($route['path'] === $path) {
                [$controller, $action] = explode('@', $route['handler']);
                if (!defined('TESTING')) {
                error_log("Router Debug - MATCH FOUND! Controller: {$controller}, Action: {$action}");
                }
                return [$controller, $action, []];
            }
        }

        if (!defined('TESTING')) {
        error_log("Router Debug - NO MATCH FOUND for path: {$path}");
        }
        return [null, null, []];
    }

    private function normalizePath(string $path): string
    {
        $trimmed = rtrim($path, '/');
        return $trimmed === '' ? '/' : $trimmed;
    }
}

