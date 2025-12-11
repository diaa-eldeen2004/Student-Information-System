<?php
/**
 * Database Reset Script
 * Drops and recreates the database and tables
 * WARNING: This will delete all data!
 * 
 * Usage:
 *   php database/reset.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/database.php';

$dbConfig = require dirname(__DIR__) . '/app/config/database.php';

$host = $dbConfig['host'];
$port = $dbConfig['port'];
$database = $dbConfig['database'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$charset = $dbConfig['charset'];

echo "WARNING: This will delete all data in the database!\n";
echo "Press Ctrl+C to cancel, or Enter to continue...\n";
readline();

echo "\nStarting database reset...\n\n";

try {
    // Connect to MySQL server
    $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $host, $port, $charset);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Drop database
    echo "1. Dropping database '{$database}'...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `{$database}`");
    echo "   ✓ Database dropped\n\n";

    // Recreate database
    echo "2. Creating database '{$database}'...\n";
    $pdo->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "   ✓ Database created\n\n";

    // Connect to the database
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create users table
    echo "3. Creating 'users' table...\n";
    $sql = "CREATE TABLE `users` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `first_name` VARCHAR(100) NOT NULL,
        `last_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `phone` VARCHAR(20) DEFAULT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('admin', 'student', 'doctor', 'advisor', 'it', 'user') NOT NULL DEFAULT 'user',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_email` (`email`),
        INDEX `idx_role` (`role`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "   ✓ Table 'users' created\n\n";

    echo "Database reset completed successfully! ✓\n";

} catch (PDOException $e) {
    echo "✗ Reset failed: " . $e->getMessage() . "\n";
    exit(1);
}

