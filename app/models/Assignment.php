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
                    (course_id, section_id, doctor_id, title, description, due_date, max_points, assignment_type)
                    VALUES 
                    (:course_id, :section_id, :doctor_id, :title, :description, :due_date, :max_points, :assignment_type)";
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
            
            foreach (['title', 'description', 'due_date', 'max_points', 'assignment_type'] as $field) {
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

