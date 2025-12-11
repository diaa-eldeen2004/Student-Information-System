<?php
namespace patterns\Adapter;

use models\Notification;

// Ensure the interface is loaded
require_once __DIR__ . '/NotificationAdapter.php';

/**
 * Database Notification Adapter
 * Adapter Pattern - Structural
 * Adapts the Notification model to the NotificationAdapter interface
 */
class DatabaseNotificationAdapter implements NotificationAdapter
{
    private Notification $notificationModel;

    public function __construct(Notification $notificationModel)
    {
        $this->notificationModel = $notificationModel;
    }

    public function send(string $title, string $message, array $recipients, string $type = 'info'): bool
    {
        $success = true;
        foreach ($recipients as $userId) {
            $result = $this->notificationModel->create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
            ]);
            if (!$result) {
                $success = false;
            }
        }
        return $success;
    }
}

