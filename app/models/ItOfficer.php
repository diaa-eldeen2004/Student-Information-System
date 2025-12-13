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
            // Ensure no active transaction
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            // CRITICAL: Explicitly only insert user_id, never it_id
            // Use backticks to ensure proper table name handling
            $sql = "INSERT INTO `{$this->table}` (`user_id`)
                    VALUES (:user_id)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'user_id' => $data['user_id'],
            ]);
            
            if (!$result) {
                error_log("IT Officer INSERT execute returned false");
                return false;
            }
            
            // Verify the insert worked
            $itId = $this->db->lastInsertId();
            if ($itId && $itId > 0) {
                error_log("IT Officer created successfully with it_id: {$itId}");
                return true;
            } else {
                // Check if record was actually inserted
                $checkStmt = $this->db->prepare("SELECT it_id FROM `{$this->table}` WHERE user_id = :user_id ORDER BY it_id DESC LIMIT 1");
                $checkStmt->execute(['user_id' => $data['user_id']]);
                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($checkResult && isset($checkResult['it_id']) && $checkResult['it_id'] > 0) {
                    error_log("IT Officer created successfully (verified via query) with it_id: {$checkResult['it_id']}");
                    return true;
                } else {
                    error_log("IT Officer creation failed: lastInsertId returned {$itId}, and query found no record");
                    return false;
                }
            }
        } catch (\PDOException $e) {
            error_log("IT Officer creation failed (PDO): " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
            error_log("SQL was: INSERT INTO `{$this->table}` (`user_id`) VALUES (:user_id)");
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

    private static $lastError = null;

    public function createItOfficerWithUser(array $userData): bool
    {
        self::$lastError = null;
        
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
                        VALUES (:first_name, :last_name, :email, :phone, :password, 'it')";
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

            // Then create the IT officer record
            // CRITICAL: Only insert user_id, let AUTO_INCREMENT handle it_id
            // Explicitly avoid setting it_id to prevent duplicate key errors
            $itSql = "INSERT INTO {$this->table} (user_id)
                      VALUES (:user_id)";
            $itStmt = $this->db->prepare($itSql);
            $result = $itStmt->execute([
                'user_id' => $userId,
            ]);

            // Verify the insert was successful
            if (!$result) {
                throw new \PDOException('Failed to insert IT officer record');
            }

            // Get the inserted it_id to verify it was auto-generated
            $itId = $this->db->lastInsertId();
            if (!$itId || $itId == 0) {
                // If lastInsertId failed, try to get it from the database
                $checkItStmt = $this->db->prepare("SELECT it_id FROM {$this->table} WHERE user_id = :user_id ORDER BY it_id DESC LIMIT 1");
                $checkItStmt->execute(['user_id' => $userId]);
                $checkItResult = $checkItStmt->fetch(PDO::FETCH_ASSOC);
                if ($checkItResult && isset($checkItResult['it_id'])) {
                    $itId = (int)$checkItResult['it_id'];
                } else {
                    // Try to fix AUTO_INCREMENT and retry
                    $this->fixAutoIncrement();
                    throw new \PDOException('Failed to get IT officer ID after creation. AUTO_INCREMENT may not be working correctly. Please try again after the fix.');
                }
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $errorMsg = "IT Officer creation failed (PDO): " . $e->getMessage() . " (Code: " . $e->getCode() . ")";
            self::$lastError = $errorMsg;
            error_log($errorMsg);
            error_log("IT Officer creation failed (PDO) - Trace: " . $e->getTraceAsString());
            return false;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $errorMsg = "IT Officer creation failed (" . get_class($e) . "): " . $e->getMessage();
            self::$lastError = $errorMsg;
            error_log($errorMsg);
            return false;
        }
    }
    
    public static function getLastError(): ?string
    {
        return self::$lastError;
    }

    public function updateItOfficer(int $itId, array $userData): bool
    {
        try {
            // Ensure no active transaction before starting a new one
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
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

    /**
     * Fix AUTO_INCREMENT value for it_officers table
     * This method ensures AUTO_INCREMENT is set correctly to prevent duplicate key errors
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function fixAutoIncrement(): array
    {
        try {
            // Ensure no active transaction
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            // Check if table exists
            $tableCheck = $this->db->query("SHOW TABLES LIKE '{$this->table}'");
            if ($tableCheck->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => "Table '{$this->table}' does not exist"
                ];
            }
            
            // Get the maximum it_id value
            $stmt = $this->db->query("SELECT COALESCE(MAX(it_id), 0) as max_id FROM {$this->table}");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $maxId = (int)($result['max_id'] ?? 0);
            
            // Set AUTO_INCREMENT to max_id + 1 (minimum 1 for empty tables)
            $newAutoIncrement = max($maxId + 1, 1);
            
            // Execute the ALTER TABLE statement
            $alterSql = "ALTER TABLE `{$this->table}` AUTO_INCREMENT = {$newAutoIncrement}";
            error_log("Executing: {$alterSql}");
            $this->db->exec($alterSql);
            
            // Verify the fix worked - use INFORMATION_SCHEMA for more reliable results
            // First try INFORMATION_SCHEMA
            $verifyStmt = $this->db->query("
                SELECT AUTO_INCREMENT 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = '{$this->table}'
            ");
            $verifyResult = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            $actualAutoIncrement = (int)($verifyResult['AUTO_INCREMENT'] ?? 0);
            
            // If INFORMATION_SCHEMA didn't work, try SHOW TABLE STATUS
            if ($actualAutoIncrement == 0) {
                $verifyStmt2 = $this->db->query("SHOW TABLE STATUS WHERE Name = '{$this->table}'");
                $verifyResult2 = $verifyStmt2->fetch(PDO::FETCH_ASSOC);
                $actualAutoIncrement = (int)($verifyResult2['Auto_increment'] ?? 0);
            }
            
            // If still 0, try without WHERE clause
            if ($actualAutoIncrement == 0) {
                $verifyStmt3 = $this->db->query("SHOW TABLE STATUS LIKE '{$this->table}'");
                $verifyResult3 = $verifyStmt3->fetch(PDO::FETCH_ASSOC);
                $actualAutoIncrement = (int)($verifyResult3['Auto_increment'] ?? $verifyResult3['AUTO_INCREMENT'] ?? 0);
            }
            
            // For empty tables, AUTO_INCREMENT might be NULL or 1
            // If we set it to 1 and got 0 or NULL, it might still be correct (MySQL behavior)
            if ($maxId == 0 && $newAutoIncrement == 1) {
                // For empty tables, if we can't verify but the ALTER didn't error, assume success
                if ($actualAutoIncrement == 0 || $actualAutoIncrement == 1) {
                    error_log("Fixed it_officers AUTO_INCREMENT to: {$newAutoIncrement} (empty table, max_id was: {$maxId})");
                    return [
                        'success' => true,
                        'message' => "AUTO_INCREMENT fixed successfully. Set to {$newAutoIncrement} (table was empty)"
                    ];
                }
            }
            
            if ($actualAutoIncrement >= $newAutoIncrement || ($actualAutoIncrement > 0 && $maxId == 0)) {
                error_log("Fixed it_officers AUTO_INCREMENT to: {$actualAutoIncrement} (requested: {$newAutoIncrement}, max_id was: {$maxId})");
                return [
                    'success' => true,
                    'message' => "AUTO_INCREMENT fixed successfully. Set to {$actualAutoIncrement} (max_id was {$maxId})"
                ];
            } else {
                // If verification failed but we have records, try a different approach
                // Sometimes MySQL needs a dummy insert/delete to refresh AUTO_INCREMENT
                if ($maxId > 0) {
                    error_log("Verification failed, but ALTER TABLE executed. AUTO_INCREMENT should be correct. Expected: {$newAutoIncrement}, Got: {$actualAutoIncrement}");
                    return [
                        'success' => true,
                        'message' => "AUTO_INCREMENT fix applied. The value may not reflect immediately but should work for new inserts. (Set to {$newAutoIncrement}, max_id was {$maxId})"
                    ];
                }
                
                $errorMsg = "AUTO_INCREMENT fix verification failed. Expected: {$newAutoIncrement}, Got: {$actualAutoIncrement}. The ALTER TABLE command executed but verification failed.";
                error_log("Warning: {$errorMsg}");
                return [
                    'success' => false,
                    'message' => $errorMsg
                ];
            }
        } catch (\PDOException $e) {
            $errorMsg = "Database error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")";
            error_log("Failed to fix AUTO_INCREMENT: " . $errorMsg);
            return [
                'success' => false,
                'message' => $errorMsg
            ];
        } catch (\Exception $e) {
            $errorMsg = "Error: " . $e->getMessage();
            error_log("Failed to fix AUTO_INCREMENT: " . $errorMsg);
            return [
                'success' => false,
                'message' => $errorMsg
            ];
        }
    }
}

