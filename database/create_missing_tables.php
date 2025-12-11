<?php
/**
 * Create all missing tables from schema.sql
 * This will create any tables that don't exist yet
 */

require_once dirname(__DIR__) . '/app/config/database.php';

$dbConfig = require dirname(__DIR__) . '/app/config/database.php';

$host = $dbConfig['host'];
$port = $dbConfig['port'];
$database = $dbConfig['database'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$charset = $dbConfig['charset'];

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "Connected to database: {$database}\n\n";
    
    // Read schema.sql
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
    
    echo "Found " . count($statements) . " SQL statements\n\n";
    
    // Get existing tables
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Existing tables: " . count($existingTables) . "\n\n";
    
    // Execute each CREATE TABLE statement
    $created = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches)) {
            $tableName = $matches[1];
            
            // Check if table already exists
            if (in_array($tableName, $existingTables)) {
                echo "ℹ Table '{$tableName}' already exists (skipped)\n";
                $skipped++;
                continue;
            }
            
            echo "Creating table '{$tableName}'...\n";
            try {
                $pdo->exec($statement . ';');
                echo "✓ Table '{$tableName}' created successfully\n";
                $created++;
                $existingTables[] = $tableName; // Add to list to avoid duplicate attempts
            } catch (PDOException $e) {
                // Check if it's a foreign key dependency issue
                if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                    echo "⚠ Table '{$tableName}' has dependency issues (will retry later): " . $e->getMessage() . "\n";
                    $errors++;
                } else {
                    echo "✗ Error creating '{$tableName}': " . $e->getMessage() . "\n";
                    $errors++;
                }
            }
        }
    }
    
    // Retry tables that had dependency issues
    if ($errors > 0) {
        echo "\nRetrying tables with dependency issues...\n";
        foreach ($statements as $statement) {
            if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches)) {
                $tableName = $matches[1];
                
                if (!in_array($tableName, $existingTables)) {
                    try {
                        $pdo->exec($statement . ';');
                        echo "✓ Table '{$tableName}' created on retry\n";
                        $created++;
                        $existingTables[] = $tableName;
                    } catch (PDOException $e) {
                        // Ignore if still fails
                    }
                }
            }
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Summary:\n";
    echo "  Created: {$created} tables\n";
    echo "  Skipped: {$skipped} tables (already exist)\n";
    echo "  Errors: {$errors} tables\n";
    
    // Verify critical tables
    echo "\nVerifying critical tables:\n";
    $criticalTables = ['users', 'students', 'doctors', 'it_officers', 'courses', 'sections', 'enrollments', 'enrollment_requests', 'audit_logs'];
    
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($criticalTables as $table) {
        if (in_array($table, $allTables)) {
            echo "  ✓ {$table}\n";
        } else {
            echo "  ✗ {$table} (MISSING)\n";
        }
    }
    
    echo "\n✓ Done!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

