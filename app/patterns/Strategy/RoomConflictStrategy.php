<?php
namespace patterns\Strategy;

use PDO;

/**
 * Strategy Pattern - Behavioral
 * Concrete strategy for room conflict detection
 */
class RoomConflictStrategy implements ConflictDetectionStrategy
{
    private PDO $db;
    private bool $hasConflict = false;
    private string $errorMessage = '';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function checkConflict(array $data): bool
    {
        $room = $data['room'] ?? '';
        $dayOfWeek = $data['day_of_week'] ?? '';
        $startTime = $data['start_time'] ?? '';
        $endTime = $data['end_time'] ?? '';
        $semester = $data['semester'] ?? '';
        $academicYear = $data['academic_year'] ?? '';
        $excludeScheduleId = $data['exclude_schedule_id'] ?? null;

        if (!$room || !$dayOfWeek || !$startTime || !$endTime || !$semester || !$academicYear) {
            $this->errorMessage = 'Missing required room data';
            return false;
        }

        $sql = "SELECT COUNT(*) as count FROM schedule
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
        
        $this->hasConflict = (int)$result['count'] > 0;
        $this->errorMessage = $this->hasConflict ? 'Room is already booked at this time' : '';
        
        return $this->hasConflict;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}

