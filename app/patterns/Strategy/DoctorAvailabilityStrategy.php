<?php
namespace patterns\Strategy;

use PDO;

/**
 * Strategy Pattern - Behavioral
 * Concrete strategy for doctor availability checking
 */
class DoctorAvailabilityStrategy implements ConflictDetectionStrategy
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
        $doctorId = $data['doctor_id'] ?? 0;
        $dayOfWeek = $data['day_of_week'] ?? '';
        $startTime = $data['start_time'] ?? '';
        $endTime = $data['end_time'] ?? '';
        $semester = $data['semester'] ?? '';
        $academicYear = $data['academic_year'] ?? '';

        if (!$doctorId || !$dayOfWeek || !$startTime || !$endTime || !$semester || !$academicYear) {
            $this->errorMessage = 'Missing required doctor availability data';
            return false;
        }

        // Check if doctor exists and is active
        $doctorStmt = $this->db->prepare("SELECT doctor_id FROM doctors WHERE doctor_id = :doctor_id");
        $doctorStmt->execute(['doctor_id' => $doctorId]);
        $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);

        if (!$doctor) {
            $this->hasConflict = true;
            $this->errorMessage = 'Doctor not found';
            return true;
        }

        // Check if doctor has conflicting sections
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM sections s
            WHERE s.doctor_id = :doctor_id
            AND s.semester = :semester
            AND s.academic_year = :academic_year
            AND s.day_of_week = :day_of_week
            AND (
                (s.start_time <= :start_time AND s.end_time > :start_time)
                OR (s.start_time < :end_time AND s.end_time >= :end_time)
                OR (s.start_time >= :start_time AND s.end_time <= :end_time)
            )
        ");
        
        $stmt->execute([
            'doctor_id' => $doctorId,
            'semester' => $semester,
            'academic_year' => $academicYear,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->hasConflict = (int)$result['count'] > 0;
        $this->errorMessage = $this->hasConflict ? 'Doctor is not available at this time' : '';
        
        return $this->hasConflict;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}

