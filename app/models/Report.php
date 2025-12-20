<?php
namespace models;

use core\Model;
use PDO;

class Report extends Model
{
    private string $table = 'reports';
    public ?string $lastError = null;

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
        error_log("=== Report Model create() START ===");
        error_log("Input data keys: " . implode(', ', array_keys($data)));
        
        try {
            // Check if file_data column exists
            $hasFileData = $this->columnExists('file_data');
            error_log("file_data column exists: " . ($hasFileData ? 'YES' : 'NO'));
            
            if ($hasFileData) {
                $sql = "INSERT INTO {$this->table} (title, type, period, status, file_path, file_data, file_name, file_type, file_size, parameters)
                        VALUES (:title, :type, :period, :status, :file_path, :file_data, :file_name, :file_type, :file_size, :parameters)";
                error_log("Using SQL with file_data columns");
            } else {
                $sql = "INSERT INTO {$this->table} (title, type, period, status, file_path, parameters)
                        VALUES (:title, :type, :period, :status, :file_path, :parameters)";
                error_log("Using SQL without file_data columns");
            }
            
            error_log("SQL: " . $sql);
            $stmt = $this->db->prepare($sql);
            
            if ($hasFileData) {
                $params = [
                    'title' => $data['title'] ?? '',
                    'type' => $data['type'] ?? 'other',
                    'period' => $data['period'] ?? 'on_demand',
                    'status' => $data['status'] ?? 'generating',
                    'file_path' => $data['file_path'] ?? null,
                    'file_data' => $data['file_data'] ?? null,
                    'file_name' => $data['file_name'] ?? null,
                    'file_type' => $data['file_type'] ?? null,
                    'file_size' => $data['file_size'] ?? null,
                    'parameters' => isset($data['parameters']) ? (is_string($data['parameters']) ? $data['parameters'] : json_encode($data['parameters'])) : null,
                ];
            } else {
                $params = [
                    'title' => $data['title'] ?? '',
                    'type' => $data['type'] ?? 'other',
                    'period' => $data['period'] ?? 'on_demand',
                    'status' => $data['status'] ?? 'generating',
                    'file_path' => $data['file_path'] ?? null,
                    'parameters' => isset($data['parameters']) ? (is_string($data['parameters']) ? $data['parameters'] : json_encode($data['parameters'])) : null,
                ];
            }
            
            error_log("Params (without file_data): " . print_r(array_merge($params, ['file_data' => isset($params['file_data']) ? '[BINARY DATA ' . strlen($params['file_data']) . ' bytes]' : 'NULL']), true));
            
            // Check file size if file_data is being inserted
            if (isset($params['file_data']) && $params['file_data'] !== null) {
                $fileSize = strlen($params['file_data']);
                error_log("File data size: " . $fileSize . " bytes (" . round($fileSize / 1024 / 1024, 2) . " MB)");
                
                // Warn if file is very large (might cause issues)
                if ($fileSize > 10 * 1024 * 1024) { // 10MB
                    error_log("WARNING: File is very large (" . round($fileSize / 1024 / 1024, 2) . " MB), may cause MySQL issues");
                }
            }
            
            // Ensure connection is alive before executing
            try {
                $this->db->query("SELECT 1");
            } catch (\PDOException $e) {
                error_log("Connection check failed, attempting to reconnect: " . $e->getMessage());
                // Connection might be dead, but we'll try anyway
            }
            
            $result = $stmt->execute($params);
            error_log("execute() returned: " . ($result ? 'TRUE' : 'FALSE'));
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $errorMessage = $errorInfo[2] ?? 'Unknown database error';
                error_log("SQL Error Info: " . print_r($errorInfo, true));
                error_log("Report creation failed - Data: " . print_r(array_merge($data, ['file_data' => isset($data['file_data']) ? '[BINARY DATA]' : 'NULL']), true));
                // Store error in a property so it can be retrieved
                $this->lastError = $errorMessage;
                error_log("=== Report Model create() END - FAILED ===");
                return false;
            } else {
                $rowCount = $stmt->rowCount();
                error_log("rowCount(): " . $rowCount);
                $lastInsertId = $this->db->lastInsertId();
                error_log("lastInsertId(): " . $lastInsertId);
                
                if ($rowCount === 0) {
                    $this->lastError = "No rows were inserted. Check if the data matches table constraints.";
                    error_log("WARNING: execute() succeeded but rowCount() is 0");
                    error_log("=== Report Model create() END - NO ROWS INSERTED ===");
                    return false;
                }
            }
            
