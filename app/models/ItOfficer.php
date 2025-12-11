<?php
namespace models;

use core\Model;
use PDO;

class ItOfficer extends Model
{
    private string $table = 'it_officers';

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT it.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} it 
                                    JOIN users u ON it.user_id = u.id 
                                    WHERE it.user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        $itOfficer = $stmt->fetch(PDO::FETCH_ASSOC);
        return $itOfficer ?: null;
    }

    public function findByItId(int $itId): ?array
    {
        $stmt = $this->db->prepare("SELECT it.*, u.first_name, u.last_name, u.email, u.phone 
                                    FROM {$this->table} it 
                                    JOIN users u ON it.user_id = u.id 
                                    WHERE it.it_id = :it_id LIMIT 1");
        $stmt->execute(['it_id' => $itId]);
        $itOfficer = $stmt->fetch(PDO::FETCH_ASSOC);
        return $itOfficer ?: null;
    }

    public function createItOfficer(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} (user_id)
                    VALUES (:user_id)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'user_id' => $data['user_id'],
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("IT Officer creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function getAllItOfficers(): array
    {
        $stmt = $this->db->query("SELECT it.*, u.first_name, u.last_name, u.email, u.phone 
                                  FROM {$this->table} it 
                                  JOIN users u ON it.user_id = u.id 
                                  ORDER BY u.last_name, u.first_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

