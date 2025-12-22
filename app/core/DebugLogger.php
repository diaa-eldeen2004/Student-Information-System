<?php
namespace core;

/**
 * Debug Logger for tracking errors and debugging information
 * Writes to a file that can be easily accessed
 */
class DebugLogger
{
    private static string $logFile;
    private static bool $initialized = false;

    private static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        // Create logs directory if it doesn't exist
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        self::$logFile = $logDir . '/debug_' . date('Y-m-d') . '.log';
        self::$initialized = true;
    }

    public static function log(string $message, array $context = []): void
    {
        // Suppress logging during tests
        if (defined('TESTING') || defined('PHPUNIT_TEST')) {
            return;
        }
        
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_PRETTY_PRINT) : '';
        $logMessage = "[{$timestamp}] {$message}{$contextStr}\n";
        
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
        
        // Also write to PHP error log for visibility
        error_log($logMessage);
    }

    public static function logError(string $message, \Throwable $exception = null, array $context = []): void
    {
        // Suppress logging during tests
        if (defined('TESTING') || defined('PHPUNIT_TEST')) {
            return;
        }
        
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $errorDetails = [
            'message' => $message,
            'exception' => $exception ? [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ] : null,
            'context' => $context
        ];
        
        $logMessage = "[{$timestamp}] ERROR: " . json_encode($errorDetails, JSON_PRETTY_PRINT) . "\n";
        
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
        error_log($logMessage);
    }

    public static function getLogFilePath(): string
    {
        self::init();
        return self::$logFile;
    }

    public static function getRecentLogs(int $lines = 50): array
    {
        self::init();
        
        if (!file_exists(self::$logFile)) {
            return [];
        }
        
        $file = file(self::$logFile);
        return array_slice($file, -$lines);
    }

    public static function clearLog(): void
    {
        self::init();
        if (file_exists(self::$logFile)) {
            file_put_contents(self::$logFile, '');
        }
    }
}

