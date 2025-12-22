<?php
namespace Tests\Unit\Models;

use Tests\TestCase;
use models\Student;
use models\User;
use PDO;

class StudentTest extends TestCase
{
    private Student $studentModel;
    private User $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDatabase();
        $this->runMigrations();
        $this->studentModel = new Student();
        $this->userModel = new User();
    }

    public function testCreateStudent(): void
    {
        $user = $this->createTestUser(['role' => 'student']);
        $uniqueNumber = 'STU' . (int)(microtime(true) * 1000000);

        $studentData = [
            'user_id' => $user['id'],
            'student_number' => $uniqueNumber,
            'gpa' => 3.75,
            'admission_date' => '2020-01-15',
            'major' => 'Computer Science',
            'minor' => 'Mathematics',
            'midterm_cardinality' => null,
            'final_cardinality' => null,
            'status' => 'active',
        ];

        // Commit test transaction before calling model method (which manages its own transaction)
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);
        
        $result = $this->studentModel->createStudent($studentData);
        $this->assertTrue($result);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);

        $student = $this->studentModel->findByUserId($user['id']);
        $this->assertNotNull($student);
        $this->assertEquals($uniqueNumber, $student['student_number']);
        $this->assertEquals(3.75, (float)$student['gpa']);
        $this->assertEquals('Computer Science', $student['major']);
    }

    public function testFindByUserId(): void
    {
        $student = $this->createTestStudent();

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);

        $found = $this->studentModel->findByUserId($student['user']['id']);
        $this->assertNotNull($found);
        $this->assertEquals($student['student_id'], $found['student_id']);
        $this->assertEquals($student['user']['email'], $found['email']);
    }

    public function testFindByStudentId(): void
    {
        $student = $this->createTestStudent();

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);

        $found = $this->studentModel->findByStudentId($student['student_id']);
        $this->assertNotNull($found);
        $this->assertEquals($student['student_id'], $found['student_id']);
    }

    public function testUpdateGPA(): void
    {
        $student = $this->createTestStudent(['gpa' => 3.0]);

        // Commit to make student visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);

        $result = $this->studentModel->updateGPA($student['student_id'], 3.8);
        $this->assertTrue($result);

        // Commit to make update visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);

        $updated = $this->studentModel->findByStudentId($student['student_id']);
        $this->assertEquals(3.8, (float)$updated['gpa']);
    }

    public function testCalculateGPA(): void
    {
        // Check if schedule table exists, if not skip this test
        $checkTable = $this->db->query("SHOW TABLES LIKE 'schedule'");
        if ($checkTable->rowCount() == 0) {
            $this->markTestSkipped('Schedule table does not exist in test database');
            return;
        }
        
        $student = $this->createTestStudent();
        $course = $this->createTestCourse(['credit_hours' => 3]);

        // Create a schedule/section and enrollment with grade
        // First check if we need a doctor
        $doctorCheck = $this->db->query("SELECT doctor_id FROM doctors LIMIT 1");
        $doctorId = 1;
        if ($doctorCheck->rowCount() > 0) {
            $doctor = $doctorCheck->fetch(PDO::FETCH_ASSOC);
            $doctorId = $doctor['doctor_id'];
        } else {
            // Create a test doctor if none exists
            $user = $this->createTestUser(['role' => 'doctor']);
            $this->db->exec("INSERT INTO doctors (user_id, department) VALUES ({$user['id']}, 'Computer Science')");
            $doctorId = (int)$this->db->lastInsertId();
        }

        $this->db->exec("
            INSERT INTO schedule (course_id, doctor_id, section_number, semester, academic_year, status)
            VALUES ({$course['course_id']}, {$doctorId}, '001', 'Fall', '2024', 'ongoing')
        ");
        $scheduleId = (int)$this->db->lastInsertId();

        $this->db->exec("
            INSERT INTO enrollments (student_id, section_id, status, final_grade)
            VALUES ({$student['student_id']}, {$scheduleId}, 'completed', 'A')
        ");

        // Commit enrollment and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);

        $gpa = $this->studentModel->calculateGPA($student['student_id']);
        $this->assertEquals(4.0, $gpa);
    }

    public function testGetAll(): void
    {
        $this->createTestStudent(['major' => 'Computer Science']);
        $this->createTestStudent(['major' => 'Mathematics']);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);

        $students = $this->studentModel->getAll();
        $this->assertGreaterThanOrEqual(2, count($students));
    }

    public function testGetAllWithFilters(): void
    {
        $this->createTestStudent(['major' => 'Computer Science', 'status' => 'active']);
        $this->createTestStudent(['major' => 'Mathematics', 'status' => 'inactive']);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);

        $activeStudents = $this->studentModel->getAll(['status' => 'active']);
        $this->assertGreaterThanOrEqual(1, count($activeStudents));

        $csStudents = $this->studentModel->getAll(['major' => 'Computer Science']);
        $this->assertGreaterThanOrEqual(1, count($csStudents));
    }

    public function testGetCount(): void
    {
        $this->createTestStudent();
        $this->createTestStudent();

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);

        $count = $this->studentModel->getCount();
        // Note: May have more students from other tests, but should have at least 2
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testGetThisMonthCount(): void
    {
        $this->createTestStudent();

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);

        $count = $this->studentModel->getThisMonthCount();
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testGetActiveCount(): void
    {
        $this->createTestStudent(['status' => 'active']);
        $this->createTestStudent(['status' => 'active']);
        $this->createTestStudent(['status' => 'inactive']);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);

        $count = $this->studentModel->getActiveCount();
        // Note: May have more active students from other tests, but should have at least 2
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testCreateStudentWithUser(): void
    {
        $uniqueEmail = 'newstudent' . (int)(microtime(true) * 1000000) . '@example.com';
        $userData = [
            'first_name' => 'New',
            'last_name' => 'Student',
            'email' => $uniqueEmail,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ];

        $uniqueNumber = 'STU' . (int)(microtime(true) * 1000000);
        $studentData = [
            'student_number' => $uniqueNumber,
            'gpa' => 3.5,
            'year_enrolled' => '2023',
            'major' => 'Engineering',
            'status' => 'active',
        ];

        // Commit test transaction before calling model method (which manages its own transaction)
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);
        $this->refreshModelConnections($this->userModel);

        $result = $this->studentModel->createStudentWithUser($userData, $studentData);
        $this->assertTrue($result);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);
        $this->refreshModelConnections($this->userModel);

        $user = $this->userModel->findByEmail($uniqueEmail);
        $this->assertNotNull($user);
        $this->assertEquals('student', $user['role']);

        $student = $this->studentModel->findByUserId($user['id']);
        $this->assertNotNull($student);
        // The model stores the student_number from the data provided
        $this->assertEquals($uniqueNumber, $student['student_number']);
    }

    public function testUpdateStudent(): void
    {
        $student = $this->createTestStudent();
        $uniqueEmail = 'updated' . (int)(microtime(true) * 1000000) . '@example.com';

        $userData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $uniqueEmail,
            'phone' => '9876543210',
        ];

        $uniqueNumber = 'STU' . (int)(microtime(true) * 1000000);
        $studentData = [
            'student_number' => $uniqueNumber,
            'gpa' => 4.0,
            'major' => 'Updated Major',
            'status' => 'active',
        ];

        // Commit test transaction and sync connection to make student visible
        $this->commitAndSync();
        
        // Refresh model connections to use synced connection
        $this->refreshModelConnections($this->studentModel);
        $this->refreshModelConnections($this->userModel);
        
        // Verify student exists before updating
        $found = $this->studentModel->findByStudentId($student['student_id']);
        $this->assertNotNull($found, 'Student should exist before update');

        $result = $this->studentModel->updateStudent($student['student_id'], $userData, $studentData);
        $this->assertTrue($result);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        
        // Refresh model connections to use synced connection
        $this->refreshModelConnections($this->studentModel);

        $updated = $this->studentModel->findByStudentId($student['student_id']);
        $this->assertEquals($uniqueNumber, $updated['student_number']);
        $this->assertEquals(4.0, (float)$updated['gpa']);
        $this->assertEquals('Updated Major', $updated['major']);
    }

    public function testDeleteStudent(): void
    {
        $student = $this->createTestStudent();
        $userId = $student['user']['id'];

        // Commit test transaction before calling model method
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);
        $this->refreshModelConnections($this->userModel);

        $result = $this->studentModel->deleteStudent($student['student_id']);
        $this->assertTrue($result);

        // Commit to make deletion visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);
        $this->refreshModelConnections($this->userModel);

        $deleted = $this->studentModel->findByStudentId($student['student_id']);
        $this->assertNull($deleted);

        // Note: Foreign key constraint cascades from users to students, not the reverse
        // So the user should still exist after deleting the student
        $user = $this->userModel->findById($userId);
        $this->assertNotNull($user, 'User should still exist after student deletion (cascade only works from users to students)');
    }

    public function testGetUniqueMajors(): void
    {
        // Create students with specific majors
        // These are inserted into the test transaction
        $student1 = $this->createTestStudent(['major' => 'Computer Science']);
        $student2 = $this->createTestStudent(['major' => 'Mathematics']);
        $student3 = $this->createTestStudent(['major' => 'Computer Science']);

        // Commit to make data visible and sync connection
        // This commits the test transaction so the data is visible to queries
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);
        
        // getUniqueMajors() uses $this->db->query() which uses the model's internal $db property
        // After commitAndSync() and refreshModelConnections(), the model's $db should be the test connection
        // But we need to ensure the model's connection is actually refreshed
        
        // Force refresh the model connection one more time to ensure it's using the committed connection
        $this->refreshModelConnections($this->studentModel);
        
        // Verify data exists directly first (for debugging)
        $directStmt = $this->db->query("SELECT DISTINCT major FROM students WHERE major IN ('Computer Science', 'Mathematics') AND major IS NOT NULL AND major != ''");
        $directMajors = array_column($directStmt->fetchAll(PDO::FETCH_ASSOC), 'major');
        
        // getUniqueMajors() queries ALL students in the database using $this->db->query()
        // Since other tests may have committed data (from createUser() autocommit mode),
        // we need to verify our specific majors exist in the results
        // The test should pass if our majors are in the list (even if there are others)
        $majors = $this->studentModel->getUniqueMajors();
        
        // If direct query found the majors but model query didn't, refresh connection and try again
        if (!empty($directMajors) && (empty($majors) || (!in_array('Computer Science', $majors) || !in_array('Mathematics', $majors)))) {
            $this->refreshModelConnections($this->studentModel);
            $majors = $this->studentModel->getUniqueMajors();
        }
        
        $this->assertContains('Computer Science', $majors, 'Majors should contain Computer Science. Found: ' . implode(', ', $majors) . '. Direct query found: ' . implode(', ', $directMajors));
        $this->assertContains('Mathematics', $majors, 'Majors should contain Mathematics. Found: ' . implode(', ', $majors) . '. Direct query found: ' . implode(', ', $directMajors));
    }

    public function testGetUniqueYears(): void
    {
        // Create students with specific admission dates
        // These are inserted into the test transaction
        $student1 = $this->createTestStudent(['admission_date' => '2020-01-01']);
        $student2 = $this->createTestStudent(['admission_date' => '2021-01-01']);

        // Commit to make data visible and sync connection
        // This commits the test transaction so the data is visible to queries
        $this->commitAndSync();
        $this->refreshModelConnections($this->studentModel);
        
        // getUniqueYears() uses $this->db->query() which uses the model's internal $db property
        // After commitAndSync() and refreshModelConnections(), the model's $db should be the test connection
        // But we need to ensure the model's connection is actually refreshed
        
        // Force refresh the model connection one more time to ensure it's using the committed connection
        $this->refreshModelConnections($this->studentModel);
        
        // Verify data exists directly first (for debugging)
        $directStmt = $this->db->query("SELECT DISTINCT YEAR(admission_date) as year FROM students WHERE admission_date IN ('2020-01-01', '2021-01-01') AND admission_date IS NOT NULL");
        $directYears = array_map('strval', array_column($directStmt->fetchAll(PDO::FETCH_ASSOC), 'year'));
        
        // getUniqueYears() queries ALL students in the database using $this->db->query()
        // Since other tests may have committed data (from createUser() autocommit mode),
        // we need to verify our specific years exist in the results
        // The test should pass if our years are in the list (even if there are others)
        $years = $this->studentModel->getUniqueYears();
        // YEAR() returns integer, so array_column will return integers
        // Convert to strings for comparison
        $yearsAsStrings = array_map('strval', $years);
        
        // If direct query found the years but model query didn't, refresh connection and try again
        if (!empty($directYears) && (empty($yearsAsStrings) || (!in_array('2020', $yearsAsStrings) || !in_array('2021', $yearsAsStrings)))) {
            $this->refreshModelConnections($this->studentModel);
            $years = $this->studentModel->getUniqueYears();
            $yearsAsStrings = array_map('strval', $years);
        }
        
        $this->assertContains('2020', $yearsAsStrings, 'Years should contain 2020. Found: ' . implode(', ', $yearsAsStrings) . '. Direct query found: ' . implode(', ', $directYears));
        $this->assertContains('2021', $yearsAsStrings, 'Years should contain 2021. Found: ' . implode(', ', $yearsAsStrings) . '. Direct query found: ' . implode(', ', $directYears));
    }
}
