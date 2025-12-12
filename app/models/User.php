<?php
namespace models;

use core\Model;
use PDO;

class User extends Model
{
    private string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        // Trim email for comparison
        $email = trim($email);
        // Use case-insensitive comparison
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE LOWER(email) = LOWER(:email) LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function createUser(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} (first_name, last_name, email, phone, password, role)
                    VALUES (:first_name, :last_name, :email, :phone, :password, :role)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? '',
                'password' => $data['password'],
                'role' => $data['role'] ?? 'user',
            ]);
            return $result && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("User creation failed: " . $e->getMessage());
            throw new \RuntimeException("Failed to create user: " . $e->getMessage());
        }
    }

    public function findById(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function getAll(array $filters = []): array
    {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        // Filter by role if specified (default users only)
        if (!empty($filters['role'])) {
            $where .= " AND role = ?";
            $params[] = $filters['role'];
        } else {
            // Default to 'user' role only
            $where .= " AND role = 'user'";
        }

        $sql = "SELECT * FROM {$this->table} 
                $where
                ORDER BY created_at DESC 
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
            $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        // Filter by role if specified (default users only)
        if (!empty($filters['role'])) {
            $where .= " AND role = ?";
            $params[] = $filters['role'];
        } else {
            // Default to 'user' role only
            $where .= " AND role = 'user'";
        }

        $sql = "SELECT COUNT(*) as cnt FROM {$this->table} $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getThisMonthCount(array $filters = []): int
    {
        $where = "WHERE YEAR(created_at) = YEAR(CURRENT_DATE()) 
                  AND MONTH(created_at) = MONTH(CURRENT_DATE())";
        $params = [];

        if (!empty($filters['search'])) {
            $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        // Filter by role if specified (default users only)
        if (!empty($filters['role'])) {
            $where .= " AND role = ?";
            $params[] = $filters['role'];
        } else {
            // Default to 'user' role only
            $where .= " AND role = 'user'";
        }

        $sql = "SELECT COUNT(*) as cnt FROM {$this->table} $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function updateUser(int $userId, array $data): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone";
            
            // Add password update if provided
            if (!empty($data['password'])) {
                $sql .= ", password = :password";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $params = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? '',
                'id' => $userId,
            ];
            
            if (!empty($data['password'])) {
                $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            return $stmt->execute($params) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("User update failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
            return $stmt->execute(['id' => $userId]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("User deletion failed: " . $e->getMessage());
            return false;
        }
    }

    public function verifyPassword(int $userId, string $password): bool
    {
        try {
            $user = $this->findById($userId);
            if (!$user || empty($user['password'])) {
                return false;
            }
            return password_verify($password, $user['password']);
        } catch (\PDOException $e) {
            error_log("Password verification failed: " . $e->getMessage());
            return false;
        }
    }
}

