<?php
namespace middleware;

use core\Middleware;

class RoleMiddleware extends Middleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            $config = require dirname(__DIR__) . '/config/config.php';
            $basePath = rtrim(parse_url($config['base_url'] ?? '', PHP_URL_PATH) ?? '', '/');
            $loginPath = $basePath ? $basePath . '/auth/login' : '/auth/login';
            header('Location: ' . $loginPath);
            return false;
        }

        $userRole = $_SESSION['user_role'] ?? null;
        
        if (!in_array($userRole, $this->allowedRoles)) {
            $config = require dirname(__DIR__) . '/config/config.php';
            $basePath = rtrim(parse_url($config['base_url'] ?? '', PHP_URL_PATH) ?? '', '/');
            $errorPath = $basePath ? $basePath . '/errors/403' : '/errors/403';
            header('Location: ' . $errorPath);
            return false;
        }

        return true;
    }
}

