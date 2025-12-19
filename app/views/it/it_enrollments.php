<?php
$requests = $requests ?? [];
$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;
?>

<div class="enrollments-container">
    <div class="enrollments-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h1><i class="fas fa-user-check"></i> Enrollment Requests</h1>
            <?php 
            $pendingCount = 0;
            foreach ($requests as $req) {
                if ($req['status'] === 'pending') $pendingCount++;
            }
            if ($pendingCount > 0): ?>
                <form method="POST" action="<?= htmlspecialchars($url('it/enrollments/approve-all')) ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to approve all <?= $pendingCount ?> pending requests?');">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-double"></i> Accept All (<?= $pendingCount ?>)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success === 'approved'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Enrollment request approved successfully!
        </div>
    <?php elseif ($success === 'approved_all'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-double"></i> 
            <?php 
            $count = $_GET['count'] ?? 0;
            $failed = $_GET['failed'] ?? 0;
            echo "Approved {$count} enrollment request(s)";
            if ($failed > 0) echo " ({$failed} failed)";
            ?>
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
/* Light Mode CSS Variables (Default) */
:root {
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --bg-tertiary: #f1f5f9;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border-color: #e2e8f0;
    --border-light: #cbd5e1;
    --primary-color: #3b82f6;
    --primary-hover: #2563eb;
    --success-color: #10b981;
    --error-color: #ef4444;
    --warning-color: #f59e0b;
    --shadow-sm: rgba(0, 0, 0, 0.1);
    --shadow-md: rgba(0, 0, 0, 0.15);
    --shadow-lg: rgba(0, 0, 0, 0.2);
}

/* Dark Mode CSS Variables */
[data-theme="dark"] {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-tertiary: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --border-color: #334155;
    --border-light: #475569;
    --primary-color: #3b82f6;
    --primary-hover: #2563eb;
    --success-color: #10b981;
    --error-color: #ef4444;
    --warning-color: #f59e0b;
    --shadow-sm: rgba(0, 0, 0, 0.3);
    --shadow-md: rgba(0, 0, 0, 0.4);
    --shadow-lg: rgba(0, 0, 0, 0.5);
}

.enrollments-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    background: var(--bg-primary);
    min-height: 100vh;
    color: var(--text-primary);
}

.enrollments-header {
    margin-bottom: 2.5rem;
    padding: 2rem;
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
    border-radius: 16px;
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 12px var(--shadow-md);
}

.enrollments-header h1 {
    font-size: 2.5rem;
    margin: 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--text-primary);
}

.enrollments-header h1 i {
    font-size: 2rem;
}

.enrollments-header > div {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    width: 100%;
}

.requests-table {
    background: var(--bg-secondary);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px var(--shadow-md);
    border: 1px solid var(--border-color);
    overflow-x: auto;
    transition: all 0.3s ease;
}

.requests-table:hover {
    box-shadow: 0 8px 24px var(--shadow-lg);
}

.requests-table table {
    width: 100%;
    border-collapse: collapse;
}

.requests-table th {
    background: var(--bg-tertiary);
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
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-success:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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

/* Enhanced Styles - Already applied above */

.requests-table {
    transition: box-shadow 0.3s ease;
}

.requests-table:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.requests-table tbody tr {
    transition: all 0.2s ease;
}

.requests-table tbody tr:hover {
    background: var(--surface-color);
    transform: scale(1.01);
}

.btn-sm {
    transition: all 0.2s ease;
}

.btn-sm:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.modal-content {
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Form inputs matching Doctor pages */
.form-input, .form-select {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--surface-color);
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.2s ease;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Responsive Design matching Doctor pages */
@media (max-width: 768px) {
    .enrollments-container {
        padding: 1rem;
    }
    
    .enrollments-header {
        padding: 1.5rem;
    }
    
    .enrollments-header h1 {
        font-size: 2rem;
    }
    
    .requests-table {
        padding: 1rem;
    }
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
