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
            
            // Create users table
            $messages[] = "Creating 'users' table...";
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
            $messages[] = "✓ Table 'users' created successfully";
            
            // Verify table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("DESCRIBE users");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $messages[] = "✓ Table has " . count($columns) . " columns";
                $success = true;
                $messages[] = "Migration completed successfully!";
            } else {
                $messages[] = "✗ Error: Table verification failed";
            }
            
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

