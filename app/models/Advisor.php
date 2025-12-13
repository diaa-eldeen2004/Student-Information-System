<?php
namespace models;

use core\Model;
use PDO;

class Advisor extends Model
{
    private string $table = 'advisors';

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT a.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} a 
                                    JOIN users u ON a.user_id = u.id 
                                    WHERE a.user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
        return $advisor ?: null;
    }

    public function findByAdvisorId(int $advisorId): ?array
    {
        $stmt = $this->db->prepare("SELECT a.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} a 
                                    JOIN users u ON a.user_id = u.id 
                                    WHERE a.advisor_id = :advisor_id LIMIT 1");
        $stmt->execute(['advisor_id' => $advisorId]);
        $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
        return $advisor ?: null;
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
            $where .= " AND a.department = ?";
            $params[] = $filters['department'];
        }

        $sql = "SELECT a.*, u.first_name, u.last_name, u.email, u.phone 
                FROM {$this->table} a 
                JOIN users u ON a.user_id = u.id 
                $where 
                ORDER BY a.created_at DESC 
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
            $where .= " AND a.department = ?";
            $params[] = $filters['department'];
        }

        $sql = "SELECT COUNT(*) as cnt 
                FROM {$this->table} a 
                JOIN users u ON a.user_id = u.id 
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

    public function getUniqueDepartments(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT department 
                                  FROM {$this->table} 
                                  WHERE department IS NOT NULL AND department != '' 
                                  ORDER BY department");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function createAdvisor(array $userData, array $advisorData): bool
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
                        VALUES (:first_name, :last_name, :email, :phone, :password, 'advisor')";
            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $email,
                'phone' => $userData['phone'] ?? '',
                'password' => $userData['password'],
            ]);

            $userId = $this->db->lastInsertId();

            // Then create the advisor record
            $advisorSql = "INSERT INTO {$this->table} (user_id, department)
                          VALUES (:user_id, :department)";
            $advisorStmt = $this->db->prepare($advisorSql);
            $advisorStmt->execute([
                'user_id' => $userId,
                'department' => $advisorData['department'] ?? null,
            ]);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Advisor creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateAdvisor(int $advisorId, array $userData, array $advisorData): bool
    {
        try {
            // Ensure no active transaction before starting a new one
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->db->beginTransaction();

            // Get user_id from advisor_id
            $advisor = $this->findByAdvisorId($advisorId);
            if (!$advisor) {
                throw new \RuntimeException("Advisor not found");
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
                'user_id' => $advisor['user_id'],
            ]);

            // Update password if provided
            if (!empty($userData['password'])) {
                $passwordSql = "UPDATE users SET password = :password WHERE id = :user_id";
                $passwordStmt = $this->db->prepare($passwordSql);
                $passwordStmt->execute([
                    'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
                    'user_id' => $advisor['user_id'],
                ]);
            }

            // Update advisor data
            $advisorSql = "UPDATE {$this->table} SET department = :department 
                          WHERE advisor_id = :advisor_id";
            $advisorStmt = $this->db->prepare($advisorSql);
            $advisorStmt->execute([
                'department' => $advisorData['department'] ?? null,
                'advisor_id' => $advisorId,
            ]);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Advisor update failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAdvisor(int $advisorId): bool
    {
        try {
            // Cascade delete will handle user deletion
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE advisor_id = :advisor_id");
            return $stmt->execute(['advisor_id' => $advisorId]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Advisor deletion failed: " . $e->getMessage());
            return false;
        }
    }
}

