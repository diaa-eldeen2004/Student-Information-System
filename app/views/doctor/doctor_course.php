<?php
$courses = $courses ?? [];
?>

<div class="course-container">
    <div class="course-header">
        <div>
            <h1><i class="fas fa-book"></i> My Courses</h1>
            <p>View all courses you are teaching</p>
        </div>
    </div>

    <div class="courses-list">
        <?php if (empty($courses)): ?>
            <div class="card text-center" style="padding: 3rem;">
                <i class="fas fa-book" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary);">No courses assigned yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <div class="card course-card">
                    <div class="course-header-section">
                        <div>
                            <h2><?= htmlspecialchars($course['course_code'] ?? '') ?> - <?= htmlspecialchars($course['name'] ?? '') ?></h2>
                            <p class="course-meta">
                                <?= htmlspecialchars($course['department'] ?? 'N/A') ?> • 
                                <?= htmlspecialchars($course['credit_hours'] ?? 0) ?> Credits
                            </p>
                        </div>
                        <span class="badge badge-active">Active</span>
                    </div>
                    <div class="course-body">
                        <?php if (!empty($course['description'])): ?>
                            <p class="course-description"><?= htmlspecialchars($course['description']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($course['sections'])): ?>
                            <div class="sections-list">
                                <h3><i class="fas fa-chalkboard-teacher"></i> Sections</h3>
                                <div class="sections-grid">
                                    <?php foreach ($course['sections'] as $section): ?>
                                        <div class="section-card">
                                            <div class="section-header">
                                                <h4><?= htmlspecialchars($section['section_name'] ?? 'Section') ?></h4>
                                                <span class="section-id">ID: <?= htmlspecialchars($section['section_id'] ?? 'N/A') ?></span>
                                            </div>
                                            <div class="section-details">
                                                <div class="detail-item">
                                                    <i class="fas fa-calendar"></i>
                                                    <span><?= htmlspecialchars($section['semester'] ?? '') ?> <?= htmlspecialchars($section['academic_year'] ?? '') ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?= htmlspecialchars($section['day'] ?? '') ?> <?= htmlspecialchars($section['start_time'] ?? '') ?> - <?= htmlspecialchars($section['end_time'] ?? '') ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span><?= htmlspecialchars($section['room'] ?? 'TBA') ?></span>
                                                </div>
                                            </div>
                                            <div class="section-actions">
                                                <a href="<?= htmlspecialchars($url('doctor/attendance?section_id=' . ($section['section_id'] ?? ''))) ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-calendar-check"></i> Take Attendance
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.course-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.course-header h1 {
    font-size: 2rem;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.course-header p {
    color: var(--text-muted);
}

.course-card {
    margin-bottom: 2rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
}

.course-header-section {
    display: flex;
    justify-content: space-between;
    align-items: start;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.course-header-section h2 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--text-color);
}

.course-meta {
    margin: 0.5rem 0 0 0;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.badge {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
}

.badge-active {
    background: #10b981;
    color: white;
}

.course-body {
    padding: 1.5rem;
}

.course-description {
    color: var(--text-color);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.sections-list h3 {
    margin-bottom: 1rem;
    color: var(--text-color);
    font-size: 1.1rem;
}

.sections-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.section-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.section-header h4 {
    margin: 0;
    color: var(--text-color);
}

.section-id {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.section-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-color);
    font-size: 0.9rem;
}

.detail-item i {
    color: var(--primary-color);
    width: 20px;
}

.section-actions {
    margin-top: 1rem;
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
