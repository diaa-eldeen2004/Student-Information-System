<?php
$users = $users ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="notification-container">
    <div class="notification-header">
        <div>
            <h1><i class="fas fa-bell"></i> Send Notification</h1>
            <p>Send notifications to all users or select specific users</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-paper-plane" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                New Notification
            </h2>
        </div>
        <form method="POST" action="<?= htmlspecialchars($url('it/send-notification')) ?>" class="notification-form">
            <div style="padding: 1.5rem;">
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
                <div class="form-group">
                    <label class="form-label">Recipients *</label>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; background: var(--surface-color); border-radius: 6px; margin-bottom: 0.5rem;">
                            <input type="checkbox" name="send_to_all" value="1" id="send_to_all" onchange="toggleUserSelection()" style="width: 18px; height: 18px;">
                            <strong>Send to All Users</strong>
                            <small style="color: var(--text-secondary); margin-left: auto;">(All students, doctors, advisors, and admins)</small>
                        </label>
                    </div>
                    <div id="userSelection" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; max-height: 400px; overflow-y: auto; background-color: var(--surface-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <label class="form-label" style="margin: 0;">Select Users</label>
                            <button type="button" class="btn btn-outline" onclick="toggleSelectAllUsers()" style="padding: 0.25rem 0.75rem; font-size: 0.85rem;">
                                <i class="fas fa-check-square" id="selectAllUsersIcon"></i> <span id="selectAllUsersText">Select All</span>
                            </button>
                        </div>
                        <?php 
                        $usersByRole = [];
                        foreach ($users as $user) {
                            $role = $user['role'] ?? 'other';
                            if (!isset($usersByRole[$role])) {
                                $usersByRole[$role] = [];
                            }
                            $usersByRole[$role][] = $user;
                        }
                        $roleLabels = ['student' => 'Students', 'doctor' => 'Doctors', 'advisor' => 'Advisors', 'admin' => 'Admins'];
                        ?>
                        <?php foreach ($roleLabels as $role => $label): ?>
                            <?php if (!empty($usersByRole[$role])): ?>
                                <div style="margin-bottom: 1rem;">
                                    <h4 style="margin: 0 0 0.5rem 0; color: var(--text-primary); font-size: 0.9rem; font-weight: 600;">
                                        <i class="fas fa-<?= $role === 'student' ? 'user-graduate' : ($role === 'doctor' ? 'user-md' : ($role === 'advisor' ? 'user-tie' : 'user-shield')) ?>"></i> <?= $label ?>
                                    </h4>
                                    <?php foreach ($usersByRole[$role] as $user): ?>
                                        <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s;" 
                                               onmouseover="this.style.backgroundColor='rgba(37, 99, 235, 0.1)'"
                                               onmouseout="this.style.backgroundColor='transparent'">
                                            <input type="checkbox" name="user_ids[]" value="<?= $user['id'] ?>" class="user-checkbox" onchange="updateSelectedUserCount()" style="width: 18px; height: 18px; cursor: pointer;">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 500; color: var(--text-primary);">
                                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                                </div>
                                                <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                                    <?= htmlspecialchars($user['email'] ?? '') ?>
                                                    <?php if ($user['identifier']): ?>
                                                        • ID: <?= htmlspecialchars($user['identifier']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <small style="display: block; color: var(--text-secondary); margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i> Selected: <strong id="selectedUserCount" style="color: var(--primary-color);">0</strong> user(s)
                    </small>
                </div>
            </div>
            <div class="form-actions" style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color);">
                <button type="submit" class="btn btn-primary" id="sendNotificationBtn" disabled>
                    <i class="fas fa-paper-plane"></i> Send Notification
                </button>
                <button type="reset" class="btn btn-outline" onclick="resetForm()">
                    <i class="fas fa-redo"></i> Clear
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.notification-container {
    max-width: 1000px;
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

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
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

<script>
let allUsersSelected = false;

function toggleUserSelection() {
    const sendToAll = document.getElementById('send_to_all');
    const userSelection = document.getElementById('userSelection');
    const sendBtn = document.getElementById('sendNotificationBtn');
    
    if (sendToAll.checked) {
        userSelection.style.opacity = '0.5';
        userSelection.style.pointerEvents = 'none';
        document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
        sendBtn.disabled = false;
        updateSelectedUserCount();
    } else {
        userSelection.style.opacity = '1';
        userSelection.style.pointerEvents = 'auto';
        updateSelectedUserCount();
    }
}

function toggleSelectAllUsers() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    allUsersSelected = !allUsersSelected;
    
    checkboxes.forEach(cb => {
        cb.checked = allUsersSelected;
    });
    
    updateSelectedUserCount();
    updateSelectAllUsersButton();
}

function updateSelectedUserCount() {
    const sendToAll = document.getElementById('send_to_all');
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const count = checkboxes.length;
    const countElement = document.getElementById('selectedUserCount');
    const sendBtn = document.getElementById('sendNotificationBtn');
    
    if (countElement) {
        if (sendToAll.checked) {
            countElement.textContent = 'All Users';
        } else {
            countElement.textContent = count;
        }
    }
    
    if (sendBtn) {
        sendBtn.disabled = !sendToAll.checked && count === 0;
    }
    
    // Update select all button state
    const allCheckboxes = document.querySelectorAll('.user-checkbox');
    if (allCheckboxes.length > 0 && !sendToAll.checked) {
        allUsersSelected = count === allCheckboxes.length;
        updateSelectAllUsersButton();
    }
}

function updateSelectAllUsersButton() {
    const icon = document.getElementById('selectAllUsersIcon');
    const text = document.getElementById('selectAllUsersText');
    if (icon && text) {
        if (allUsersSelected) {
            icon.className = 'fas fa-square';
            text.textContent = 'Deselect All';
        } else {
            icon.className = 'fas fa-check-square';
            text.textContent = 'Select All';
        }
    }
}

function resetForm() {
    if (confirm('Are you sure you want to reset the form? All changes will be lost.')) {
        document.querySelector('.notification-form').reset();
        document.getElementById('send_to_all').checked = false;
        toggleUserSelection();
        document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
        updateSelectedUserCount();
        allUsersSelected = false;
        updateSelectAllUsersButton();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedUserCount();
});
</script>