            error_log("=== Report Model create() END - SUCCESS ===");
            return true;
        } catch (\PDOException $e) {
            $errorMessage = $e->errorInfo[2] ?? $e->getMessage();
            $this->lastError = $errorMessage;
            error_log("=== PDO EXCEPTION in Report Model create() ===");
            error_log("Error Code: " . $e->getCode());
            error_log("Error Message: " . $e->getMessage());
            error_log("SQL State: " . $e->errorInfo[0] ?? 'N/A');
            error_log("Driver Error Code: " . ($e->errorInfo[1] ?? 'N/A'));
            error_log("Driver Error Message: " . ($e->errorInfo[2] ?? 'N/A'));
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("Data: " . print_r(array_merge($data, ['file_data' => isset($data['file_data']) ? '[BINARY DATA]' : 'NULL']), true));
            error_log("=== END PDO EXCEPTION ===");
            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("=== GENERAL EXCEPTION in Report Model create() ===");
            error_log("Error Message: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("=== END GENERAL EXCEPTION ===");
            return false;
        }
    }

    public function update(int $reportId, array $data): bool
    {
        try {
            // Check if file_data column exists
            $hasFileData = $this->columnExists('file_data');
            
            if ($hasFileData) {
                $sql = "UPDATE {$this->table} SET 
                        title = :title,
                        type = :type,
                        period = :period,
                        status = :status,
                        file_path = :file_path,
                        file_data = :file_data,
                        file_name = :file_name,
                        file_type = :file_type,
                        file_size = :file_size,
                        parameters = :parameters
                        WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    'title' => $data['title'],
                    'type' => $data['type'] ?? 'other',
                    'period' => $data['period'] ?? 'on_demand',
                    'status' => $data['status'] ?? 'completed',
                    'file_path' => $data['file_path'] ?? null,
                    'file_data' => $data['file_data'] ?? null,
                    'file_name' => $data['file_name'] ?? null,
                    'file_type' => $data['file_type'] ?? null,
                    'file_size' => $data['file_size'] ?? null,
                    'parameters' => isset($data['parameters']) ? (is_string($data['parameters']) ? $data['parameters'] : json_encode($data['parameters'])) : null,
                    'id' => $reportId,
                ]);
            } else {
                // Fallback to old structure
                $sql = "UPDATE {$this->table} SET 
                        title = :title,
                        type = :type,
                        period = :period,
                        status = :status,
                        file_path = :file_path,
                        parameters = :parameters
                        WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    'title' => $data['title'],
                    'type' => $data['type'] ?? 'other',
                    'period' => $data['period'] ?? 'on_demand',
                    'status' => $data['status'] ?? 'completed',
                    'file_path' => $data['file_path'] ?? null,
                    'parameters' => isset($data['parameters']) ? (is_string($data['parameters']) ? $data['parameters'] : json_encode($data['parameters'])) : null,
                    'id' => $reportId,
                ]);
            }
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->lastError = $errorInfo[2] ?? 'Unknown database error';
                return false;
            }
            
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            $this->lastError = $e->errorInfo[2] ?? $e->getMessage();
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

    public function columnExists(string $columnName): bool
    {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE '{$columnName}'");
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function getFileData(int $reportId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT file_data, file_name, file_type, file_size FROM {$this->table} WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $reportId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Report getFileData failed: " . $e->getMessage());
            return null;
        }
    }
}

