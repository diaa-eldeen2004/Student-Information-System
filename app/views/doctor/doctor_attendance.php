<?php
$sections = $sections ?? [];
?>

<div class="attendance-container">
    <div class="attendance-header">
        <div>
            <h1><i class="fas fa-calendar-check"></i> Attendance Management</h1>
            <p>Manage and view student attendance for your sections</p>
        </div>
    </div>

    <div class="sections-list">
        <?php if (empty($sections)): ?>
            <div class="card text-center" style="padding: 3rem;">
                <i class="fas fa-calendar-check" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary);">No sections assigned yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($sections as $section): ?>
                <div class="card section-card">
                    <div class="section-header">
                        <div>
                            <h2><?= htmlspecialchars($section['section_name'] ?? 'Section') ?></h2>
                            <p class="section-meta">
                                <?= htmlspecialchars($section['course_code'] ?? 'N/A') ?> • 
                                <?= htmlspecialchars($section['day'] ?? '') ?> 
                                <?= htmlspecialchars($section['start_time'] ?? '') ?> - <?= htmlspecialchars($section['end_time'] ?? '') ?>
                            </p>
                        </div>
                        <a href="<?= htmlspecialchars($url('doctor/take-attendance?section_id=' . ($section['section_id'] ?? ''))) ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Take Attendance
                        </a>
                    </div>
                    <div class="section-body">
                        <?php if (isset($section['attendance_stats'])): ?>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-icon" style="background: #10b981;">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?= $section['attendance_stats']['present_count'] ?? 0 ?></h3>
                                        <p>Present</p>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon" style="background: #f59e0b;">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?= $section['attendance_stats']['absent_count'] ?? 0 ?></h3>
                                        <p>Absent</p>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon" style="background: #3b82f6;">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?= $section['attendance_stats']['late_count'] ?? 0 ?></h3>
                                        <p>Late</p>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon" style="background: #6b7280;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3><?= $section['attendance_stats']['total_students'] ?? 0 ?></h3>
                                        <p>Total Students</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No attendance data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.attendance-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.attendance-header h1 {
    font-size: 2rem;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.attendance-header p {
    color: var(--text-muted);
}

.section-card {
    margin-bottom: 2rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.section-header h2 {
    margin: 0;
    font-size: 1.3rem;
    color: var(--text-color);
}

.section-meta {
    margin: 0.5rem 0 0 0;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.section-body {
    padding: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 8px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-content h3 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--text-color);
}

.stat-content p {
    margin: 0.25rem 0 0 0;
    color: var(--text-muted);
    font-size: 0.9rem;
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

.card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
}

.text-center {
    text-align: center;
}

.text-muted {
    color: var(--text-muted);
}
</style>
