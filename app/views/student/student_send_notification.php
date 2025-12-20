<?php
$student = $student ?? null;
$recipients = $recipients ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-paper-plane"></i> Send Message</h1>
                <p>Send messages to your course instructors and administrators</p>
            </div>
            <a href="<?= htmlspecialchars($url('student/notifications')) ?>" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>" style="margin-bottom: 1.5rem;">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>
                <i class="fas fa-envelope" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                New Message
            </h3>
        </div>
        <form method="POST" action="<?= htmlspecialchars($url('student/send-notification')) ?>" class="notification-form">
            <div style="padding: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Recipients *</label>
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 6px; padding: 0.75rem;">
                        <?php if (empty($recipients)): ?>
                            <p style="color: var(--text-muted); margin: 0; padding: 1rem; text-align: center;">
                                No recipients available. You need to be enrolled in courses to send messages to instructors.
                            </p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border-radius: 4px; transition: background 0.2s;" 
                                       onmouseover="this.style.background='var(--bg-secondary)'" 
                                       onmouseout="this.style.background='transparent'">
                                    <input type="checkbox" id="selectAll" onchange="toggleAllRecipients(this)">
                                    <strong>Select All Recipients</strong>
                                </label>
                                <?php 
                                // Group recipients by role
                                $recipientsByRole = [];
                                foreach ($recipients as $recipient) {
                                    $role = $recipient['role'] ?? 'doctor';
                                    if (!isset($recipientsByRole[$role])) {
                                        $recipientsByRole[$role] = [];
                                    }
                                    $recipientsByRole[$role][] = $recipient;
                                }
                                $roleLabels = [
                                    'doctor' => 'Course Instructors',
                                    'admin' => 'Administrators'
                                ];
                                foreach ($roleLabels as $role => $label): 
                                    if (isset($recipientsByRole[$role]) && !empty($recipientsByRole[$role])):
                                ?>
                                    <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--border-color);">
                                        <strong style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.5rem;"><?= htmlspecialchars($label) ?></strong>
                                        <?php foreach ($recipientsByRole[$role] as $recipient): ?>
                                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border-radius: 4px; transition: background 0.2s;" 
                                                   onmouseover="this.style.background='var(--bg-secondary)'" 
                                                   onmouseout="this.style.background='transparent'">
                                                <input type="checkbox" name="user_ids[]" value="<?= $recipient['user_id'] ?? '' ?>" class="recipient-checkbox">
                                                <span>
                                                    <?= htmlspecialchars($recipient['first_name'] ?? '') ?> <?= htmlspecialchars($recipient['last_name'] ?? '') ?>
                                                    <?php if (isset($recipient['course_code']) && $recipient['course_code']): ?>
                                                        <small style="color: var(--text-secondary);"> - <?= htmlspecialchars($recipient['course_code']) ?></small>
                                                    <?php endif; ?>
                                                    <small style="color: var(--text-secondary);"> (<?= htmlspecialchars($recipient['email'] ?? '') ?>)</small>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                        Select one or more recipients to send your message to.
                    </small>
                </div>
                <div class="form-group">
                    <label class="form-label">Message Type *</label>
                    <select name="type" class="form-input" required>
                        <option value="info">Info</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject *</label>
                    <input type="text" name="title" class="form-input" 
                           placeholder="Enter message subject" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Message *</label>
                    <textarea name="message" class="form-input" rows="6" 
                              placeholder="Enter your message" required></textarea>
                </div>
            </div>
            <div class="form-actions" style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
                <button type="reset" class="btn btn-outline">
                    <i class="fas fa-redo"></i> Clear
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAllRecipients(selectAllCheckbox) {
    const checkboxes = document.querySelectorAll('.recipient-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Validate form before submission
document.querySelector('.notification-form')?.addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.recipient-checkbox:checked');
    if (checked.length === 0) {
        e.preventDefault();
        alert('Please select at least one recipient to send your message to.');
        return false;
    }
});
</script>

<style>
.alert {
    padding: 1rem;
    border-radius: 6px;
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

