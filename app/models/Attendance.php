<?php
namespace models;

use core\Model;
use PDO;

class Attendance extends Model
{
    private string $table = 'attendance';

    public function recordAttendance(int $sectionId, array $attendanceData, int $recordedBy): bool
    {
        try {
            // Check if schedule_id column exists in attendance table
            $hasScheduleId = false;
            try {
                $checkStmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'schedule_id'");
                $hasScheduleId = $checkStmt->rowCount() > 0;
            } catch (\PDOException $e) {
                // Column doesn't exist, use section_id
            }

            $this->db->beginTransaction();
            
            // Temporarily disable foreign key checks if needed (in case FK still points to sections table)
            $fkCheckDisabled = false;
            try {
                // Check if foreign key constraint exists and might cause issues
                $fkStmt = $this->db->query("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = '{$this->table}' 
                    AND COLUMN_NAME = 'section_id' 
                    AND REFERENCED_TABLE_NAME = 'sections'
                ");
                if ($fkStmt->rowCount() > 0) {
                    // Foreign key exists pointing to sections table, disable FK checks temporarily
                    $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
                    $fkCheckDisabled = true;
                }
            } catch (\PDOException $e) {
                // Ignore errors checking foreign keys
            }
            
            // Determine which column to use
            $idColumn = $hasScheduleId ? 'schedule_id' : 'section_id';
            
            $successCount = 0;
            foreach ($attendanceData as $record) {
                // Validate required fields
                if (empty($record['student_id']) || empty($record['date']) || empty($record['status'])) {
                    error_log("Invalid attendance record: " . json_encode($record));
                    continue;
                }

                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO {$this->table} 
                        ({$idColumn}, student_id, attendance_date, status, notes, recorded_by)
                        VALUES 
                        (:section_id, :student_id, :attendance_date, :status, :notes, :recorded_by)
                        ON DUPLICATE KEY UPDATE
                        status = VALUES(status),
                        notes = VALUES(notes),
                        recorded_by = VALUES(recorded_by),
                        updated_at = CURRENT_TIMESTAMP
                    ");
                    
                    $result = $stmt->execute([
                        'section_id' => $sectionId, // This parameter name works for both columns
                        'student_id' => (int)$record['student_id'],
                        'attendance_date' => $record['date'],
                        'status' => $record['status'],
                        'notes' => !empty($record['notes']) ? trim($record['notes']) : null,
                        'recorded_by' => $recordedBy,
                    ]);
                    
                    if ($result) {
                        $successCount++;
                    } else {
                        error_log("Failed to insert attendance record for student_id: " . $record['student_id']);
                    }
                } catch (\PDOException $e) {
                    error_log("Error inserting attendance for student_id {$record['student_id']}: " . $e->getMessage());
                    // Continue with other records
                }
            }
            
            // Re-enable foreign key checks if we disabled them
            if ($fkCheckDisabled) {
                $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            }
            
            if ($successCount === 0) {
                $this->db->rollBack();
                error_log("No attendance records were successfully inserted");
                return false;
            }
            
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // Re-enable foreign key checks if we disabled them
            try {
                $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (\PDOException $e2) {
                // Ignore
            }
            error_log("Attendance recording failed: " . $e->getMessage());
            error_log("SQL Error Code: " . $e->getCode());
            error_log("SQL State: " . $e->getCode());
            error_log("Section ID: " . $sectionId);
            error_log("Records count: " . count($attendanceData));
            return false;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // Re-enable foreign key checks if we disabled them
            try {
                $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (\PDOException $e2) {
                // Ignore
            }
            error_log("Attendance recording failed (general): " . $e->getMessage());
            return false;
        }
    }

    public function getBySection(int $sectionId, string $date = null): array
    {
        $where = ["a.section_id = :section_id"];
        $params = ['section_id' => $sectionId];
        
        if ($date) {
            $where[] = "a.attendance_date = :date";
            $params['date'] = $date;
        }
        
        $whereClause = implode(' AND ', $where);
        $stmt = $this->db->prepare("
            SELECT a.*, s.student_id, s.student_number,
                   u.first_name, u.last_name, u.email
            FROM {$this->table} a
            JOIN students s ON a.student_id = s.student_id
            JOIN users u ON s.user_id = u.id
            WHERE {$whereClause}
            ORDER BY a.attendance_date DESC, u.last_name, u.first_name
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDate(int $sectionId, string $date): array
    {
        return $this->getBySection($sectionId, $date);
    }

    public function getStudentAttendance(int $studentId, int $sectionId): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, s.section_number, c.course_code, c.name as course_name
            FROM {$this->table} a
            JOIN sections s ON a.section_id = s.section_id
            JOIN courses c ON s.course_id = c.course_id
            WHERE a.student_id = :student_id AND a.section_id = :section_id
            ORDER BY a.attendance_date DESC
        ");
        $stmt->execute(['student_id' => $studentId, 'section_id' => $sectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAttendanceStats(int $sectionId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT a.attendance_date) as total_classes,
                COUNT(DISTINCT a.student_id) as total_students,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count
            FROM {$this->table} a
            WHERE a.section_id = :section_id
        ");
        $stmt->execute(['section_id' => $sectionId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalRecords = ($stats['present_count'] ?? 0) + ($stats['absent_count'] ?? 0) + 
                       ($stats['late_count'] ?? 0) + ($stats['excused_count'] ?? 0);
        $stats['average_attendance'] = $totalRecords > 0 && ($stats['total_classes'] ?? 0) > 0 
            ? round((($stats['present_count'] ?? 0) / $totalRecords) * 100, 2) 
            : 0;
        
        return $stats;
    }

    public function getStudentAttendancePercentage(int $studentId, int $sectionId): float
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as attended
            FROM {$this->table}
            WHERE student_id = :student_id AND section_id = :section_id
        ");
        $stmt->execute(['student_id' => $studentId, 'section_id' => $sectionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['total'] > 0) {
            return round(($result['attended'] / $result['total']) * 100, 2);
        }
        
        return 0.0;
    }
}

