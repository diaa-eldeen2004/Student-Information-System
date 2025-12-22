<?php
namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use PDO;
use patterns\Singleton\DatabaseConnection;

/**
 * Base Test Case for all tests
 * Provides common setup and teardown methods
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected PDO $db;
    protected array $config;
    protected string $testDbName = 'swe_app_test';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Suppress output to prevent "headers already sent" errors
        ob_start();
        
        // Load test database configuration
        $this->config = require dirname(__DIR__) . '/app/config/database.php';
        $this->config['database'] = $this->testDbName;
        
        // Get database connection
        $this->db = $this->getTestDatabaseConnection();
        
        // Reset singleton connection for testing
        $this->resetDatabaseSingleton();
        
        // Start transaction for test isolation
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction to clean up test data
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        
        // Reset singleton
        $this->resetDatabaseSingleton();
        
        // Clean output buffer
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        parent::tearDown();
    }

    /**
     * Get test database connection
     */
    protected function getTestDatabaseConnection(): PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['port'],
            $this->testDbName,
            $this->config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => false,
        ];

        $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
        
        // Override singleton connection for tests to use test database
        $this->syncSingletonConnection($pdo);
        
        return $pdo;
    }

    /**
     * Sync singleton connection with test database connection
     */
    protected function syncSingletonConnection(PDO $pdo): void
    {
        try {
            $reflection = new \ReflectionClass(DatabaseConnection::class);
            $instance = $reflection->getProperty('instance');
            $instance->setAccessible(true);
            $dbInstance = $instance->getValue();
            
            if ($dbInstance) {
                $connectionProp = $reflection->getProperty('connection');
                $connectionProp->setAccessible(true);
                $connectionProp->setValue($dbInstance, $pdo);
            } else {
                // Create new instance and set connection
                $dbInstance = DatabaseConnection::getInstance();
                $connectionProp = $reflection->getProperty('connection');
                $connectionProp->setAccessible(true);
                $connectionProp->setValue($dbInstance, $pdo);
            }
        } catch (\ReflectionException $e) {
            // If reflection fails, continue with direct connection
            error_log("Warning: Could not override singleton connection: " . $e->getMessage());
        }
    }

    /**
     * Commit transaction and sync singleton connection, then restart transaction
     */
    protected function commitAndSync(): void
    {
        // Only commit if there's an active transaction
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
        $this->syncSingletonConnection($this->db);
        // Only start transaction if not already in one
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    /**
     * Sync singleton connection without committing/restarting transaction
     * Useful when model methods manage their own transactions
     */
    protected function syncSingletonConnectionOnly(): void
    {
        $this->syncSingletonConnection($this->db);
    }

    /**
     * Re-create model instances to use the synced connection
     * Call this after commitAndSync() if models need to see committed data
     */
    protected function refreshModelConnections(object &$model): void
    {
        // Use reflection to update the model's db property
        try {
            $reflection = new \ReflectionClass($model);
            $dbProperty = $reflection->getProperty('db');
            $dbProperty->setAccessible(true);
            $dbProperty->setValue($model, $this->db);
        } catch (\ReflectionException $e) {
            // If reflection fails, model will use singleton connection
        }
    }

    /**
     * Reset database singleton connection
     */
    protected function resetDatabaseSingleton(): void
    {
        // Use reflection to reset singleton instance
        $reflection = new \ReflectionClass(DatabaseConnection::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    /**
     * Create test database if it doesn't exist
     */
    protected function createTestDatabase(): void
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['port'],
            $this->config['charset']
        );

        $pdo = new PDO($dsn, $this->config['username'], $this->config['password']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->testDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Drop test database
     */
    protected function dropTestDatabase(): void
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['port'],
            $this->config['charset']
        );

        $pdo = new PDO($dsn, $this->config['username'], $this->config['password']);
        $pdo->exec("DROP DATABASE IF EXISTS `{$this->testDbName}`");
    }

    /**
     * Run database migrations for test database
     */
    protected function runMigrations(): void
    {
        $schemaFile = dirname(__DIR__) . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            // Replace database name in SQL
            $sql = str_replace('swe_app', $this->testDbName, $sql);
            
            // Split SQL into individual statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && !preg_match('/^--/', $stmt) && !preg_match('/^\/\*/', $stmt);
                }
            );
            
            foreach ($statements as $statement) {
                try {
                    if (!empty(trim($statement))) {
                        $this->db->exec($statement);
                    }
                } catch (\PDOException $e) {
                    // Ignore errors for existing tables/columns
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate') === false) {
                        // Only log non-duplicate errors
                        error_log("Migration warning: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Truncate all tables in test database
     */
    protected function truncateAllTables(): void
    {
        $tables = [
            'assignment_submissions',
            'assignments',
            'attendance',
            'enrollment_requests',
            'enrollments',
            'materials',
            'notifications',
            'audit_logs',
            'calendar_events',
            'reports',
            'schedule',
            'course_prerequisites',
            'courses',
            'students',
            'doctors',
            'it_officers',
            'admins',
            'users'
        ];

        $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            try {
                $this->db->exec("TRUNCATE TABLE `{$table}`");
            } catch (\PDOException $e) {
                // Table might not exist, ignore
            }
        }
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Create a test user
     */
    protected function createTestUser(array $data = []): array
    {
        // Use microtime for truly unique emails
        $uniqueId = (int)(microtime(true) * 1000000);
        $defaultData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test' . $uniqueId . '@example.com',
            'phone' => '1234567890',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user',
        ];

        $userData = array_merge($defaultData, $data);

        $stmt = $this->db->prepare("
            INSERT INTO users (first_name, last_name, email, phone, password, role)
            VALUES (:first_name, :last_name, :email, :phone, :password, :role)
        ");

        // Only pass the required parameters to execute
        $params = [
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'],
            'password' => $userData['password'],
            'role' => $userData['role'],
        ];
        
        $stmt->execute($params);
        $userId = (int)$this->db->lastInsertId();

        return array_merge($userData, ['id' => $userId]);
    }

    /**
     * Create a test student
     */
    protected function createTestStudent(array $userData = [], array $studentData = []): array
    {
        $user = $this->createTestUser(array_merge(['role' => 'student'], $userData));

        $uniqueId = (int)(microtime(true) * 1000000);
        $defaultStudentData = [
            'user_id' => $user['id'],
            'student_number' => 'STU' . $uniqueId,
            'gpa' => 3.5,
            'admission_date' => date('Y-m-d'),
            'major' => 'Computer Science',
            'minor' => null,
            'status' => 'active',
        ];

        $studentData = array_merge($defaultStudentData, $studentData);
        
        // Ensure all required fields are present (Student model expects these)
        if (!isset($studentData['midterm_cardinality'])) {
            $studentData['midterm_cardinality'] = null;
        }
        if (!isset($studentData['final_cardinality'])) {
            $studentData['final_cardinality'] = null;
        }

        $stmt = $this->db->prepare("
            INSERT INTO students (user_id, student_number, gpa, admission_date, major, minor, midterm_cardinality, final_cardinality, status)
            VALUES (:user_id, :student_number, :gpa, :admission_date, :major, :minor, :midterm_cardinality, :final_cardinality, :status)
        ");

        // Only pass the required parameters to execute
        $params = [
            'user_id' => $studentData['user_id'],
            'student_number' => $studentData['student_number'],
            'gpa' => $studentData['gpa'],
            'admission_date' => $studentData['admission_date'],
            'major' => $studentData['major'],
            'minor' => $studentData['minor'],
            'midterm_cardinality' => $studentData['midterm_cardinality'],
            'final_cardinality' => $studentData['final_cardinality'],
            'status' => $studentData['status'],
        ];
        
        $stmt->execute($params);
        $studentId = (int)$this->db->lastInsertId();

        return array_merge($studentData, ['student_id' => $studentId, 'user' => $user]);
    }

    /**
     * Create a test course
     */
    protected function createTestCourse(array $data = []): array
    {
        // Use microtime for truly unique course codes
        $uniqueId = (int)(microtime(true) * 1000000);
        $defaultData = [
            'course_code' => 'CS' . $uniqueId,
            'name' => 'Test Course',
            'description' => 'Test Course Description',
            'credit_hours' => 3,
            'department' => 'Computer Science',
        ];

        $courseData = array_merge($defaultData, $data);

        $stmt = $this->db->prepare("
            INSERT INTO courses (course_code, name, description, credit_hours, department)
            VALUES (:course_code, :name, :description, :credit_hours, :department)
        ");

        $stmt->execute($courseData);
        $courseId = (int)$this->db->lastInsertId();

        return array_merge($courseData, ['course_id' => $courseId]);
    }

    /**
     * Mock session
     */
    protected function mockSession(array $data = []): void
    {
        $_SESSION = array_merge([
            'user' => [
                'id' => 1,
                'email' => 'test@example.com',
                'role' => 'user',
                'first_name' => 'Test',
                'last_name' => 'User',
            ]
        ], $data);
    }

    /**
     * Clear session
     */
    protected function clearSession(): void
    {
        $_SESSION = [];
    }
}
