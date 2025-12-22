<?php
namespace models;

use core\Model;
use PDO;

class AdminRole extends Model
{
    private string $table = 'admins';

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT a.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} a 
                                    JOIN users u ON a.user_id = u.id 
                                    WHERE a.user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        return $admin ?: null;
    }

    public function findByAdminId(int $adminId): ?array
    {
        $stmt = $this->db->prepare("SELECT a.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} a 
                                    JOIN users u ON a.user_id = u.id 
                                    WHERE a.admin_id = :admin_id LIMIT 1");
        $stmt->execute(['admin_id' => $adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        return $admin ?: null;
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

    public function getTotalAdmins(): int
    {
        return $this->getCount();
    }

    public function createAdminWithUser(array $userData, array $adminData): bool
    {
        try {
            // Ensure no active transaction before starting a new one
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->db->beginTransaction();

            // Validate required fields
            if (empty($userData['first_name']) || empty($userData['last_name']) || empty($userData['email'])) {
                throw new \InvalidArgumentException('First name, last name, and email are required');
            }

            if (empty($userData['password'])) {
                throw new \InvalidArgumentException('Password is required');
            }

            // Normalize email to lowercase
            $email = trim(strtolower($userData['email'] ?? ''));
            if (empty($email)) {
                throw new \InvalidArgumentException('Email is required and cannot be empty');
            }

            // First create the user
            $userSql = "INSERT INTO users (first_name, last_name, email, phone, password, role)
                        VALUES (:first_name, :last_name, :email, :phone, :password, 'admin')";
            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $email,
                'phone' => $userData['phone'] ?? '',
                'password' => $userData['password'],
            ]);

            $userId = $this->db->lastInsertId();

            // Then create the admin record
            $adminSql = "INSERT INTO {$this->table} (user_id)
                          VALUES (:user_id)";
            $adminStmt = $this->db->prepare($adminSql);
            $adminStmt->execute([
                'user_id' => $userId,
            ]);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Admin creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateAdmin(int $adminId, array $userData, array $adminData): bool
    {
        try {
            // Ensure no active transaction before starting a new one
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->db->beginTransaction();

            // Get user_id from admin_id
            $admin = $this->findByAdminId($adminId);
            if (!$admin) {
                throw new \RuntimeException("Admin not found");
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
                'user_id' => $admin['user_id'],
            ]);

            // Update password if provided
            if (!empty($userData['password'])) {
                $passwordSql = "UPDATE users SET password = :password WHERE id = :user_id";
                $passwordStmt = $this->db->prepare($passwordSql);
                $passwordStmt->execute([
                    'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
                    'user_id' => $admin['user_id'],
                ]);
            }

            // Admin table doesn't have additional fields to update beyond user table
            // All admin data is stored in the users table

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Admin update failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAdmin(int $adminId): bool
    {
        try {
            // Prevent deleting yourself
            if (isset($_SESSION['user']['admin_id']) && $_SESSION['user']['admin_id'] == $adminId) {
                return false;
            }

            // Cascade delete will handle user deletion
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE admin_id = :admin_id");
            return $stmt->execute(['admin_id' => $adminId]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Admin deletion failed: " . $e->getMessage());
            return false;
        }
    }
}

