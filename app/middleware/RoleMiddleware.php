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
            header('Location: /auth/login');
            return false;
        }

        $userRole = $_SESSION['user_role'] ?? null;
        
        if (!in_array($userRole, $this->allowedRoles)) {
            header('Location: /errors/403');
            return false;
        }

        return true;
    }
}

