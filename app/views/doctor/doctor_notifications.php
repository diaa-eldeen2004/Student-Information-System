<?php
$notifications = $notifications ?? [];
$unreadCount = $unreadCount ?? 0;
?>

<div class="notifications-container">
    <div class="notifications-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-bell"></i> Notifications</h1>
                <p>View and manage your notifications</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('doctor/send-notification')) ?>" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Notification
                </a>
            </div>
        </div>
    </div>

    <?php if ($unreadCount > 0): ?>
        <div class="alert alert-info" style="margin-bottom: 1.5rem;">
            <i class="fas fa-info-circle"></i>
            You have <?= $unreadCount ?> unread notification<?= $unreadCount > 1 ? 's' : '' ?>
        </div>
    <?php endif; ?>

    <div class="notifications-list">
        <?php if (empty($notifications)): ?>
            <div class="card text-center" style="padding: 3rem;">
                <i class="fas fa-bell-slash" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary);">No notifications yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="card notification-item <?= !($notification['is_read'] ?? false) ? 'unread' : '' ?>">
                    <div class="notification-content">
                        <div class="notification-icon">
                            <?php
                            $icon = 'fa-info-circle';
                            $color = '#3b82f6';
                            switch ($notification['type'] ?? 'info') {
                                case 'success':
                                    $icon = 'fa-check-circle';
                                    $color = '#10b981';
                                    break;
                                case 'warning':
                                    $icon = 'fa-exclamation-triangle';
                                    $color = '#f59e0b';
                                    break;
                                case 'error':
                                    $icon = 'fa-times-circle';
                                    $color = '#ef4444';
                                    break;
                            }
                            ?>
                            <i class="fas <?= $icon ?>" style="color: <?= $color ?>;"></i>
                        </div>
                        <div class="notification-body">
                            <h3><?= htmlspecialchars($notification['title'] ?? 'Notification') ?></h3>
                            <p><?= htmlspecialchars($notification['message'] ?? '') ?></p>
                            <div class="notification-meta">
                                <span class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <?= date('M d, Y H:i', strtotime($notification['created_at'] ?? 'now')) ?>
                                </span>
                                <?php if (!($notification['is_read'] ?? false)): ?>
                                    <span class="badge badge-unread">Unread</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!($notification['is_read'] ?? false)): ?>
                            <div class="notification-actions">
                                <form method="POST" action="<?= htmlspecialchars($url('doctor/notifications')) ?>" style="display: inline;">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?? '' ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" title="Mark as read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.notifications-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.notifications-header h1 {
    font-size: 2rem;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.notifications-header p {
    color: var(--text-muted);
}

.notification-item {
    margin-bottom: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1.5rem;
    transition: all 0.2s;
}

.notification-item.unread {
    background: var(--bg-secondary);
    border-left: 4px solid var(--primary-color);
}

.notification-content {
    display: flex;
    align-items: start;
    gap: 1rem;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bg-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-body {
    flex: 1;
}

.notification-body h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    color: var(--text-color);
}

.notification-body p {
    margin: 0 0 0.75rem 0;
    color: var(--text-color);
    line-height: 1.6;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.notification-time {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-unread {
    background: var(--primary-color);
    color: white;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-info {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    opacity: 0.9;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

.btn-outline:hover {
    background: var(--bg-secondary);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
}

.text-center {
    text-align: center;
}
</style>
