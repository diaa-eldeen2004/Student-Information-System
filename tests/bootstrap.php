<?php
/**
 * PHPUnit Bootstrap File
 * Sets up the testing environment
 */

declare(strict_types=1);

// Define test environment
define('APP_ENV', 'testing');
define('TESTING', true);
define('PHPUNIT_TEST', true);

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Suppress display errors in tests
ini_set('log_errors', '0'); // Suppress error logging during tests

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
