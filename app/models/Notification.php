<?php
namespace models;

use core\Model;
use PDO;

class Notification extends Model
{
    private string $table = 'notifications';

    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO {$this->table} (user_id, title, message, type, related_id, related_type)
                    VALUES (:user_id, :title, :message, :type, :related_id, :related_type)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'user_id' => $data['user_id'],
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => $data['type'] ?? 'info',
                'related_id' => $data['related_id'] ?? null,
                'related_type' => $data['related_type'] ?? null,
            ]) && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function getByUserId(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnreadByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE user_id = :user_id AND is_read = FALSE
            ORDER BY created_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET is_read = TRUE 
                                       WHERE notification_id = :notification_id AND user_id = :user_id");
            return $stmt->execute([
                'notification_id' => $notificationId,
                'user_id' => $userId,
            ]);
        } catch (\PDOException $e) {
            error_log("Notification mark as read failed: " . $e->getMessage());
            return false;
        }
    }
}

