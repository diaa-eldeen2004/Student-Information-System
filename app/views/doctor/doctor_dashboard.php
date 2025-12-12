<?php
$doctor = $doctor ?? null;
$sections = $sections ?? [];
$assignments = $assignments ?? [];
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
                <h3><?= count($sections) ?></h3>
                <p>Active Sections</p>
                <a href="<?= htmlspecialchars($url('doctor/course')) ?>" class="stat-link">View All →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #10b981;">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-content">
                <h3><?= count($assignments) ?></h3>
                <p>Total Assignments</p>
                <a href="<?= htmlspecialchars($url('doctor/assignments')) ?>" class="stat-link">View All →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #f59e0b;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3>Attendance</h3>
                <p>Manage Student Attendance</p>
                <a href="<?= htmlspecialchars($url('doctor/attendance')) ?>" class="stat-link">Manage →</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background-color: #ef4444;">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <h3>Calendar</h3>
                <p>View Schedule & Events</p>
                <a href="<?= htmlspecialchars($url('doctor/calendar')) ?>" class="stat-link">View Calendar →</a>
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
                                    Section ID: <?= htmlspecialchars($section['section_id'] ?? 'N/A') ?>
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
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.dashboard-header p {
    color: var(--text-muted);
    font-size: 1.1rem;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-content h3 {
    font-size: 2rem;
    margin: 0;
    color: var(--text-color);
}

.stat-content p {
    margin: 0.5rem 0;
    color: var(--text-muted);
}

.stat-link {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
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
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1.5rem;
}

.dashboard-section h2 {
    font-size: 1.3rem;
    margin-bottom: 1rem;
    color: var(--text-color);
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
}

.section-item:last-child, .assignment-item:last-child {
    border-bottom: none;
}

.section-icon, .assignment-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.section-content p, .assignment-content p {
    margin: 0.25rem 0;
}

.text-muted {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.section-time, .assignment-type {
    margin-left: 1rem;
    padding: 0.25rem 0.5rem;
    background: var(--bg-secondary);
    border-radius: 4px;
    font-size: 0.85rem;
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

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

.btn-outline:hover {
    background: var(--bg-secondary);
}
</style>
