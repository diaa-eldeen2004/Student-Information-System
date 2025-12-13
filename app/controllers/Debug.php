<?php
namespace controllers;

use core\Controller;
use core\DebugLogger;

class Debug extends Controller
{
    public function viewLog(): void
    {
        // Only allow admins to view debug logs
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            echo 'Access Denied';
            exit;
        }

        $logFile = DebugLogger::getLogFilePath();
        $logs = [];

        if (file_exists($logFile)) {
            $logs = DebugLogger::getRecentLogs(100); // Last 100 lines
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo "=== Debug Log Viewer ===\n";
        echo "Log File: " . $logFile . "\n";
        echo "Last Updated: " . (file_exists($logFile) ? date('Y-m-d H:i:s', filemtime($logFile)) : 'N/A') . "\n";
        echo "===========================================\n\n";

        if (empty($logs)) {
            echo "No logs found.\n";
        } else {
            foreach (array_reverse($logs) as $log) {
                echo $log;
            }
        }
    }

    public function clearLog(): void
    {
        // Only allow admins to clear debug logs
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            echo 'Access Denied';
            exit;
        }

        DebugLogger::clearLog();
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '/');
        exit;
    }
}

