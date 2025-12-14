<?php
namespace models;

use core\Model;
use PDO;

class Assignment extends Model
{
    private string $table = 'assignments';

    public function findById(int $assignmentId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, c.course_code, c.name as course_name,
                   s.section_number, s.semester, s.academic_year,
                   u.first_name as doctor_first_name, u.last_name as doctor_last_name
            FROM {$this->table} a
            JOIN courses c ON a.course_id = c.course_id
            JOIN sections s ON a.section_id = s.section_id
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.id
            WHERE a.assignment_id = :assignment_id LIMIT 1
        ");
        $stmt->execute(['assignment_id' => $assignmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getByDoctor(int $doctorId, array $filters = []): array
    {
        $where = ["a.doctor_id = :doctor_id"];
        $params = ['doctor_id' => $doctorId];
        
        if (!empty($filters['course_id'])) {
            $where[] = "a.course_id = :course_id";
            $params['course_id'] = $filters['course_id'];
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $where[] = "a.due_date >= NOW()";
            } elseif ($filters['status'] === 'completed') {
                $where[] = "a.due_date < NOW()";
            }
        }
        
        if (!empty($filters['type'])) {
            $where[] = "a.assignment_type = :type";
            $params['type'] = $filters['type'];
        }
        
        $whereClause = implode(' AND ', $where);
        $stmt = $this->db->prepare("
            SELECT a.*, c.course_code, c.name as course_name,
                   s.section_number, s.semester, s.academic_year
            FROM {$this->table} a
            JOIN courses c ON a.course_id = c.course_id
            JOIN sections s ON a.section_id = s.section_id
            WHERE {$whereClause}
            ORDER BY a.due_date DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (course_id, section_id, doctor_id, title, description, due_date, max_points, assignment_type, 
                     file_path, file_name, file_size, is_visible, visible_until, semester, academic_year)
                    VALUES 
                    (:course_id, :section_id, :doctor_id, :title, :description, :due_date, :max_points, :assignment_type,
                     :file_path, :file_name, :file_size, :is_visible, :visible_until, :semester, :academic_year)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'course_id' => $data['course_id'],
                'section_id' => $data['section_id'],
                'doctor_id' => $data['doctor_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'],
                'max_points' => $data['max_points'] ?? 100,
                'assignment_type' => $data['assignment_type'] ?? 'homework',
                'file_path' => $data['file_path'] ?? null,
                'file_name' => $data['file_name'] ?? null,
                'file_size' => $data['file_size'] ?? null,
                'is_visible' => $data['is_visible'] ?? 1,
                'visible_until' => $data['visible_until'] ?? null,
                'semester' => $data['semester'] ?? null,
                'academic_year' => $data['academic_year'] ?? null,
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Assignment creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $assignmentId, array $data): bool
    {
        try {
            $fields = [];
            $params = ['assignment_id' => $assignmentId];
            
            $allowedFields = ['title', 'description', 'due_date', 'max_points', 'assignment_type', 
                             'file_path', 'file_name', 'file_size', 'is_visible', 'visible_until', 
                             'semester', 'academic_year'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "{$field} = :{$field}";
                    $params[$field] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE assignment_id = :assignment_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Assignment update failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function getBySemester(int $doctorId, string $semester, string $academicYear): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, c.course_code, c.name as course_name,
                   s.section_number, s.semester, s.academic_year
            FROM {$this->table} a
            JOIN courses c ON a.course_id = c.course_id
            JOIN sections s ON a.section_id = s.section_id
            WHERE a.doctor_id = :doctor_id 
            AND (a.semester = :semester OR s.semester = :semester)
            AND (a.academic_year = :academic_year OR s.academic_year = :academic_year)
            ORDER BY a.due_date DESC
        ");
        $stmt->execute([
            'doctor_id' => $doctorId,
            'semester' => $semester,
            'academic_year' => $academicYear
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function toggleVisibility(int $assignmentId, bool $isVisible, ?string $visibleUntil = null): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET is_visible = :is_visible, visible_until = :visible_until WHERE assignment_id = :assignment_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'assignment_id' => $assignmentId,
                'is_visible' => $isVisible ? 1 : 0,
                'visible_until' => $visibleUntil
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Toggle visibility failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function getSubmissionsByAssignment(int $assignmentId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, u.first_name, u.last_name, u.email, st.student_number
            FROM assignment_submissions s
            JOIN students st ON s.student_id = st.student_id
            JOIN users u ON st.user_id = u.id
            WHERE s.assignment_id = :assignment_id
            ORDER BY s.submitted_at DESC
        ");
        $stmt->execute(['assignment_id' => $assignmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateGrade(int $submissionId, float $grade, ?string $feedback = null): bool
    {
        try {
            $sql = "UPDATE assignment_submissions 
                    SET grade = :grade, feedback = :feedback, status = 'graded', graded_at = NOW() 
                    WHERE submission_id = :submission_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'submission_id' => $submissionId,
                'grade' => $grade,
                'feedback' => $feedback
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Update grade failed: " . $e->getMessage());
            return false;
        }
    }

    public function getSubmissionCount(int $assignmentId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN status = 'graded' THEN 1 ELSE 0 END) as graded
            FROM assignment_submissions
            WHERE assignment_id = :assignment_id
        ");
        $stmt->execute(['assignment_id' => $assignmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'submitted' => 0, 'graded' => 0];
    }

    public function getLastInsertId(): int
    {
        return (int)$this->db->lastInsertId();
    }
}

