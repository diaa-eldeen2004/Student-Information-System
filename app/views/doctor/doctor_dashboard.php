<?php
$doctor = $doctor ?? null;
$sections = $sections ?? [];
$assignments = $assignments ?? [];
$totalSections = $totalSections ?? count($sections);
$totalAssignments = $totalAssignments ?? count($assignments);
$totalStudents = $totalStudents ?? 0;
$pendingGradings = $pendingGradings ?? 0;
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Doctor Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($doctor['first_name'] ?? 'Doctor') ?> <?= htmlspecialchars($doctor['last_name'] ?? '') ?>!</p>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #3b82f6;">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalSections ?></h3>
                <p>Active Sections</p>
                <a href="<?= htmlspecialchars($url('doctor/course')) ?>" class="stat-link">View All →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #10b981;">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalAssignments ?></h3>
                <p>Total Assignments</p>
                <a href="<?= htmlspecialchars($url('doctor/assignments')) ?>" class="stat-link">View All →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #8b5cf6;">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalStudents ?></h3>
                <p>Total Students</p>
                <a href="<?= htmlspecialchars($url('doctor/course')) ?>" class="stat-link">View Courses →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #f59e0b;">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="stat-content">
                <h3><?= $pendingGradings ?></h3>
                <p>Pending Gradings</p>
                <a href="<?= htmlspecialchars($url('doctor/course')) ?>" class="stat-link">Grade Now →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #ef4444;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3>Attendance</h3>
                <p>Manage Student Attendance</p>
                <a href="<?= htmlspecialchars($url('doctor/attendance')) ?>" class="stat-link">Manage →</a>
            </div>
        </div>
    </div>

    <div class="dashboard-sections">
        <div class="dashboard-section">
            <h2><i class="fas fa-book"></i> My Sections</h2>
            <div class="section-list">
                <?php if (empty($sections)): ?>
                    <p class="text-muted">No sections assigned yet</p>
                <?php else: ?>
                    <?php foreach (array_slice($sections, 0, 5) as $section): ?>
                        <div class="section-item">
                            <div class="section-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="section-content">
                                <p><strong><?= htmlspecialchars($section['section_name'] ?? 'Section') ?></strong></p>
                                <p class="text-muted">
                                    Section ID: <?= htmlspecialchars($section['schedule_id'] ?? $section['section_id'] ?? 'N/A') ?>
                                    <span class="section-time">
                                        <?= htmlspecialchars($section['day'] ?? '') ?> 
                                        <?= htmlspecialchars($section['start_time'] ?? '') ?> - 
                                        <?= htmlspecialchars($section['end_time'] ?? '') ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="<?= htmlspecialchars($url('doctor/course')) ?>" class="btn btn-outline">View All Sections</a>
        </div>

        <div class="dashboard-section">
            <h2><i class="fas fa-tasks"></i> Recent Assignments</h2>
            <div class="assignment-list">
                <?php if (empty($assignments)): ?>
                    <p class="text-muted">No assignments created yet</p>
                <?php else: ?>
                    <?php foreach (array_slice($assignments, 0, 5) as $assignment): ?>
                        <div class="assignment-item">
                            <div class="assignment-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="assignment-content">
                                <p><strong><?= htmlspecialchars($assignment['title'] ?? 'Assignment') ?></strong></p>
                                <p class="text-muted">
                                    Due: <?= date('M d, Y', strtotime($assignment['due_date'] ?? 'now')) ?>
                                    <span class="assignment-type"><?= htmlspecialchars($assignment['type'] ?? 'homework') ?></span>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="<?= htmlspecialchars($url('doctor/assignments')) ?>" class="btn btn-outline">View All Assignments</a>
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
    background: var(--bg-tertiary);
    border-radius: 8px;
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
    background: var(--bg-tertiary);
    border-radius: 4px;
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
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
