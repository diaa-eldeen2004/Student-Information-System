<?php
$students = $students ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="notification-container">
    <div class="notification-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-paper-plane"></i> Send Notification</h1>
                <p>Send messages to students in your courses</p>
            </div>
            <a href="<?= htmlspecialchars($url('doctor/notifications')) ?>" class="btn btn-outline">
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

    <div class="notification-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-envelope" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                    New Notification
                </h2>
            </div>
            <form method="POST" action="<?= htmlspecialchars($url('doctor/send-notification')) ?>" class="notification-form">
                <div style="padding: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label">Recipients *</label>
                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 6px; padding: 0.75rem;">
                            <?php if (empty($students)): ?>
                                <p style="color: var(--text-muted); margin: 0; padding: 1rem; text-align: center;">
                                    No students enrolled in your sections yet.
                                </p>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border-radius: 4px; transition: background 0.2s;" 
                                           onmouseover="this.style.background='var(--bg-secondary)'" 
                                           onmouseout="this.style.background='transparent'">
                                        <input type="checkbox" id="selectAll" onchange="toggleAllStudents(this)">
                                        <strong>Select All Students</strong>
                                    </label>
                                    <?php foreach ($students as $student): ?>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border-radius: 4px; transition: background 0.2s;" 
                                               onmouseover="this.style.background='var(--bg-secondary)'" 
                                               onmouseout="this.style.background='transparent'">
                                            <input type="checkbox" name="user_ids[]" value="<?= $student['user_id'] ?? '' ?>" class="student-checkbox">
                                            <span>
                                                <?= htmlspecialchars($student['first_name'] ?? '') ?> <?= htmlspecialchars($student['last_name'] ?? '') ?>
                                                <small style="color: var(--text-secondary);">(<?= htmlspecialchars($student['student_number'] ?? '') ?>)</small>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                            Select one or more students to send the notification to.
                        </small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notification Type *</label>
                        <select name="type" class="form-input" required>
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="error">Error</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-input" 
                               placeholder="Enter notification title" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Message *</label>
                        <textarea name="message" class="form-input" rows="5" 
                                  placeholder="Enter notification message" required></textarea>
                    </div>
                </div>
                <div class="form-actions" style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color);">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Notification
                    </button>
                    <button type="reset" class="btn btn-outline">
                        <i class="fas fa-redo"></i> Clear
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAllStudents(selectAllCheckbox) {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Validate form before submission
document.querySelector('.notification-form')?.addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.student-checkbox:checked');
    if (checked.length === 0) {
        e.preventDefault();
        alert('Please select at least one student to send the notification to.');
        return false;
    }
});
</script>

<style>
.notification-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
}

.notification-header h1 {
    font-size: 2rem;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.notification-header p {
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

.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 1.5rem;
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
    font-size: 1rem;
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
}

textarea.form-input {
    resize: vertical;
    min-height: 120px;
}

.form-actions {
    display: flex;
    gap: 1rem;
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
</style>
