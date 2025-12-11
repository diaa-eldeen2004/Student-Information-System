<?php
namespace models;

use core\Model;
use PDO;

class Doctor extends Model
{
    private string $table = 'doctors';

    public function findById(int $doctorId): ?array
    {
        $stmt = $this->db->prepare("SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} d 
                                    JOIN users u ON d.user_id = u.id 
                                    WHERE d.doctor_id = :doctor_id LIMIT 1");
        $stmt->execute(['doctor_id' => $doctorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} d 
                                    JOIN users u ON d.user_id = u.id 
                                    WHERE d.user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
                                  FROM {$this->table} d 
                                  JOIN users u ON d.user_id = u.id 
                                  ORDER BY u.last_name, u.first_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isAvailable(int $doctorId, string $dayOfWeek, string $startTime, string $endTime, string $semester, string $academicYear): bool
    {
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
        return (int)$result['count'] === 0;
    }
}

