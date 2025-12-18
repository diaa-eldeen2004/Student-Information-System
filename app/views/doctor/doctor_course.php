<?php
$courses = $courses ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="course-container">
    <div class="course-header">
        <div>
            <h1><i class="fas fa-book"></i> My Courses</h1>
            <p>View all courses you are teaching. View student assignments and grade them.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

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
                        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: var(--bg-secondary); border-radius: 8px;">
                            <div style="flex: 1;">
                                <strong>Total Students:</strong> <?= $course['student_count'] ?? 0 ?>
                            </div>
                            <div style="flex: 1;">
                                <strong>Total Assignments/Quizzes:</strong> <?= count($course['assignments'] ?? []) ?>
                            </div>
                            <div style="flex: 1;">
                                <strong>Course Materials:</strong> <?= count($course['materials'] ?? []) ?>
                            </div>
                            <div>
                                <a href="<?= htmlspecialchars($url('doctor/upload-material?course_id=' . $course['course_id'])) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-upload"></i> Upload File
                                </a>
                            </div>
                        </div>
                        
                        <?php if (!empty($course['description'])): ?>
                            <p class="course-description"><?= htmlspecialchars($course['description']) ?></p>
                        <?php endif; ?>
                        
                        <!-- Course Materials -->
                        <?php if (!empty($course['materials'])): ?>
                            <div class="materials-section" style="margin-bottom: 2rem;">
                                <h3><i class="fas fa-file-alt"></i> Course Materials</h3>
                                <div class="materials-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
                                    <?php foreach ($course['materials'] as $material): ?>
                                        <div class="material-card" style="padding: 1rem; background: var(--bg-secondary); border-radius: 8px; border: 1px solid var(--border-color);">
                                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                                <h4 style="margin: 0; font-size: 0.95rem;"><?= htmlspecialchars($material['title']) ?></h4>
                                                <a href="<?= htmlspecialchars($url('doctor/edit-material?id=' . $material['material_id'])) ?>" class="btn btn-sm btn-outline" style="padding: 0.25rem 0.5rem;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                            <a href="<?= htmlspecialchars($material['file_path'] ?? '#') ?>" target="_blank" style="color: var(--primary-color); text-decoration: none; font-size: 0.85rem;">
                                                <i class="fas fa-download"></i> <?= htmlspecialchars($material['file_name']) ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Student Assignments & Grading -->
                        <?php if (!empty($course['assignments'])): ?>
                            <div class="assignments-section" style="margin-bottom: 2rem;">
                                <h3><i class="fas fa-tasks"></i> Assignments/Quizzes & Student Submissions</h3>
                                <div class="assignments-list" style="margin-top: 1rem;">
                                    <?php foreach ($course['assignments'] as $assignment): ?>
                                        <div class="assignment-card" style="margin-bottom: 1.5rem; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                                            <div style="padding: 1rem; background: var(--bg-secondary); border-bottom: 1px solid var(--border-color);">
                                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                                    <div>
                                                        <h4 style="margin: 0 0 0.5rem 0;"><?= htmlspecialchars($assignment['title']) ?></h4>
                                                        <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">
                                                            <?= htmlspecialchars($assignment['course_code'] ?? '') ?> • 
                                                            Due: <?= date('M d, Y H:i', strtotime($assignment['due_date'] ?? 'now')) ?> • 
                                                            <?= htmlspecialchars($assignment['max_points']) ?> points
                                                        </p>
                                                    </div>
                                                    <?php if (!empty($assignment['file_name'])): ?>
                                                        <a href="<?= htmlspecialchars($assignment['file_path'] ?? '#') ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;">
                                                            <i class="fas fa-file"></i> <?= htmlspecialchars($assignment['file_name']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($assignment['submissions'])): ?>
                                                <div style="padding: 1rem;">
                                                    <h5 style="margin: 0 0 1rem 0; font-size: 1rem;">Student Submissions (<?= count($assignment['submissions']) ?>)</h5>
                                                    <div class="submissions-table" style="overflow-x: auto;">
                                                        <table style="width: 100%; border-collapse: collapse;">
                                                            <thead>
                                                                <tr style="background: var(--bg-secondary);">
                                                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Student</th>
                                                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Submission</th>
                                                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Grade</th>
                                                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Feedback</th>
                                                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border-color);">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($assignment['submissions'] as $submission): ?>
                                                                    <tr>
                                                                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                                            <?= htmlspecialchars($submission['first_name'] ?? '') ?> <?= htmlspecialchars($submission['last_name'] ?? '') ?><br>
                                                                            <small style="color: var(--text-secondary);"><?= htmlspecialchars($submission['student_number'] ?? '') ?></small>
                                                                        </td>
                                                                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                                            <?php if (!empty($submission['file_name']) && !empty($submission['file_path'])): ?>
                                                                                <a href="<?= htmlspecialchars(isset($asset) && is_callable($asset) ? $asset($submission['file_path']) : $submission['file_path']) ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;">
                                                                                    <i class="fas fa-file"></i> <?= htmlspecialchars($submission['file_name']) ?>
                                                                                </a>
                                                                            <?php else: ?>
                                                                                <span style="color: var(--text-secondary);">Text submission</span>
                                                                            <?php endif; ?>
                                                                            <br>
                                                                            <small style="color: var(--text-secondary);">
                                                                                Submitted: <?= date('M d, Y H:i', strtotime($submission['submitted_at'] ?? 'now')) ?>
                                                                            </small>
                                                                        </td>
                                                                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                                            <form method="POST" action="<?= htmlspecialchars($url('doctor/course')) ?>" style="display: inline;">
                                                                                <input type="hidden" name="action" value="update_grade">
                                                                                <input type="hidden" name="submission_id" value="<?= $submission['submission_id'] ?>">
                                                                                <input type="number" name="grade" value="<?= $submission['grade'] ?? '' ?>" 
                                                                                       min="0" max="<?= $assignment['max_points'] ?>" step="0.01" 
                                                                                       style="width: 80px; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 4px;">
                                                                                <span style="color: var(--text-secondary);">/ <?= $assignment['max_points'] ?></span>
                                                                        </td>
                                                                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                                            <textarea name="feedback" rows="2" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.9rem;" 
                                                                                      placeholder="Enter feedback..."><?= htmlspecialchars($submission['feedback'] ?? '') ?></textarea>
                                                                        </td>
                                                                        <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                                            <button type="submit" class="btn btn-sm btn-primary" style="padding: 0.5rem 1rem;">
                                                                                <i class="fas fa-save"></i> Save
                                                                            </button>
                                                                            </form>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div style="padding: 1rem; text-align: center; color: var(--text-secondary);">
                                                    No submissions yet
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($course['sections'])): ?>
                            <div class="sections-list">
                                <h3><i class="fas fa-chalkboard-teacher"></i> Sections</h3>
                                <div class="sections-grid">
                                    <?php foreach ($course['sections'] as $section): ?>
                                        <div class="section-card">
                                            <div class="section-header">
                                                <h4><?= htmlspecialchars($section['section_name'] ?? 'Section') ?></h4>
                                                <span class="section-id">ID: <?= htmlspecialchars($section['schedule_id'] ?? $section['section_id'] ?? 'N/A') ?></span>
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
                                                <a href="<?= htmlspecialchars($url('doctor/attendance?section_id=' . ($section['schedule_id'] ?? $section['section_id'] ?? ''))) ?>" class="btn btn-sm btn-primary">
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

.submissions-table table tbody tr:hover {
    background: var(--bg-secondary);
}
</style>
