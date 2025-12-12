<?php
// Ensure variables exist with defaults
$users = $users ?? [];
$totalUsers = $totalUsers ?? 0;
$usersThisMonth = $usersThisMonth ?? 0;
$search = $search ?? '';
$message = $message ?? null;
$messageType = $messageType ?? 'info';
$editUser = $editUser ?? null;
?>

<div class="admin-container">
    <div class="admin-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-users"></i> Manage Users</h1>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('admin/manage-user')) ?>" class="btn btn-outline">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
                <button class="btn btn-primary" onclick="addUser()">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </div>
        </div>
    </div>

    <div class="admin-content">
        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : ($messageType === 'warning' ? 'warning' : 'info')) ?>" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- User Statistics -->
        <section class="user-stats" style="margin-bottom: 2rem;">
            <div class="grid grid-4">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalUsers) ?></div>
                    <div style="color: var(--text-secondary);">Total Users</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalUsers) ?></div>
                    <div style="color: var(--text-secondary);">Active Users</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--warning-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;">0</div>
                    <div style="color: var(--text-secondary);">Pending Approval</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--accent-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($usersThisMonth) ?></div>
                    <div style="color: var(--text-secondary);">New This Month</div>
                </div>
            </div>
        </section>

        <!-- User Filter -->
        <section class="user-filter" style="margin-bottom: 2rem;">
            <div class="card">
                <form method="GET" action="<?= htmlspecialchars($url('admin/manage-user')) ?>" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="search" class="form-input" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter'){this.form.submit();}">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?= htmlspecialchars($url('admin/manage-user')) ?>" class="btn btn-outline">Clear</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- Users List -->
        <section class="users-list">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-users" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        User Directory
                    </h2>
                </div>

                <!-- Users Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th>User</th>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><input type="checkbox" class="user-checkbox" value="<?= htmlspecialchars($u['id'] ?? '') ?>"></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 40px; height: 40px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></div>
                                                    <div style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($u['id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($u['created_at'] ?? 'now'))) ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.25rem;">
                                                <button class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="viewUser(<?= htmlspecialchars($u['id'] ?? '') ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editUser(<?= htmlspecialchars($u['id'] ?? '') ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="deleteUser(<?= htmlspecialchars($u['id'] ?? '') ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                        <div>No users found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">
                        Showing <?= count($users) ?> of <?= htmlspecialchars($totalUsers) ?> users
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="quick-actions" style="margin-top: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-bolt" style="color: var(--accent-color); margin-right: 0.5rem;"></i>
                        Quick Actions
                    </h2>
                </div>
                <div class="grid grid-4">
                    <button class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="addUser()">
                        <i class="fas fa-plus" style="font-size: 2rem;"></i>
                        <span>Add User</span>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeAllModals()" style="display: none;"></div>

<!-- View User Details Modal -->
<div id="userViewModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2><i class="fas fa-user"></i> User Details</h2>
            <button class="modal-close" onclick="closeUserViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="userViewContent" style="padding: 1.5rem;">
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                <p style="margin-top: 1rem; color: var(--text-secondary);">Loading user details...</p>
            </div>
        </div>
        <div class="modal-footer" style="padding: 1rem; border-top: 1px solid var(--border-color); display: flex; gap: 1rem; justify-content: flex-end;">
            <button class="btn btn-outline" onclick="closeUserViewModal()">Close</button>
            <button class="btn btn-primary" onclick="editUserFromView()">
                <i class="fas fa-edit"></i> Edit User
            </button>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="userFormModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 id="userModalTitle">Add User</h2>
            <button class="modal-close" onclick="closeUserFormModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="userForm" method="POST" action="<?= htmlspecialchars($url('admin/manage-user')) ?>" onsubmit="return handleUserFormSubmit(event)">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="user_id" id="userId" value="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-input" placeholder="e.g., +1234567890">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Leave blank to auto-generate">
                <small style="color: var(--text-secondary); font-size: 0.9rem;">Leave blank for new users to auto-generate a password</small>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save User
                </button>
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeUserFormModal()">
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

// Toggle select all
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    userCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Store current viewing user ID
let currentViewingUserId = null;

// Helper function to escape HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

async function viewUser(userId) {
    currentViewingUserId = userId;
    const modal = document.getElementById('userViewModal');
    const content = document.getElementById('userViewContent');
    
    content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i><p style="margin-top: 1rem; color: var(--text-secondary);">Loading user details...</p></div>`;
    
    const overlay = document.getElementById('modalOverlay');
    if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
    modal.style.display = 'flex';
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/user')) ?>?id=' + userId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const u = result.data;
            content.innerHTML = `
                <div style="display: grid; gap: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background-color: var(--surface-color); border-radius: 8px;">
                        <div style="width: 80px; height: 80px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; color: var(--text-primary);">${escapeHtml(u.first_name || '')} ${escapeHtml(u.last_name || '')}</h3>
                            <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary);">${escapeHtml(u.email || '')}</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">User ID</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(u.id || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Phone</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(u.phone || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Role</label>
                            <span style="background-color: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                ${escapeHtml((u.role || 'user').charAt(0).toUpperCase() + (u.role || 'user').slice(1))}
                            </span>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Email</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(u.email || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Created</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(u.created_at ? new Date(u.created_at).toLocaleDateString() : 'N/A')}</div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i><p style="color: var(--text-secondary);">${escapeHtml(result.message || 'Failed to load user details')}</p></div>`;
        }
    } catch (error) {
        content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i><p style="color: var(--text-secondary);">An error occurred while loading user details.</p></div>`;
        console.error('Error loading user:', error);
    }
}

function closeUserViewModal() {
    const modal = document.getElementById('userViewModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) { modal.style.display = 'none'; modal.classList.remove('active'); }
    if (overlay) { overlay.style.display = 'none'; overlay.classList.remove('active'); }
    document.body.style.overflow = '';
    currentViewingUserId = null;
}

function editUserFromView() {
    if (currentViewingUserId) {
        closeUserViewModal();
        editUser(currentViewingUserId);
    }
}

function closeAllModals() {
    closeUserViewModal();
    closeUserFormModal();
}

async function editUser(userId) {
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/user')) ?>?id=' + userId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const user = result.data;
            document.getElementById('userForm').elements['first_name'].value = user.first_name || '';
            document.getElementById('userForm').elements['last_name'].value = user.last_name || '';
            document.getElementById('userForm').elements['email'].value = user.email || '';
            document.getElementById('userForm').elements['phone'].value = user.phone || '';
            document.getElementById('userId').value = user.id;
            document.getElementById('formAction').value = 'update';
            document.getElementById('userModalTitle').textContent = 'Edit User';
            
            const formModal = document.getElementById('userFormModal');
            const overlay = document.getElementById('modalOverlay');
            if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
            formModal.style.display = 'flex';
            formModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            if (typeof showToastifyNotification !== 'undefined') {
                showToastifyNotification(result.message || 'Failed to load user data', 'error');
            } else {
                alert(result.message || 'Failed to load user data');
            }
        }
    } catch (error) {
        console.error('Error loading user:', error);
        if (typeof showToastifyNotification !== 'undefined') {
            showToastifyNotification('An error occurred while loading user data', 'error');
        } else {
            alert('An error occurred while loading user data');
        }
    }
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= htmlspecialchars($url('admin/manage-user')) ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'user_id';
        idInput.value = userId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function addUser() {
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('userModalTitle').textContent = 'Add User';
    
    const formModal = document.getElementById('userFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
    formModal.style.display = 'flex';
    formModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeUserFormModal() {
    const modal = document.getElementById('userFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) { modal.style.display = 'none'; modal.classList.remove('active'); }
    if (overlay) { overlay.style.display = 'none'; overlay.classList.remove('active'); }
    document.body.style.overflow = '';
}

function handleUserFormSubmit(e) {
    // Form validation is handled by HTML5 required attributes
    // The form will submit normally to the controller
    return true;
}

// Auto-populate form if editing
<?php if ($editUser): ?>
document.addEventListener('DOMContentLoaded', function() {
    const user = <?php echo json_encode($editUser, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (user) {
        document.getElementById('userForm').elements['first_name'].value = user.first_name || '';
        document.getElementById('userForm').elements['last_name'].value = user.last_name || '';
        document.getElementById('userForm').elements['email'].value = user.email || '';
        document.getElementById('userForm').elements['phone'].value = user.phone || '';
        document.getElementById('userId').value = user.id;
        document.getElementById('formAction').value = 'update';
        document.getElementById('userModalTitle').textContent = 'Edit User';
        
        // Open modal
        addUser();
    }
});
<?php endif; ?>
</script>
