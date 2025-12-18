<?php
namespace models;

use core\Model;
use PDO;

class Schedule extends Model
{
    private string $table = 'schedule';

    public function findById(int $scheduleId): ?array
    {
        // Check if course_ids column exists
        $hasCourseIds = false;
        try {
            $checkStmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'course_ids'");
            $hasCourseIds = $checkStmt->rowCount() > 0;
        } catch (\PDOException $e) {
            $hasCourseIds = false;
        }
        
        $selectFields = "s.*, c.course_code, c.name as course_name, c.credit_hours,
                   u.first_name as doctor_first_name, u.last_name as doctor_last_name";
        
        if ($hasCourseIds) {
            $selectFields .= ", s.course_ids, s.weekly_schedule, s.is_weekly";
        } else {
            $selectFields .= ", s.weekly_schedule, s.is_weekly";
        }
        
        $stmt = $this->db->prepare("
            SELECT {$selectFields}
            FROM {$this->table} s
            JOIN courses c ON s.course_id = c.course_id
            JOIN doctors d ON s.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.id
            WHERE s.schedule_id = :schedule_id LIMIT 1
        ");
        $stmt->execute(['schedule_id' => $scheduleId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If this schedule has multiple courses (course_ids), add them to the result
        if ($result && $hasCourseIds && !empty($result['course_ids']) && ($result['is_weekly'] ?? 0) == 1) {
            $courseIds = json_decode($result['course_ids'], true);
            if (is_array($courseIds) && count($courseIds) > 1) {
                $result['all_course_ids'] = $courseIds;
            }
        }
        
        return $result ?: null;
    }

    public function getBySemester(string $semester, string $academicYear): array
    {
        // Check if enrollments has schedule_id column
        $hasScheduleId = false;
        try {
            $checkStmt = $this->db->query("SHOW COLUMNS FROM enrollments LIKE 'schedule_id'");
            $hasScheduleId = $checkStmt->rowCount() > 0;
        } catch (\PDOException $e) {
            $hasScheduleId = false;
        }
        
        // Build enrollment count subquery based on available columns
        if ($hasScheduleId) {
            $enrollmentSubquery = "(SELECT COUNT(*) FROM enrollments e WHERE e.schedule_id = s.schedule_id AND e.status = 'enrolled')";
        } else {
            // If schedule_id doesn't exist, try to match by section_id (for backward compatibility)
            // Note: This assumes schedule_id values match section_id values
            $enrollmentSubquery = "(SELECT COUNT(*) FROM enrollments e WHERE e.section_id = s.schedule_id AND e.status = 'enrolled')";
        }
        
        // Check if course_ids column exists
        $hasCourseIds = false;
        try {
            $checkStmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'course_ids'");
            $hasCourseIds = $checkStmt->rowCount() > 0;
        } catch (\PDOException $e) {
            $hasCourseIds = false;
        }
        
        $selectFields = "s.*, c.course_code, c.name as course_name, c.credit_hours,
                   u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                   COALESCE({$enrollmentSubquery}, 0) as current_enrollment";
        
        if ($hasCourseIds) {
            $selectFields .= ", s.course_ids, s.weekly_schedule, s.is_weekly";
        } else {
            $selectFields .= ", s.weekly_schedule, s.is_weekly";
        }
        
        $stmt = $this->db->prepare("
            SELECT DISTINCT {$selectFields}
            FROM {$this->table} s
            JOIN courses c ON s.course_id = c.course_id
            JOIN doctors d ON s.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.id
            WHERE s.semester = :semester AND s.academic_year = :academic_year
            ORDER BY s.schedule_id, s.day_of_week, s.start_time, c.course_code, s.section_number
        ");
        $stmt->execute(['semester' => $semester, 'academic_year' => $academicYear]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Don't expand weekly schedules here - let getWeeklyTimetable handle them
        // Only expand non-weekly entries with multiple courses
        $expandedResults = [];
        foreach ($results as $entry) {
            // If this entry has multiple courses (course_ids JSON) but is NOT weekly, expand it
            if ($hasCourseIds && !empty($entry['course_ids']) && ($entry['is_weekly'] ?? 0) != 1) {
                $courseIds = json_decode($entry['course_ids'], true);
                if (is_array($courseIds) && count($courseIds) > 1) {
                    // Create an entry for each course in course_ids
                    foreach ($courseIds as $courseId) {
                        $courseEntry = $entry;
                        $courseEntry['course_id'] = $courseId;
                        // Get course details for this course_id
                        $courseStmt = $this->db->prepare("SELECT course_code, name as course_name, credit_hours FROM courses WHERE course_id = :course_id");
                        $courseStmt->execute(['course_id' => $courseId]);
                        $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
                        if ($course) {
                            $courseEntry['course_code'] = $course['course_code'];
                            $courseEntry['course_name'] = $course['course_name'];
                            $courseEntry['credit_hours'] = $course['credit_hours'];
                        }
                        $expandedResults[] = $courseEntry;
                    }
                } else {
                    // Single course or invalid JSON, add as is
                    $expandedResults[] = $entry;
                }
            } else {
                // Weekly schedule or no course_ids, add as is (weekly schedules will be handled by getWeeklyTimetable)
                $expandedResults[] = $entry;
            }
        }
        
        return $expandedResults;
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
        
        // Track processed schedule IDs to avoid duplicates
        $processedScheduleIds = [];
        // Also track unique session keys to prevent duplicate sessions
        $processedSessions = [];
        
        foreach ($entries as $entry) {
            $scheduleId = $entry['schedule_id'] ?? null;
            
            // Check if this is a weekly schedule with weekly_schedule JSON
            if (!empty($entry['is_weekly']) && $entry['is_weekly'] == 1 && !empty($entry['weekly_schedule'])) {
                // Only process each schedule entry once (avoid duplicates from course expansion)
                if ($scheduleId && in_array($scheduleId, $processedScheduleIds)) {
                    continue; // Skip if already processed
                }
                $processedScheduleIds[] = $scheduleId;
                
                $weeklySchedule = is_string($entry['weekly_schedule']) 
                    ? json_decode($entry['weekly_schedule'], true) 
                    : $entry['weekly_schedule'];
                
                if (is_array($weeklySchedule)) {
                    // Process each day in the weekly schedule
                    foreach ($weeklySchedule as $day => $sessions) {
                        // Normalize day name - handle various formats (monday, Monday, MONDAY, etc.)
                        $dayLower = strtolower(trim($day));
                        $dayCapitalized = ucfirst($dayLower); // Normalize to "Monday", "Tuesday", etc.
                        
                        // Map common variations
                        $dayMap = [
                            'monday' => 'Monday',
                            'tuesday' => 'Tuesday',
                            'wednesday' => 'Wednesday',
                            'thursday' => 'Thursday',
                            'friday' => 'Friday',
                            'saturday' => 'Saturday',
                            'sunday' => 'Sunday',
                            'mon' => 'Monday',
                            'tue' => 'Tuesday',
                            'wed' => 'Wednesday',
                            'thu' => 'Thursday',
                            'fri' => 'Friday',
                            'sat' => 'Saturday',
                            'sun' => 'Sunday',
                        ];
                        
                        $dayCapitalized = $dayMap[$dayLower] ?? $dayCapitalized;
                        
                        if (isset($timetable[$dayCapitalized]) && is_array($sessions)) {
                            // Create an entry for each session on this day
                            foreach ($sessions as $session) {
                                if (is_array($session) && !empty($session['start_time']) && !empty($session['end_time'])) {
                                    // Build a unique key for this session to prevent duplicates
                                    $sessionCourseId = $session['course_id'] ?? $entry['course_id'] ?? 0;
                                    $sessionStartTime = $session['start_time'] ?? '';
                                    $sessionEndTime = $session['end_time'] ?? '';
                                    $sessionKey = $scheduleId . '_' . $dayCapitalized . '_' . $sessionStartTime . '_' . $sessionEndTime . '_' . $sessionCourseId;
                                    
                                    // Skip if this exact session was already added
                                    if (isset($processedSessions[$sessionKey])) {
                                        continue;
                                    }
                                    $processedSessions[$sessionKey] = true;
                                    
                                    // Build a timetable entry from the session data
                                    $timetableEntry = $entry;
                                    $timetableEntry['day_of_week'] = $dayCapitalized;
                                    $timetableEntry['start_time'] = $session['start_time'] ?? $entry['start_time'] ?? '';
                                    $timetableEntry['end_time'] = $session['end_time'] ?? $entry['end_time'] ?? '';
                                    $timetableEntry['room'] = $session['room'] ?? $entry['room'] ?? '';
                                    $timetableEntry['session_type'] = $session['session_type'] ?? $entry['session_type'] ?? 'lecture';
                                    
                                    // Ensure start_time and end_time are in proper format (HH:MM:SS)
                                    if (!empty($timetableEntry['start_time']) && strlen($timetableEntry['start_time']) == 5) {
                                        $timetableEntry['start_time'] .= ':00'; // Add seconds if missing
                                    }
                                    if (!empty($timetableEntry['end_time']) && strlen($timetableEntry['end_time']) == 5) {
                                        $timetableEntry['end_time'] .= ':00'; // Add seconds if missing
                                    }
                                    
                                    // If session has course_id, get course details for it
                                    if (!empty($session['course_id']) && $session['course_id'] != $entry['course_id']) {
                                        $courseStmt = $this->db->prepare("SELECT course_code, name as course_name, credit_hours FROM courses WHERE course_id = :course_id");
                                        $courseStmt->execute(['course_id' => $session['course_id']]);
                                        $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
                                        if ($course) {
                                            $timetableEntry['course_id'] = $session['course_id'];
                                            $timetableEntry['course_code'] = $course['course_code'];
                                            $timetableEntry['course_name'] = $course['course_name'];
                                            $timetableEntry['credit_hours'] = $course['credit_hours'];
                                        }
                                    }
                                    
                                    // Use session-specific section_number if available
                                    if (!empty($session['section_number'])) {
                                        $timetableEntry['section_number'] = $session['section_number'];
                                    }
                                    
                                    $timetable[$dayCapitalized][] = $timetableEntry;
                                }
                            }
                        }
                    }
                }
            } else {
                // Regular single-day schedule entry
                $day = $entry['day_of_week'] ?? '';
                if ($day && isset($timetable[$day])) {
                    $timetable[$day][] = $entry;
                }
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

    /**
     * Get timetable for a single schedule entry
     */
    public function getScheduleTimetable(int $scheduleId): array
    {
        $entry = $this->findById($scheduleId);
        if (!$entry) {
            return [];
        }
        
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
        
        // Check if this is a weekly schedule with weekly_schedule JSON
        if (!empty($entry['is_weekly']) && $entry['is_weekly'] == 1 && !empty($entry['weekly_schedule'])) {
            $weeklySchedule = is_string($entry['weekly_schedule']) 
                ? json_decode($entry['weekly_schedule'], true) 
                : $entry['weekly_schedule'];
            
            if (is_array($weeklySchedule)) {
                // Process each day in the weekly schedule
                foreach ($weeklySchedule as $day => $sessions) {
                    // Normalize day name
                    $dayLower = strtolower(trim($day));
                    $dayMap = [
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday',
                        'mon' => 'Monday',
                        'tue' => 'Tuesday',
                        'wed' => 'Wednesday',
                        'thu' => 'Thursday',
                        'fri' => 'Friday',
                        'sat' => 'Saturday',
                        'sun' => 'Sunday',
                    ];
                    $dayCapitalized = $dayMap[$dayLower] ?? ucfirst($dayLower);
                    
                    if (isset($timetable[$dayCapitalized]) && is_array($sessions)) {
                        // Create an entry for each session on this day
                        foreach ($sessions as $session) {
                            if (is_array($session) && !empty($session['start_time']) && !empty($session['end_time'])) {
                                // Build a timetable entry from the session data
                                $timetableEntry = $entry;
                                $timetableEntry['day_of_week'] = $dayCapitalized;
                                $timetableEntry['start_time'] = $session['start_time'] ?? $entry['start_time'] ?? '';
                                $timetableEntry['end_time'] = $session['end_time'] ?? $entry['end_time'] ?? '';
                                $timetableEntry['room'] = $session['room'] ?? $entry['room'] ?? '';
                                $timetableEntry['session_type'] = $session['session_type'] ?? $entry['session_type'] ?? 'lecture';
                                
                                // Ensure start_time and end_time are in proper format
                                if (!empty($timetableEntry['start_time']) && strlen($timetableEntry['start_time']) == 5) {
                                    $timetableEntry['start_time'] .= ':00';
                                }
                                if (!empty($timetableEntry['end_time']) && strlen($timetableEntry['end_time']) == 5) {
                                    $timetableEntry['end_time'] .= ':00';
                                }
                                
                                // If session has course_id, get course details for it
                                if (!empty($session['course_id']) && $session['course_id'] != $entry['course_id']) {
                                    $courseStmt = $this->db->prepare("SELECT course_code, name as course_name, credit_hours FROM courses WHERE course_id = :course_id");
                                    $courseStmt->execute(['course_id' => $session['course_id']]);
                                    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
                                    if ($course) {
                                        $timetableEntry['course_id'] = $session['course_id'];
                                        $timetableEntry['course_code'] = $course['course_code'];
                                        $timetableEntry['course_name'] = $course['course_name'];
                                        $timetableEntry['credit_hours'] = $course['credit_hours'];
                                    }
                                }
                                
                                // Use session-specific section_number if available
                                if (!empty($session['section_number'])) {
                                    $timetableEntry['section_number'] = $session['section_number'];
                                }
                                
                                $timetable[$dayCapitalized][] = $timetableEntry;
                            }
                        }
                    }
                }
            }
        } else {
            // Regular single-day schedule entry
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
            $sectionNumber = $data['section_number'] ?? '';
            $sessionType = $data['session_type'] ?? '';
            
            // If session type is provided, append it to section number for identification
            if ($sessionType && strpos($sectionNumber, $sessionType) === false) {
                $sectionNumber = $sectionNumber . '-' . ucfirst($sessionType);
            }
            
            // Check if this is a weekly schedule
            $isWeekly = !empty($data['weekly_schedule']) || !empty($data['is_weekly']);
            $weeklySchedule = $data['weekly_schedule'] ?? null;
            
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
            
            // Add course_ids if provided
            if (isset($data['course_ids']) && is_array($data['course_ids']) && !empty($data['course_ids'])) {
                try {
                    $checkCourseIds = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'course_ids'");
                    if ($checkCourseIds->rowCount() > 0) {
                        $sql .= ", course_ids";
                        $values .= ", :course_ids";
                        $courseIdsJson = json_encode(array_values($data['course_ids'])); // Ensure proper array format
                        $params['course_ids'] = $courseIdsJson;
                        error_log("Schedule Model: Saving course_ids = " . $courseIdsJson);
                    } else {
                        error_log("Schedule Model: course_ids column does not exist in schedule table");
                    }
                } catch (\PDOException $e) {
                    error_log("Schedule Model: Error checking course_ids column: " . $e->getMessage());
                    // Column doesn't exist yet, skip it
                }
            } else {
                error_log("Schedule Model: course_ids not provided or empty. Data: " . json_encode($data['course_ids'] ?? 'not set'));
            }
            
            // Add weekly schedule support if columns exist
            if ($isWeekly && $weeklySchedule) {
                try {
                    $checkWeekly = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'weekly_schedule'");
                    $checkIsWeekly = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'is_weekly'");
                    if ($checkWeekly->rowCount() > 0 && $checkIsWeekly->rowCount() > 0) {
                        $sql .= ", weekly_schedule, is_weekly";
                        $values .= ", :weekly_schedule, :is_weekly";
                        $params['weekly_schedule'] = is_string($weeklySchedule) ? $weeklySchedule : json_encode($weeklySchedule);
                        $params['is_weekly'] = 1;
                    }
                } catch (\PDOException $e) {
                    // Columns don't exist yet, skip them
                }
            }
            
            // Add session_type if provided and column exists
            if (isset($data['session_type']) && !empty($data['session_type'])) {
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
            
            error_log("Schedule INSERT SQL: " . $sql);
            error_log("Schedule INSERT Params: " . json_encode($params));
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            $rowCount = $stmt->rowCount();
            
            error_log("Schedule INSERT Result: " . ($result ? 'SUCCESS' : 'FAILED') . ", Rows affected: " . $rowCount);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Schedule INSERT Error: " . json_encode($errorInfo));
            }
            
            return $result && $rowCount > 0;
        } catch (\PDOException $e) {
            error_log("Schedule creation failed: " . $e->getMessage());
            error_log("Schedule creation error trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function checkTimeConflict(int $doctorId, string $dayOfWeek, string $startTime, string $endTime, string $semester, string $academicYear, ?int $excludeScheduleId = null): bool
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
        
        if ($excludeScheduleId) {
            $sql .= " AND schedule_id != :exclude_schedule_id";
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
        
        if ($excludeScheduleId) {
            $params['exclude_schedule_id'] = $excludeScheduleId;
        }
        
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'] > 0;
    }

    public function checkRoomConflict(string $room, string $dayOfWeek, string $startTime, string $endTime, string $semester, string $academicYear, ?int $excludeScheduleId = null): bool
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
        
        if ($excludeScheduleId) {
            $sql .= " AND schedule_id != :exclude_schedule_id";
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
        
        if ($excludeScheduleId) {
            $params['exclude_schedule_id'] = $excludeScheduleId;
        }
        
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'] > 0;
    }

    public function checkStudentScheduleConflict(int $studentId, string $dayOfWeek, string $startTime, string $endTime, string $semester, string $academicYear): bool
    {
        // Check if enrollments has schedule_id column
        $hasScheduleId = false;
        try {
            $checkStmt = $this->db->query("SHOW COLUMNS FROM enrollments LIKE 'schedule_id'");
            $hasScheduleId = $checkStmt->rowCount() > 0;
        } catch (\PDOException $e) {
            $hasScheduleId = false;
        }
        
        // Build join condition based on available columns
        if ($hasScheduleId) {
            $joinCondition = "e.schedule_id = s.schedule_id";
        } else {
            $joinCondition = "e.section_id = s.schedule_id";
        }
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM enrollments e
            JOIN schedule s ON {$joinCondition}
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

    public function hasCapacity(int $scheduleId): bool
    {
        $schedule = $this->findById($scheduleId);
        if (!$schedule) {
            return false;
        }
        return (int)$schedule['current_enrollment'] < (int)$schedule['capacity'];
    }

    public function incrementEnrollment(int $scheduleId): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET current_enrollment = current_enrollment + 1 WHERE schedule_id = :schedule_id");
            return $stmt->execute(['schedule_id' => $scheduleId]);
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
        // Check if course_ids column exists
        $hasCourseIds = false;
        try {
            $checkStmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'course_ids'");
            $hasCourseIds = $checkStmt->rowCount() > 0;
        } catch (\PDOException $e) {
            $hasCourseIds = false;
        }
        
        $selectFields = "s.*, 
                   c.course_code, c.name as course_name, c.credit_hours,
                   CONCAT(c.course_code, ' - Section ', s.section_number) as section_name,
                   s.day_of_week, s.start_time, s.end_time, s.room, 
                   s.semester, s.academic_year, s.section_number,
                   s.weekly_schedule, s.is_weekly";
        
        if ($hasCourseIds) {
            $selectFields .= ", s.course_ids";
        }
        
        $stmt = $this->db->prepare("
            SELECT {$selectFields}
            FROM {$this->table} s
            JOIN courses c ON s.course_id = c.course_id
            WHERE s.doctor_id = :doctor_id
            ORDER BY s.semester DESC, s.academic_year DESC, c.course_code, s.day_of_week, s.start_time
        ");
        $stmt->execute(['doctor_id' => $doctorId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Expand entries with multiple courses (from course_ids JSON)
        $expandedResults = [];
        foreach ($results as $entry) {
            // If this entry has multiple courses (course_ids JSON), expand it
            if ($hasCourseIds && !empty($entry['course_ids']) && ($entry['is_weekly'] ?? 0) == 1) {
                $courseIds = json_decode($entry['course_ids'], true);
                if (is_array($courseIds) && count($courseIds) > 1) {
                    // Create an entry for each course in course_ids
                    foreach ($courseIds as $courseId) {
                        $courseEntry = $entry;
                        $courseEntry['course_id'] = $courseId;
                        // Get course details for this course_id
                        $courseStmt = $this->db->prepare("SELECT course_code, name as course_name, credit_hours FROM courses WHERE course_id = :course_id");
                        $courseStmt->execute(['course_id' => $courseId]);
                        $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
                        if ($course) {
                            $courseEntry['course_code'] = $course['course_code'];
                            $courseEntry['course_name'] = $course['course_name'];
                            $courseEntry['credit_hours'] = $course['credit_hours'];
                            $courseEntry['section_name'] = $course['course_code'] . ' - Section ' . $entry['section_number'];
                        }
                        $expandedResults[] = $courseEntry;
                    }
                } else {
                    // Single course or invalid JSON, add as is
                    $expandedResults[] = $entry;
                }
            } else {
                // No course_ids or not weekly, add as is
                $expandedResults[] = $entry;
            }
        }
        
        return $expandedResults;
    }

    public function getEnrolledStudents(int $scheduleId): array
    {
        // Check if enrollments has schedule_id column
        $hasScheduleId = false;
        try {
            $checkStmt = $this->db->query("SHOW COLUMNS FROM enrollments LIKE 'schedule_id'");
            $hasScheduleId = $checkStmt->rowCount() > 0;
        } catch (\PDOException $e) {
            $hasScheduleId = false;
        }
        
        // Build WHERE condition based on available columns
        if ($hasScheduleId) {
            $whereCondition = "e.schedule_id = :schedule_id";
        } else {
            $whereCondition = "e.section_id = :schedule_id";
        }
        
        $stmt = $this->db->prepare("
            SELECT e.student_id, s.student_number, u.first_name, u.last_name, u.email
            FROM enrollments e
            JOIN students s ON e.student_id = s.student_id
            JOIN users u ON s.user_id = u.id
            WHERE {$whereCondition} AND e.status = 'enrolled'
            ORDER BY u.last_name, u.first_name
        ");
        $stmt->execute(['schedule_id' => $scheduleId]);
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

