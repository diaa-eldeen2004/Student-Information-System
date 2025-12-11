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
}

