<?php
$student = $student ?? null;
$gpa = $gpa ?? 0.00;
$enrolledCourses = $enrolledCourses ?? [];
$notifications = $notifications ?? [];
$upcomingAssignments = $upcomingAssignments ?? [];
$recentGrades = $recentGrades ?? [];
$currentSemester = $currentSemester ?? 'Fall';
$currentYear = $currentYear ?? date('Y');
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-graduation-cap"></i> Student Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($student['first_name'] ?? 'Student') ?> <?= htmlspecialchars($student['last_name'] ?? '') ?>!</p>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #3b82f6;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <h3><?= number_format($gpa, 2) ?></h3>
                <p>Current GPA</p>
                <a href="<?= htmlspecialchars($url('student/profile')) ?>" class="stat-link">View Details →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #10b981;">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3><?= count($enrolledCourses) ?></h3>
                <p>Enrolled Courses</p>
                <a href="<?= htmlspecialchars($url('student/course')) ?>" class="stat-link">View All →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #8b5cf6;">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-content">
                <h3><?= count($upcomingAssignments) ?></h3>
                <p>Upcoming Assignments</p>
                <a href="<?= htmlspecialchars($url('student/assignments')) ?>" class="stat-link">View All →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #f59e0b;">
                <i class="fas fa-bell"></i>
            </div>
            <div class="stat-content">
                <h3><?= count($notifications) ?></h3>
                <p>Unread Notifications</p>
                <a href="<?= htmlspecialchars($url('student/notifications')) ?>" class="stat-link">View All →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #ef4444;">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3><?= count($enrolledCourses) ?></h3>
                <p>Active Courses</p>
                <a href="<?= htmlspecialchars($url('student/course')) ?>" class="stat-link">View All Courses →</a>
            </div>
        </div>
    </div>

    <div class="dashboard-sections">
        <div class="dashboard-section">
            <h2><i class="fas fa-book"></i> My Courses (<?= htmlspecialchars($currentSemester) ?> <?= htmlspecialchars($currentYear) ?>)</h2>
            <div class="section-list">
                <?php if (empty($enrolledCourses)): ?>
                    <p class="text-muted">No courses enrolled yet. <a href="<?= htmlspecialchars($url('student/schedule')) ?>" style="color: var(--primary-color); text-decoration: none;">Browse available courses</a></p>
                <?php else: ?>
                    <?php foreach (array_slice($enrolledCourses, 0, 5) as $course): ?>
                        <div class="section-item">
                            <div class="section-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="section-content">
                                <p><strong><?= htmlspecialchars($course['course_code'] ?? '') ?> - <?= htmlspecialchars($course['course_name'] ?? '') ?></strong></p>
                                <p class="text-muted">
                                    Section: <?= htmlspecialchars($course['section_number'] ?? 'N/A') ?> | 
                                    <?= htmlspecialchars($course['doctor_first_name'] ?? '') ?> <?= htmlspecialchars($course['doctor_last_name'] ?? '') ?>
                                    <?php if (!empty($course['day_of_week']) && !empty($course['start_time'])): ?>
                                        <span class="section-time">
                                            <?= htmlspecialchars($course['day_of_week']) ?> <?= htmlspecialchars($course['start_time']) ?>-<?= htmlspecialchars($course['end_time'] ?? '') ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if (count($enrolledCourses) > 5): ?>
                <a href="<?= htmlspecialchars($url('student/course')) ?>" class="btn btn-outline">View All Courses</a>
            <?php endif; ?>
        </div>

        <div class="dashboard-section">
            <h2><i class="fas fa-tasks"></i> Upcoming Assignments</h2>
            <div class="assignment-list">
                <?php if (empty($upcomingAssignments)): ?>
                    <p class="text-muted">No upcoming assignments</p>
                <?php else: ?>
                    <?php foreach ($upcomingAssignments as $assignment): ?>
                        <div class="assignment-item">
                            <div class="assignment-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="assignment-content">
                                <p><strong><?= htmlspecialchars($assignment['title'] ?? '') ?></strong></p>
                                <p class="text-muted">
                                    <?= htmlspecialchars($assignment['course_code'] ?? '') ?> | 
                                    Due: <?= !empty($assignment['due_date']) ? date('M d, Y', strtotime($assignment['due_date'])) : 'No due date' ?>
                                    <span class="assignment-type"><?= htmlspecialchars($assignment['assignment_type'] ?? 'homework') ?></span>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="<?= htmlspecialchars($url('student/assignments')) ?>" class="btn btn-outline">View All Assignments</a>
        </div>

        <div class="dashboard-section">
            <h2><i class="fas fa-star"></i> Recent Grades</h2>
            <div class="assignment-list">
                <?php if (empty($recentGrades)): ?>
                    <p class="text-muted">No graded assignments yet</p>
                <?php else: ?>
                    <?php foreach ($recentGrades as $assignment): ?>
                        <div class="assignment-item">
                            <div class="assignment-icon" style="background-color: <?= ($assignment['grade'] ?? 0) >= ($assignment['max_points'] ?? 100) * 0.7 ? '#10b981' : (($assignment['grade'] ?? 0) >= ($assignment['max_points'] ?? 100) * 0.5 ? '#f59e0b' : '#ef4444') ?>;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="assignment-content">
                                <p><strong><?= htmlspecialchars($assignment['title'] ?? '') ?></strong></p>
                                <p class="text-muted">
                                    <?= htmlspecialchars($assignment['course_code'] ?? '') ?> | 
                                    Grade: <strong style="color: var(--primary-color);">
                                        <?= number_format($assignment['grade'] ?? 0, 1) ?> / <?= htmlspecialchars($assignment['max_points'] ?? 100) ?>
                                    </strong>
                                    <?php if (!empty($assignment['feedback'])): ?>
                                        <span style="display: block; margin-top: 0.25rem; font-style: italic; color: var(--text-secondary);">
                                            <?= htmlspecialchars(substr($assignment['feedback'], 0, 60)) ?><?= strlen($assignment['feedback']) > 60 ? '...' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if (count($recentGrades) > 5): ?>
                <a href="<?= htmlspecialchars($url('student/assignments')) ?>" class="btn btn-outline">View All Grades</a>
            <?php endif; ?>
        </div>

        <div class="dashboard-section">
            <h2><i class="fas fa-bell"></i> Recent Notifications</h2>
            <div class="activity-list">
                <?php if (empty($notifications)): ?>
                    <p class="text-muted">No new notifications</p>
                <?php else: ?>
                    <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-circle"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?= htmlspecialchars($notification['title'] ?? '') ?></strong></p>
                                <p class="text-muted">
                                    <?= htmlspecialchars(substr($notification['message'] ?? '', 0, 100)) ?>
                                    <?= strlen($notification['message'] ?? '') > 100 ? '...' : '' ?>
                                    <span class="activity-time"><?= !empty($notification['created_at']) ? date('M d, Y H:i', strtotime($notification['created_at'])) : '' ?></span>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="<?= htmlspecialchars($url('student/notifications')) ?>" class="btn btn-outline">View All Notifications</a>
        </div>
    </div>
