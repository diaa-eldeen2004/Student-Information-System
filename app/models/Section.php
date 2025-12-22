<?php
namespace models;

use core\Model;
use PDO;

class Section extends Model
{
    private string $table = 'sections';

    public function findById(int $sectionId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, c.course_code, c.name as course_name, c.credit_hours,
                   u.first_name as doctor_first_name, u.last_name as doctor_last_name
            FROM {$this->table} s
            JOIN courses c ON s.course_id = c.course_id
            JOIN doctors d ON s.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.id
            WHERE s.section_id = :section_id LIMIT 1
        ");
        $stmt->execute(['section_id' => $sectionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getBySemester(string $semester, string $academicYear): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, c.course_code, c.name as course_name, c.credit_hours,
                   u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                   (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = s.section_id) as current_enrollment
            FROM {$this->table} s
            JOIN courses c ON s.course_id = c.course_id
            JOIN doctors d ON s.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.id
            WHERE s.semester = :semester AND s.academic_year = :academic_year
            ORDER BY s.day_of_week, s.start_time, c.course_code, s.section_number
        ");
        $stmt->execute(['semester' => $semester, 'academic_year' => $academicYear]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get schedule entries organized by day for weekly timetable view
     */
    public function getWeeklyTimetable(string $semester, string $academicYear): array
    {
        $entries = $this->getBySemester($semester, $academicYear);
        
        // Organize by day of week
        $timetable = [
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => [],
            'Friday' => [],
            'Saturday' => [],
            'Sunday' => [],
        ];
        
        foreach ($entries as $entry) {
            $day = $entry['day_of_week'] ?? '';
            if ($day && isset($timetable[$day])) {
                $timetable[$day][] = $entry;
            }
        }
        
        // Sort each day by start time
        foreach ($timetable as $day => &$dayEntries) {
            usort($dayEntries, function($a, $b) {
                return strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
            });
        }
        
        return $timetable;
    }

    public function create(array $data): bool
    {
        try {
            // Note: session_type is stored in section_number or can be added as a separate field
            // For now, we'll store it in the time_slot field or add it to section_number
            $sectionNumber = $data['section_number'] ?? '';
            $sessionType = $data['session_type'] ?? '';
            
            // If session type is provided, append it to section number for identification
            // Format: "001-Lecture" or "L01" (if section number already includes type)
            if ($sessionType && strpos($sectionNumber, $sessionType) === false) {
                $sectionNumber = $sectionNumber . '-' . ucfirst($sessionType);
            }
            
            // Build SQL with session_type if column exists
            $sql = "INSERT INTO {$this->table} 
                    (course_id, doctor_id, section_number, semester, academic_year, 
                     room, time_slot, day_of_week, start_time, end_time, capacity";
            
            $values = "(:course_id, :doctor_id, :section_number, :semester, :academic_year,
                     :room, :time_slot, :day_of_week, :start_time, :end_time, :capacity";
            
            $params = [
                'course_id' => $data['course_id'],
                'doctor_id' => $data['doctor_id'],
                'section_number' => $sectionNumber,
                'semester' => $data['semester'],
                'academic_year' => $data['academic_year'],
                'room' => $data['room'] ?? null,
                'time_slot' => $data['time_slot'] ?? null,
                'day_of_week' => $data['day_of_week'] ?? null,
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'capacity' => $data['capacity'] ?? 30,
            ];
            
            // Add session_type if provided and column exists
            if (isset($data['session_type']) && !empty($data['session_type'])) {
                // Check if session_type column exists
                try {
                    $checkColumn = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'session_type'");
                    if ($checkColumn->rowCount() > 0) {
                        $sql .= ", session_type";
                        $values .= ", :session_type";
                        $params['session_type'] = $data['session_type'];
                    }
                } catch (\PDOException $e) {
                    // Column doesn't exist, skip it
                }
            }
            
            $sql .= ") VALUES " . $values . ")";
            
            // Log SQL for debugging
            error_log("Section INSERT SQL: " . $sql);
            error_log("Section INSERT Params: " . json_encode($params));
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            $rowCount = $stmt->rowCount();
            
            error_log("Section INSERT Result: " . ($result ? 'SUCCESS' : 'FAILED') . ", Rows affected: " . $rowCount);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Section INSERT Error: " . json_encode($errorInfo));
            }
            
            return $result && $rowCount > 0;
        } catch (\PDOException $e) {
            error_log("Section creation failed: " . $e->getMessage());
            error_log("Section creation error trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function checkTimeConflict(int $doctorId, string $dayOfWeek, string $startTime, string $endTime, string $semester, string $academicYear, ?int $excludeSectionId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE doctor_id = :doctor_id
                AND semester = :semester
                AND academic_year = :academic_year
                AND day_of_week = :day_of_week
                AND (
                    (start_time <= :start_time AND end_time > :start_time)
                    OR (start_time < :end_time AND end_time >= :end_time)
                    OR (start_time >= :start_time AND end_time <= :end_time)
                )";
        
        if ($excludeSectionId) {
            $sql .= " AND section_id != :exclude_section_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [
            'doctor_id' => $doctorId,
            'semester' => $semester,
            'academic_year' => $academicYear,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        
        if ($excludeSectionId) {
            $params['exclude_section_id'] = $excludeSectionId;
        }
        
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'] > 0;
    }

    public function checkRoomConflict(string $room, string $dayOfWeek, string $startTime, string $endTime, string $semester, string $academicYear, ?int $excludeSectionId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE room = :room
                AND semester = :semester
                AND academic_year = :academic_year
                AND day_of_week = :day_of_week
                AND (
                    (start_time <= :start_time AND end_time > :start_time)
                    OR (start_time < :end_time AND end_time >= :end_time)
                    OR (start_time >= :start_time AND end_time <= :end_time)
                )";
        
        if ($excludeSectionId) {
            $sql .= " AND section_id != :exclude_section_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [
            'room' => $room,
            'semester' => $semester,
            'academic_year' => $academicYear,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        
        if ($excludeSectionId) {
            $params['exclude_section_id'] = $excludeSectionId;
        }
        
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'] > 0;
    }

    public function checkStudentScheduleConflict(int $studentId, string $dayOfWeek, string $startTime, string $endTime, string $semester, string $academicYear): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM enrollments e
            JOIN sections s ON e.section_id = s.section_id
            WHERE e.student_id = :student_id
            AND s.semester = :semester
            AND s.academic_year = :academic_year
            AND s.day_of_week = :day_of_week
            AND e.status = 'enrolled'
            AND (
                (s.start_time <= :start_time AND s.end_time > :start_time)
                OR (s.start_time < :end_time AND s.end_time >= :end_time)
                OR (s.start_time >= :start_time AND s.end_time <= :end_time)
            )
        ");
        
        $stmt->execute([
            'student_id' => $studentId,
            'semester' => $semester,
            'academic_year' => $academicYear,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'] > 0;
    }

    public function hasCapacity(int $sectionId): bool
    {
        $section = $this->findById($sectionId);
        if (!$section) {
            return false;
        }
        return (int)$section['current_enrollment'] < (int)$section['capacity'];
    }

    public function incrementEnrollment(int $sectionId): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET current_enrollment = current_enrollment + 1 WHERE section_id = :section_id");
            return $stmt->execute(['section_id' => $sectionId]);
        } catch (\PDOException $e) {
            error_log("Enrollment increment failed: " . $e->getMessage());
            return false;
        }
    }

    public function getLastInsertId(): int
    {
        return (int)$this->db->lastInsertId();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT s.*, c.course_code, c.name as course_name, c.credit_hours,
                   u.first_name as doctor_first_name, u.last_name as doctor_last_name
            FROM {$this->table} s
            LEFT JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN doctors d ON s.doctor_id = d.doctor_id
            LEFT JOIN users u ON d.user_id = u.id
            ORDER BY s.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDoctor(int $doctorId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   c.course_code, c.name as course_name, c.credit_hours,
                   CONCAT(c.course_code, ' - Section ', s.section_number) as section_name,
                   s.day_of_week, s.start_time, s.end_time, s.room, 
                   s.semester, s.academic_year, s.section_number
            FROM {$this->table} s
            JOIN courses c ON s.course_id = c.course_id
            WHERE s.doctor_id = :doctor_id
            ORDER BY s.semester DESC, s.academic_year DESC, c.course_code, s.day_of_week, s.start_time
        ");
        $stmt->execute(['doctor_id' => $doctorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEnrolledStudents(int $sectionId): array
    {
        $stmt = $this->db->prepare("
            SELECT e.student_id, s.student_number, u.first_name, u.last_name, u.email
            FROM enrollments e
            JOIN students s ON e.student_id = s.student_id
            JOIN users u ON s.user_id = u.id
            WHERE e.section_id = :section_id AND e.status = 'enrolled'
            ORDER BY u.last_name, u.first_name
        ");
        $stmt->execute(['section_id' => $sectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unique section numbers for a course
     */
    public function getSectionNumbersByCourse(int $courseId, string $semester = null, string $academicYear = null): array
    {
        $sql = "
            SELECT DISTINCT section_number
            FROM {$this->table}
            WHERE course_id = :course_id
        ";
        $params = ['course_id' => $courseId];
        
        if ($semester) {
            $sql .= " AND semester = :semester";
            $params['semester'] = $semester;
        }
        
        if ($academicYear) {
            $sql .= " AND academic_year = :academic_year";
            $params['academic_year'] = $academicYear;
        }
        
        $sql .= " ORDER BY section_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_column($results, 'section_number');
    }
}

