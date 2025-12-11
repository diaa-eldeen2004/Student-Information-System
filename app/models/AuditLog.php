<?php
namespace models;

use core\Model;
use PDO;

class AuditLog extends Model
{
    private string $table = 'audit_logs';

    public function log(string $action, ?int $userId = null, ?string $entityType = null, ?int $entityId = null, ?string $details = null): bool
    {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $sql = "INSERT INTO {$this->table} (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
                    VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address, :user_agent)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => $details,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        } catch (\PDOException $e) {
            error_log("Audit log failed: " . $e->getMessage());
            return false;
        }
    }

    public function getAll(int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT al.*, u.first_name, u.last_name, u.email
            FROM {$this->table} al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT al.*, u.first_name, u.last_name, u.email
            FROM {$this->table} al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.user_id = :user_id
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByAction(string $action, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT al.*, u.first_name, u.last_name, u.email
            FROM {$this->table} al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.action = :action
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");
        $stmt->execute(['action' => $action]);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $sql = "INSERT INTO {$this->table} (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
                    VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address, :user_agent)";
            $stmt = $this->db->prepare($sql);
            
            $details = isset($data['details']) ? (is_string($data['details']) ? $data['details'] : json_encode($data['details'])) : null;
            
            return $stmt->execute([
                'user_id' => $data['user_id'] ?? null,
                'action' => $data['action'] ?? '',
                'entity_type' => $data['entity_type'] ?? null,
                'entity_id' => $data['entity_id'] ?? null,
                'details' => $details,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        } catch (\PDOException $e) {
            error_log("Audit log creation failed: " . $e->getMessage());
            return false;
        }
    }
}

