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
            SELECT al.log_id, al.user_id, al.action, al.entity_type, al.entity_id, al.details, 
                   al.ip_address, al.user_agent, al.created_at,
                   u.first_name, u.last_name, u.email, u.role as user_role
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
            SELECT al.log_id, al.user_id, al.action, al.entity_type, al.entity_id, al.details, 
                   al.ip_address, al.user_agent, al.created_at,
                   u.first_name, u.last_name, u.email, u.role as user_role
            FROM {$this->table} al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.action = :action
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':action', $action);
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

    public function getWithFilters(array $filters = [], int $limit = 100): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['action'])) {
            $where[] = "al.action LIKE :action";
            $params['action'] = '%' . $filters['action'] . '%';
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = "al.entity_type = :entity_type";
            $params['entity_type'] = $filters['entity_type'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(al.action LIKE :search OR al.details LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['dateRange'])) {
            $dateCondition = $this->getDateRangeCondition($filters['dateRange']);
            if ($dateCondition) {
                $where[] = $dateCondition;
            }
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "
            SELECT al.log_id, al.user_id, al.action, al.entity_type, al.entity_id, al.details, 
                   al.ip_address, al.user_agent, al.created_at,
                   u.first_name, u.last_name, u.email, u.role as user_role
            FROM {$this->table} al
            LEFT JOIN users u ON al.user_id = u.id
            {$whereClause}
            ORDER BY al.created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(string $dateRange = 'month'): array
    {
        $dateCondition = $this->getDateRangeCondition($dateRange);
        $whereClause = $dateCondition ? "WHERE {$dateCondition}" : '';
        
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN al.action LIKE '%error%' OR al.action LIKE '%failed%' THEN 1 ELSE 0 END) as errors,
                SUM(CASE WHEN al.action LIKE '%warning%' OR al.action LIKE '%reject%' THEN 1 ELSE 0 END) as warnings,
                SUM(CASE WHEN al.action LIKE '%info%' OR al.action LIKE '%view%' THEN 1 ELSE 0 END) as info,
                SUM(CASE WHEN al.action LIKE '%success%' OR al.action LIKE '%approve%' OR al.action LIKE '%create%' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN al.action LIKE '%critical%' THEN 1 ELSE 0 END) as critical
            FROM {$this->table} al
            {$whereClause}
        ";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total' => (int)($result['total'] ?? 0),
            'errors' => (int)($result['errors'] ?? 0),
            'warnings' => (int)($result['warnings'] ?? 0),
            'info' => (int)($result['info'] ?? 0),
            'success' => (int)($result['success'] ?? 0),
            'critical' => (int)($result['critical'] ?? 0),
        ];
    }

    public function clearAll(): bool
    {
        try {
            $sql = "TRUNCATE TABLE {$this->table}";
            $this->db->exec($sql);
            return true;
        } catch (\PDOException $e) {
            error_log("Clear logs failed: " . $e->getMessage());
            return false;
        }
    }

    private function getDateRangeCondition(string $dateRange): ?string
    {
        return match($dateRange) {
            'today' => "DATE(al.created_at) = CURDATE()",
            'week' => "al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => "al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            'all' => null,
            default => "al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        };
    }
}

