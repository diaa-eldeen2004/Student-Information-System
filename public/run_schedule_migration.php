<?php
/**
 * Standalone migration script to create schedule table and drop sections table
 * Run this directly: http://your-domain/run_schedule_migration.php
 */

header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/patterns/Singleton/DatabaseConnection.php';

use patterns\Singleton\DatabaseConnection;

try {
    $db = DatabaseConnection::getInstance()->getConnection();
    
    $results = [];
    $errors = [];
    
    // Step 1: Create schedule table
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `schedule` (
                `schedule_id` INT(11) NOT NULL AUTO_INCREMENT,
                `course_id` INT(11) NOT NULL,
                `doctor_id` INT(11) NOT NULL,
                `section_number` VARCHAR(10) NOT NULL,
                `semester` VARCHAR(20) NOT NULL,
                `academic_year` VARCHAR(10) NOT NULL,
                `room` VARCHAR(50) DEFAULT NULL,
                `time_slot` VARCHAR(100) DEFAULT NULL,
                `day_of_week` VARCHAR(20) DEFAULT NULL,
                `start_time` TIME DEFAULT NULL,
                `end_time` TIME DEFAULT NULL,
                `capacity` INT(11) NOT NULL DEFAULT 30,
                `current_enrollment` INT(11) DEFAULT 0,
                `session_type` VARCHAR(20) DEFAULT 'lecture',
                `status` ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`schedule_id`),
                INDEX `idx_course_id` (`course_id`),
                INDEX `idx_doctor_id` (`doctor_id`),
                INDEX `idx_semester` (`semester`, `academic_year`),
                INDEX `idx_day_time` (`day_of_week`, `start_time`, `end_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results[] = "✓ Schedule table created successfully";
    } catch (\PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $results[] = "ℹ Schedule table already exists";
        } else {
            $errors[] = "Error creating schedule table: " . $e->getMessage();
        }
    }
    
    // Step 2: Get database name
    $dbName = null;
    try {
        $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    } catch (\PDOException $e) {
        $errors[] = "Could not get database name: " . $e->getMessage();
    }
    
    // Step 3: Drop foreign key constraints
    $tablesToCheck = ['enrollments', 'enrollment_requests', 'assignments'];
    $fkDropped = 0;
    
    foreach ($tablesToCheck as $tableName) {
        try {
            // Check if table exists
            $tableCheck = $db->query("SHOW TABLES LIKE '{$tableName}'");
            if ($tableCheck->rowCount() == 0) {
                continue;
            }
            
            // Method 1: Find all foreign key constraints using information_schema
            if ($dbName) {
                try {
                    $fkStmt = $db->prepare("
                        SELECT DISTINCT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = ? 
                        AND TABLE_NAME = ? 
                        AND REFERENCED_TABLE_NAME = 'sections'
                    ");
                    $fkStmt->execute([$dbName, $tableName]);
                    $fks = $fkStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($fks as $fk) {
                        if (!empty($fk['CONSTRAINT_NAME'])) {
                            try {
                                $constraintName = $fk['CONSTRAINT_NAME'];
                                $db->exec("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`");
                                $fkDropped++;
                                $results[] = "✓ Dropped foreign key {$constraintName} from {$tableName}";
                            } catch (\PDOException $e) {
                                // Try next method
                            }
                        }
                    }
                } catch (\PDOException $e) {
                    // Try method 2
                }
            }
            
            // Method 2: Use SHOW CREATE TABLE as fallback
            try {
                $createStmt = $db->query("SHOW CREATE TABLE `{$tableName}`");
                $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
                if (isset($createRow['Create Table'])) {
                    $createTable = $createRow['Create Table'];
                    if (preg_match_all("/CONSTRAINT\s+`([^`]+)`\s+FOREIGN KEY.*?REFERENCES\s+`sections`/i", $createTable, $matches)) {
                        foreach ($matches[1] as $constraintName) {
                            try {
                                $db->exec("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`");
                                $fkDropped++;
                                $results[] = "✓ Dropped foreign key {$constraintName} from {$tableName} (method 2)";
                            } catch (\PDOException $e) {
                                // Continue
                            }
                        }
                    }
                }
            } catch (\PDOException $e) {
                // Continue
            }
            
        } catch (\PDOException $e) {
            $errors[] = "Error processing {$tableName}: " . $e->getMessage();
        }
    }
    
    // Step 4: Drop sections table
    try {
        $db->exec("DROP TABLE IF EXISTS `sections`");
        $results[] = "✓ Sections table dropped successfully";
    } catch (\PDOException $e) {
        // If foreign key constraint error, disable checks temporarily
        if (strpos($e->getMessage(), 'foreign key') !== false || 
            strpos($e->getMessage(), '1451') !== false ||
            strpos($e->getMessage(), '23000') !== false) {
            try {
                $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                $db->exec("DROP TABLE IF EXISTS `sections`");
                $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                $results[] = "✓ Sections table dropped (foreign key checks were temporarily disabled)";
            } catch (\PDOException $e2) {
                $errors[] = "Error dropping sections table even with FK checks disabled: " . $e2->getMessage();
            }
        } else {
            $errors[] = "Error dropping sections table: " . $e->getMessage();
        }
    }
    
    // Verify results
    $stmt = $db->query("SHOW TABLES LIKE 'schedule'");
    $scheduleExists = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW TABLES LIKE 'sections'");
    $sectionsExists = $stmt->rowCount() > 0;
    
    echo json_encode([
        'success' => $scheduleExists && !$sectionsExists,
        'message' => 'Migration completed',
        'results' => $results,
        'errors' => $errors,
        'schedule_exists' => $scheduleExists,
        'sections_removed' => !$sectionsExists
    ], JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}

