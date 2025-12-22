<?php
/**
 * PHPUnit Bootstrap File
 * Sets up the testing environment
 */

declare(strict_types=1);

// Define test environment
define('APP_ENV', 'testing');
define('TESTING', true);

// Suppress header warnings during tests (they're expected when testing redirects)
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Suppress "Cannot modify header information" warnings during tests
    if (strpos($errstr, 'Cannot modify header information') !== false ||
        strpos($errstr, 'headers already sent') !== false) {
        return true; // Suppress the error
    }
    return false; // Let other errors through
}, E_WARNING);
define('PHPUNIT_TEST', true);

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Suppress display errors in tests
ini_set('log_errors', '0'); // Suppress error logging during tests

// Redirect error_log to null during tests to suppress header error messages
// This prevents "Signup error" messages from appearing in test output
if (php_sapi_name() === 'cli') {
    // On Windows, use nul, on Unix use /dev/null
    $nullDevice = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'nul' : '/dev/null';
    ini_set('error_log', $nullDevice);
}

// Set timezone
date_default_timezone_set('UTC');

// Get the project root directory
$root = dirname(__DIR__);

// Include the autoloader
require_once $root . '/app/config/config.php';
require_once $root . '/app/config/database.php';

// Simple PSR-4 autoloader for the app namespace
spl_autoload_register(function (string $class): void {
    $baseDir = dirname(__DIR__) . '/app/';
    $class = ltrim($class, '\\');
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load Composer autoloader if it exists
$composerAutoload = $root . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Set up test database configuration
$_ENV['DB_DATABASE'] = 'swe_app_test';
$_ENV['APP_ENV'] = 'testing';
