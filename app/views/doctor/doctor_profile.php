<?php
$doctor = $doctor ?? null;
$sectionsCount = $sectionsCount ?? 0;
$assignmentsCount = $assignmentsCount ?? 0;
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="profile-container">
    <div class="profile-header">
        <div>
            <h1><i class="fas fa-user"></i> My Profile</h1>
            <p>Manage your profile information</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="profile-content">
        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #3b82f6;">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $sectionsCount ?></h3>
                    <p>Active Sections</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #10b981;">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $assignmentsCount ?></h3>
                    <p>Total Assignments</p>
                </div>
            </div>
        </div>

        <div class="profile-form-section">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-user-edit" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        Profile Information
                    </h2>
                </div>
                <form method="POST" action="<?= htmlspecialchars($url('doctor/profile')) ?>" class="profile-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; padding: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-input" 
                                   value="<?= htmlspecialchars($doctor['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-input" 
                                   value="<?= htmlspecialchars($doctor['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" 
                                   value="<?= htmlspecialchars($doctor['email'] ?? '') ?>" disabled>
                            <small style="color: var(--text-muted); font-size: 0.85rem;">Email cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-input" 
                                   value="<?= htmlspecialchars($doctor['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-input" 
                                   value="<?= htmlspecialchars($doctor['department'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Doctor ID</label>
                            <input type="text" class="form-input" 
                                   value="<?= htmlspecialchars($doctor['doctor_id'] ?? 'N/A') ?>" disabled>
                        </div>
                    </div>
                    <div class="form-actions" style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color);">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="<?= htmlspecialchars($url('doctor/dashboard')) ?>" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.profile-header h1 {
    font-size: 2rem;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.profile-header p {
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

.profile-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-content h3 {
    font-size: 2rem;
    margin: 0;
    color: var(--text-color);
}

.stat-content p {
    margin: 0.5rem 0 0 0;
    color: var(--text-muted);
}

.profile-form-section {
    margin-top: 2rem;
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

.profile-form {
    display: flex;
    flex-direction: column;
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
    font-size: 1rem;
}

.form-input:disabled {
    background: var(--bg-secondary);
    opacity: 0.6;
    cursor: not-allowed;
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
