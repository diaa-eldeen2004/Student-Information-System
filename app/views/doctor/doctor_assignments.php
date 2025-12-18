<?php
$assignments = $assignments ?? [];
$allAssignments = $allAssignments ?? $assignments; // All assignments for history
$courses = $courses ?? [];
$courseFilter = $_GET['course'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$currentSemester = $currentSemester ?? (date('n') <= 6 ? 'Spring' : 'Fall');
$currentYear = $currentYear ?? date('Y');
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="assignments-container">
    <div class="assignments-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-tasks"></i> Assignments/Quizzes</h1>
                <p>View all assignments and quizzes for the semester. Hide/show them to students.</p>
            </div>
            <a href="<?= htmlspecialchars($url('doctor/create-assignment')) ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Assignment
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filters-section">
        <div class="card">
            <form method="GET" action="<?= htmlspecialchars($url('doctor/assignments')) ?>" class="filters-form">
                <div class="form-group">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-input" onchange="this.form.submit()">
                        <option value="Fall" <?= $currentSemester === 'Fall' ? 'selected' : '' ?>>Fall</option>
                        <option value="Spring" <?= $currentSemester === 'Spring' ? 'selected' : '' ?>>Spring</option>
                        <option value="Summer" <?= $currentSemester === 'Summer' ? 'selected' : '' ?>>Summer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Year</label>
                    <input type="text" name="year" class="form-input" value="<?= htmlspecialchars($currentYear) ?>" onchange="this.form.submit()">
                </div>
                <div class="form-group">
                    <label class="form-label">Course</label>
                    <select name="course" class="form-input" onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>" <?= $courseFilter == $course['course_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] ?? '') ?> - <?= htmlspecialchars($course['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input" onchange="this.form.submit()">
                        <option value="">All Status (History)</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-input" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="homework" <?= $typeFilter === 'homework' ? 'selected' : '' ?>>Homework</option>
                        <option value="project" <?= $typeFilter === 'project' ? 'selected' : '' ?>>Project</option>
                        <option value="exam" <?= $typeFilter === 'exam' ? 'selected' : '' ?>>Exam</option>
                        <option value="quiz" <?= $typeFilter === 'quiz' ? 'selected' : '' ?>>Quiz</option>
                    </select>
                </div>
                <div class="form-group">
                    <a href="<?= htmlspecialchars($url('doctor/assignments')) ?>" class="btn btn-outline">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignments List -->
    <div class="assignments-list">
        <?php if (empty($assignments)): ?>
            <div class="card text-center" style="padding: 3rem;">
                <i class="fas fa-tasks" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary);">No assignments/quizzes found.</p>
                <a href="<?= htmlspecialchars($url('doctor/create-assignment')) ?>" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-plus"></i> Create Your First Assignment/Quiz
                </a>
            </div>
        <?php else: ?>
            <!-- History Section -->
            <div class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 style="margin: 0; color: var(--text-color); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-history" style="color: var(--primary-color);"></i>
                        History of Assignments/Quizzes
                    </h2>
                    <span class="badge badge-info" style="background: var(--primary-color); color: white; padding: 0.5rem 1rem; border-radius: 6px;">
                        Total: <?= count($allAssignments) ?>
                    </span>
                </div>
                <p style="color: var(--text-muted); margin: 0;">All uploaded assignments and quizzes, including past and current ones.</p>
            </div>
            
            <!-- Show all assignments in history -->
            <?php if (!empty($allAssignments)): ?>
                <div style="margin-bottom: 2rem;">
                    <h3 style="color: var(--text-color); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-list"></i> All Assignments/Quizzes (<?= count($allAssignments) ?>)
                    </h3>
                    <?php foreach ($allAssignments as $assignment): ?>
                <div class="card assignment-card">
                    <div class="assignment-header">
                        <div>
                            <h2><?= htmlspecialchars($assignment['title'] ?? 'Untitled Assignment') ?></h2>
                            <p class="assignment-meta">
                                <?= htmlspecialchars($assignment['course_code'] ?? 'N/A') ?> • 
                                <?= htmlspecialchars($assignment['section_number'] ?? 'N/A') ?> • 
                                <?= htmlspecialchars($assignment['assignment_type'] ?? $assignment['type'] ?? 'homework') ?>
                            </p>
                        </div>
                        <div>
                            <?= $assignment['status_badge'] ?? '<span class="badge badge-active">Active</span>' ?>
                        </div>
                    </div>
                    <div class="assignment-body">
                        <?php if (!empty($assignment['description'])): ?>
                            <p class="assignment-description"><?= htmlspecialchars($assignment['description']) ?></p>
                        <?php endif; ?>
                        <div class="assignment-details">
                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span><strong>Due Date:</strong> <?= date('M d, Y H:i', strtotime($assignment['due_date'] ?? 'now')) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-star"></i>
                                <span><strong>Points:</strong> <?= htmlspecialchars($assignment['max_points'] ?? 0) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-tag"></i>
                                <span><strong>Type:</strong> <?= htmlspecialchars(ucfirst($assignment['assignment_type'] ?? $assignment['type'] ?? 'homework')) ?></span>
                            </div>
                            <?php if (isset($assignment['submission_stats'])): ?>
                                <div class="detail-item">
                                    <i class="fas fa-users"></i>
                                    <span><strong>Submissions:</strong> 
                                        <?= $assignment['submission_stats']['submitted'] ?? 0 ?> / 
                                        <?= $assignment['submission_stats']['total'] ?? 0 ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="assignment-footer">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <span class="assignment-date">Created: <?= date('M d, Y', strtotime($assignment['created_at'] ?? 'now')) ?></span>
                                <?php if (!empty($assignment['file_name'])): ?>
                                    <div style="margin-top: 0.5rem;">
                                        <i class="fas fa-file"></i> 
                                        <a href="<?= htmlspecialchars($assignment['file_path'] ?? '#') ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;">
                                            <?= htmlspecialchars($assignment['file_name']) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="assignment-actions" style="display: flex; gap: 0.5rem; align-items: center;">
                                <form method="POST" action="<?= htmlspecialchars($url('doctor/assignments')) ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to change visibility?')">
                                    <input type="hidden" name="action" value="toggle_visibility">
                                    <input type="hidden" name="assignment_id" value="<?= $assignment['assignment_id'] ?>">
                                    <input type="hidden" name="is_visible" value="<?= $assignment['is_visible'] ? 0 : 1 ?>">
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <label style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.9rem;">
                                            <input type="number" name="duration" value="7" min="1" style="width: 50px; padding: 0.25rem;" required>
                                            <select name="duration_type" style="padding: 0.25rem;">
                                                <option value="hours">Hours</option>
                                                <option value="days" selected>Days</option>
                                            </select>
                                        </label>
                                        <button type="submit" class="btn btn-sm <?= $assignment['is_visible'] ? 'btn-warning' : 'btn-success' ?>" title="<?= $assignment['is_visible'] ? 'Hide from students' : 'Show to students' ?>">
                                            <i class="fas fa-<?= $assignment['is_visible'] ? 'eye-slash' : 'eye' ?>"></i>
                                            <?= $assignment['is_visible'] ? 'Hide' : 'Show' ?>
                                        </button>
                                    </div>
                                </form>
                                <a href="<?= htmlspecialchars($url('doctor/edit-assignment?id=' . $assignment['assignment_id'])) ?>" class="btn btn-sm btn-outline">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.assignments-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.assignments-header h1 {
    font-size: 2rem;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.assignments-header p {
    color: var(--text-muted);
}

.filters-section {
    margin: 2rem 0;
}

.filters-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1.5rem;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-color);
}

.form-input {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--card-bg);
    color: var(--text-color);
}

.assignment-card {
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
}

.assignment-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.assignment-header h2 {
    margin: 0;
    font-size: 1.3rem;
    color: var(--text-color);
}

.assignment-meta {
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

.badge-completed {
    background: #6b7280;
    color: white;
}

.badge-draft {
    background: #f59e0b;
    color: white;
}

.assignment-body {
    padding: 1.5rem;
}

.assignment-description {
    color: var(--text-color);
    margin-bottom: 1rem;
    line-height: 1.6;
}

.assignment-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-color);
}

.detail-item i {
    color: var(--primary-color);
}

.assignment-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--bg-secondary);
    border-top: 1px solid var(--border-color);
}

.assignment-date {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.assignment-actions {
    display: flex;
    gap: 0.5rem;
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

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-warning:hover {
    background: #d97706;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
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
