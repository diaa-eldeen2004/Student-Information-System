<?php
/**
 * Database Migration Script
 * Creates the database and tables for the SWE application
 * 
 * Usage:
 *   php database/migrate.php
 *   OR visit: http://localhost/swe/public/database/migrate.php (if moved to public)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/database.php';

// Get database config
$dbConfig = require dirname(__DIR__) . '/app/config/database.php';

$host = $dbConfig['host'];
$port = $dbConfig['port'];
$database = $dbConfig['database'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$charset = $dbConfig['charset'];

echo "Starting database migration...\n\n";

try {
    // Connect to MySQL server (without database)
    $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $host, $port, $charset);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create database if it doesn't exist
    echo "1. Checking database '{$database}'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "   ✓ Database '{$database}' is ready\n\n";

    // Connect to the specific database
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create users table
    echo "2. Creating 'users' table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `users` (
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
    echo "   ✓ Table 'users' created successfully\n\n";

    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "3. Verification:\n";
        echo "   ✓ Table 'users' exists\n";
        
        // Count columns
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   ✓ Table has " . count($columns) . " columns\n\n";
        
        echo "Migration completed successfully! ✓\n";
        echo "You can now use the application.\n";
    } else {
        echo "   ✗ Error: Table verification failed\n";
        exit(1);
    }

} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    exit(1);
}

