<?php
$assignments = $assignments ?? [];
$courses = $courses ?? [];
$courseFilter = $_GET['course'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
?>

<div class="assignments-container">
    <div class="assignments-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-tasks"></i> Assignments</h1>
                <p>Manage and view all your assignments</p>
            </div>
            <a href="<?= htmlspecialchars($url('doctor/create-assignment')) ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Assignment
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <div class="card">
            <form method="GET" action="<?= htmlspecialchars($url('doctor/assignments')) ?>" class="filters-form">
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
                        <option value="">All Status</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
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
                <p style="color: var(--text-secondary);">No assignments found.</p>
                <a href="<?= htmlspecialchars($url('doctor/create-assignment')) ?>" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-plus"></i> Create Your First Assignment
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($assignments as $assignment): ?>
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
                        <span class="assignment-date">Created: <?= date('M d, Y', strtotime($assignment['created_at'] ?? 'now')) ?></span>
                        <div class="assignment-actions">
                            <a href="#" class="btn btn-sm btn-outline">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <a href="#" class="btn btn-sm btn-outline">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
