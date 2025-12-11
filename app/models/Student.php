<?php
namespace models;

use core\Model;
use PDO;

class Student extends Model
{
    private string $table = 'students';

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT s.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} s 
                                    JOIN users u ON s.user_id = u.id 
                                    WHERE s.user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        return $student ?: null;
    }

    public function findByStudentId(int $studentId): ?array
    {
        $stmt = $this->db->prepare("SELECT s.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} s 
                                    JOIN users u ON s.user_id = u.id 
                                    WHERE s.student_id = :student_id LIMIT 1");
        $stmt->execute(['student_id' => $studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        return $student ?: null;
    }

    public function createStudent(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} (user_id, student_number, gpa, admission_date, status, advisor_id)
                    VALUES (:user_id, :student_number, :gpa, :admission_date, :status, :advisor_id)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'user_id' => $data['user_id'],
                'student_number' => $data['student_number'] ?? null,
                'gpa' => $data['gpa'] ?? 0.00,
                'admission_date' => $data['admission_date'] ?? null,
                'status' => $data['status'] ?? 'active',
                'advisor_id' => $data['advisor_id'] ?? null,
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Student creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateGPA(int $studentId, float $gpa): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET gpa = :gpa WHERE student_id = :student_id");
            return $stmt->execute([
                'gpa' => $gpa,
                'student_id' => $studentId,
            ]);
        } catch (\PDOException $e) {
            error_log("GPA update failed: " . $e->getMessage());
            return false;
        }
    }

    public function calculateGPA(int $studentId): float
    {
        // Get all completed enrollments with grades
        $stmt = $this->db->prepare("
            SELECT e.final_grade, c.credit_hours
            FROM enrollments e
            JOIN sections s ON e.section_id = s.section_id
            JOIN courses c ON s.course_id = c.course_id
            WHERE e.student_id = :student_id 
            AND e.status = 'completed'
            AND e.final_grade IS NOT NULL
        ");
        $stmt->execute(['student_id' => $studentId]);
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($grades)) {
            return 0.00;
        }

        // Grade point mapping
        $gradePoints = [
            'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
            'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
            'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
            'D+' => 1.3, 'D' => 1.0, 'D-' => 0.7,
            'F' => 0.0
        ];

        $totalPoints = 0;
        $totalCredits = 0;

        foreach ($grades as $grade) {
            $letterGrade = strtoupper(trim($grade['final_grade']));
            $credits = (float)$grade['credit_hours'];
            
            if (isset($gradePoints[$letterGrade])) {
                $totalPoints += $gradePoints[$letterGrade] * $credits;
                $totalCredits += $credits;
            }
        }

        return $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0.00;
    }

    public function getAllStudents(): array
    {
        $stmt = $this->db->query("SELECT s.*, u.first_name, u.last_name, u.email, u.phone 
                                  FROM {$this->table} s 
                                  JOIN users u ON s.user_id = u.id 
                                  ORDER BY u.last_name, u.first_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

