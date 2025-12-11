<?php
namespace core;

use Throwable;
use core\Router;

class App
{
    private array $config;
    private array $routes;

    public function __construct()
    {
        $this->config = require dirname(__DIR__) . '/config/config.php';
        $this->routes = require dirname(__DIR__) . '/config/routes.php';
    }

    public function run(): void
    {
        $baseUrl = $this->config['base_url'] ?? '';
        $basePath = parse_url($baseUrl, PHP_URL_PATH) ?? '';
        $basePath = rtrim($basePath, '/');
        
        $router = new Router($this->routes, $basePath);
        
        // Get the request URI, handling both direct access and mod_rewrite
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string from URI for routing
        $requestUri = strtok($requestUri, '?');
        
        // If PATH_INFO is set (from index.php/path format), use it
        if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
            // PATH_INFO gives us the path after index.php (e.g., /it/course)
            $pathInfo = $_SERVER['PATH_INFO'];
            // Combine base path with PATH_INFO
            $requestUri = $basePath . $pathInfo;
        } elseif (isset($_SERVER['REDIRECT_URL'])) {
            // Some Apache configurations use REDIRECT_URL
            $requestUri = $_SERVER['REDIRECT_URL'];
        } elseif (strpos($requestUri, '/index.php/') !== false) {
            // Handle index.php/path format manually if PATH_INFO not set
            $parts = explode('/index.php/', $requestUri, 2);
            if (isset($parts[1])) {
                $requestUri = $basePath . '/' . $parts[1];
            }
        }
        
        // Debug: Log the URI for troubleshooting (temporary - output to page for easier debugging)
        $debugInfo = [
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'N/A',
            'REDIRECT_URL' => $_SERVER['REDIRECT_URL'] ?? 'N/A',
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
            'Final URI' => $requestUri,
            'Base Path' => $basePath
        ];
        // Uncomment the line below to see debug info in error log
        // error_log("App Debug: " . json_encode($debugInfo));
        
        [$controllerName, $method, $params] = $router->resolve(
            $requestUri,
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );

        if (!$controllerName || !$method) {
            // Debug information
            $debugInfo = [
                'URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
                'Base Path' => $basePath,
                'Method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A'
            ];
            error_log("Route not found: " . json_encode($debugInfo));
            $this->renderError(404, 'Route not found: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
            return;
        }

        $controllerClass = '\\controllers\\' . $controllerName;

        if (!class_exists($controllerClass)) {
            $this->renderError(404, "Controller {$controllerName} not found");
            return;
        }

        try {
            $controller = new $controllerClass();
        } catch (Throwable $e) {
            error_log("Error instantiating controller {$controllerClass}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->renderError(500, "Error loading controller: " . $e->getMessage());
            return;
        }

        if (!method_exists($controller, $method)) {
            $this->renderError(404, "Method {$method} not found");
            return;
        }

        try {
            call_user_func_array([$controller, $method], $params);
        } catch (Throwable $e) {
            error_log("Error in controller method {$controllerClass}::{$method}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->renderError(500, $e->getMessage());
        }
    }

    private function renderError(int $code, string $message): void
    {
        http_response_code($code);
        $view = new View();
        $view->render("errors/{$code}", [
            'message' => $message,
            'title' => "Error {$code}"
        ]);
    }
}

