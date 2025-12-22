<?php
namespace controllers;

use core\Controller;

class Home extends Controller
{
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;
        $effectiveRole = $user['role'] ?? null;

        $config = require dirname(__DIR__) . '/config/config.php';
        $basePath = rtrim(parse_url($config['base_url'] ?? '', PHP_URL_PATH) ?? '', '/');
        $buildPath = function(string $path) use ($basePath) {
            $clean = ltrim($path, '/');
            if ($basePath === '' || $basePath === '/') {
                return '/' . $clean;
            }
            return $basePath . '/' . $clean;
        };

        $roleDestinations = [
            'doctor' => $buildPath('doctor/dashboard'),
            'admin' => $buildPath('admin/dashboard'),
            'student' => $buildPath('student/dashboard'),
            'it' => $buildPath('it/dashboard'),
            'user' => $basePath === '' ? '/' : $basePath,
        ];

        $roleLabels = [
            'doctor' => 'Doctor Dashboard',
            'admin' => 'Admin Dashboard',
            'student' => 'Student Dashboard',
            'it' => 'IT Dashboard',
            'user' => 'Profile',
        ];

        $destination = $effectiveRole && isset($roleDestinations[$effectiveRole])
            ? $roleDestinations[$effectiveRole]
            : '';
        $label = $effectiveRole && isset($roleLabels[$effectiveRole])
            ? $roleLabels[$effectiveRole]
            : 'Dashboard';

        $this->view->render('home/index', [
            'title' => 'University Portal',
            'effectiveRole' => $effectiveRole,
            'dashboardDestination' => $destination,
            'dashboardLabel' => $label,
        ]);
    }
}

