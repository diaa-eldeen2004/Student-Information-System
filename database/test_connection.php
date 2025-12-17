<?php
/**
 * Database Connection Test Script
 * Run this to verify your database connection is working correctly
 */

// Load database configuration
$config = require __DIR__ . '/../app/config/database.php';

echo "=== Database Connection Test ===\n\n";
echo "Configuration:\n";
echo "  Host: {$config['host']}\n";
echo "  Port: {$config['port']}\n";
echo "  Database: {$config['database']}\n";
echo "  Username: {$config['username']}\n";
echo "  Password: " . (empty($config['password']) ? '(empty)' : '***') . "\n";
echo "  Driver: {$config['driver']}\n";
echo "  Charset: {$config['charset']}\n\n";

// Test connection
try {
    $dsn = sprintf(
        '%s:host=%s;port=%d;dbname=%s;charset=%s',
        $config['driver'],
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    echo "Attempting connection...\n";
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "✓ Connection successful!\n\n";

    // Test basic query
    echo "Testing database access...\n";
    $stmt = $pdo->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
    $result = $stmt->fetch();
    echo "  Current Database: {$result['current_db']}\n";
    echo "  MySQL Version: {$result['mysql_version']}\n\n";

    // Check if required tables exist
    echo "Checking required tables...\n";
    $requiredTables = [
        'users',
        'courses',
        'sections',
        'enrollments',
        'enrollment_requests',
        'doctors',
        'students',
        'it_officers',
        'audit_logs',
        'notifications'
    ];

    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingTables = [];
    foreach ($requiredTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "  ✓ Table '{$table}' exists\n";
        } else {
            echo "  ✗ Table '{$table}' is MISSING\n";
            $missingTables[] = $table;
        }
    }

    if (!empty($missingTables)) {
        echo "\n⚠ WARNING: Missing tables detected!\n";
        echo "The following tables are missing: " . implode(', ', $missingTables) . "\n";
        echo "You may need to run the migration script or import the schema.\n";
    } else {
        echo "\n✓ All required tables exist!\n";
    }

    // Check table structure for key tables
    echo "\nChecking table structures...\n";
    $keyTables = ['users', 'courses', 'sections'];
    foreach ($keyTables as $table) {
        if (in_array($table, $existingTables)) {
            $stmt = $pdo->query("DESCRIBE `{$table}`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "  Table '{$table}' has " . count($columns) . " columns\n";
        }
    }

    echo "\n=== Test Complete ===\n";
    echo "✓ Database connection is working correctly!\n";

} catch (PDOException $e) {
    echo "\n✗ CONNECTION FAILED!\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "Common issues:\n";
    echo "1. Database name is incorrect - check 'database' in app/config/database.php\n";
    echo "2. Database doesn't exist - create it first: CREATE DATABASE {$config['database']};\n";
    echo "3. Wrong username/password - check credentials in app/config/database.php\n";
    echo "4. MySQL server is not running - start MySQL in XAMPP Control Panel\n";
    echo "5. Wrong host/port - check 'host' and 'port' in app/config/database.php\n";
    echo "6. Firewall blocking connection - check firewall settings\n\n";
    
    exit(1);
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

