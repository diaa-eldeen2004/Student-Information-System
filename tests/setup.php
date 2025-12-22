<?php
/**
 * Test Database Setup Script
 * Creates the test database and runs migrations
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$config = require dirname(__DIR__) . '/app/config/database.php';
$testDbName = 'swe_app_test';

echo "Setting up test database...\n";

try {
    // Connect without database
    $dsn = sprintf(
        '%s:host=%s;port=%d;charset=%s',
        $config['driver'],
        $config['host'],
        $config['port'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create test database
    echo "Creating database: {$testDbName}...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$testDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database created\n";

    // Connect to test database
    $dsn = sprintf(
        '%s:host=%s;port=%d;dbname=%s;charset=%s',
        $config['driver'],
        $config['host'],
        $config['port'],
        $testDbName,
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Run migrations
    $schemaFile = dirname(__DIR__) . '/database/schema.sql';
    if (file_exists($schemaFile)) {
        echo "Running migrations...\n";
        $sql = file_get_contents($schemaFile);
        // Replace database name
        $sql = str_replace('swe_app', $testDbName, $sql);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );

        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignore errors for existing tables/columns
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
        echo "✓ Migrations completed\n";
    } else {
        echo "⚠ Schema file not found: {$schemaFile}\n";
    }

    echo "\n✓ Test database setup complete!\n";
    echo "You can now run tests with: composer test\n";

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
