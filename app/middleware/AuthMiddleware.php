<?php
namespace middleware;

use core\Middleware;

class AuthMiddleware extends Middleware
{
    public function handle(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/login');
            return false;
        }
        return true;
    }
}

