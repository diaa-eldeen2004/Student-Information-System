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

    // Read and execute schema.sql
    echo "2. Reading schema file...\n";
    $schemaFile = dirname(__DIR__) . '/database/schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: {$schemaFile}");
    }
    
    $schema = file_get_contents($schemaFile);
    
    // Remove comments and split by semicolon
    $schema = preg_replace('/--.*$/m', '', $schema);
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*$/s', $stmt);
        }
    );
    
    echo "   Found " . count($statements) . " SQL statements\n\n";
    
    // Execute each statement
    $tableCount = 0;
    foreach ($statements as $index => $statement) {
        if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches)) {
            $tableName = $matches[1];
            echo "   Creating table '{$tableName}'...\n";
            try {
                $pdo->exec($statement . ';');
                echo "   ✓ Table '{$tableName}' created successfully\n";
                $tableCount++;
            } catch (PDOException $e) {
                // Table might already exist, which is okay
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "   ⚠ Warning: " . $e->getMessage() . "\n";
                } else {
                    echo "   ℹ Table '{$tableName}' already exists (skipped)\n";
                    $tableCount++;
                }
            }
        }
    }
    
    echo "\n3. Verification:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   ✓ Found " . count($tables) . " tables in database\n";
    
    $expectedTables = [
        'users', 'students', 'doctors', 'advisors', 'it_officers', 'admins',
        'courses', 'course_prerequisites', 'sections', 'enrollments',
        'enrollment_requests', 'assignments', 'assignment_submissions',
        'materials', 'attendance', 'notifications', 'student_notes', 'audit_logs'
    ];
    
    foreach ($expectedTables as $table) {
        if (in_array($table, $tables)) {
            echo "   ✓ Table '{$table}' exists\n";
        } else {
            echo "   ✗ Table '{$table}' missing\n";
        }
    }
    
    echo "\nMigration completed successfully! ✓\n";
    echo "You can now use the application.\n";

} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    exit(1);
}

