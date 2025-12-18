<?php
namespace models;

use core\Model;
use PDO;

class Material extends Model
{
    private string $table = 'materials';

    public function findById(int $materialId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, c.course_code, c.name as course_name,
                   s.section_number, u.first_name as doctor_first_name, u.last_name as doctor_last_name
            FROM {$this->table} m
            JOIN courses c ON m.course_id = c.course_id
            LEFT JOIN schedule s ON m.section_id = s.schedule_id
            JOIN doctors d ON m.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.id
            WHERE m.material_id = :material_id LIMIT 1
        ");
        $stmt->execute(['material_id' => $materialId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getByCourse(int $courseId, ?int $sectionId = null): array
    {
        $where = ["m.course_id = :course_id"];
        $params = ['course_id' => $courseId];
        
        if ($sectionId !== null) {
            $where[] = "(m.section_id = :section_id OR m.section_id IS NULL)";
            $params['section_id'] = $sectionId;
        }
        
        $whereClause = implode(' AND ', $where);
        $stmt = $this->db->prepare("
            SELECT m.*, c.course_code, c.name as course_name,
                   s.section_number
            FROM {$this->table} m
            JOIN courses c ON m.course_id = c.course_id
            LEFT JOIN schedule s ON m.section_id = s.schedule_id
            WHERE {$whereClause}
            ORDER BY m.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDoctor(int $doctorId, ?int $courseId = null): array
    {
        $where = ["m.doctor_id = :doctor_id"];
        $params = ['doctor_id' => $doctorId];
        
        if ($courseId !== null) {
            $where[] = "m.course_id = :course_id";
            $params['course_id'] = $courseId;
        }
        
        $whereClause = implode(' AND ', $where);
        $stmt = $this->db->prepare("
            SELECT m.*, c.course_code, c.name as course_name,
                   s.section_number
            FROM {$this->table} m
            JOIN courses c ON m.course_id = c.course_id
            LEFT JOIN schedule s ON m.section_id = s.schedule_id
            WHERE {$whereClause}
            ORDER BY m.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (course_id, section_id, doctor_id, title, description, file_path, file_name, file_type, file_size, material_type)
                    VALUES 
                    (:course_id, :section_id, :doctor_id, :title, :description, :file_path, :file_name, :file_type, :file_size, :material_type)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'course_id' => $data['course_id'],
                'section_id' => $data['section_id'] ?? null,
                'doctor_id' => $data['doctor_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'file_path' => $data['file_path'],
                'file_name' => $data['file_name'],
                'file_type' => $data['file_type'] ?? null,
                'file_size' => $data['file_size'] ?? null,
                'material_type' => $data['material_type'] ?? 'other',
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Material creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $materialId, array $data): bool
    {
        try {
            $fields = [];
            $params = ['material_id' => $materialId];
            
            $allowedFields = ['title', 'description', 'file_path', 'file_name', 'file_type', 'file_size', 'material_type'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "{$field} = :{$field}";
                    $params[$field] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE material_id = :material_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Material update failed: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $materialId): bool
    {
        try {
            // Get file path before deletion
            $material = $this->findById($materialId);
            if ($material && !empty($material['file_path'])) {
                $filePath = dirname(__DIR__, 2) . '/public' . $material['file_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE material_id = :material_id");
            return $stmt->execute(['material_id' => $materialId]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Material deletion failed: " . $e->getMessage());
            return false;
        }
    }

    public function getLastInsertId(): int
    {
        return (int)$this->db->lastInsertId();
    }
}

