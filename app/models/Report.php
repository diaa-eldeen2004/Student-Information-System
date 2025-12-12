<?php
namespace models;

use core\Model;
use PDO;

class Report extends Model
{
    private string $table = 'reports';

    public function findById(int $reportId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $reportId]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            return $report ?: null;
        } catch (\PDOException $e) {
            error_log("Report findById failed: " . $e->getMessage());
            return null;
        }
    }

    public function getAll(array $filters = []): array
    {
        try {
            $where = "WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $where .= " AND (title LIKE ? OR type LIKE ?)";
                $like = "%{$filters['search']}%";
                $params[] = $like;
                $params[] = $like;
            }

            if (!empty($filters['type'])) {
                $where .= " AND type = ?";
                $params[] = $filters['type'];
            }

            if (!empty($filters['period'])) {
                $where .= " AND period = ?";
                $params[] = $filters['period'];
            }

            if (!empty($filters['status'])) {
                $where .= " AND status = ?";
                $params[] = $filters['status'];
            }

            $sql = "SELECT * FROM {$this->table} 
                    $where 
                    ORDER BY created_at DESC 
                    LIMIT 100";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Report getAll failed: " . $e->getMessage());
            return [];
        }
    }

    public function getCount(array $filters = []): int
    {
        try {
            $where = "WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $where .= " AND (title LIKE ? OR type LIKE ?)";
                $like = "%{$filters['search']}%";
                $params[] = $like;
                $params[] = $like;
            }

            if (!empty($filters['type'])) {
                $where .= " AND type = ?";
                $params[] = $filters['type'];
            }

            if (!empty($filters['period'])) {
                $where .= " AND period = ?";
                $params[] = $filters['period'];
            }

            if (!empty($filters['status'])) {
                $where .= " AND status = ?";
                $params[] = $filters['status'];
            }

            $sql = "SELECT COUNT(*) as cnt FROM {$this->table} $where";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Report getCount failed: " . $e->getMessage());
            return 0;
        }
    }

    public function getTodayCount(): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM {$this->table} WHERE DATE(created_at) = CURRENT_DATE()");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Report getTodayCount failed: " . $e->getMessage());
            return 0;
        }
    }

    public function getScheduledCount(): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM {$this->table} WHERE status = 'scheduled'");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Report getScheduledCount failed: " . $e->getMessage());
            return 0;
        }
    }

    public function getDownloadsCount(): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM {$this->table} WHERE file_path IS NOT NULL");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Report getDownloadsCount failed: " . $e->getMessage());
            return 0;
        }
    }

    public function getReportsByType(): array
    {
        try {
            $stmt = $this->db->query("SELECT type, COUNT(*) as count FROM {$this->table} GROUP BY type");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $byType = [
                'academic' => 0,
                'attendance' => 0,
                'financial' => 0,
                'system' => 0,
                'other' => 0
            ];
            foreach ($results as $row) {
                $type = $row['type'] ?? 'other';
                if (isset($byType[$type])) {
                    $byType[$type] = (int)$row['count'];
                } else {
                    $byType['other'] += (int)$row['count'];
                }
            }
            return $byType;
        } catch (\PDOException $e) {
            error_log("Report getReportsByType failed: " . $e->getMessage());
            return [
                'academic' => 0,
                'attendance' => 0,
                'financial' => 0,
                'system' => 0,
                'other' => 0
            ];
        }
    }

    public function getReportsByStatus(): array
    {
        try {
            $stmt = $this->db->query("SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $byStatus = [
                'completed' => 0,
                'generating' => 0,
                'scheduled' => 0,
                'failed' => 0
            ];
            foreach ($results as $row) {
                $status = $row['status'] ?? 'completed';
                if (isset($byStatus[$status])) {
                    $byStatus[$status] = (int)$row['count'];
                }
            }
            return $byStatus;
        } catch (\PDOException $e) {
            error_log("Report getReportsByStatus failed: " . $e->getMessage());
            return [
                'completed' => 0,
                'generating' => 0,
                'scheduled' => 0,
                'failed' => 0
            ];
        }
    }

    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} (title, type, period, status, file_path, parameters)
                    VALUES (:title, :type, :period, :status, :file_path, :parameters)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'title' => $data['title'],
                'type' => $data['type'] ?? 'other',
                'period' => $data['period'] ?? 'on_demand',
                'status' => $data['status'] ?? 'generating',
                'file_path' => $data['file_path'] ?? null,
                'parameters' => isset($data['parameters']) ? (is_string($data['parameters']) ? $data['parameters'] : json_encode($data['parameters'])) : null,
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Report creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $reportId, array $data): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET 
                    title = :title,
                    type = :type,
                    period = :period,
                    status = :status,
                    file_path = :file_path,
                    parameters = :parameters
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'title' => $data['title'],
                'type' => $data['type'] ?? 'other',
                'period' => $data['period'] ?? 'on_demand',
                'status' => $data['status'] ?? 'completed',
                'file_path' => $data['file_path'] ?? null,
                'parameters' => isset($data['parameters']) ? (is_string($data['parameters']) ? $data['parameters'] : json_encode($data['parameters'])) : null,
                'id' => $reportId,
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Report update failed: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $reportId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
            return $stmt->execute(['id' => $reportId]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Report deletion failed: " . $e->getMessage());
            return false;
        }
    }

    public function tableExists(): bool
    {
        try {
            $stmt = $this->db->query("SELECT 1 FROM {$this->table} LIMIT 1");
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }
}

