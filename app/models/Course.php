<?php
namespace models;

use core\Model;
use PDO;

class Course extends Model
{
    private string $table = 'courses';

    public function findById(int $courseId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE course_id = :course_id LIMIT 1");
        $stmt->execute(['course_id' => $courseId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        return $course ?: null;
    }

    public function findByCode(string $courseCode): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE course_code = :course_code LIMIT 1");
        $stmt->execute(['course_code' => $courseCode]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        return $course ?: null;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY course_code");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} (course_code, name, description, credit_hours, department)
                    VALUES (:course_code, :name, :description, :credit_hours, :department)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'course_code' => $data['course_code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'credit_hours' => $data['credit_hours'] ?? 3,
                'department' => $data['department'] ?? null,
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Course creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function getPrerequisites(int $courseId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.* 
            FROM courses c
            JOIN course_prerequisites cp ON c.course_id = cp.prerequisite_course_id
            WHERE cp.course_id = :course_id
        ");
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addPrerequisite(int $courseId, int $prerequisiteCourseId): bool
    {
        try {
            $sql = "INSERT INTO course_prerequisites (course_id, prerequisite_course_id)
                    VALUES (:course_id, :prerequisite_course_id)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'course_id' => $courseId,
                'prerequisite_course_id' => $prerequisiteCourseId,
            ]);
        } catch (\PDOException $e) {
            error_log("Prerequisite addition failed: " . $e->getMessage());
            return false;
        }
    }

    public function checkPrerequisites(int $studentId, int $courseId): bool
    {
        $prerequisites = $this->getPrerequisites($courseId);
        
        if (empty($prerequisites)) {
            return true; // No prerequisites
        }

        // Check if student has completed all prerequisites
        $prerequisiteIds = array_column($prerequisites, 'course_id');
        $placeholders = implode(',', array_fill(0, count($prerequisiteIds), '?'));
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM enrollments e
            JOIN sections s ON e.section_id = s.section_id
            WHERE e.student_id = :student_id
            AND s.course_id IN ({$placeholders})
            AND e.status = 'completed'
            AND e.final_grade IS NOT NULL
            AND e.final_grade != 'F'
        ");
        
        $params = array_merge(['student_id' => $studentId], $prerequisiteIds);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['count'] === count($prerequisiteIds);
    }

    // Doctor-Course Assignment Methods
    public function assignDoctor(int $courseId, int $doctorId): bool
    {
        try {
            // Check if assignment already exists
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM sections 
                WHERE course_id = ? AND doctor_id = ?
            ");
            $checkStmt->execute([$courseId, $doctorId]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return false; // Already assigned
            }
            
            // Create a default section for this assignment
            $stmt = $this->db->prepare("
                INSERT INTO sections (course_id, doctor_id, section_number, semester, academic_year, status)
                VALUES (?, ?, 'DEFAULT', 'Fall', ?, 'scheduled')
            ");
            $currentYear = date('Y');
            return $stmt->execute([$courseId, $doctorId, $currentYear]);
        } catch (\PDOException $e) {
            error_log("Doctor assignment failed: " . $e->getMessage());
            return false;
        }
    }

    public function removeDoctor(int $courseId, int $doctorId): bool
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM sections 
                WHERE course_id = ? AND doctor_id = ? AND section_number = 'DEFAULT'
            ");
            return $stmt->execute([$courseId, $doctorId]);
        } catch (\PDOException $e) {
            error_log("Doctor removal failed: " . $e->getMessage());
            return false;
        }
    }

    public function getAssignedDoctors(int $courseId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT d.doctor_id, d.department, u.first_name, u.last_name, u.email
                FROM doctors d
                JOIN users u ON d.user_id = u.id
                JOIN sections s ON d.doctor_id = s.doctor_id
                WHERE s.course_id = ?
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get assigned doctors failed: " . $e->getMessage());
            return [];
        }
    }

    // Student-Course Enrollment Methods (direct, not through sections)
    public function enrollStudent(int $courseId, int $studentId, string $status = 'taking'): bool
    {
        try {
            // Check if already enrolled
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM enrollments e
                JOIN sections s ON e.section_id = s.section_id
                WHERE s.course_id = ? AND e.student_id = ?
            ");
            $checkStmt->execute([$courseId, $studentId]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return false; // Already enrolled
            }
            
            // Get or create a default section for this course
            $sectionStmt = $this->db->prepare("
                SELECT section_id FROM sections 
                WHERE course_id = ? AND section_number = 'DEFAULT'
                LIMIT 1
            ");
            $sectionStmt->execute([$courseId]);
            $section = $sectionStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$section) {
                // Create default section if it doesn't exist
                $createSection = $this->db->prepare("
                    INSERT INTO sections (course_id, doctor_id, section_number, semester, academic_year, status)
                    VALUES (?, 1, 'DEFAULT', 'Fall', ?, 'scheduled')
                ");
                $currentYear = date('Y');
                $createSection->execute([$courseId, $currentYear]);
                $sectionId = $this->db->lastInsertId();
            } else {
                $sectionId = $section['section_id'];
            }
            
            // Create enrollment
            $enrollStmt = $this->db->prepare("
                INSERT INTO enrollments (student_id, section_id, status, enrollment_date)
                VALUES (?, ?, ?, NOW())
            ");
            return $enrollStmt->execute([$studentId, $sectionId, $status]);
        } catch (\PDOException $e) {
            error_log("Student enrollment failed: " . $e->getMessage());
            return false;
        }
    }

    public function removeStudent(int $courseId, int $studentId): bool
    {
        try {
            $stmt = $this->db->prepare("
                DELETE e FROM enrollments e
                JOIN sections s ON e.section_id = s.section_id
                WHERE s.course_id = ? AND e.student_id = ?
            ");
            return $stmt->execute([$courseId, $studentId]);
        } catch (\PDOException $e) {
            error_log("Student removal failed: " . $e->getMessage());
            return false;
        }
    }

    public function getEnrolledStudents(int $courseId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT s.student_id, s.student_number, u.first_name, u.last_name, u.email, e.status
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN enrollments e ON s.student_id = e.student_id
                JOIN sections sec ON e.section_id = sec.section_id
                WHERE sec.course_id = ?
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get enrolled students failed: " . $e->getMessage());
            return [];
        }
    }

    public function getCoursesWithFilters(array $filters = []): array
    {
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['search'])) {
            $where[] = "(course_code LIKE ? OR name LIKE ? OR description LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        
        if (!empty($filters['department'])) {
            $where[] = "department = ?";
            $params[] = $filters['department'];
        }
        
        $whereClause = implode(' AND ', $where);
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE {$whereClause}
            ORDER BY created_at DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUniqueDepartments(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT DISTINCT department 
                FROM {$this->table} 
                WHERE department IS NOT NULL AND department != '' 
                ORDER BY department
            ");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function getCount(array $filters = []): int
    {
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['search'])) {
            $where[] = "(course_code LIKE ? OR name LIKE ? OR description LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        
        if (!empty($filters['department'])) {
            $where[] = "department = ?";
            $params[] = $filters['department'];
        }
        
        $whereClause = implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getThisMonthCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) 
            FROM {$this->table} 
            WHERE YEAR(created_at) = YEAR(CURRENT_DATE()) 
            AND MONTH(created_at) = MONTH(CURRENT_DATE())
        ");
        return (int)$stmt->fetchColumn();
    }

    public function getActiveCount(): int
    {
        // Since status column doesn't exist in schema, count all courses
        // If status exists in your DB, uncomment the WHERE clause
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return (int)$stmt->fetchColumn();
    }

    public function getCoursesWithDoctorInfo(array $filters = []): array
    {
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['search'])) {
            $where[] = "(c.course_code LIKE ? OR c.name LIKE ? OR c.description LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        
        if (!empty($filters['department'])) {
            $where[] = "c.department = ?";
            $params[] = $filters['department'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT c.*, 
                   GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') as doctors,
                   COUNT(DISTINCT e.student_id) as student_count
            FROM {$this->table} c
            LEFT JOIN sections s ON c.course_id = s.course_id
            LEFT JOIN doctors d ON s.doctor_id = d.doctor_id
            LEFT JOIN users u ON d.user_id = u.id
            LEFT JOIN enrollments e ON s.section_id = e.section_id
            WHERE {$whereClause}
            GROUP BY c.course_id
            ORDER BY c.created_at DESC
            LIMIT 100
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(array $data): bool
    {
        try {
            $courseId = $data['course_id'] ?? $data['id'] ?? null;
            if (!$courseId) {
                return false;
            }
            
            $sql = "UPDATE {$this->table} SET 
                    course_code = :course_code,
                    name = :name,
                    description = :description,
                    credit_hours = :credit_hours,
                    department = :department
                    WHERE course_id = :course_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'course_code' => $data['course_code'] ?? '',
                'name' => $data['name'] ?? '',
                'description' => $data['description'] ?? null,
                'credit_hours' => $data['credit_hours'] ?? 3,
                'department' => $data['department'] ?? null,
                'course_id' => $courseId,
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Course update failed: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $courseId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE course_id = :course_id");
            return $stmt->execute(['course_id' => $courseId]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Course deletion failed: " . $e->getMessage());
            return false;
        }
    }
}

