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
            $this->db->beginTransaction();
            
            foreach ($attendanceData as $record) {
                $stmt = $this->db->prepare("
                    INSERT INTO {$this->table} 
                    (section_id, student_id, attendance_date, status, notes, recorded_by)
                    VALUES 
                    (:section_id, :student_id, :attendance_date, :status, :notes, :recorded_by)
                    ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    notes = VALUES(notes),
                    recorded_by = VALUES(recorded_by)
                ");
                $stmt->execute([
                    'section_id' => $sectionId,
                    'student_id' => $record['student_id'],
                    'attendance_date' => $record['date'],
                    'status' => $record['status'],
                    'notes' => $record['notes'] ?? null,
                    'recorded_by' => $recordedBy,
                ]);
            }
            
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Attendance recording failed: " . $e->getMessage());
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

