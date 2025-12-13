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
            $sql = "INSERT INTO {$this->table} (user_id, student_number, gpa, admission_date, major, minor, midterm_cardinality, final_cardinality, status, advisor_id)
                    VALUES (:user_id, :student_number, :gpa, :admission_date, :major, :minor, :midterm_cardinality, :final_cardinality, :status, :advisor_id)";
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
            
            // Convert advisor_id to null if it's 0 or empty
            $advisorId = !empty($studentData['advisor_id']) && $studentData['advisor_id'] > 0 
                ? (int)$studentData['advisor_id'] 
                : null;
            
            $studentSql = "INSERT INTO {$this->table} (user_id, student_number, gpa, admission_date, major, minor, midterm_cardinality, final_cardinality, status, advisor_id)
                          VALUES (:user_id, :student_number, :gpa, :admission_date, :major, :minor, :midterm_cardinality, :final_cardinality, :status, :advisor_id)";
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
                'advisor_id' => $advisorId,
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
                'status = :status',
                'advisor_id = :advisor_id'
            ];
            
            $params = [
                'student_number' => $studentData['student_number'] ?? null,
                'gpa' => $studentData['gpa'] ?? 0.00,
                'admission_date' => $admissionDate,
                'major' => $studentData['major'] ?? null,
                'minor' => $studentData['minor'] ?? null,
                'status' => $studentData['status'] ?? 'active',
                'advisor_id' => $studentData['advisor_id'] ?? null,
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
}

