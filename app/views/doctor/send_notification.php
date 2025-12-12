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
                <p>Send notifications to your students</p>
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
                        <label class="form-label">Recipient *</label>
                        <select name="user_id" class="form-input" required>
                            <option value="">Select a student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['user_id'] ?? '' ?>">
                                    <?= htmlspecialchars($student['first_name'] ?? '') ?> <?= htmlspecialchars($student['last_name'] ?? '') ?>
                                    (<?= htmlspecialchars($student['student_number'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($students)): ?>
                            <small style="color: var(--text-muted); font-size: 0.85rem;">
                                No students enrolled in your sections yet.
                            </small>
                        <?php endif; ?>
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