</div>

<style>
/* Use CSS variables from main style.css - they automatically change with data-theme */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    background: var(--background-color);
    min-height: 100vh;
    transition: background-color 0.3s ease;
    color: var(--text-primary);
}

[data-theme="light"] .dashboard-container {
    background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
}

[data-theme="dark"] .dashboard-container {
    background: var(--background-color);
}

.dashboard-header {
    margin-bottom: 2.5rem;
    padding: 2rem;
    background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%);
    border-radius: 16px;
    color: white;
    box-shadow: 0 10px 25px var(--shadow-color);
    transition: background 0.3s ease, box-shadow 0.3s ease;
}

[data-theme="dark"] .dashboard-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    box-shadow: 0 10px 25px var(--shadow-color);
}

.dashboard-header h1 {
    font-size: 2.5rem;
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: white;
}

.dashboard-header h1 i {
    font-size: 2rem;
}

.dashboard-header p {
    font-size: 1.1rem;
    margin: 0;
    opacity: 0.95;
    color: white;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: var(--surface-color);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    box-shadow: 0 4px 12px var(--shadow-color);
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
    box-shadow: 0 12px 32px var(--shadow-color);
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
    box-shadow: 0 4px 12px var(--shadow-color);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.stat-card:hover .stat-icon {
    transform: rotate(5deg) scale(1.1);
    box-shadow: 0 6px 20px var(--shadow-color);
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
    transition: color 0.3s ease;
}

[data-theme="dark"] .stat-content h3 {
    color: var(--text-primary);
}

.stat-content p {
    margin: 0.5rem 0;
    color: var(--text-secondary);
    font-size: 0.95rem;
    font-weight: 500;
    transition: color 0.3s ease;
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
    color: var(--primary-color);
    opacity: 0.8;
    gap: 0.5rem;
    text-decoration: none;
}

.dashboard-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 2rem;
}

.dashboard-section {
    background: var(--surface-color);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 12px var(--shadow-color);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.dashboard-section:hover {
    box-shadow: 0 8px 24px var(--shadow-color);
    transform: translateY(-2px);
    border-color: var(--primary-color);
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

.section-list, .assignment-list {
    margin-bottom: 1rem;
}

.section-item, .assignment-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    transition: all 0.2s ease;
}

.section-item:hover, .assignment-item:hover {
    background: var(--background-color);
    border-radius: 8px;
}

[data-theme="dark"] .section-item:hover, 
[data-theme="dark"] .assignment-item:hover {
    background: var(--surface-color);
}

.section-item:last-child, .assignment-item:last-child {
    border-bottom: none;
}

.section-icon, .assignment-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
}

.section-content p, .assignment-content p {
    margin: 0.25rem 0;
}

.text-muted {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.section-time, .assignment-type {
    margin-left: 1rem;
    padding: 0.25rem 0.5rem;
    background: var(--background-color);
    border-radius: 4px;
    font-size: 0.85rem;
    transition: background-color 0.3s ease;
}

[data-theme="dark"] .section-time, 
[data-theme="dark"] .assignment-type {
    background: var(--surface-color);
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
    background: var(--background-color);
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
    background: var(--background-color);
    margin: 0 -1rem;
    padding-left: 1rem;
    padding-right: 1rem;
    border-radius: 8px;
}

[data-theme="dark"] .activity-item:hover {
    background: var(--surface-color);
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
    box-shadow: 0 4px 12px var(--shadow-color);
}

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
        font-size: 1.75rem;
    }
    
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
    
    .dashboard-sections {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Show success/error messages
<?php if (isset($_SESSION['success'])): ?>
    if (typeof showNotification === 'function') {
        showNotification('<?= htmlspecialchars($_SESSION['success']) ?>', 'success');
    }
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    if (typeof showNotification === 'function') {
        showNotification('<?= htmlspecialchars($_SESSION['error']) ?>', 'error');
    }
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
</script>
