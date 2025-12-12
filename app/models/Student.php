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
            $sql = "INSERT INTO {$this->table} (user_id, student_number, gpa, admission_date, status, advisor_id)
                    VALUES (:user_id, :student_number, :gpa, :admission_date, :status, :advisor_id)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'user_id' => $data['user_id'],
                'student_number' => $data['student_number'] ?? null,
                'gpa' => $data['gpa'] ?? 0.00,
                'admission_date' => $data['admission_date'] ?? null,
                'status' => $data['status'] ?? 'active',
                'advisor_id' => $data['advisor_id'] ?? null,
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

        if (!empty($filters['year_enrolled'])) {
            $where .= " AND s.year_enrolled = ?";
            $params[] = $filters['year_enrolled'];
        }

        if (!empty($filters['status'])) {
            $where .= " AND s.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['major'])) {
            $where .= " AND s.major = ?";
            $params[] = $filters['major'];
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

        if (!empty($filters['year_enrolled'])) {
            $where .= " AND s.year_enrolled = ?";
            $params[] = $filters['year_enrolled'];
        }

        if (!empty($filters['status'])) {
            $where .= " AND s.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['major'])) {
            $where .= " AND s.major = ?";
            $params[] = $filters['major'];
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
            $this->db->beginTransaction();

            // First create the user
            $userSql = "INSERT INTO users (first_name, last_name, email, phone, password, role)
                        VALUES (:first_name, :last_name, :email, :phone, :password, 'student')";
            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'] ?? '',
                'password' => $userData['password'],
            ]);

            $userId = $this->db->lastInsertId();

            // Then create the student record
            $studentSql = "INSERT INTO {$this->table} (user_id, student_number, year_enrolled, major, minor, gpa, status)
                          VALUES (:user_id, :student_number, :year_enrolled, :major, :minor, :gpa, :status)";
            $studentStmt = $this->db->prepare($studentSql);
            $studentStmt->execute([
                'user_id' => $userId,
                'student_number' => $studentData['student_number'] ?? null,
                'year_enrolled' => $studentData['year_enrolled'] ?? null,
                'major' => $studentData['major'] ?? null,
                'minor' => $studentData['minor'] ?? null,
                'gpa' => $studentData['gpa'] ?? 0.00,
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
            $studentSql = "UPDATE {$this->table} SET 
                          student_number = :student_number,
                          year_enrolled = :year_enrolled,
                          major = :major,
                          minor = :minor,
                          gpa = :gpa,
                          status = :status
                          WHERE student_id = :student_id";
            $studentStmt = $this->db->prepare($studentSql);
            $studentStmt->execute([
                'student_number' => $studentData['student_number'] ?? null,
                'year_enrolled' => $studentData['year_enrolled'] ?? null,
                'major' => $studentData['major'] ?? null,
                'minor' => $studentData['minor'] ?? null,
                'gpa' => $studentData['gpa'] ?? 0.00,
                'status' => $studentData['status'] ?? 'active',
                'student_id' => $studentId,
            ]);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Student update failed: " . $e->getMessage());
            return false;
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
        $stmt = $this->db->query("SELECT DISTINCT major FROM {$this->table} WHERE major IS NOT NULL AND major != '' ORDER BY major");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_column($results, 'major');
    }

    public function getUniqueYears(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT year_enrolled FROM {$this->table} WHERE year_enrolled IS NOT NULL ORDER BY year_enrolled DESC");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_column($results, 'year_enrolled');
    }
}

