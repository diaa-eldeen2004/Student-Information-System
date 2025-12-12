<?php
$recentCourses = $recentCourses ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="course-container">
    <div class="course-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-plus-circle"></i> Create Course</h1>
                <p>Request a new course to be created</p>
            </div>
            <a href="<?= htmlspecialchars($url('doctor/course')) ?>" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="course-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-info-circle" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                    Course Creation
                </h2>
            </div>
            <div class="card-body">
                <p>Course creation is typically managed by the IT Officer. If you need to create a new course, please contact the IT Officer or submit a request through the appropriate channels.</p>
                <p>You can view your existing courses and sections on the <a href="<?= htmlspecialchars($url('doctor/course')) ?>">Courses page</a>.</p>
            </div>
        </div>

        <?php if (!empty($recentCourses)): ?>
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-history" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                    Recent Courses
                </h2>
            </div>
            <div class="courses-list">
                <?php foreach ($recentCourses as $course): ?>
                    <div class="course-item">
                        <div class="course-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="course-content">
                            <h3><?= htmlspecialchars($course['course_code'] ?? '') ?> - <?= htmlspecialchars($course['name'] ?? '') ?></h3>
                            <p class="course-meta">
                                <?= htmlspecialchars($course['department'] ?? 'N/A') ?> • 
                                <?= htmlspecialchars($course['credit_hours'] ?? 0) ?> Credits
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
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

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

.alert-info {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.card-title {
    margin: 0;
    font-size: 1.2rem;
    color: var(--text-color);
}

.card-body {
    padding: 1.5rem;
    color: var(--text-color);
    line-height: 1.6;
}

.card-body a {
    color: var(--primary-color);
    text-decoration: none;
}

.card-body a:hover {
    text-decoration: underline;
}

.courses-list {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.course-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 8px;
}

.course-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.course-content h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--text-color);
}

.course-meta {
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

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

.btn-outline:hover {
    background: var(--bg-secondary);
}
</style>
