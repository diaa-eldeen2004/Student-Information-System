<?php
namespace models;

use core\Model;
use PDO;

class Student extends Model
{
    private string $table = 'students';

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT s.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} s 
                                    JOIN users u ON s.user_id = u.id 
                                    WHERE s.user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        return $student ?: null;
    }

    public function findByStudentId(int $studentId): ?array
    {
        $stmt = $this->db->prepare("SELECT s.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} s 
                                    JOIN users u ON s.user_id = u.id 
                                    WHERE s.student_id = :student_id LIMIT 1");
        $stmt->execute(['student_id' => $studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        return $student ?: null;
    }

    public function createStudent(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} (user_id, student_number, gpa, admission_date, major, minor, midterm_cardinality, final_cardinality, status)
                    VALUES (:user_id, :student_number, :gpa, :admission_date, :major, :minor, :midterm_cardinality, :final_cardinality, :status)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'user_id' => $data['user_id'],
                'student_number' => $data['student_number'] ?? null,
                'gpa' => $data['gpa'] ?? 0.00,
                'admission_date' => $data['admission_date'] ?? null,
                'major' => $data['major'] ?? null,
                'minor' => $data['minor'] ?? null,
                'midterm_cardinality' => $data['midterm_cardinality'] ?? null,
                'final_cardinality' => $data['final_cardinality'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Student creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateGPA(int $studentId, float $gpa): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET gpa = :gpa WHERE student_id = :student_id");
            return $stmt->execute([
                'gpa' => $gpa,
                'student_id' => $studentId,
            ]);
        } catch (\PDOException $e) {
            error_log("GPA update failed: " . $e->getMessage());
            return false;
        }
    }

    public function calculateGPA(int $studentId): float
    {
        // Get all completed enrollments with grades
        $stmt = $this->db->prepare("
            SELECT e.final_grade, c.credit_hours
            FROM enrollments e
            JOIN sections s ON e.section_id = s.section_id
            JOIN courses c ON s.course_id = c.course_id
            WHERE e.student_id = :student_id 
            AND e.status = 'completed'
            AND e.final_grade IS NOT NULL
        ");
        $stmt->execute(['student_id' => $studentId]);
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($grades)) {
            return 0.00;
        }

        // Grade point mapping
        $gradePoints = [
            'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
            'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
            'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
            'D+' => 1.3, 'D' => 1.0, 'D-' => 0.7,
            'F' => 0.0
        ];

        $totalPoints = 0;
        $totalCredits = 0;

        foreach ($grades as $grade) {
            $letterGrade = strtoupper(trim($grade['final_grade']));
            $credits = (float)$grade['credit_hours'];
            
            if (isset($gradePoints[$letterGrade])) {
                $totalPoints += $gradePoints[$letterGrade] * $credits;
                $totalCredits += $credits;
            }
        }

        return $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0.00;
    }

    public function getAll(array $filters = []): array
    {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR s.student_number LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['status'])) {
            $where .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['major'])) {
            $where .= " AND s.major = ?";
            $params[] = $filters['major'];
        }
        
        if (!empty($filters['year_enrolled'])) {
            $where .= " AND YEAR(s.admission_date) = ?";
            $params[] = $filters['year_enrolled'];
        }

        $sql = "SELECT s.*, u.first_name, u.last_name, u.email, u.phone 
                FROM {$this->table} s 
                JOIN users u ON s.user_id = u.id 
                $where
                ORDER BY s.created_at DESC 
                LIMIT 100";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllStudents(): array
    {
        return $this->getAll();
    }

    public function findById(int $studentId): ?array
    {
        return $this->findByStudentId($studentId);
    }

    public function getCount(array $filters = []): int
    {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR s.student_number LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['status'])) {
            $where .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['major'])) {
            $where .= " AND s.major = ?";
            $params[] = $filters['major'];
        }
        
        if (!empty($filters['year_enrolled'])) {
            $where .= " AND YEAR(s.admission_date) = ?";
            $params[] = $filters['year_enrolled'];
        }

        $sql = "SELECT COUNT(*) as cnt 
                                  FROM {$this->table} s 
                                  JOIN users u ON s.user_id = u.id 
                $where";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getThisMonthCount(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt 
                                    FROM {$this->table} 
                                    WHERE YEAR(created_at) = YEAR(CURRENT_DATE()) 
                                    AND MONTH(created_at) = MONTH(CURRENT_DATE())");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getActiveCount(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM {$this->table} WHERE status = 'active'");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function createStudentWithUser(array $userData, array $studentData): bool
    {
        try {
            // Ensure no active transaction before starting a new one
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->db->beginTransaction();

            // Normalize email to lowercase
            $email = trim(strtolower($userData['email'] ?? ''));
            if (empty($email)) {
                throw new \InvalidArgumentException('Email is required and cannot be empty');
            }

            // First create the user
            $userSql = "INSERT INTO users (first_name, last_name, email, phone, password, role)
                        VALUES (:first_name, :last_name, :email, :phone, :password, 'student')";
            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $email,
                'phone' => $userData['phone'] ?? '',
                'password' => $userData['password'],
            ]);

            $userId = $this->db->lastInsertId();
            
            // CRITICAL: If lastInsertId returns 0/false, query the database directly
            // This can happen in some MySQL configurations or transaction scenarios
            if (!$userId || $userId == 0) {
                $checkStmt = $this->db->prepare("SELECT id FROM users WHERE email = :email ORDER BY id DESC LIMIT 1");
                $checkStmt->execute(['email' => $email]);
                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($checkResult && isset($checkResult['id'])) {
                    $userId = (int)$checkResult['id'];
                } else {
                    throw new \PDOException('Failed to get user ID after creation. lastInsertId returned: ' . ($userId ?: '0/false') . ' and query by email also failed.');
                }
            }

            // Then create the student record
            $admissionDate = null;
            if (!empty($studentData['year_enrolled'])) {
                // Convert year_enrolled to admission_date (use January 1st of that year)
                $admissionDate = $studentData['year_enrolled'] . '-01-01';
            }
            
            $studentSql = "INSERT INTO {$this->table} (user_id, student_number, gpa, admission_date, major, minor, midterm_cardinality, final_cardinality, status)
                          VALUES (:user_id, :student_number, :gpa, :admission_date, :major, :minor, :midterm_cardinality, :final_cardinality, :status)";
            $studentStmt = $this->db->prepare($studentSql);
            $studentStmt->execute([
                'user_id' => $userId,
                'student_number' => $studentData['student_number'] ?? null,
                'gpa' => $studentData['gpa'] ?? 0.00,
                'admission_date' => $admissionDate,
                'major' => $studentData['major'] ?? null,
                'minor' => $studentData['minor'] ?? null,
                'midterm_cardinality' => $studentData['midterm_cardinality'] ?? null,
                'final_cardinality' => $studentData['final_cardinality'] ?? null,
                'status' => $studentData['status'] ?? 'active',
            ]);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Student creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateStudent(int $studentId, array $userData, array $studentData): bool
    {
        try {
            // Ensure no active transaction before starting a new one
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->db->beginTransaction();

            // Get user_id from student_id
            $student = $this->findByStudentId($studentId);
            if (!$student) {
                throw new \RuntimeException("Student not found");
            }

            // Update user data
            $userSql = "UPDATE users SET 
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        phone = :phone
                        WHERE id = :user_id";
            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'] ?? '',
                'user_id' => $student['user_id'],
            ]);

            // Update password if provided
            if (!empty($userData['password'])) {
                $passwordSql = "UPDATE users SET password = :password WHERE id = :user_id";
                $passwordStmt = $this->db->prepare($passwordSql);
                $passwordStmt->execute([
                    'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
                    'user_id' => $student['user_id'],
                ]);
            }

            // Update student data
            $admissionDate = null;
            if (!empty($studentData['year_enrolled'])) {
                // Convert year_enrolled to admission_date (use January 1st of that year)
                $admissionDate = $studentData['year_enrolled'] . '-01-01';
            }
            
            // Build UPDATE query dynamically - only update password fields if provided
            $updateFields = [
                'student_number = :student_number',
                'gpa = :gpa',
                'admission_date = :admission_date',
                'major = :major',
                'minor = :minor',
                'status = :status'
            ];
            
            $params = [
                'student_number' => $studentData['student_number'] ?? null,
                'gpa' => $studentData['gpa'] ?? 0.00,
                'admission_date' => $admissionDate,
                'major' => $studentData['major'] ?? null,
                'minor' => $studentData['minor'] ?? null,
                'status' => $studentData['status'] ?? 'active',
                'student_id' => $studentId,
            ];
            
            // Only update password fields if they are provided (not empty)
            if (!empty($studentData['midterm_cardinality'])) {
                $updateFields[] = 'midterm_cardinality = :midterm_cardinality';
                $params['midterm_cardinality'] = $studentData['midterm_cardinality'];
            }
            
            if (!empty($studentData['final_cardinality'])) {
                $updateFields[] = 'final_cardinality = :final_cardinality';
                $params['final_cardinality'] = $studentData['final_cardinality'];
            }
            
            $studentSql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE student_id = :student_id";
            $studentStmt = $this->db->prepare($studentSql);
            $studentStmt->execute($params);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Student update failed: " . $e->getMessage());
            // Re-throw to get error message in controller
            throw $e;
        }
    }

    public function deleteStudent(int $studentId): bool
    {
        try {
            // Cascade delete will handle user deletion
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE student_id = :student_id");
            return $stmt->execute(['student_id' => $studentId]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Student deletion failed: " . $e->getMessage());
            return false;
        }
    }

    public function getUniqueMajors(): array
    {
        try {
            $stmt = $this->db->query("SELECT DISTINCT major FROM {$this->table} WHERE major IS NOT NULL AND major != '' ORDER BY major");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($results, 'major');
        } catch (\PDOException $e) {
            error_log("getUniqueMajors failed: " . $e->getMessage());
            return [];
        }
    }

    public function getUniqueYears(): array
    {
        // Extract years from admission_date column
        try {
            $stmt = $this->db->query("SELECT DISTINCT YEAR(admission_date) as year FROM {$this->table} WHERE admission_date IS NOT NULL ORDER BY year DESC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($results, 'year');
        } catch (\PDOException $e) {
            error_log("getUniqueYears failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if student is enrolled in any schedule for the given semester/year
     */
    public function isEnrolledInAnySchedule(int $studentId, string $semester, string $academicYear): bool
    {
        try {
            // Check if enrollments has schedule_id column
            $hasScheduleId = false;
            try {
                $checkStmt = $this->db->query("SHOW COLUMNS FROM enrollments LIKE 'schedule_id'");
                $hasScheduleId = $checkStmt->rowCount() > 0;
            } catch (\PDOException $e) {
                $hasScheduleId = false;
            }
            
            // Build JOIN condition based on available columns
            if ($hasScheduleId) {
                $joinCondition = "e.schedule_id = s.schedule_id";
            } else {
                $joinCondition = "e.section_id = s.schedule_id";
            }
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM enrollments e
                JOIN schedule s ON {$joinCondition}
                WHERE e.student_id = :student_id
                AND e.status = 'enrolled'
                AND s.semester = :semester
                AND s.academic_year = :academic_year
            ");
            $stmt->execute([
                'student_id' => $studentId,
                'semester' => $semester,
                'academic_year' => $academicYear
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0) > 0;
        } catch (\PDOException $e) {
            error_log("isEnrolledInAnySchedule failed: " . $e->getMessage());
            return false;
        }
    }

    public function getEnrolledCourses(int $studentId, ?string $semester = null, ?string $academicYear = null): array
    {
        try {
            // Check if enrollments has schedule_id column
            $hasScheduleId = false;
            try {
                $checkStmt = $this->db->query("SHOW COLUMNS FROM enrollments LIKE 'schedule_id'");
                $hasScheduleId = $checkStmt->rowCount() > 0;
            } catch (\PDOException $e) {
                $hasScheduleId = false;
            }
            
            // Check if sections table exists, otherwise use schedule table
            $hasSectionsTable = false;
            try {
                $checkStmt = $this->db->query("SHOW TABLES LIKE 'sections'");
                $hasSectionsTable = $checkStmt->rowCount() > 0;
            } catch (\PDOException $e) {
                $hasSectionsTable = false;
            }
            
            $where = ["e.student_id = :student_id", "e.status = 'enrolled'"];
            $params = ['student_id' => $studentId];
            
            if ($semester) {
                $where[] = "s.semester = :semester";
                $params['semester'] = $semester;
            }
            
            if ($academicYear) {
                $where[] = "s.academic_year = :academic_year";
                $params['academic_year'] = $academicYear;
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Build join based on database structure
            if ($hasSectionsTable) {
                // Old structure: sections table exists
                $scheduleJoin = "JOIN sections s ON e.section_id = s.section_id";
            } else {
                // New structure: using schedule table
                if ($hasScheduleId) {
                    $scheduleJoin = "JOIN schedule s ON e.schedule_id = s.schedule_id";
                } else {
                    $scheduleJoin = "JOIN schedule s ON e.section_id = s.schedule_id";
                }
            }
            
            // Check if schedule table has course_ids column (for weekly schedules with multiple courses)
            $hasCourseIds = false;
            try {
                $checkStmt = $this->db->query("SHOW COLUMNS FROM schedule LIKE 'course_ids'");
                $hasCourseIds = $checkStmt->rowCount() > 0;
            } catch (\PDOException $e) {
                $hasCourseIds = false;
            }
            
            $selectFields = "c.course_id, c.course_code, c.name as course_name, c.credit_hours, c.description,
                       s.schedule_id as section_id, s.section_number, s.semester, s.academic_year, s.room, s.time_slot,
                       s.day_of_week, s.start_time, s.end_time,
                       u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                       e.enrollment_date, e.status as enrollment_status";
            
            if ($hasCourseIds && !$hasSectionsTable) {
                $selectFields .= ", s.course_ids, s.is_weekly";
            }
            
            $stmt = $this->db->prepare("
                SELECT {$selectFields}
                FROM enrollments e
                {$scheduleJoin}
                JOIN courses c ON s.course_id = c.course_id
                JOIN doctors d ON s.doctor_id = d.doctor_id
                JOIN users u ON d.user_id = u.id
                WHERE {$whereClause}
                ORDER BY s.semester DESC, s.academic_year DESC, c.course_code
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Expand courses if schedule has multiple courses in course_ids JSON
            $expandedResults = [];
            foreach ($results as $entry) {
                // Check if this schedule has multiple courses
                if ($hasCourseIds && !empty($entry['course_ids']) && ($entry['is_weekly'] ?? 0) == 1) {
                    $courseIds = json_decode($entry['course_ids'], true);
                    if (is_array($courseIds) && count($courseIds) > 1) {
                        // Create an entry for each course
                        foreach ($courseIds as $courseId) {
                            if ($courseId == $entry['course_id']) {
                                // Main course already in result, add it
                                $expandedResults[] = $entry;
                            } else {
                                // Get details for additional course
                                $courseStmt = $this->db->prepare("SELECT course_id, course_code, name as course_name, credit_hours, description FROM courses WHERE course_id = :course_id");
                                $courseStmt->execute(['course_id' => $courseId]);
                                $additionalCourse = $courseStmt->fetch(PDO::FETCH_ASSOC);
                                if ($additionalCourse) {
                                    $courseEntry = $entry;
                                    $courseEntry['course_id'] = $additionalCourse['course_id'];
                                    $courseEntry['course_code'] = $additionalCourse['course_code'];
                                    $courseEntry['course_name'] = $additionalCourse['course_name'];
                                    $courseEntry['credit_hours'] = $additionalCourse['credit_hours'];
                                    $courseEntry['description'] = $additionalCourse['description'];
                                    $expandedResults[] = $courseEntry;
                                }
                            }
                        }
                    } else {
                        // Single course or invalid JSON, add as is
                        $expandedResults[] = $entry;
                    }
                } else {
                    // No course_ids or not weekly, add as is
                    $expandedResults[] = $entry;
                }
            }
            
            // Remove duplicates based on course_id + section_id combination
            $uniqueCourses = [];
            foreach ($expandedResults as $course) {
                $key = $course['course_id'] . '_' . ($course['section_id'] ?? '');
                if (!isset($uniqueCourses[$key])) {
                    $uniqueCourses[$key] = $course;
                }
            }
            
            return array_values($uniqueCourses);
        } catch (\PDOException $e) {
            error_log("getEnrolledCourses failed: " . $e->getMessage());
            error_log("getEnrolledCourses error trace: " . $e->getTraceAsString());
            return [];
        }
    }

    public function getAssignmentsForStudent(int $studentId, ?int $courseId = null): array
    {
        try {
            // Check if enrollments has schedule_id column
            $hasScheduleId = false;
            try {
                $checkStmt = $this->db->query("SHOW COLUMNS FROM enrollments LIKE 'schedule_id'");
                $hasScheduleId = $checkStmt->rowCount() > 0;
            } catch (\PDOException $e) {
                $hasScheduleId = false;
            }
            
            // Check if sections table exists, otherwise use schedule table
            $hasSectionsTable = false;
            try {
                $checkStmt = $this->db->query("SHOW TABLES LIKE 'sections'");
                $hasSectionsTable = $checkStmt->rowCount() > 0;
            } catch (\PDOException $e) {
                $hasSectionsTable = false;
            }
            
            $where = [
                "e.student_id = :student_id",
                "e.status = 'enrolled'",
                "(a.is_visible IS NULL OR a.is_visible = 1)",
                "(a.visible_until IS NULL OR a.visible_until >= NOW())"
            ];
            $params = ['student_id' => $studentId];
            
            if ($courseId) {
                $where[] = "a.course_id = :course_id";
                $params['course_id'] = $courseId;
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Build join conditions based on database structure
            // Note: assignments.section_id can reference either sections.section_id or schedule.schedule_id
            if ($hasSectionsTable) {
                // Old structure: sections table exists
                $scheduleJoin = "JOIN sections s ON a.section_id = s.section_id";
                if ($hasScheduleId) {
                    $enrollmentJoin = "JOIN enrollments e ON (s.section_id = e.section_id OR s.section_id = e.schedule_id)";
                } else {
                    $enrollmentJoin = "JOIN enrollments e ON s.section_id = e.section_id";
                }
            } else {
                // New structure: using schedule table
                // assignments.section_id stores schedule_id values
                $scheduleJoin = "JOIN schedule s ON a.section_id = s.schedule_id";
                if ($hasScheduleId) {
                    $enrollmentJoin = "JOIN enrollments e ON s.schedule_id = e.schedule_id";
                } else {
                    // enrollments.section_id actually stores schedule_id values
                    $enrollmentJoin = "JOIN enrollments e ON s.schedule_id = e.section_id";
                }
            }
            
            $stmt = $this->db->prepare("
                SELECT DISTINCT a.*, c.course_code, c.name as course_name,
                       s.section_number, s.semester, s.academic_year,
                       sub.submission_id, sub.submitted_at, sub.grade, sub.status as submission_status,
                       sub.feedback, sub.file_path, sub.file_name
                FROM assignments a
                JOIN courses c ON a.course_id = c.course_id
                {$scheduleJoin}
                {$enrollmentJoin}
                LEFT JOIN assignment_submissions sub ON a.assignment_id = sub.assignment_id AND sub.student_id = :student_id
                WHERE {$whereClause}
                ORDER BY a.due_date DESC, c.course_code
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("getAssignmentsForStudent failed: " . $e->getMessage());
            error_log("getAssignmentsForStudent error trace: " . $e->getTraceAsString());
            return [];
        }
    }

    public function getSubmission(int $studentId, int $assignmentId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM assignment_submissions
                WHERE student_id = :student_id AND assignment_id = :assignment_id
                ORDER BY submitted_at DESC
                LIMIT 1
            ");
            $stmt->execute(['student_id' => $studentId, 'assignment_id' => $assignmentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("getSubmission failed: " . $e->getMessage());
            return null;
        }
    }

    public function getStudentSchedule(int $studentId, string $semester, string $academicYear): array
    {
        try {
            // Check if enrollments has schedule_id column
            $hasScheduleId = false;
            try {
                $checkStmt = $this->db->query("SHOW COLUMNS FROM enrollments LIKE 'schedule_id'");
                $hasScheduleId = $checkStmt->rowCount() > 0;
            } catch (\PDOException $e) {
                $hasScheduleId = false;
            }
            
            // Build JOIN condition based on available columns
            if ($hasScheduleId) {
                $joinCondition = "e.schedule_id = s.schedule_id";
            } else {
                $joinCondition = "e.section_id = s.schedule_id";
            }
            
            // Check if course_ids column exists
            $hasCourseIds = false;
            try {
                $checkCourseIds = $this->db->query("SHOW COLUMNS FROM schedule LIKE 'course_ids'");
                $hasCourseIds = $checkCourseIds->rowCount() > 0;
            } catch (\PDOException $e) {
                $hasCourseIds = false;
            }
            
            $selectFields = "s.*, c.course_code, c.name as course_name, c.credit_hours,
                       u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                       s.weekly_schedule, s.is_weekly";
            if ($hasCourseIds) {
                $selectFields .= ", s.course_ids";
            }
            
            $stmt = $this->db->prepare("
                SELECT {$selectFields}
                FROM enrollments e
                JOIN schedule s ON {$joinCondition}
                JOIN courses c ON s.course_id = c.course_id
                JOIN doctors d ON s.doctor_id = d.doctor_id
                JOIN users u ON d.user_id = u.id
                WHERE e.student_id = :student_id
                AND e.status = 'enrolled'
                AND s.semester = :semester
                AND s.academic_year = :academic_year
                ORDER BY s.day_of_week, s.start_time
            ");
            $stmt->execute([
                'student_id' => $studentId,
                'semester' => $semester,
                'academic_year' => $academicYear
            ]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Expand weekly schedules into individual session entries
            $expandedResults = [];
            foreach ($results as $entry) {
                $isWeekly = !empty($entry['is_weekly']) && (int)$entry['is_weekly'] === 1;
                $weeklySchedule = !empty($entry['weekly_schedule']) ? json_decode($entry['weekly_schedule'], true) : null;
                
                if ($isWeekly && $weeklySchedule && is_array($weeklySchedule)) {
                    // Expand weekly schedule - each day can have multiple sessions
                    foreach ($weeklySchedule as $day => $sessions) {
                        // Normalize day name
                        $dayLower = strtolower(trim($day));
                        $dayMap = [
                            'monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday',
                            'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday',
                            'sunday' => 'Sunday', 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday',
                            'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday',
                        ];
                        $dayCapitalized = $dayMap[$dayLower] ?? ucfirst($dayLower);
                        
                        // Check if sessions is an array of sessions or a single session object
                        if (is_array($sessions) && isset($sessions[0]) && is_array($sessions[0])) {
                            // Multiple sessions per day
                            foreach ($sessions as $session) {
                                if (is_array($session) && !empty($session['start_time']) && !empty($session['end_time'])) {
                                    $sessionEntry = $entry;
                                    $sessionEntry['day_of_week'] = $dayCapitalized;
                                    $sessionEntry['start_time'] = $session['start_time'] ?? $entry['start_time'] ?? '';
                                    $sessionEntry['end_time'] = $session['end_time'] ?? $entry['end_time'] ?? '';
                                    $sessionEntry['room'] = $session['room'] ?? $entry['room'] ?? '';
                                    $sessionEntry['session_type'] = $session['session_type'] ?? $entry['session_type'] ?? 'lecture';
                                    
                                    // Ensure time format is consistent (HH:MM:SS)
                                    if (!empty($sessionEntry['start_time']) && strlen($sessionEntry['start_time']) == 5) {
                                        $sessionEntry['start_time'] .= ':00';
                                    }
                                    if (!empty($sessionEntry['end_time']) && strlen($sessionEntry['end_time']) == 5) {
                                        $sessionEntry['end_time'] .= ':00';
                                    }
                                    
                                    // If session has a different course_id, fetch its details
                                    if (!empty($session['course_id']) && $session['course_id'] != $entry['course_id']) {
                                        $courseStmt = $this->db->prepare("SELECT course_code, name as course_name, credit_hours FROM courses WHERE course_id = :course_id");
                                        $courseStmt->execute(['course_id' => $session['course_id']]);
                                        $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
                                        if ($course) {
                                            $sessionEntry['course_id'] = $session['course_id'];
                                            $sessionEntry['course_code'] = $course['course_code'];
                                            $sessionEntry['course_name'] = $course['course_name'];
                                            $sessionEntry['credit_hours'] = $course['credit_hours'];
                                        }
                                    }
                                    
                                    if (!empty($session['section_number'])) {
                                        $sessionEntry['section_number'] = $session['section_number'];
                                    }
                                    
                                    // Remove weekly schedule fields
                                    unset($sessionEntry['weekly_schedule']);
                                    unset($sessionEntry['is_weekly']);
                                    unset($sessionEntry['course_ids']);
                                    
                                    $expandedResults[] = $sessionEntry;
                                }
                            }
                        } else {
                            // Single session object for this day (legacy format)
                            if (is_array($sessions) && !empty($sessions['start_time']) && !empty($sessions['end_time'])) {
                                $dayEntry = $entry;
                                $dayEntry['day_of_week'] = $dayCapitalized;
                                $dayEntry['start_time'] = $sessions['start_time'] ?? $entry['start_time'] ?? '';
                                $dayEntry['end_time'] = $sessions['end_time'] ?? $entry['end_time'] ?? '';
                                $dayEntry['room'] = $sessions['room'] ?? $entry['room'] ?? '';
                                $dayEntry['session_type'] = $sessions['session_type'] ?? $entry['session_type'] ?? 'lecture';
                                
                                // Ensure time format
                                if (!empty($dayEntry['start_time']) && strlen($dayEntry['start_time']) == 5) {
                                    $dayEntry['start_time'] .= ':00';
                                }
                                if (!empty($dayEntry['end_time']) && strlen($dayEntry['end_time']) == 5) {
                                    $dayEntry['end_time'] .= ':00';
                                }
                                
                                unset($dayEntry['weekly_schedule']);
                                unset($dayEntry['is_weekly']);
                                unset($dayEntry['course_ids']);
                                
                                $expandedResults[] = $dayEntry;
                            }
                        }
                    }
                } else {
                    // Regular single-day entry or non-weekly schedule
                    // Check if it has multiple courses in course_ids
                    if ($hasCourseIds && !empty($entry['course_ids'])) {
                        $courseIds = json_decode($entry['course_ids'], true);
                        if (is_array($courseIds) && count($courseIds) > 1) {
                            // Expand for each course
                            foreach ($courseIds as $courseId) {
                                $courseEntry = $entry;
                                $courseEntry['course_id'] = $courseId;
                                // Get course details
                                $courseStmt = $this->db->prepare("SELECT course_code, name as course_name, credit_hours FROM courses WHERE course_id = :course_id");
                                $courseStmt->execute(['course_id' => $courseId]);
                                $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
                                if ($course) {
                                    $courseEntry['course_code'] = $course['course_code'];
                                    $courseEntry['course_name'] = $course['course_name'];
                                    $courseEntry['credit_hours'] = $course['credit_hours'];
                                }
                                unset($courseEntry['weekly_schedule']);
                                unset($courseEntry['is_weekly']);
                                unset($courseEntry['course_ids']);
                                $expandedResults[] = $courseEntry;
                            }
                        } else {
                            // Single course, add as is
                            unset($entry['weekly_schedule']);
                            unset($entry['is_weekly']);
                            unset($entry['course_ids']);
                            $expandedResults[] = $entry;
                        }
                    } else {
                        // No course_ids, add as is
                        unset($entry['weekly_schedule']);
                        unset($entry['is_weekly']);
                        if (isset($entry['course_ids'])) {
                            unset($entry['course_ids']);
                        }
                        $expandedResults[] = $entry;
                    }
                }
            }
            
            return $expandedResults;
        } catch (\PDOException $e) {
            error_log("getStudentSchedule failed: " . $e->getMessage());
            return [];
        }
    }

    public function getAvailableSectionsForEnrollment(string $semester, string $academicYear): array
    {
        try {
            // Check if enrollments has schedule_id column
            $hasScheduleId = false;
            try {
                $checkStmt = $this->db->query("SHOW COLUMNS FROM enrollments LIKE 'schedule_id'");
                $hasScheduleId = $checkStmt->rowCount() > 0;
            } catch (\PDOException $e) {
                $hasScheduleId = false;
            }
            
            // Build enrollment count subquery based on available columns
            if ($hasScheduleId) {
                $enrollmentSubquery = "(SELECT COUNT(*) FROM enrollments e WHERE e.schedule_id = s.schedule_id AND e.status = 'enrolled')";
            } else {
                $enrollmentSubquery = "(SELECT COUNT(*) FROM enrollments e WHERE e.section_id = s.schedule_id AND e.status = 'enrolled')";
            }
            
            $stmt = $this->db->prepare("
                SELECT s.*, s.schedule_id as section_id, c.course_code, c.name as course_name, c.credit_hours, c.description,
                       u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                       {$enrollmentSubquery} as current_enrollment,
                       s.capacity
                FROM schedule s
                JOIN courses c ON s.course_id = c.course_id
                JOIN doctors d ON s.doctor_id = d.doctor_id
                JOIN users u ON d.user_id = u.id
                WHERE s.semester = :semester
                AND s.academic_year = :academic_year
                AND (s.status = 'published' OR s.status = 'scheduled')
                AND (s.capacity IS NULL OR (s.capacity > 0 AND {$enrollmentSubquery} < s.capacity))
                ORDER BY c.course_code, s.section_number
            ");
            $stmt->execute(['semester' => $semester, 'academic_year' => $academicYear]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("getAvailableSectionsForEnrollment failed: " . $e->getMessage());
            return [];
        }
    }

    public function getEnrollmentRequests(int $studentId): array
    {
        try {
            // Check if enrollment_requests has schedule_id column
            $hasScheduleId = false;
            try {
                $checkStmt = $this->db->query("SHOW COLUMNS FROM enrollment_requests LIKE 'schedule_id'");
                $hasScheduleId = $checkStmt->rowCount() > 0;
            } catch (\PDOException $e) {
                $hasScheduleId = false;
            }
            
            // Build JOIN condition based on available columns
            if ($hasScheduleId) {
                $joinCondition = "er.schedule_id = s.schedule_id";
                $selectSectionId = "s.schedule_id as section_id";
            } else {
                $joinCondition = "er.section_id = s.schedule_id";
                $selectSectionId = "s.schedule_id as section_id";
            }
            
            $stmt = $this->db->prepare("
                SELECT er.*, {$selectSectionId}, s.section_number, s.semester, s.academic_year, s.room, s.time_slot,
                       s.day_of_week, s.start_time, s.end_time,
                       c.course_code, c.name as course_name, c.credit_hours,
                       u.first_name as doctor_first_name, u.last_name as doctor_last_name
                FROM enrollment_requests er
                JOIN schedule s ON {$joinCondition}
                JOIN courses c ON s.course_id = c.course_id
                JOIN doctors d ON s.doctor_id = d.doctor_id
                JOIN users u ON d.user_id = u.id
                WHERE er.student_id = :student_id
                ORDER BY er.requested_at DESC
            ");
            $stmt->execute(['student_id' => $studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("getEnrollmentRequests failed: " . $e->getMessage());
            return [];
        }
    }
}

