<?php
$itOfficer = $itOfficer ?? null;
$pendingRequestsCount = $pendingRequestsCount ?? 0;
$recentLogs = $recentLogs ?? [];
$totalCourses = $totalCourses ?? 0;
$totalSections = $totalSections ?? 0;
$totalDoctors = $totalDoctors ?? 0;
$totalStudents = $totalStudents ?? 0;
$approvedEnrollmentsCount = $approvedEnrollmentsCount ?? 0;
$rejectedEnrollmentsCount = $rejectedEnrollmentsCount ?? 0;
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
                <h3><?= $totalSections ?? 0 ?></h3>
                <p>Total Sections</p>
                <a href="<?= htmlspecialchars($url('it/schedule')) ?>" class="stat-link">Manage Schedule →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #f59e0b;">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalCourses ?? 0 ?></h3>
                <p>Total Courses</p>
                <a href="<?= htmlspecialchars($url('it/course')) ?>" class="stat-link">View Courses →</a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #8b5cf6;">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalDoctors ?? 0 ?></h3>
                <p>Total Doctors</p>
                <a href="<?= htmlspecialchars($url('it/course')) ?>" class="stat-link">Manage →</a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #06b6d4;">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalStudents ?? 0 ?></h3>
                <p>Total Students</p>
                <a href="<?= htmlspecialchars($url('it/course')) ?>" class="stat-link">Manage →</a>
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
/* Dark Mode CSS Variables */
:root {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-tertiary: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --border-color: #334155;
    --border-light: #475569;
    --primary-color: #3b82f6;
    --primary-hover: #2563eb;
    --success-color: #10b981;
    --error-color: #ef4444;
    --warning-color: #f59e0b;
    --shadow-sm: rgba(0, 0, 0, 0.3);
    --shadow-md: rgba(0, 0, 0, 0.4);
    --shadow-lg: rgba(0, 0, 0, 0.5);
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    background: var(--bg-primary);
    min-height: 100vh;
    color: var(--text-primary);
}

.dashboard-header {
    margin-bottom: 2.5rem;
    padding: 2rem;
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
    border-radius: 16px;
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 12px var(--shadow-md);
}

.dashboard-header h1 {
    font-size: 2.5rem;
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.dashboard-header h1 i {
    font-size: 2rem;
}

.dashboard-header p {
    font-size: 1.1rem;
    margin: 0;
    opacity: 0.95;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: var(--bg-secondary);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    box-shadow: 0 4px 12px var(--shadow-md);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s;
}

.stat-card:hover::before {
    transform: translateX(100%);
}

.stat-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 12px 32px var(--shadow-lg);
    border-color: var(--primary-color);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.stat-card:hover .stat-icon {
    transform: rotate(5deg) scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
}

.stat-content {
    flex: 1;
    min-width: 0;
}

.stat-content h3 {
    font-size: 2rem;
    margin: 0 0 0.5rem 0;
    color: var(--text-primary);
    font-weight: 700;
    line-height: 1;
}

.stat-content p {
    margin: 0.5rem 0;
    color: var(--text-secondary);
    font-size: 0.95rem;
    font-weight: 500;
}

.stat-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-top: 0.75rem;
    transition: all 0.2s ease;
}

.stat-link:hover {
    color: var(--primary-hover);
    gap: 0.5rem;
    text-decoration: none;
}

.dashboard-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 2rem;
}

.dashboard-section {
    background: var(--bg-secondary);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 12px var(--shadow-md);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.dashboard-section:hover {
    box-shadow: 0 8px 24px var(--shadow-lg);
    transform: translateY(-2px);
}

.dashboard-section h2 {
    font-size: 1.75rem;
    margin: 0 0 1.5rem 0;
    color: var(--text-primary);
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-color);
}

.dashboard-section h2 i {
    color: var(--primary-color);
}

.activity-list {
    max-height: 450px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.activity-list::-webkit-scrollbar {
    width: 6px;
}

.activity-list::-webkit-scrollbar-track {
    background: var(--bg-primary);
    border-radius: 10px;
}

.activity-list::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 10px;
}

.activity-list::-webkit-scrollbar-thumb:hover {
    background: var(--text-secondary);
}

.activity-item {
    display: flex;
    gap: 1rem;
    padding: 1.25rem 0;
    border-bottom: 1px solid var(--border-color);
    transition: all 0.2s ease;
}

.activity-item:hover {
    background: var(--bg-tertiary);
    margin: 0 -1rem;
    padding-left: 1rem;
    padding-right: 1rem;
    border-radius: 8px;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    color: var(--primary-color);
    font-size: 0.5rem;
    margin-top: 0.5rem;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-content p {
    margin: 0.25rem 0;
    line-height: 1.5;
}

.activity-content p strong {
    color: var(--text-primary);
    font-weight: 600;
}

.activity-time {
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin-left: 0.75rem;
    font-weight: 500;
}

.text-muted {
    color: var(--text-secondary);
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
    margin-top: 1rem;
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .dashboard-header {
        padding: 1.5rem;
    }
    
    .dashboard-header h1 {
        font-size: 2rem;
    }
    
    .stat-card {
        padding: 1.5rem;
    }
    
    .stat-content h3 {
        font-size: 2rem;
    }
    
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
    
    .dashboard-sections {
        grid-template-columns: 1fr;
    }
}

/* Animation for stat cards */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stat-card {
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }
.stat-card:nth-child(5) { animation-delay: 0.5s; }
.stat-card:nth-child(6) { animation-delay: 0.6s; }
</style>
