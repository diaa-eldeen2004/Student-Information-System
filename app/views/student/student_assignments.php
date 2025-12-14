<?php
$student = $student ?? null;
$assignments = $assignments ?? [];
$pending = $pending ?? [];
$submitted = $submitted ?? [];
$graded = $graded ?? [];
$overdue = $overdue ?? [];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-tasks"></i> My Assignments</h1>
        <p>View and submit your assignments</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card text-center">
            <div style="padding: 1.5rem;">
                <h3 style="color: var(--warning-color); margin: 0 0 0.5rem 0;"><?= count($pending) ?></h3>
                <p style="margin: 0;">Pending</p>
            </div>
        </div>
        <div class="card text-center">
            <div style="padding: 1.5rem;">
                <h3 style="color: var(--primary-color); margin: 0 0 0.5rem 0;"><?= count($submitted) ?></h3>
                <p style="margin: 0;">Submitted</p>
            </div>
        </div>
        <div class="card text-center">
            <div style="padding: 1.5rem;">
                <h3 style="color: var(--success-color); margin: 0 0 0.5rem 0;"><?= count($graded) ?></h3>
                <p style="margin: 0;">Graded</p>
            </div>
        </div>
        <div class="card text-center">
            <div style="padding: 1.5rem;">
                <h3 style="color: var(--error-color); margin: 0 0 0.5rem 0;"><?= count($overdue) ?></h3>
                <p style="margin: 0;">Overdue</p>
            </div>
        </div>
    </div>

    <?php if (!empty($overdue)): ?>
        <div class="card mb-4" style="border-left: 4px solid var(--error-color);">
            <div class="card-header" style="background-color: var(--error-color); color: white;">
                <h3><i class="fas fa-exclamation-triangle"></i> Overdue Assignments</h3>
            </div>
            <div style="padding: 1.5rem;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Course</th>
                                <th>Due Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdue as $assignment): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($assignment['title'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($assignment['course_code'] ?? '') ?></td>
                                    <td style="color: var(--error-color);">
                                        <strong><?= !empty($assignment['due_date']) ? date('M d, Y H:i', strtotime($assignment['due_date'])) : 'N/A' ?></strong>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showSubmissionModal(<?= htmlspecialchars($assignment['assignment_id']) ?>)">
                                            Submit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($pending)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Pending Assignments</h3>
            </div>
            <div style="padding: 1.5rem;">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Course</th>
                                <th>Due Date</th>
                                <th>Points</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending as $assignment): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($assignment['title'] ?? '') ?></strong>
                                        <?php if (!empty($assignment['description'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(substr($assignment['description'], 0, 100)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($assignment['course_code'] ?? '') ?></td>
                                    <td><?= !empty($assignment['due_date']) ? date('M d, Y H:i', strtotime($assignment['due_date'])) : 'No due date' ?></td>
                                    <td><?= htmlspecialchars($assignment['max_points'] ?? 100) ?> pts</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showSubmissionModal(<?= htmlspecialchars($assignment['assignment_id']) ?>)">
                                            <i class="fas fa-upload"></i> Submit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($submitted)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-check-circle"></i> Submitted Assignments</h3>
            </div>
            <div style="padding: 1.5rem;">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Course</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submitted as $assignment): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($assignment['title'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($assignment['course_code'] ?? '') ?></td>
                                    <td><?= !empty($assignment['submitted_at']) ? date('M d, Y H:i', strtotime($assignment['submitted_at'])) : 'N/A' ?></td>
                                    <td>
                                        <span class="badge" style="background-color: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">Submitted</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showSubmissionModal(<?= htmlspecialchars($assignment['assignment_id']) ?>)">
                                            Resubmit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($graded)): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-check-double"></i> Graded Assignments</h3>
            </div>
            <div style="padding: 1.5rem;">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Course</th>
                                <th>Grade</th>
                                <th>Feedback</th>
                                <th>Graded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($graded as $assignment): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($assignment['title'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($assignment['course_code'] ?? '') ?></td>
                                    <td>
                                        <strong><?= number_format($assignment['grade'] ?? 0, 1) ?></strong> / 
                                        <?= htmlspecialchars($assignment['max_points'] ?? 100) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($assignment['feedback'])): ?>
                                            <?= htmlspecialchars($assignment['feedback']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">No feedback</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= !empty($assignment['graded_at']) ? date('M d, Y', strtotime($assignment['graded_at'])) : 'N/A' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($assignments)): ?>
        <div class="card">
            <div style="padding: 3rem; text-align: center;">
                <i class="fas fa-tasks fa-3x" style="color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <h3>No Assignments</h3>
                <p class="text-muted">You don't have any assignments yet.</p>
            </div>
        </div>
    <?php endif; ?>
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
    }
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    if (typeof showNotification === 'function') {
        showNotification('<?= htmlspecialchars($_SESSION['error']) ?>', 'error');
    }
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
</script>
