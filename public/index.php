<?php
// Front controller: bootstrap the application

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/config/database.php';

// Simple PSR-4–style autoloader for the app namespace
spl_autoload_register(function (string $class): void {
    $baseDir = dirname(__DIR__) . '/app/';
    $class = ltrim($class, '\\');
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use core\App;

// Run the application
$app = new App();
$app->run();

