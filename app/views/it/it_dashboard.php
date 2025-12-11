<?php
$itOfficer = $itOfficer ?? null;
$pendingRequestsCount = $pendingRequestsCount ?? 0;
$recentLogs = $recentLogs ?? [];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> IT Officer Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($itOfficer['first_name'] ?? 'IT Officer') ?>!</p>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #3b82f6;">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-content">
                <h3><?= $pendingRequestsCount ?></h3>
                <p>Pending Enrollment Requests</p>
                <a href="<?= htmlspecialchars($url('it/enrollments')) ?>" class="stat-link">View All →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #10b981;">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <h3>Schedule</h3>
                <p>Manage Semester Schedule</p>
                <a href="<?= htmlspecialchars($url('it/schedule')) ?>" class="stat-link">Manage →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #f59e0b;">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3>Courses</h3>
                <p>Course Management</p>
                <a href="<?= htmlspecialchars($url('it/course')) ?>" class="stat-link">View Courses →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #ef4444;">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3>Audit Logs</h3>
                <p>System Activity Logs</p>
                <a href="<?= htmlspecialchars($url('it/logs')) ?>" class="stat-link">View Logs →</a>
            </div>
        </div>
    </div>

    <div class="dashboard-sections">
        <div class="dashboard-section">
            <h2><i class="fas fa-history"></i> Recent Activity</h2>
            <div class="activity-list">
                <?php if (empty($recentLogs)): ?>
                    <p class="text-muted">No recent activity</p>
                <?php else: ?>
                    <?php foreach (array_slice($recentLogs, 0, 10) as $log): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-circle"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?= htmlspecialchars($log['action'] ?? 'Action') ?></strong></p>
                                <p class="text-muted">
                                    <?= htmlspecialchars($log['first_name'] ?? 'System') ?> <?= htmlspecialchars($log['last_name'] ?? '') ?>
                                    <span class="activity-time"><?= date('M d, Y H:i', strtotime($log['created_at'] ?? 'now')) ?></span>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="<?= htmlspecialchars($url('it/logs')) ?>" class="btn btn-outline">View All Logs</a>
        </div>

    </div>
</div>

<style>
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.dashboard-header {
    margin-bottom: 2rem;
}

.dashboard-header h1 {
    font-size: 2rem;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--surface-color);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 8px var(--shadow-color);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-content h3 {
    font-size: 2rem;
    margin: 0;
    color: var(--text-primary);
}

.stat-content p {
    margin: 0.25rem 0;
    color: var(--text-secondary);
}

.stat-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
}

.stat-link:hover {
    text-decoration: underline;
}

.dashboard-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.dashboard-section {
    background: var(--surface-color);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px var(--shadow-color);
}

.dashboard-section h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--text-primary);
}

.activity-list {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    color: var(--primary-color);
    font-size: 0.5rem;
}

.activity-content p {
    margin: 0.25rem 0;
}

.activity-time {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-left: 0.5rem;
}

</style>
