<?php
$sections = $sections ?? [];
$assignments = $assignments ?? [];
?>

<div class="calendar-container">
    <div class="calendar-header">
        <div>
            <h1><i class="fas fa-calendar-alt"></i> Calendar</h1>
            <p>View your schedule and upcoming assignments</p>
        </div>
    </div>

    <div class="calendar-content">
        <div class="calendar-section">
            <h2><i class="fas fa-chalkboard-teacher"></i> Schedule</h2>
            <div class="schedule-list">
                <?php if (empty($sections)): ?>
                    <p class="text-muted">No sections scheduled</p>
                <?php else: ?>
                    <?php foreach ($sections as $section): ?>
                        <div class="schedule-item">
                            <div class="schedule-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="schedule-content">
                                <h3><?= htmlspecialchars($section['section_name'] ?? 'Section') ?></h3>
                                <p class="schedule-meta">
                                    <?= htmlspecialchars($section['course_code'] ?? 'N/A') ?> • 
                                    <?= htmlspecialchars($section['day'] ?? '') ?> 
                                    <?= htmlspecialchars($section['start_time'] ?? '') ?> - <?= htmlspecialchars($section['end_time'] ?? '') ?>
                                </p>
                                <p class="schedule-location">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($section['room'] ?? 'TBA') ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="calendar-section">
            <h2><i class="fas fa-tasks"></i> Upcoming Assignments</h2>
            <div class="assignments-list">
                <?php if (empty($assignments)): ?>
                    <p class="text-muted">No upcoming assignments</p>
                <?php else: ?>
                    <?php 
                    // Sort assignments by due date
                    usort($assignments, function($a, $b) {
                        return strtotime($a['due_date'] ?? '9999-12-31') - strtotime($b['due_date'] ?? '9999-12-31');
                    });
                    ?>
                    <?php foreach (array_slice($assignments, 0, 10) as $assignment): ?>
                        <div class="assignment-item">
                            <div class="assignment-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="assignment-content">
                                <h3><?= htmlspecialchars($assignment['title'] ?? 'Assignment') ?></h3>
                                <p class="assignment-meta">
                                    <?= htmlspecialchars($assignment['course_code'] ?? 'N/A') ?> • 
                                    Due: <?= date('M d, Y', strtotime($assignment['due_date'] ?? 'now')) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.calendar-header h1 {
    font-size: 2rem;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.calendar-header p {
    color: var(--text-muted);
}

.calendar-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.calendar-section {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1.5rem;
}

.calendar-section h2 {
    font-size: 1.3rem;
    color: var(--text-color);
    margin-bottom: 1rem;
}

.schedule-list, .assignments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.schedule-item, .assignment-item {
    display: flex;
    align-items: start;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.schedule-icon, .assignment-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.schedule-content h3, .assignment-content h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    color: var(--text-color);
}

.schedule-meta, .assignment-meta {
    margin: 0.25rem 0;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.schedule-location {
    margin: 0.5rem 0 0 0;
    color: var(--text-color);
    font-size: 0.9rem;
}

.schedule-location i {
    color: var(--primary-color);
    margin-right: 0.5rem;
}

.text-muted {
    color: var(--text-muted);
    font-style: italic;
}
</style>
