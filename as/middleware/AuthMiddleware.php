<?php
namespace middleware;

use core\Middleware;

class AuthMiddleware extends Middleware
{
    public function handle(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            $config = require dirname(__DIR__) . '/config/config.php';
            $basePath = rtrim(parse_url($config['base_url'] ?? '', PHP_URL_PATH) ?? '', '/');
            $loginPath = $basePath ? $basePath . '/auth/login' : '/auth/login';
            header('Location: ' . $loginPath);
            return false;
        }
        return true;
    }
}

