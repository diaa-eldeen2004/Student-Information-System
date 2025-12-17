<?php
/**
 * Migration script for IT Officers table
 * This script creates the it_officers table if it doesn't exist
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

$dbConfig = require __DIR__ . '/../app/config/database.php';
$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Check if table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'it_officers'");
    $tableExists = $checkTable->rowCount() > 0;
    
    if ($tableExists) {
        echo json_encode([
            'success' => true,
            'message' => 'IT Officers table already exists.',
            'action' => 'none'
        ]);
    } else {
        // Create the table
        $sql = "CREATE TABLE IF NOT EXISTS `it_officers` (
            `it_id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`it_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        // Check if there are users with role 'it' who need IT officer records
        $stmt = $pdo->query("
            SELECT u.id, u.email, u.first_name, u.last_name 
            FROM users u 
            LEFT JOIN it_officers it ON u.id = it.user_id 
            WHERE u.role = 'it' AND it.user_id IS NULL
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $recordsCreated = 0;
        if (!empty($users)) {
            $insertStmt = $pdo->prepare("INSERT INTO it_officers (user_id) VALUES (?)");
            foreach ($users as $user) {
                $insertStmt->execute([$user['id']]);
                $recordsCreated++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "IT Officers table created successfully. {$recordsCreated} IT officer record(s) created for existing users.",
            'action' => 'created',
            'records_created' => $recordsCreated
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Migration failed: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}

