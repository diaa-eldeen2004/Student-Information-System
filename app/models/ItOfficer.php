<?php
namespace models;

use core\Model;
use PDO;

class ItOfficer extends Model
{
    private string $table = 'it_officers';

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT it.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} it 
                                    JOIN users u ON it.user_id = u.id 
                                    WHERE it.user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        $itOfficer = $stmt->fetch(PDO::FETCH_ASSOC);
        return $itOfficer ?: null;
    }

    public function findByItId(int $itId): ?array
    {
        $stmt = $this->db->prepare("SELECT it.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} it 
                                    JOIN users u ON it.user_id = u.id 
                                    WHERE it.it_id = :it_id LIMIT 1");
        $stmt->execute(['it_id' => $itId]);
        $itOfficer = $stmt->fetch(PDO::FETCH_ASSOC);
        return $itOfficer ?: null;
    }

    public function createItOfficer(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} (user_id)
                    VALUES (:user_id)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'user_id' => $data['user_id'],
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("IT Officer creation failed: " . $e->getMessage());
            return false;
        }
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

        $sql = "SELECT it.*, u.first_name, u.last_name, u.email, u.phone 
                FROM {$this->table} it 
                JOIN users u ON it.user_id = u.id 
                $where
                ORDER BY it.created_at DESC 
                LIMIT 100";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllItOfficers(): array
    {
        return $this->getAll();
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
                FROM {$this->table} it 
                JOIN users u ON it.user_id = u.id 
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

    public function createItOfficerWithUser(array $userData): bool
    {
        $userStmt = null;
        $itStmt = null;
        
        try {
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
                        VALUES (:first_name, :last_name, :email, :phone, :password, 'it')";
            $userStmt = $this->db->prepare($userSql);
            $result = $userStmt->execute([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $email,
                'phone' => $userData['phone'] ?? '',
                'password' => $userData['password'],
            ]);

            if (!$result) {
                $errorInfo = $userStmt->errorInfo();
                $errorMsg = $errorInfo[2] ?? 'Unknown error';
                error_log("User creation failed. Error info: " . print_r($errorInfo, true));
                throw new \PDOException('Failed to create user record: ' . $errorMsg);
            }

            $userId = $this->db->lastInsertId();
            
            // Use getAttribute to get the last insert ID if lastInsertId() fails
            if (!$userId || $userId === 0 || $userId === '0') {
                // Try alternative method
                $userId = $this->db->lastInsertId('users');
                if (!$userId || $userId === 0 || $userId === '0') {
                    // Query directly for the last insert
                    $stmt = $this->db->query("SELECT LAST_INSERT_ID() as id");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $userId = $result['id'] ?? null;
                    
                    if (!$userId || $userId === 0) {
                        error_log("All methods to get lastInsertId failed. User creation may have failed.");
                        throw new \PDOException('Failed to get user ID after creation. User may not have been created.');
                    }
                }
            }

            // Then create the IT officer record
            $itSql = "INSERT INTO {$this->table} (user_id)
                      VALUES (:user_id)";
            $itStmt = $this->db->prepare($itSql);
            $result = $itStmt->execute([
                'user_id' => $userId,
            ]);

            if (!$result) {
                $errorInfo = $itStmt->errorInfo();
                throw new \PDOException('Failed to create IT officer record: ' . ($errorInfo[2] ?? 'Unknown error'));
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("IT Officer creation failed: " . $e->getMessage());
            if ($userStmt) {
                error_log("User SQL Error Info: " . print_r($userStmt->errorInfo(), true));
            }
            if ($itStmt) {
                error_log("IT SQL Error Info: " . print_r($itStmt->errorInfo(), true));
            }
            throw $e; // Re-throw to allow controller to catch and display error
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("IT Officer creation failed: " . $e->getMessage());
            throw $e; // Re-throw to allow controller to catch and display error
        }
    }

    public function updateItOfficer(int $itId, array $userData): bool
    {
        try {
            $this->db->beginTransaction();

            // Get user_id from it_id
            $itOfficer = $this->findByItId($itId);
            if (!$itOfficer) {
                throw new \RuntimeException("IT Officer not found");
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
                'user_id' => $itOfficer['user_id'],
            ]);

            // Update password if provided
            if (!empty($userData['password'])) {
                $passwordSql = "UPDATE users SET password = :password WHERE id = :user_id";
                $passwordStmt = $this->db->prepare($passwordSql);
                $passwordStmt->execute([
                    'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
                    'user_id' => $itOfficer['user_id'],
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("IT Officer update failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteItOfficer(int $itId): bool
    {
        try {
            // Cascade delete will handle user deletion
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE it_id = :it_id");
            return $stmt->execute(['it_id' => $itId]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("IT Officer deletion failed: " . $e->getMessage());
            return false;
        }
    }
}

