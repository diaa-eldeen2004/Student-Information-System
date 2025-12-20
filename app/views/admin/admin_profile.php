<?php
// Ensure variables exist with defaults
$admin = $admin ?? [
    'first_name' => 'Admin',
    'last_name' => 'User',
    'email' => 'admin@university.edu',
    'phone' => '',
    'created_at' => date('Y-m-d H:i:s')
];
$adminLevel = $adminLevel ?? 'admin';
$totalStudents = $totalStudents ?? 0;
$studentsThisMonth = $studentsThisMonth ?? 0;
$totalDoctors = $totalDoctors ?? 0;
$doctorsThisMonth = $doctorsThisMonth ?? 0;
$totalCourses = $totalCourses ?? 0;
$coursesThisSemester = $coursesThisSemester ?? 0;
$totalReports = $totalReports ?? 0;
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-user-shield"></i> Admin Profile</h1>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('admin/profile')) ?>" class="btn btn-outline">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
                <button class="btn btn-primary" onclick="editProfile()">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : ($messageType === 'warning' ? 'warning' : 'info')) ?>" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Profile Overview -->
        <section class="profile-overview" style="margin-bottom: 2rem;">
            <div class="card">
                <div style="display: flex; align-items: center; gap: 2rem; padding: 2rem; flex-wrap: wrap;">
                    <div style="text-align: center;">
                        <div style="width: 120px; height: 120px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; margin-bottom: 1rem;">
                            <i class="fas fa-user-shield"></i>
                        </div>

                    </div>
                    <div style="flex: 1;">
                        <h2 style="margin: 0 0 0.5rem 0; color: var(--text-primary);"><?= htmlspecialchars(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '')) ?></h2>
                        <p style="margin: 0 0 1rem 0; color: var(--text-secondary); font-size: 1.1rem;">System Administrator</p>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Access Level</div>
                                <div style="font-size: 1.1rem; font-weight: 600; color: var(--accent-color);"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $adminLevel))) ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Email</div>
                                <div style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($admin['email'] ?? 'N/A') ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Phone</div>
                                <div style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($admin['phone'] ?? 'N/A') ?></div>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Member Since</div>
                                <div style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary);"><?= $admin['created_at'] ? htmlspecialchars(date('M Y', strtotime($admin['created_at']))) : 'N/A' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- System Statistics -->
        <section class="system-stats" style="margin-bottom: 2rem;">
            <div class="grid grid-4">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalStudents) ?></div>
                    <div style="color: var(--text-secondary);">Students Managed</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--accent-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalDoctors) ?></div>
                    <div style="color: var(--text-secondary);">Faculty Managed</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-book"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalCourses) ?></div>
                    <div style="color: var(--text-secondary);">Courses Managed</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--warning-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalReports) ?></div>
                    <div style="color: var(--text-secondary);">Reports Generated</div>
                </div>
            </div>
        </section>

        <!-- Profile Information -->
        <div class="grid grid-2" style="gap: 2rem;">
            <!-- Personal Information -->
            <section class="personal-info">
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h2 class="card-title">
                            <i class="fas fa-user" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                            Personal Information
                        </h2>
                        <button class="btn btn-outline" onclick="editProfile()">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                    <div class="info-list">
                        <div class="info-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color);">
                            <div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Full Name</div>
                                <div style="font-weight: 600; color: var(--text-primary);" id="displayName"><?= htmlspecialchars(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '')) ?></div>
                            </div>
                            <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editProfile()">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="info-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color);">
                            <div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Email Address</div>
                                <div style="font-weight: 600; color: var(--text-primary);" id="displayEmail"><?= htmlspecialchars($admin['email'] ?? 'N/A') ?></div>
                            </div>
                            <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editProfile()">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="info-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color);">
                            <div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Phone Number</div>
                                <div style="font-weight: 600; color: var(--text-primary);" id="displayPhone"><?= htmlspecialchars($admin['phone'] ?? 'N/A') ?></div>
                            </div>
                            <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editProfile()">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="info-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color);">
                            <div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Access Level</div>
                                <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $adminLevel))) ?></div>
                            </div>
                        </div>
                        <div class="info-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem;">
                            <div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Member Since</div>
                                <div style="font-weight: 600; color: var(--text-primary);"><?= $admin['created_at'] ? htmlspecialchars(date('F Y', strtotime($admin['created_at']))) : 'N/A' ?></div>
                            </div>
                        </div>
                                    </div>
            </section>
        </div>

        <!-- Password Management -->
        <section class="password-management" style="margin-top: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-lock" style="color: var(--error-color); margin-right: 0.5rem;"></i>
                        Password Management
                    </h2>
                </div>
                <div style="padding: 1.5rem;">
                    <form class="password-form" id="passwordForm" method="POST" action="<?= htmlspecialchars($url('admin/profile')) ?>" onsubmit="return handlePasswordUpdate(event)">
                        <input type="hidden" name="action" value="update_password">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" id="currentPassword" class="form-input" placeholder="Enter current password" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" id="newPassword" class="form-input" placeholder="Enter new password" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-input" placeholder="Confirm new password" required>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Password
                            </button>
                            <button type="button" class="btn btn-outline" onclick="resetPassword()">
                                <i class="fas fa-undo"></i> Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- System Settings -->
        <section class="system-settings" style="margin-top: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-cog" style="color: var(--accent-color); margin-right: 0.5rem;"></i>
                        System Settings
                    </h2>
                </div>
                <div style="padding: 1.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <div>
                            <h4 style="margin-bottom: 1rem; color: var(--text-primary);">Notification Preferences</h4>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" checked>
                                    <span>System alerts and warnings</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" checked>
                                    <span>User registration notifications</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" checked>
                                    <span>Security breach alerts</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox">
                                    <span>Daily system reports</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 1rem; color: var(--text-primary);">Security Settings</h4>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" checked>
                                    <span>Two-factor authentication</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" checked>
                                    <span>Session timeout (30 minutes)</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" checked>
                                    <span>Login attempt monitoring</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox">
                                    <span>IP address restrictions</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 1.5rem;">
                        <button class="btn btn-primary" onclick="saveSettings()">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="profileEditModal" class="modal" data-header-style="primary">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2>Edit Profile</h2>
            <button class="modal-close" onclick="closeProfileEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="profileEditForm" method="POST" action="<?= htmlspecialchars($url('admin/profile')) ?>" onsubmit="return handleProfileEditSubmit(event)">
            <input type="hidden" name="action" value="update_profile">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" id="editFirstName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" id="editLastName" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" id="editEmail" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" id="editPhone" class="form-input" placeholder="e.g., +1234567890">
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeProfileEditModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Show toast notification on page load if there's a message
<?php if (!empty($message)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const messageType = '<?php echo htmlspecialchars($messageType, ENT_QUOTES); ?>';
    const message = <?php echo json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    
    if (typeof showToastifyNotification !== 'undefined') {
        showToastifyNotification(message, messageType);
    } else if (typeof showNotification !== 'undefined') {
        showNotification(message, messageType);
    }
});
<?php endif; ?>

