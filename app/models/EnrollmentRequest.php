<?php
namespace models;

use core\Model;
use PDO;

class EnrollmentRequest extends Model
{
    private string $table = 'enrollment_requests';

    public function findById(int $requestId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT er.*, 
                   s.student_id, s.gpa,
                   u.first_name as student_first_name, u.last_name as student_last_name, u.email as student_email,
                   sec.schedule_id, sec.schedule_id as section_id, sec.section_number, sec.semester, sec.academic_year, sec.room, sec.time_slot,
                   c.course_code, c.name as course_name, c.credit_hours,
                   d.doctor_id,
                   doc.first_name as doctor_first_name, doc.last_name as doctor_last_name
            FROM {$this->table} er
            JOIN students s ON er.student_id = s.student_id
            JOIN users u ON s.user_id = u.id
            JOIN schedule sec ON er.section_id = sec.schedule_id
            JOIN courses c ON sec.course_id = c.course_id
            JOIN doctors d ON sec.doctor_id = d.doctor_id
            JOIN users doc ON d.user_id = doc.id
            WHERE er.request_id = :request_id LIMIT 1
        ");
        $stmt->execute(['request_id' => $requestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getPendingRequests(): array
    {
        $stmt = $this->db->prepare("
            SELECT er.*, 
                   s.student_id, s.gpa,
                   u.first_name as student_first_name, u.last_name as student_last_name, u.email as student_email,
                   sec.schedule_id, sec.schedule_id as section_id, sec.section_number, sec.semester, sec.academic_year, sec.room, sec.time_slot,
                   c.course_code, c.name as course_name, c.credit_hours,
                   doc.first_name as doctor_first_name, doc.last_name as doctor_last_name
            FROM {$this->table} er
            JOIN students s ON er.student_id = s.student_id
            JOIN users u ON s.user_id = u.id
            JOIN schedule sec ON er.section_id = sec.schedule_id
            JOIN courses c ON sec.course_id = c.course_id
            JOIN doctors d ON sec.doctor_id = d.doctor_id
            JOIN users doc ON d.user_id = doc.id
            WHERE er.status = 'pending'
            ORDER BY er.requested_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllRequests(): array
    {
        $stmt = $this->db->prepare("
            SELECT er.*, 
                   s.student_id, s.gpa,
                   u.first_name as student_first_name, u.last_name as student_last_name, u.email as student_email,
                   sec.schedule_id, sec.schedule_id as section_id, sec.section_number, sec.semester, sec.academic_year, sec.room, sec.time_slot,
                   c.course_code, c.name as course_name, c.credit_hours,
                   doc.first_name as doctor_first_name, doc.last_name as doctor_last_name
            FROM {$this->table} er
            JOIN students s ON er.student_id = s.student_id
            JOIN users u ON s.user_id = u.id
            JOIN schedule sec ON er.section_id = sec.schedule_id
            JOIN courses c ON sec.course_id = c.course_id
            JOIN doctors d ON sec.doctor_id = d.doctor_id
            JOIN users doc ON d.user_id = doc.id
            ORDER BY er.requested_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createRequest(int $studentId, int $sectionId): bool
    {
        try {
            // Check if request already exists
            $existing = $this->db->prepare("SELECT request_id FROM {$this->table} 
                                            WHERE student_id = :student_id AND section_id = :section_id 
                                            AND status = 'pending'");
            $existing->execute(['student_id' => $studentId, 'section_id' => $sectionId]);
            if ($existing->fetch()) {
                return false; // Request already exists
            }

            $sql = "INSERT INTO {$this->table} (student_id, section_id, status)
                    VALUES (:student_id, :section_id, 'pending')";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'student_id' => $studentId,
                'section_id' => $sectionId,
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Enrollment request creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function approveRequest(int $requestId, int $reviewedBy): bool
    {
        try {
            $this->db->beginTransaction();

            // Get request details
            $request = $this->findById($requestId);
            if (!$request || $request['status'] !== 'pending') {
                $this->db->rollBack();
                return false;
            }

            // Create enrollment - check if schedule_id column exists
            $hasScheduleId = false;
            try {
                $checkStmt = $this->db->query("SHOW COLUMNS FROM enrollments LIKE 'schedule_id'");
                $hasScheduleId = $checkStmt->rowCount() > 0;
            } catch (\PDOException $e) {
                $hasScheduleId = false;
            }
            
            $scheduleId = $request['schedule_id'] ?? $request['section_id'];
            
            if ($hasScheduleId) {
                $enrollmentSql = "INSERT INTO enrollments (student_id, schedule_id, status)
                                 VALUES (:student_id, :schedule_id, 'enrolled')";
                $enrollmentStmt = $this->db->prepare($enrollmentSql);
                $enrollmentStmt->execute([
                    'student_id' => $request['student_id'],
                    'schedule_id' => $scheduleId,
                ]);
            } else {
                // Fallback to section_id
                $enrollmentSql = "INSERT INTO enrollments (student_id, section_id, status)
                                 VALUES (:student_id, :section_id, 'enrolled')";
                $enrollmentStmt = $this->db->prepare($enrollmentSql);
                $enrollmentStmt->execute([
                    'student_id' => $request['student_id'],
                    'section_id' => $scheduleId,
                ]);
            }

            // Update schedule enrollment count
            $sectionStmt = $this->db->prepare("UPDATE schedule SET current_enrollment = current_enrollment + 1 
                                              WHERE schedule_id = :section_id");
            $sectionStmt->execute(['section_id' => $request['section_id']]);

            // Update request status
            $updateStmt = $this->db->prepare("UPDATE {$this->table} 
                                             SET status = 'approved', 
                                                 reviewed_at = NOW(), 
                                                 reviewed_by = :reviewed_by
                                             WHERE request_id = :request_id");
            $updateStmt->execute([
                'request_id' => $requestId,
                'reviewed_by' => $reviewedBy,
            ]);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Enrollment approval failed: " . $e->getMessage());
            return false;
        }
    }

    public function rejectRequest(int $requestId, int $reviewedBy, string $reason = ''): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} 
                                       SET status = 'rejected', 
                                           reviewed_at = NOW(), 
                                           reviewed_by = :reviewed_by,
                                           rejection_reason = :rejection_reason
                                       WHERE request_id = :request_id AND status = 'pending'");
            return $stmt->execute([
                'request_id' => $requestId,
                'reviewed_by' => $reviewedBy,
                'rejection_reason' => $reason,
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Enrollment rejection failed: " . $e->getMessage());
            return false;
        }
    }
}

