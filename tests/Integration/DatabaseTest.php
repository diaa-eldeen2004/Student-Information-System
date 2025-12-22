<?php
namespace Tests\Integration;

use Tests\TestCase;
use PDO;

class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDatabase();
        $this->runMigrations();
    }

    public function testDatabaseConnection(): void
    {
        $this->assertInstanceOf(PDO::class, $this->db);
        // Note: In setUp(), we start a transaction, so this will be true
        // But the test connection itself is valid
        $this->assertTrue($this->db instanceof PDO);
    }

    public function testCreateTables(): void
    {
        $tables = ['users', 'students', 'courses', 'enrollments'];
        
        foreach ($tables as $table) {
            $stmt = $this->db->query("SHOW TABLES LIKE '{$table}'");
            $this->assertEquals(1, $stmt->rowCount(), "Table {$table} should exist");
        }
        
        // Check for schedule OR sections table (migration may use either)
        $scheduleCheck = $this->db->query("SHOW TABLES LIKE 'schedule'");
        $sectionsCheck = $this->db->query("SHOW TABLES LIKE 'sections'");
        $this->assertTrue(
            $scheduleCheck->rowCount() > 0 || $sectionsCheck->rowCount() > 0,
            "Either 'schedule' or 'sections' table should exist"
        );
    }

    public function testForeignKeys(): void
    {
        // Test cascade delete
        // createTestStudent creates its own user, so we'll use that user for deletion
        $student = $this->createTestStudent();
        $user = $student['user']; // Get the user that was created for this student

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        
        // Verify student exists before deletion
        $beforeStmt = $this->db->prepare("SELECT * FROM students WHERE student_id = ?");
        $beforeStmt->execute([$student['student_id']]);
        $beforeResult = $beforeStmt->fetch();
        $this->assertNotFalse($beforeResult, 'Student should exist before user deletion');

        // Verify foreign key constraint exists
        // Use proper table aliases to avoid ambiguous column names
        $fkCheck = $this->db->query("
            SELECT rc.CONSTRAINT_NAME, rc.DELETE_RULE
            FROM information_schema.REFERENTIAL_CONSTRAINTS rc
            JOIN information_schema.KEY_COLUMN_USAGE kcu 
                ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                AND rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
            WHERE rc.CONSTRAINT_SCHEMA = DATABASE() 
            AND kcu.TABLE_NAME = 'students' 
            AND kcu.COLUMN_NAME = 'user_id' 
            AND kcu.REFERENCED_TABLE_NAME = 'users'
        ");
        $fkInfo = $fkCheck->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($fkInfo, 'Foreign key constraint should exist on students.user_id');
        $this->assertEquals('CASCADE', $fkInfo['DELETE_RULE'] ?? '', 'Foreign key should have ON DELETE CASCADE');
        
        // Delete user (cascade should delete student)
        // The foreign key constraint has ON DELETE CASCADE, so deleting the user
        // should automatically delete the associated student
        // For cascade delete to work, we need to ensure foreign key constraints are enabled
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Delete the user - cascade should delete the student
        // The cascade delete happens automatically when we delete the user
        // We need to ensure the deletion is committed so the cascade is visible
        // Commit any active transaction first to ensure data is visible
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
        
        // Start a new transaction for the deletion
        // This ensures the cascade delete happens within a transaction that we can commit
        $this->db->beginTransaction();
        
        // Delete in transaction mode - cascade delete should happen immediately
        // Use prepared statement to avoid SQL injection
        $deleteStmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->execute([$user['id']]);
        
        // Commit the deletion (and cascade)
        $this->db->commit();

        // Sync connection to ensure we see the deletion
        $this->syncSingletonConnectionOnly();
        
        // Start a new transaction for tearDown
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }

        // Student should be deleted (cascade)
        // Query using the test database connection directly
        // The cascade delete happens automatically when we delete the user
        // Since we committed the deletion, the cascade should have happened
        $stmt = $this->db->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$student['student_id']]);
        $result = $stmt->fetch();
        $this->assertFalse($result, 'Student should be deleted when user is deleted (cascade delete). Student ID: ' . $student['student_id']);
    }

    public function testTransactionRollback(): void
    {
        // Note: This test creates a nested transaction which may not work as expected
        // The parent setUp already starts a transaction
        $uniqueEmail = 'transaction' . (int)(microtime(true) * 1000000) . '@example.com';
        $this->createTestUser(['email' => $uniqueEmail]);
        
        // Rollback the parent transaction
        $this->db->rollBack();
        $this->db->beginTransaction(); // Restart for tearDown
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$uniqueEmail]);
        $this->assertFalse($stmt->fetch());
    }

    public function testUniqueConstraints(): void
    {
        $uniqueEmail = 'unique' . (int)(microtime(true) * 1000000) . '@example.com';
        $this->createTestUser(['email' => $uniqueEmail]);

        $this->expectException(\PDOException::class);
        $this->createTestUser(['email' => $uniqueEmail]);
    }
}
