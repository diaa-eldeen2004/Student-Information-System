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
        $basePath = parse_url($this->config['base_url'] ?? '', PHP_URL_PATH) ?? '';
        $router = new Router($this->routes, $basePath);
        [$controllerName, $method, $params] = $router->resolve(
            $_SERVER['REQUEST_URI'] ?? '/',
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );

        if (!$controllerName || !$method) {
            $this->renderError(404, 'Route not found');
            return;
        }

        $controllerClass = '\\controllers\\' . $controllerName;

        if (!class_exists($controllerClass)) {
            $this->renderError(404, "Controller {$controllerName} not found");
            return;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            $this->renderError(404, "Method {$method} not found");
            return;
        }

        try {
            call_user_func_array([$controller, $method], $params);
        } catch (Throwable $e) {
            $this->renderError(500, $e->getMessage());
        }
    }

    private function renderError(int $code, string $message): void
    {
        http_response_code($code);
        $view = new View();
        $view->render("errors/{$code}", ['message' => $message]);
    }
}