// Profile actions
function editProfile() {
    // Populate form with current admin data
    document.getElementById('editFirstName').value = <?php echo json_encode($admin['first_name'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    document.getElementById('editLastName').value = <?php echo json_encode($admin['last_name'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    document.getElementById('editEmail').value = <?php echo json_encode($admin['email'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    document.getElementById('editPhone').value = <?php echo json_encode($admin['phone'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    
    if (typeof showModal !== 'undefined') {
        showModal(document.getElementById('profileEditModal'));
    } else {
        document.getElementById('profileEditModal').classList.add('active');
        document.getElementById('profileEditModal').style.display = 'flex';
    }
}

function closeProfileEditModal() {
    const modal = document.getElementById('profileEditModal');
    if (typeof hideModal !== 'undefined') {
        hideModal(modal);
    } else {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

function handleProfileEditSubmit(e) {
    // Form validation is handled by HTML5 required attributes
    // The form will submit normally to the controller
    return true;
}

function handlePasswordUpdate(e) {
    const form = document.getElementById('passwordForm');
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // Validate passwords match
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        if (typeof showNotification !== 'undefined') {
            showNotification('New passwords do not match', 'error');
        } else if (typeof showToastifyNotification !== 'undefined') {
            showToastifyNotification('New passwords do not match', 'error');
        }
        return false;
    }

    // Validate password length
    if (newPassword.length < 8) {
        e.preventDefault();
        if (typeof showNotification !== 'undefined') {
            showNotification('Password must be at least 8 characters long', 'error');
        } else if (typeof showToastifyNotification !== 'undefined') {
            showToastifyNotification('Password must be at least 8 characters long', 'error');
        }
        return false;
    }

    // Form will submit normally
    return true;
}

function changeProfilePicture() {
    if (typeof showNotification !== 'undefined') {
        showNotification('Profile picture upload feature coming soon', 'info');
    }
}


function resetPassword() {
    if (confirm('Are you sure you want to reset your password? You will receive an email with instructions.')) {
        if (typeof showNotification !== 'undefined') {
            showNotification('Password reset email sent!', 'success');
        }
    }
}

function saveSettings() {
    if (typeof showNotification !== 'undefined') {
        showNotification('Settings saved successfully!', 'success');
    }
}
</script>

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
    --surface-color: var(--bg-primary);
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
    --surface-color: var(--bg-secondary);
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    background: var(--bg-primary);
    min-height: 100vh;
    color: var(--text-primary);
}

.dashboard-header {
    margin-bottom: 2.5rem;
    padding: 2rem;
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
    border-radius: 16px;
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 12px var(--shadow-md);
}

.dashboard-header h1 {
    font-size: 2.5rem;
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.dashboard-header h1 i {
    font-size: 2rem;
}

.dashboard-header p {
    font-size: 1.1rem;
    margin: 0;
    opacity: 0.95;
}

.dashboard-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
}

.btn-success {
    background: var(--success-color);
    color: white;
}

.btn-success:hover {
    background: #059669;
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
}

.btn-warning {
    background: var(--warning-color);
    color: white;
}

.btn-warning:hover {
    background: #d97706;
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
}

.btn-danger {
    background: var(--error-color);
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    box-shadow: 0 4px 12px var(--shadow-md);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s;
}

.card:hover::before {
    transform: translateX(100%);
}

.card:hover {
    box-shadow: 0 12px 32px var(--shadow-lg);
    transform: translateY(-8px) scale(1.02);
    border-color: var(--primary-color);
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-tertiary);
}

.card-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-input, .form-select {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--surface-color);
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.2s ease;
    width: 100%;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    transform: translateY(-1px);
}

.grid {
    display: grid;
    gap: 1.5rem;
}

.grid-2 {
    grid-template-columns: repeat(2, 1fr);
}

.grid-3 {
    grid-template-columns: repeat(3, 1fr);
}

.grid-4 {
    grid-template-columns: repeat(4, 1fr);
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

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #f59e0b;
}

.alert-info {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
    transition: transform 0.2s ease;
}

.badge:hover {
    transform: scale(1.05);
}

.modal {
    backdrop-filter: blur(4px);
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

table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

table tbody tr {
    transition: all 0.2s ease;
}

table tbody tr:hover {
    background: var(--bg-tertiary);
    transform: scale(1.01);
}

@media (max-width: 1200px) {
    .grid-2, .grid-3, .grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .dashboard-header {
        padding: 1.5rem;
    }
    
    .dashboard-header h1 {
        font-size: 2rem;
    }
    
    .grid-2, .grid-3, .grid-4 {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        padding: 1rem;
    }
}
</style>
