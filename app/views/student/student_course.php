<?php
$student = $student ?? null;
$enrolledCourses = $enrolledCourses ?? [];
$selectedCourse = $selectedCourse ?? null;
$materials = $materials ?? [];
$assignments = $assignments ?? [];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-book"></i> My Courses</h1>
        <p>View course materials and assignments</p>
    </div>

    <div>
        <?php if (empty($enrolledCourses)): ?>
            <div class="card">
                <div style="padding: 3rem; text-align: center;">
                    <i class="fas fa-book fa-3x" style="color: var(--text-secondary); margin-bottom: 1rem;"></i>
                    <h3>No Courses Enrolled</h3>
                    <p class="text-muted">You haven't enrolled in any courses yet</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h3>Enrolled Courses</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="courses-grid">
                        <?php foreach ($enrolledCourses as $course): ?>
                            <a href="<?= htmlspecialchars($url('student/course?course_id=' . $course['course_id'])) ?>" 
                               class="course-card <?= $selectedCourse && $selectedCourse['course_id'] == $course['course_id'] ? 'course-card-active' : '' ?>"
                               style="text-decoration: none;">
                                <div class="course-card-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="course-card-content">
                                    <strong class="course-card-code"><?= htmlspecialchars($course['course_code'] ?? '') ?></strong>
                                    <p class="course-card-name"><?= htmlspecialchars($course['course_name'] ?? '') ?></p>
                                    <?php if (!empty($course['section_number'])): ?>
                                        <small class="course-card-section">Section: <?= htmlspecialchars($course['section_number']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div>
            <?php if ($selectedCourse): ?>
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h2><?= htmlspecialchars($selectedCourse['course_code'] ?? '') ?> - <?= htmlspecialchars($selectedCourse['course_name'] ?? '') ?></h2>
                        <p style="margin: 0;">
                            Section: <?= htmlspecialchars($selectedCourse['section_number'] ?? 'N/A') ?> | 
                            Instructor: <?= htmlspecialchars($selectedCourse['doctor_first_name'] ?? '') ?> <?= htmlspecialchars($selectedCourse['doctor_last_name'] ?? '') ?>
                            <?php if (!empty($selectedCourse['day_of_week']) && !empty($selectedCourse['start_time'])): ?>
                                | <?= htmlspecialchars($selectedCourse['day_of_week']) ?> <?= htmlspecialchars($selectedCourse['start_time']) ?>-<?= htmlspecialchars($selectedCourse['end_time'] ?? '') ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div style="padding: 1rem;">
                        <?php if (!empty($selectedCourse['description'])): ?>
                            <p><?= htmlspecialchars($selectedCourse['description']) ?></p>
                        <?php endif; ?>
                        <p><strong>Credit Hours:</strong> <?= htmlspecialchars($selectedCourse['credit_hours'] ?? 'N/A') ?></p>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt"></i> Course Materials</h3>
                    </div>
                    <div style="padding: 1rem;">
                        <?php if (empty($materials)): ?>
                            <p class="text-muted">No materials available</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Uploaded</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($materials as $material): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($material['title'] ?? '') ?></strong>
                                                    <?php if (!empty($material['description'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars(substr($material['description'], 0, 100)) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($material['material_type'] ?? 'other') ?></td>
                                                <td><?= !empty($material['created_at']) ? date('M d, Y', strtotime($material['created_at'])) : 'N/A' ?></td>
                                                <td>
                                                    <?php if (!empty($material['file_path'])): ?>
                                                        <a href="<?= htmlspecialchars($asset($material['file_path'])) ?>" target="_blank" class="btn btn-primary">
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-tasks"></i> Assignments</h3>
                    </div>
                    <div style="padding: 1rem;">
                        <?php if (empty($assignments)): ?>
                            <p class="text-muted">No assignments available</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Grade</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($assignment['title'] ?? '') ?></strong>
                                                    <?php if (!empty($assignment['description'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars(substr($assignment['description'], 0, 100)) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($assignment['due_date'])): ?>
                                                        <?= date('M d, Y H:i', strtotime($assignment['due_date'])) ?>
                                                    <?php else: ?>
                                                        No due date
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($assignment['submission_id'])): ?>
                                                        <?php if (!empty($assignment['grade'])): ?>
                                                            <span class="badge badge-success">Graded</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-info">Submitted</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Not Submitted</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($assignment['grade'])): ?>
                                                        <strong><?= number_format($assignment['grade'], 1) ?></strong> / <?= htmlspecialchars($assignment['max_points'] ?? 100) ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($assignment['submission_id'])): ?>
                                                        <?php if (!empty($assignment['grade'])): ?>
                                                            <!-- Already graded, show download link if file exists -->
                                                            <?php if (!empty($assignment['file_path'])): ?>
                                                                <a href="<?= htmlspecialchars(isset($asset) && is_callable($asset) ? $asset($assignment['file_path']) : $assignment['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline" title="Download submitted file">
                                                                    <i class="fas fa-download"></i> Download
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <!-- Submitted but not graded yet -->
                                                            <button type="button" class="btn btn-sm btn-primary" onclick="showSubmissionModal(<?= htmlspecialchars($assignment['assignment_id']) ?>)">
                                                                <i class="fas fa-upload"></i> Resubmit
                                                            </button>
                                                            <?php if (!empty($assignment['file_path'])): ?>
                                                                <a href="<?= htmlspecialchars(isset($asset) && is_callable($asset) ? $asset($assignment['file_path']) : $assignment['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline" title="View submitted file" style="margin-left: 0.5rem;">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <!-- Not submitted yet -->
                                                        <button type="button" class="btn btn-sm btn-primary" onclick="showSubmissionModal(<?= htmlspecialchars($assignment['assignment_id']) ?>)">
                                                            <i class="fas fa-upload"></i> Submit
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div style="padding: 3rem; text-align: center;">
                        <i class="fas fa-book fa-3x" style="color: var(--text-secondary); margin-bottom: 1rem;"></i>
                        <h3>Select a Course</h3>
                        <p class="text-muted">Choose a course from the list to view materials and assignments</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Submission Modal -->
<div id="submissionModal" class="modal">
    <div class="modal-content" style="max-width: 500px; width: 90%;">
        <div class="modal-header">
            <h2 style="margin: 0;">Submit Assignment</h2>
            <button class="modal-close" onclick="hideSubmissionModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div style="padding: 1.5rem;">
            <form method="POST" action="<?= htmlspecialchars($url('student/assignments/upload')) ?>" enctype="multipart/form-data">
                <input type="hidden" name="assignment_id" id="modal_assignment_id">
                <div class="form-group">
                    <label class="form-label">Select File</label>
                    <input type="file" name="submission_file" class="form-input" required accept=".pdf,.doc,.docx,.txt,.zip,.rar">
                    <small style="display: block; color: var(--text-secondary); margin-top: 0.5rem;">Allowed types: PDF, DOC, DOCX, TXT, ZIP, RAR (Max 10MB)</small>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="hideSubmissionModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showSubmissionModal(assignmentId) {
    document.getElementById('modal_assignment_id').value = assignmentId;
    const modal = document.getElementById('submissionModal');
    if (typeof showModal === 'function') {
        showModal(modal);
    } else {
        modal.style.display = 'block';
    }
}

function hideSubmissionModal() {
    const modal = document.getElementById('submissionModal');
    if (typeof hideModal === 'function') {
        hideModal(modal);
    } else {
        modal.style.display = 'none';
    }
}

// Show success/error messages
<?php if (isset($_SESSION['success'])): ?>
    if (typeof showNotification === 'function') {
        showNotification('<?= htmlspecialchars($_SESSION['success']) ?>', 'success');
    } else {
        alert('<?= htmlspecialchars($_SESSION['success']) ?>');
    }
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    if (typeof showNotification === 'function') {
        showNotification('<?= htmlspecialchars($_SESSION['error']) ?>', 'error');
    } else {
        alert('<?= htmlspecialchars($_SESSION['error']) ?>');
    }
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
</script>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: var(--card-bg);
    margin: 5% auto;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-color);
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--card-bg);
    color: var(--text-color);
    font-size: 1rem;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

.btn-outline:hover {
    background: var(--bg-secondary);
}

.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.course-card {
    display: flex;
    flex-direction: column;
    padding: 1.5rem;
    border: 2px solid var(--border-color, #e2e8f0);
    border-radius: 12px;
    background: var(--bg-secondary, #f8fafc);
    transition: all 0.3s ease;
    cursor: pointer;
    min-height: 140px;
}

.course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-color, #3b82f6);
}

.course-card-active {
    border-color: var(--primary-color, #3b82f6);
    background: var(--primary-color, #3b82f6);
    color: white;
}

.course-card-active .course-card-code,
.course-card-active .course-card-name,
.course-card-active .course-card-section {
    color: white;
}

.course-card-icon {
    font-size: 2.5rem;
    color: var(--primary-color, #3b82f6);
    margin-bottom: 1rem;
    text-align: center;
}

.course-card-active .course-card-icon {
    color: white;
}

.course-card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.course-card-code {
    font-size: 1.25rem;
    color: var(--text-primary, #1e293b);
    margin-bottom: 0.5rem;
    display: block;
}

.course-card-name {
    color: var(--text-secondary, #64748b);
    font-size: 0.95rem;
    margin: 0 0 0.5rem 0;
    line-height: 1.4;
    flex: 1;
}

.course-card-section {
    color: var(--text-muted, #94a3b8);
    font-size: 0.85rem;
    margin-top: auto;
}

@media (max-width: 768px) {
    .courses-grid {
        grid-template-columns: 1fr;
    }
}
</style>

