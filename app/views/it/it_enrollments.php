<?php
$requests = $requests ?? [];
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;
?>

<div class="enrollments-container">
    <div class="enrollments-header">
        <h1><i class="fas fa-user-check"></i> Enrollment Requests</h1>
    </div>

    <?php if ($success === 'approved'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Enrollment request approved successfully!
        </div>
    <?php elseif ($success === 'rejected'): ?>
        <div class="alert alert-warning">
            <i class="fas fa-times-circle"></i> Enrollment request rejected.
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="enrollments-content">
        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No enrollment requests found.</p>
            </div>
        <?php else: ?>
            <div class="requests-table">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Section</th>
                            <th>Semester</th>
                            <th>Requested</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr class="status-<?= htmlspecialchars($request['status']) ?>">
                                <td>
                                    <strong><?= htmlspecialchars($request['student_first_name']) ?> <?= htmlspecialchars($request['student_last_name']) ?></strong><br>
                                    <small><?= htmlspecialchars($request['student_email']) ?></small><br>
                                    <small>GPA: <?= number_format($request['gpa'], 2) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($request['course_code']) ?></strong><br>
                                    <small><?= htmlspecialchars($request['course_name']) ?></small>
                                </td>
                                <td>
                                    Section <?= htmlspecialchars($request['section_number']) ?><br>
                                    <small><?= htmlspecialchars($request['doctor_first_name']) ?> <?= htmlspecialchars($request['doctor_last_name']) ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($request['semester']) ?> <?= htmlspecialchars($request['academic_year']) ?><br>
                                    <small><?= htmlspecialchars($request['time_slot'] ?? 'TBA') ?></small>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($request['requested_at'])) ?><br>
                                    <small><?= date('H:i', strtotime($request['requested_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($request['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($request['status'])) ?>
                                    </span>
                                    <?php if ($request['status'] === 'rejected' && $request['rejection_reason']): ?>
                                        <br><small class="rejection-reason"><?= htmlspecialchars($request['rejection_reason']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <form method="post" action="<?= htmlspecialchars($url('it/enrollments/approve')) ?>" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this enrollment request?')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="showRejectModal(<?= $request['request_id'] ?>)">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <?php if ($request['reviewed_at']): ?>
                                                Reviewed: <?= date('M d, Y', strtotime($request['reviewed_at'])) ?>
                                            <?php endif; ?>
                                        </span>
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

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Reject Enrollment Request</h2>
            <span class="close" onclick="closeRejectModal()">&times;</span>
        </div>
        <form method="post" action="<?= htmlspecialchars($url('it/enrollments/reject')) ?>">
            <input type="hidden" name="request_id" id="reject_request_id">
            <div class="form-group">
                <label for="rejection_reason" class="form-label">Rejection Reason (Optional)</label>
                <textarea id="rejection_reason" name="reason" class="form-input" rows="4" placeholder="Enter reason for rejection..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject Request</button>
            </div>
        </form>
    </div>
</div>

<style>
.enrollments-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.enrollments-header h1 {
    font-size: 2rem;
    color: var(--text-primary);
    margin-bottom: 2rem;
}

.requests-table {
    background: var(--surface-color);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px var(--shadow-color);
    overflow-x: auto;
}

.requests-table table {
    width: 100%;
    border-collapse: collapse;
}

.requests-table th {
    background: var(--background-color);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid var(--border-color);
}

.requests-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-badge.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.status-approved {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    margin: 0.25rem;
}

.btn-success {
    background: #10b981;
    color: white;
    border: none;
}

.btn-danger {
    background: #ef4444;
    color: white;
    border: none;
}

.rejection-reason {
    color: var(--text-secondary);
    font-style: italic;
    margin-top: 0.25rem;
    display: block;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background: var(--surface-color);
    margin: 10% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.close {
    font-size: 2rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.close:hover {
    color: var(--text-primary);
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
</style>

<script>
function showRejectModal(requestId) {
    document.getElementById('reject_request_id').value = requestId;
    document.getElementById('rejectModal').style.display = 'block';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('rejection_reason').value = '';
}

window.onclick = function(event) {
    const modal = document.getElementById('rejectModal');
    if (event.target == modal) {
        closeRejectModal();
    }
}
</script>
