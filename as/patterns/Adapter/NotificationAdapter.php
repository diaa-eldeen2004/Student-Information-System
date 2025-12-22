<?php
namespace patterns\Adapter;

use models\Notification;

/**
 * Adapter Pattern - Structural
 * Adapts different notification systems to a common interface
 */
interface NotificationAdapter
{
    public function send(string $title, string $message, array $recipients, string $type = 'info'): bool;
}

/**
 * Email Notification Adapter (placeholder for future implementation)
 */
class EmailNotificationAdapter implements NotificationAdapter
{
    public function send(string $title, string $message, array $recipients, string $type = 'info'): bool
    {
        // Future implementation: send emails
        // For now, just return true as placeholder
        return true;
    }
}

/**
 * Notification Service that uses adapters
 */
class NotificationService
{
    private NotificationAdapter $adapter;

    public function __construct(NotificationAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function setAdapter(NotificationAdapter $adapter): void
    {
        $this->adapter = $adapter;
    }

    public function notify(string $title, string $message, array $recipients, string $type = 'info'): bool
    {
        return $this->adapter->send($title, $message, $recipients, $type);
    }
}

