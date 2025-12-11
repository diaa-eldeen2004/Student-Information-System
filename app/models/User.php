<?php
namespace models;

use core\Model;
use PDO;

class User extends Model
{
    private string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function createUser(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} (first_name, last_name, email, phone, password, role)
                    VALUES (:first_name, :last_name, :email, :phone, :password, :role)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? '',
                'password' => $data['password'],
                'role' => $data['role'] ?? 'user',
            ]);
            return $result && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("User creation failed: " . $e->getMessage());
            throw new \RuntimeException("Failed to create user: " . $e->getMessage());
        }
    }
}

