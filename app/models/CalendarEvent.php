<?php
namespace models;

use core\Model;
use PDO;

class CalendarEvent extends Model
{
    private string $table = 'calendar_events';

    public function tableExists(): bool
    {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE '{$this->table}'");
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function findById(int $eventId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            return $event ?: null;
        } catch (\PDOException $e) {
            error_log("CalendarEvent findById failed: " . $e->getMessage());
            return null;
        }
    }

    public function getAll(array $filters = []): array
    {
        try {
            $where = "WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $where .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
                $like = "%{$filters['search']}%";
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }

            if (!empty($filters['eventType'])) {
                $where .= " AND event_type = ?";
                $params[] = $filters['eventType'];
            }

            if (!empty($filters['department'])) {
                $where .= " AND department = ?";
                $params[] = $filters['department'];
            }

            if (!empty($filters['month'])) {
                $where .= " AND MONTH(start_date) = ? AND YEAR(start_date) = YEAR(CURRENT_DATE())";
                $params[] = (int)$filters['month'];
            }

            if (!empty($filters['status'])) {
                $where .= " AND status = ?";
                $params[] = $filters['status'];
            }

            $stmt = $this->db->prepare("SELECT * FROM {$this->table} {$where} ORDER BY start_date ASC LIMIT 100");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("CalendarEvent getAll failed: " . $e->getMessage());
            return [];
        }
    }

    public function getEventsForMonth(int $month, int $year, string $status = 'active'): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM {$this->table} 
                WHERE MONTH(start_date) = :month 
                AND YEAR(start_date) = :year 
                AND status = :status
                ORDER BY start_date ASC
            ");
            $stmt->execute(['month' => $month, 'year' => $year, 'status' => $status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("CalendarEvent getEventsForMonth failed: " . $e->getMessage());
            return [];
        }
    }

    public function getUpcomingEvents(int $days = 7, int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM {$this->table} 
                WHERE start_date >= CURRENT_DATE() 
                AND start_date <= DATE_ADD(CURRENT_DATE(), INTERVAL :days DAY)
                AND status = 'active'
                ORDER BY start_date ASC 
                LIMIT :limit
            ");
            $stmt->execute(['days' => $days, 'limit' => $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("CalendarEvent getUpcomingEvents failed: " . $e->getMessage());
            return [];
        }
    }

    public function getCountThisMonth(int $month, int $year): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt 
                FROM {$this->table} 
                WHERE MONTH(start_date) = :month 
                AND YEAR(start_date) = :year
            ");
            $stmt->execute(['month' => $month, 'year' => $year]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['cnt'] ?? 0);
        } catch (\PDOException $e) {
            error_log("CalendarEvent getCountThisMonth failed: " . $e->getMessage());
            return 0;
        }
    }

    public function getExamsScheduledCount(): int
    {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as cnt 
                FROM {$this->table} 
                WHERE event_type = 'exam' 
                AND status = 'active'
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['cnt'] ?? 0);
        } catch (\PDOException $e) {
            error_log("CalendarEvent getExamsScheduledCount failed: " . $e->getMessage());
            return 0;
        }
    }

    public function getConflictsCount(): int
    {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as cnt FROM (
                    SELECT DATE(start_date) as event_date, COUNT(*) as cnt 
                    FROM {$this->table} 
                    WHERE status = 'active' 
                    GROUP BY DATE(start_date) 
                    HAVING cnt > 1
                ) as conflicts
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['cnt'] ?? 0);
        } catch (\PDOException $e) {
            error_log("CalendarEvent getConflictsCount failed: " . $e->getMessage());
            return 0;
        }
    }

    public function getPeopleAffectedCount(): int
    {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(DISTINCT course_id) as cnt 
                FROM {$this->table} 
                WHERE course_id IS NOT NULL 
                AND status = 'active'
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $courseCount = (int)($result['cnt'] ?? 0);
            // Rough estimate: 30 people per course
            return $courseCount * 30;
        } catch (\PDOException $e) {
            error_log("CalendarEvent getPeopleAffectedCount failed: " . $e->getMessage());
            return 0;
        }
    }

    public function getUniqueDepartments(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT DISTINCT department 
                FROM {$this->table} 
                WHERE department IS NOT NULL 
                ORDER BY department
            ");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            error_log("CalendarEvent getUniqueDepartments failed: " . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): bool
    {
        try {
            $fields = [];
            $values = [];
            $params = [];

            $allowedFields = ['title', 'description', 'event_type', 'status', 'start_date', 'end_date', 'department', 'location', 'course_id'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = $field;
                    $values[] = ":{$field}";
                    $params[$field] = $data[$field];
                }
            }

            if (empty($fields)) {
                return false;
            }

            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("CalendarEvent create failed: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $eventId, array $data): bool
    {
        try {
            $set = [];
            $params = ['id' => $eventId];

            $allowedFields = ['title', 'description', 'event_type', 'status', 'start_date', 'end_date', 'department', 'location', 'course_id'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $set[] = "{$field} = :{$field}";
                    $params[$field] = $data[$field];
                }
            }

            if (empty($set)) {
                return false;
            }

            $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("CalendarEvent update failed: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $eventId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
            return $stmt->execute(['id' => $eventId]);
        } catch (\PDOException $e) {
            error_log("CalendarEvent delete failed: " . $e->getMessage());
            return false;
        }
    }
}

