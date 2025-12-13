<?php
namespace models;

use core\Model;
use PDO;

class Doctor extends Model
{
    private string $table = 'doctors';

    public function findById(int $doctorId): ?array
    {
        $stmt = $this->db->prepare("SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} d 
                                    JOIN users u ON d.user_id = u.id 
                                    WHERE d.doctor_id = :doctor_id LIMIT 1");
        $stmt->execute(['doctor_id' => $doctorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} d 
                                    JOIN users u ON d.user_id = u.id 
                                    WHERE d.user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(array $filters = []): array
    {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['department'])) {
            $where .= " AND d.department = ?";
            $params[] = $filters['department'];
        }

        $sql = "SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
                FROM {$this->table} d 
                JOIN users u ON d.user_id = u.id 
                $where
                ORDER BY d.created_at DESC 
                LIMIT 100";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCount(array $filters = []): int
    {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['department'])) {
            $where .= " AND d.department = ?";
            $params[] = $filters['department'];
        }

        $sql = "SELECT COUNT(*) as cnt 
                FROM {$this->table} d 
                JOIN users u ON d.user_id = u.id 
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
        // For now, all doctors are considered active
        // In the future, add a status field
        return $this->getCount();
    }

    public function createDoctorWithUser(array $userData, array $doctorData): bool
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
                        VALUES (:first_name, :last_name, :email, :phone, :password, 'doctor')";
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

            // Then create the doctor record
            $doctorSql = "INSERT INTO {$this->table} (user_id, department, bio)
                          VALUES (:user_id, :department, :bio)";
            $doctorStmt = $this->db->prepare($doctorSql);
            $doctorStmt->execute([
                'user_id' => $userId,
                'department' => $doctorData['department'] ?? null,
                'bio' => $doctorData['bio'] ?? null,
            ]);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Doctor creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateDoctor(int $doctorId, array $userData, array $doctorData): bool
    {
        try {
            // Ensure no active transaction before starting a new one
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->db->beginTransaction();

            // Get user_id from doctor_id
            $doctor = $this->findById($doctorId);
            if (!$doctor) {
                throw new \RuntimeException("Doctor not found");
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
                'user_id' => $doctor['user_id'],
            ]);

            // Update password if provided
            if (!empty($userData['password'])) {
                $passwordSql = "UPDATE users SET password = :password WHERE id = :user_id";
                $passwordStmt = $this->db->prepare($passwordSql);
                $passwordStmt->execute([
                    'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
                    'user_id' => $doctor['user_id'],
                ]);
            }

            // Update doctor data
            $doctorSql = "UPDATE {$this->table} SET 
                         department = :department
                         WHERE doctor_id = :doctor_id";
            $doctorStmt = $this->db->prepare($doctorSql);
            $doctorStmt->execute([
                'department' => $doctorData['department'] ?? null,
                'doctor_id' => $doctorId,
            ]);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Doctor update failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteDoctor(int $doctorId): bool
    {
        try {
            // Cascade delete will handle user deletion
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE doctor_id = :doctor_id");
            return $stmt->execute(['doctor_id' => $doctorId]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Doctor deletion failed: " . $e->getMessage());
            return false;
        }
    }

    public function getUniqueDepartments(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT department FROM {$this->table} WHERE department IS NOT NULL AND department != '' ORDER BY department");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_column($results, 'department');
    }

    public function isAvailable(int $doctorId, string $dayOfWeek, string $startTime, string $endTime, string $semester, string $academicYear): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM sections s
            WHERE s.doctor_id = :doctor_id
            AND s.semester = :semester
            AND s.academic_year = :academic_year
            AND s.day_of_week = :day_of_week
            AND (
                (s.start_time <= :start_time AND s.end_time > :start_time)
                OR (s.start_time < :end_time AND s.end_time >= :end_time)
                OR (s.start_time >= :start_time AND s.end_time <= :end_time)
            )
        ");
        $stmt->execute([
            'doctor_id' => $doctorId,
            'semester' => $semester,
            'academic_year' => $academicYear,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'] === 0;
    }
}

