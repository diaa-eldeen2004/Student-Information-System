<?php
$student = $student ?? null;
$notifications = $notifications ?? [];
$unread = $unread ?? [];
$byType = $byType ?? ['info' => [], 'success' => [], 'warning' => [], 'error' => []];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-bell"></i> Notifications</h1>
                <p>Stay updated with important information</p>
            </div>
            <a href="<?= htmlspecialchars($url('student/send-notification')) ?>" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Send Message
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card text-center">
            <div style="padding: 1.5rem;">
                <h3 style="color: var(--primary-color); margin: 0 0 0.5rem 0;"><?= count($notifications) ?></h3>
                <p style="margin: 0;">Total</p>
            </div>
        </div>
        <div class="card text-center">
            <div style="padding: 1.5rem;">
                <h3 style="color: var(--warning-color); margin: 0 0 0.5rem 0;"><?= count($unread) ?></h3>
                <p style="margin: 0;">Unread</p>
            </div>
        </div>
        <div class="card text-center">
            <div style="padding: 1.5rem;">
                <h3 style="color: var(--success-color); margin: 0 0 0.5rem 0;"><?= count($byType['success'] ?? []) ?></h3>
                <p style="margin: 0;">Success</p>
            </div>
        </div>
        <div class="card text-center">
            <div style="padding: 1.5rem;">
                <h3 style="color: var(--error-color); margin: 0 0 0.5rem 0;"><?= count($byType['error'] ?? []) ?></h3>
                <p style="margin: 0;">Alerts</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>All Notifications</h3>
        </div>
        <div style="padding: 1.5rem;">
            <?php if (empty($notifications)): ?>
                <p class="text-muted text-center" style="padding: 3rem 0;">No notifications</p>
            <?php else: ?>
                <div class="notification-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?= empty($notification['is_read']) ? 'unread' : '' ?>" style="display: flex; align-items: start; padding: 1rem; border-bottom: 1px solid var(--border-color); transition: background-color 0.2s; <?= empty($notification['is_read']) ? 'background-color: rgba(37, 99, 235, 0.05);' : '' ?>">
                            <div style="margin-right: 1rem; font-size: 1.5rem;">
                                <?php
                                $type = $notification['type'] ?? 'info';
                                $iconClass = 'fa-info-circle';
                                $colorClass = 'var(--primary-color)';
                                if ($type === 'success') { $iconClass = 'fa-check-circle'; $colorClass = 'var(--success-color)'; }
                                elseif ($type === 'warning') { $iconClass = 'fa-exclamation-triangle'; $colorClass = 'var(--warning-color)'; }
                                elseif ($type === 'error') { $iconClass = 'fa-times-circle'; $colorClass = 'var(--error-color)'; }
                                ?>
                                <i class="fas <?= $iconClass ?>" style="color: <?= $colorClass ?>;"></i>
                            </div>
                            <div style="flex: 1;">
                                <h5 style="margin: 0 0 0.5rem 0;">
                                    <?= htmlspecialchars($notification['title'] ?? '') ?>
                                    <?php if (empty($notification['is_read'])): ?>
                                        <span class="badge" style="background-color: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; margin-left: 0.5rem;">New</span>
                                    <?php endif; ?>
                                </h5>
                                <p style="margin: 0 0 0.5rem 0;"><?= htmlspecialchars($notification['message'] ?? '') ?></p>
                                <small class="text-muted">
                                    <?= !empty($notification['created_at']) ? date('M d, Y H:i', strtotime($notification['created_at'])) : 'N/A' ?>
                                </small>
                            </div>
                            <div style="margin-left: 1rem;">
                                <?php if (empty($notification['is_read'])): ?>
                                    <a href="<?= htmlspecialchars($url('student/notifications?mark_read=' . $notification['notification_id'])) ?>" 
                                       class="btn btn-sm btn-outline">
                                        Mark as Read
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.notification-item:hover {
    background-color: var(--background-color) !important;
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
</style>
