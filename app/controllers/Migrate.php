<?php
namespace controllers;

use core\Controller;
use PDO;
use PDOException;

class Migrate extends Controller
{
    public function run(): void
    {
        // Security: Only allow in development (remove this check in production or add proper auth)
        // For now, we'll allow it but you should restrict this in production
        
        $dbConfig = require dirname(__DIR__) . '/config/database.php';
        
        $host = $dbConfig['host'];
        $port = $dbConfig['port'];
        $database = $dbConfig['database'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];
        $charset = $dbConfig['charset'];
        
        $messages = [];
        $success = false;
        
        try {
            // Connect to MySQL server (without database)
            $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $host, $port, $charset);
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            
            // Create database if it doesn't exist
            $messages[] = "Checking database '{$database}'...";
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $messages[] = "✓ Database '{$database}' is ready";
            
            // Connect to the specific database
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            
            // Read and execute schema.sql
            $messages[] = "Reading schema file...";
            $schemaFile = dirname(__DIR__, 2) . '/database/schema.sql';
            
            if (!file_exists($schemaFile)) {
                throw new \Exception("Schema file not found: {$schemaFile}");
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
            
            $messages[] = "Found " . count($statements) . " SQL statements";
            
            // Execute each statement
            $tableCount = 0;
            foreach ($statements as $index => $statement) {
                if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches)) {
                    $tableName = $matches[1];
                    $messages[] = "Creating table '{$tableName}'...";
                    try {
                        $pdo->exec($statement . ';');
                        $messages[] = "✓ Table '{$tableName}' created successfully";
                        $tableCount++;
                    } catch (PDOException $e) {
                        // Table might already exist, which is okay
                        if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), 'Duplicate') === false) {
                            $messages[] = "⚠ Warning for '{$tableName}': " . $e->getMessage();
                        } else {
                            $messages[] = "ℹ Table '{$tableName}' already exists (skipped)";
                            $tableCount++;
                        }
                    }
                }
            }
            
            // Verify tables exist
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $messages[] = "✓ Found " . count($tables) . " tables in database";
            
            $expectedTables = [
                'users', 'students', 'doctors', 'advisors', 'it_officers', 'admins',
                'courses', 'course_prerequisites', 'sections', 'enrollments',
                'enrollment_requests', 'assignments', 'assignment_submissions',
                'materials', 'attendance', 'notifications', 'student_notes', 'audit_logs'
            ];
            
            foreach ($expectedTables as $table) {
                if (in_array($table, $tables)) {
                    $messages[] = "✓ Table '{$table}' exists";
                } else {
                    $messages[] = "✗ Table '{$table}' missing";
                }
            }
            
            $success = true;
            $messages[] = "Migration completed successfully!";
            
        } catch (PDOException $e) {
            $messages[] = "✗ Migration failed: " . $e->getMessage();
            $messages[] = "Error Code: " . $e->getCode();
        }
        
            $this->view->render('migrate/result', [
            'title' => 'Database Migration',
            'messages' => $messages,
            'success' => $success,
        ]);
    }

    public function runMigration(): void
    {
        // Security: Only allow in development (add auth check in production)
        
        $migrationFile = $_GET['file'] ?? '';
        if (empty($migrationFile)) {
            $this->view->render('migrate/result', [
                'title' => 'Migration Error',
                'messages' => ['✗ No migration file specified'],
                'success' => false,
            ]);
            return;
        }

        $dbConfig = require dirname(__DIR__) . '/config/database.php';
        
        $host = $dbConfig['host'];
        $port = $dbConfig['port'];
        $database = $dbConfig['database'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];
        $charset = $dbConfig['charset'];
        
        $messages = [];
        $success = false;
        
        try {
            // Connect to the database
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            
            // Read migration file
            $migrationPath = dirname(__DIR__, 2) . '/database/migrations/' . basename($migrationFile);
            
            if (!file_exists($migrationPath)) {
                throw new \Exception("Migration file not found: {$migrationFile}");
            }
            
            $messages[] = "Reading migration file: {$migrationFile}";
            $sql = file_get_contents($migrationPath);
            
            // Remove comments
            $sql = preg_replace('/--.*$/m', '', $sql);
            
            // Split SQL into individual statements by semicolon
            // Handle multi-line statements properly
            $statements = [];
            $currentStatement = '';
            $lines = explode("\n", $sql);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '--') === 0) {
                    continue;
                }
                
                $currentStatement .= $line . " ";
                
                // Check if line ends with semicolon
                if (substr(rtrim($line), -1) === ';') {
                    $stmt = trim($currentStatement);
                    if (!empty($stmt)) {
                        $statements[] = rtrim($stmt, ';');
                    }
                    $currentStatement = '';
                }
            }
            
            // Add last statement if no semicolon
            if (!empty(trim($currentStatement))) {
                $statements[] = trim($currentStatement);
            }
            
            // Filter out empty statements
            $statements = array_filter($statements, function($stmt) {
                return !empty(trim($stmt));
            });
            
            $messages[] = "Found " . count($statements) . " SQL statement(s)";
            
            // Track which columns we've successfully added
            $addedColumns = [];
            
            // Execute each statement
            foreach ($statements as $index => $statement) {
                $statement = trim($statement);
                if (empty($statement) || strpos($statement, '--') === 0) {
                    continue;
                }
                
                // Remove trailing semicolon if present
                $statement = rtrim($statement, ';');
                
                // Check if we're trying to CREATE INDEX but the column doesn't exist yet
                if (stripos($statement, 'CREATE INDEX') !== false) {
                    // Extract column name from CREATE INDEX statement
                    if (preg_match('/ON\s+`?(\w+)`?\s*\(`?(\w+)`?/i', $statement, $matches)) {
                        $tableName = $matches[1] ?? '';
                        $columnName = $matches[2] ?? '';
                        
                        // Check if column exists
                        try {
                            $checkStmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'");
                            if ($checkStmt->rowCount() === 0) {
                                $messages[] = "ℹ Column '{$columnName}' doesn't exist yet, skipping CREATE INDEX";
                                continue; // Skip this statement
                            }
                        } catch (\Exception $e) {
                            // If we can't check, try to execute anyway
                        }
                    }
                }
                
                try {
                    $pdo->exec($statement);
                    
                    // Extract operation type for better messaging
                    if (stripos($statement, 'ADD COLUMN') !== false) {
                        preg_match('/ADD COLUMN\s+`?(\w+)`?/i', $statement, $matches);
                        $colName = $matches[1] ?? 'column';
                        $addedColumns[] = $colName;
                        $messages[] = "✓ Added column '{$colName}'";
                    } elseif (stripos($statement, 'MODIFY COLUMN') !== false) {
                        preg_match('/MODIFY COLUMN\s+`?(\w+)`?/i', $statement, $matches);
                        $colName = $matches[1] ?? 'column';
                        $messages[] = "✓ Modified column '{$colName}'";
                    } elseif (stripos($statement, 'CREATE INDEX') !== false) {
                        preg_match('/CREATE INDEX\s+`?(\w+)`?/i', $statement, $matches);
                        $indexName = $matches[1] ?? 'index';
                        $messages[] = "✓ Created index '{$indexName}'";
                    } else {
                        $messages[] = "✓ Executed statement " . ($index + 1);
                    }
                } catch (PDOException $e) {
                    $errorMsg = $e->getMessage();
                    $errorCode = $e->getCode();
                    
                    // Check if it's a "duplicate" or "already exists" error (which is okay)
                    if (stripos($errorMsg, 'Duplicate column name') !== false ||
                        stripos($errorMsg, 'Duplicate key name') !== false ||
                        stripos($errorMsg, 'already exists') !== false ||
                        $errorCode == '42S21') { // Duplicate column/key error code
                        $messages[] = "ℹ " . $errorMsg . " (skipped - already exists)";
                    } 
                    // Check if it's a "column doesn't exist" error for MODIFY (which is okay, we'll skip it)
                    elseif (stripos($statement, 'MODIFY COLUMN') !== false && 
                            (stripos($errorMsg, "doesn't exist") !== false || 
                             stripos($errorMsg, 'Unknown column') !== false ||
                             $errorCode == '42S22')) {
                        preg_match('/MODIFY COLUMN\s+`?(\w+)`?/i', $statement, $matches);
                        $colName = $matches[1] ?? 'column';
                        $messages[] = "ℹ Column '{$colName}' doesn't exist yet, skipping MODIFY";
                    }
                    // Check if it's a "column doesn't exist" error for CREATE INDEX (which is okay, we'll skip it)
                    elseif (stripos($statement, 'CREATE INDEX') !== false && 
                            (stripos($errorMsg, "doesn't exist") !== false || 
                             stripos($errorMsg, 'Unknown column') !== false ||
                             $errorCode == '42S22')) {
                        preg_match('/ON\s+`?(\w+)`?\s*\(`?(\w+)`?/i', $statement, $matches);
                        $colName = $matches[2] ?? 'column';
                        $messages[] = "ℹ Column '{$colName}' doesn't exist yet, skipping CREATE INDEX";
                    }
                    // Otherwise it's a real error
                    else {
                        $messages[] = "✗ Error executing statement: " . $errorMsg;
                        $messages[] = "   Statement: " . substr($statement, 0, 100) . "...";
                        throw $e; // Re-throw if it's a real error
                    }
                }
            }
            
            $success = true;
            $messages[] = "Migration '{$migrationFile}' completed successfully!";
            
        } catch (PDOException $e) {
            $messages[] = "✗ Migration failed: " . $e->getMessage();
            $messages[] = "Error Code: " . $e->getCode();
        } catch (\Exception $e) {
            $messages[] = "✗ Error: " . $e->getMessage();
        }
        
        // If this is an AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'messages' => $messages
            ]);
            return;
        }
        
        $this->view->render('migrate/result', [
            'title' => 'Migration: ' . basename($migrationFile),
            'messages' => $messages,
            'success' => $success,
        ]);
    }
}

