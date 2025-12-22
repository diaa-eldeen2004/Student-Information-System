<?php
namespace Tests\Unit\Models;

use Tests\TestCase;
use models\Course;

class CourseTest extends TestCase
{
    private Course $courseModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDatabase();
        $this->runMigrations();
        $this->courseModel = new Course();
    }

    public function testCreateCourse(): void
    {
        $uniqueCode = 'CS' . (int)(microtime(true) * 1000000);
        $courseData = [
            'course_code' => $uniqueCode,
            'name' => 'Introduction to Computer Science',
            'description' => 'Basic computer science concepts',
            'credit_hours' => 3,
            'department' => 'Computer Science',
        ];

        // Commit test transaction before calling model method (which manages its own transaction)
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $result = $this->courseModel->create($courseData);
        $this->assertTrue($result);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $course = $this->courseModel->findByCode($uniqueCode);
        $this->assertNotNull($course);
        $this->assertEquals($uniqueCode, $course['course_code']);
        $this->assertEquals('Introduction to Computer Science', $course['name']);
        $this->assertEquals(3, (int)$course['credit_hours']);
    }

    public function testFindById(): void
    {
        $course = $this->createTestCourse();

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $found = $this->courseModel->findById($course['course_id']);
        $this->assertNotNull($found);
        $this->assertEquals($course['course_id'], $found['course_id']);
        $this->assertEquals($course['course_code'], $found['course_code']);
    }

    public function testFindByCode(): void
    {
        $uniqueCode = 'MATH' . (int)(microtime(true) * 1000000);
        $course = $this->createTestCourse(['course_code' => $uniqueCode]);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $found = $this->courseModel->findByCode($uniqueCode);
        $this->assertNotNull($found);
        $this->assertEquals($course['course_id'], $found['course_id']);
    }

    public function testGetAll(): void
    {
        $this->createTestCourse();
        $this->createTestCourse();

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $courses = $this->courseModel->getAll();
        $this->assertGreaterThanOrEqual(2, count($courses));
    }

    public function testAddPrerequisite(): void
    {
        $course1 = $this->createTestCourse();
        $course2 = $this->createTestCourse();

        // Commit to make courses visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $result = $this->courseModel->addPrerequisite($course2['course_id'], $course1['course_id']);
        $this->assertTrue($result);

        // Commit to make prerequisite visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $prerequisites = $this->courseModel->getPrerequisites($course2['course_id']);
        $this->assertCount(1, $prerequisites);
        $this->assertEquals($course1['course_id'], $prerequisites[0]['course_id']);
    }

    public function testGetPrerequisites(): void
    {
        $course1 = $this->createTestCourse();
        $course2 = $this->createTestCourse();
        $course3 = $this->createTestCourse();

        // Commit to make courses visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $this->courseModel->addPrerequisite($course2['course_id'], $course1['course_id']);
        $this->courseModel->addPrerequisite($course3['course_id'], $course2['course_id']);

        // Commit to make prerequisites visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $prerequisites = $this->courseModel->getPrerequisites($course3['course_id']);
        $this->assertCount(1, $prerequisites);
        $this->assertEquals($course2['course_id'], $prerequisites[0]['course_id']);
    }

    public function testCheckPrerequisites(): void
    {
        // Check if schedule table exists, if not skip this test
        $checkTable = $this->db->query("SHOW TABLES LIKE 'schedule'");
        if ($checkTable->rowCount() == 0) {
            $this->markTestSkipped('Schedule table does not exist in test database');
            return;
        }

        $student = $this->createTestStudent();
        $course1 = $this->createTestCourse();
        $course2 = $this->createTestCourse();

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        // Add prerequisite
        $this->courseModel->addPrerequisite($course2['course_id'], $course1['course_id']);

        // Commit prerequisite and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        // Student hasn't completed prerequisite
        $result = $this->courseModel->checkPrerequisites($student['student_id'], $course2['course_id']);
        $this->assertFalse($result);

        // Create a doctor if needed
        $doctorCheck = $this->db->query("SELECT doctor_id FROM doctors LIMIT 1");
        $doctorId = 1;
        if ($doctorCheck->rowCount() > 0) {
            $doctor = $doctorCheck->fetch(PDO::FETCH_ASSOC);
            $doctorId = $doctor['doctor_id'];
        } else {
            $user = $this->createTestUser(['role' => 'doctor']);
            $this->db->exec("INSERT INTO doctors (user_id, department) VALUES ({$user['id']}, 'Computer Science')");
            $doctorId = (int)$this->db->lastInsertId();
        }

        // Create enrollment with completed grade
        $this->db->exec("
            INSERT INTO schedule (course_id, doctor_id, section_number, semester, academic_year, status)
            VALUES ({$course1['course_id']}, {$doctorId}, '001', 'Fall', '2024', 'ongoing')
        ");
        $scheduleId = (int)$this->db->lastInsertId();

        $this->db->exec("
            INSERT INTO enrollments (student_id, section_id, status, final_grade)
            VALUES ({$student['student_id']}, {$scheduleId}, 'completed', 'A')
        ");

        // Commit enrollment and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        // Now student has completed prerequisite
        $result = $this->courseModel->checkPrerequisites($student['student_id'], $course2['course_id']);
        $this->assertTrue($result);
    }

    public function testUpdateCourse(): void
    {
        $course = $this->createTestCourse();
        // Limit to 20 chars (VARCHAR(20) constraint) - 'UPDATED' (6) + max 14 digits
        $uniqueCode = 'UPD' . (int)(microtime(true) * 1000000);
        // Ensure it doesn't exceed 20 characters
        if (strlen($uniqueCode) > 20) {
            $uniqueCode = substr($uniqueCode, 0, 20);
        }

        $updateData = [
            'course_id' => $course['course_id'],
            'course_code' => $uniqueCode,
            'name' => 'Updated Course Name',
            'description' => 'Updated description',
            'credit_hours' => 4,
            'department' => 'Updated Department',
        ];

        // Commit test transaction before calling model method
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $result = $this->courseModel->update($updateData);
        $this->assertTrue($result);

        // Commit to make update visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $updated = $this->courseModel->findById($course['course_id']);
        $this->assertEquals($uniqueCode, $updated['course_code']);
        $this->assertEquals('Updated Course Name', $updated['name']);
        $this->assertEquals(4, (int)$updated['credit_hours']);
    }

    public function testDeleteCourse(): void
    {
        $course = $this->createTestCourse();

        // Commit test transaction before calling model method
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $result = $this->courseModel->delete($course['course_id']);
        $this->assertTrue($result);

        // Commit to make deletion visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $deleted = $this->courseModel->findById($course['course_id']);
        $this->assertNull($deleted);
    }

    public function testGetCoursesWithFilters(): void
    {
        $this->createTestCourse(['department' => 'Computer Science']);
        $this->createTestCourse(['department' => 'Mathematics']);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $csCourses = $this->courseModel->getCoursesWithFilters(['department' => 'Computer Science']);
        $this->assertGreaterThanOrEqual(1, count($csCourses));

        $searchResults = $this->courseModel->getCoursesWithFilters(['search' => 'CS']);
        $this->assertGreaterThanOrEqual(1, count($searchResults));
    }

    public function testGetUniqueDepartments(): void
    {
        $this->createTestCourse(['department' => 'Computer Science']);
        $this->createTestCourse(['department' => 'Mathematics']);
        $this->createTestCourse(['department' => 'Computer Science']);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $departments = $this->courseModel->getUniqueDepartments();
        $this->assertContains('Computer Science', $departments);
        $this->assertContains('Mathematics', $departments);
    }

    public function testGetCount(): void
    {
        $this->createTestCourse();
        $this->createTestCourse();

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $count = $this->courseModel->getCount();
        // Note: May have more courses from other tests, but should have at least 2
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testGetThisMonthCount(): void
    {
        $this->createTestCourse();

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->courseModel);

        $count = $this->courseModel->getThisMonthCount();
        $this->assertGreaterThanOrEqual(1, $count);
    }
}
