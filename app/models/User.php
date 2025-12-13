<?php
namespace models;

use core\Model;
use core\DebugLogger;
use PDO;

class User extends Model
{
    private string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        // Trim and normalize email for comparison
        $email = trim(strtolower($email));
        
        try {
            // CRITICAL FIX: Use singleton's ensureCleanState to avoid transaction state issues
            // This ensures we see only committed data without interfering with active transactions
            $dbSingleton = \patterns\Singleton\DatabaseConnection::getInstance();
            $wasInTransaction = $dbSingleton->getConnection()->inTransaction();
            
            DebugLogger::log("findByEmail called", [
                'email' => $email,
                'was_in_transaction' => $wasInTransaction,
                'connection_state' => $wasInTransaction ? 'IN_TRANSACTION' : 'CLEAN'
            ]);
            
            $dbSingleton->ensureCleanState();
            
            // Get a clean connection for read-only operation
            $readOnlyDb = $dbSingleton->getReadOnlyConnection();
            
            // Check connection state again
            $afterCleanState = $readOnlyDb->inTransaction();
            DebugLogger::log("After ensureCleanState", [
                'in_transaction' => $afterCleanState,
                'connection_state' => $afterCleanState ? 'STILL_IN_TRANSACTION' : 'CLEAN'
            ]);
            
            // Email is already normalized to lowercase in PHP
            // Compare with database column (which may have different case) using LOWER(TRIM())
            // Since email is already normalized, we only need to apply LOWER to the database column
            $stmt = $readOnlyDb->prepare("SELECT * FROM {$this->table} WHERE LOWER(TRIM(email)) = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Enhanced debug logging to help diagnose issues
            if ($user) {
                $logMessage = "findByEmail FOUND: ID={$user['id']}, Stored Email='{$user['email']}', Role={$user['role']}, Searched for: '{$email}'";
                error_log($logMessage);
                DebugLogger::log("findByEmail RESULT: FOUND", [
                    'found_user_id' => $user['id'],
                    'found_email' => $user['email'],
                    'found_role' => $user['role'],
                    'searched_email' => $email,
                    'match' => strtolower(trim($user['email'])) === $email
                ]);
            } else {
                $logMessage = "findByEmail NOT FOUND: Searched for email: '{$email}'";
                error_log($logMessage);
                DebugLogger::log("findByEmail RESULT: NOT FOUND", [
                    'searched_email' => $email
                ]);
            }
            
            return $user ?: null;
        } catch (\PDOException $e) {
            DebugLogger::logError("findByEmail PDOException", $e, [
                'email' => $email,
                'sql_state' => $e->getCode(),
                'error_info' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            DebugLogger::logError("findByEmail Exception", $e, [
                'email' => $email
            ]);
            throw $e;
        }
    }

    public function createUser(array $data): bool
    {
        try {
            // Ensure no active transaction (autocommit mode for single INSERT)
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
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

