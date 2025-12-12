<?php
// Ensure variables exist with defaults
$admins = $admins ?? [];
$totalAdmins = $totalAdmins ?? 0;
$adminsThisMonth = $adminsThisMonth ?? 0;
$search = $search ?? '';
$message = $message ?? null;
$messageType = $messageType ?? 'info';
$editAdmin = $editAdmin ?? null;
?>

<div class="admin-container">
    <div class="admin-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-user-shield"></i> Manage Admins</h1>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('admin/manage-admin')) ?>" class="btn btn-outline">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
                <button class="btn btn-primary" onclick="addAdmin()">
                    <i class="fas fa-plus"></i> Add Admin
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

        <!-- Admin Statistics -->
        <section class="admin-stats" style="margin-bottom: 2rem;">
            <div class="grid grid-4">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalAdmins) ?></div>
                    <div style="color: var(--text-secondary);">Total Admins</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalAdmins) ?></div>
                    <div style="color: var(--text-secondary);">Active Admins</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--warning-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalAdmins) ?></div>
                    <div style="color: var(--text-secondary);">Full Access</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--accent-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($adminsThisMonth) ?></div>
                    <div style="color: var(--text-secondary);">New This Month</div>
                </div>
            </div>
        </section>

        <!-- Admin Filter -->
        <section class="admin-filter" style="margin-bottom: 2rem;">
            <div class="card">
                <form method="GET" action="<?= htmlspecialchars($url('admin/manage-admin')) ?>" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="search" class="form-input" placeholder="Search admins..." value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter'){this.form.submit();}">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?= htmlspecialchars($url('admin/manage-admin')) ?>" class="btn btn-outline">Clear</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- Admins List -->
        <section class="admins-list">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-user-shield" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        Administrator Directory
                    </h2>
                </div>

                <!-- Admins Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th>Admin</th>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($admins)): ?>
                                <?php foreach ($admins as $a): ?>
                                    <tr>
                                        <td><input type="checkbox" class="admin-checkbox" value="<?= htmlspecialchars($a['admin_id'] ?? '') ?>"></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 40px; height: 40px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                                    <i class="fas fa-user-shield"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')) ?></div>
                                                    <div style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($a['email'] ?? '') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($a['admin_id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($a['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($a['phone'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($a['created_at'] ?? 'now'))) ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.25rem;">
                                                <button class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="viewAdmin(<?= htmlspecialchars($a['admin_id'] ?? '') ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editAdmin(<?= htmlspecialchars($a['admin_id'] ?? '') ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (isset($a['admin_id']) && isset($_SESSION['user']['admin_id']) && $a['admin_id'] != $_SESSION['user']['admin_id']): ?>
                                                    <button class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="deleteAdmin(<?= htmlspecialchars($a['admin_id'] ?? '') ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" disabled title="Cannot delete your own account">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                        <i class="fas fa-user-shield" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                        <div>No admins found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">
                        Showing <?= count($admins) ?> of <?= htmlspecialchars($totalAdmins) ?> administrators
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
                    <button class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="addAdmin()">
                        <i class="fas fa-plus" style="font-size: 2rem;"></i>
                        <span>Add Admin</span>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeAllModals()" style="display: none;"></div>

<!-- View Admin Details Modal -->
<div id="adminViewModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2><i class="fas fa-user-shield"></i> Admin Details</h2>
            <button class="modal-close" onclick="closeAdminViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="adminViewContent" style="padding: 1.5rem;">
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                <p style="margin-top: 1rem; color: var(--text-secondary);">Loading admin details...</p>
            </div>
        </div>
        <div class="modal-footer" style="padding: 1rem; border-top: 1px solid var(--border-color); display: flex; gap: 1rem; justify-content: flex-end;">
            <button class="btn btn-outline" onclick="closeAdminViewModal()">Close</button>
            <button class="btn btn-primary" onclick="editAdminFromView()">
                <i class="fas fa-edit"></i> Edit Admin
            </button>
        </div>
    </div>
</div>

<!-- Add/Edit Admin Modal -->
<div id="adminFormModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 id="adminModalTitle">Add Admin</h2>
            <button class="modal-close" onclick="closeAdminFormModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="adminForm" method="POST" action="<?= htmlspecialchars($url('admin/manage-admin')) ?>" onsubmit="return handleAdminFormSubmit(event)">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="admin_id" id="adminId" value="">
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
                <small style="color: var(--text-secondary); font-size: 0.9rem;">Leave blank for new admins to auto-generate a password</small>
            </div>
            <div class="alert alert-info" style="padding: 1rem; border-radius: 6px; margin-top: 1rem; background-color: rgba(37, 99, 235, 0.1); border-left: 4px solid var(--primary-color);">
                <i class="fas fa-info-circle" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                <strong>Admin Capabilities:</strong> Administrators have full access to manage students, doctors, courses, advisors, IT officers, and system settings.
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Admin
                </button>
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeAdminFormModal()">
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
    const adminCheckboxes = document.querySelectorAll('.admin-checkbox');
    adminCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Store current viewing admin ID
let currentViewingAdminId = null;

// Helper function to escape HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

async function viewAdmin(adminId) {
    currentViewingAdminId = adminId;
    const modal = document.getElementById('adminViewModal');
    const content = document.getElementById('adminViewContent');
    
    content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i><p style="margin-top: 1rem; color: var(--text-secondary);">Loading admin details...</p></div>`;
    
    const overlay = document.getElementById('modalOverlay');
    if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
    modal.style.display = 'flex';
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/admin')) ?>?id=' + adminId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const a = result.data;
            content.innerHTML = `
                <div style="display: grid; gap: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background-color: var(--surface-color); border-radius: 8px;">
                        <div style="width: 80px; height: 80px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; color: var(--text-primary);">${escapeHtml(a.first_name || '')} ${escapeHtml(a.last_name || '')}</h3>
                            <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary);">${escapeHtml(a.email || '')}</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Admin ID</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(a.admin_id || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Phone</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(a.phone || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Admin Level</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(a.admin_level || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Email</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(a.email || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Created</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(a.created_at ? new Date(a.created_at).toLocaleDateString() : 'N/A')}</div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i><p style="color: var(--text-secondary);">${escapeHtml(result.message || 'Failed to load admin details')}</p></div>`;
        }
    } catch (error) {
        content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i><p style="color: var(--text-secondary);">An error occurred while loading admin details.</p></div>`;
        console.error('Error loading admin:', error);
    }
}

function closeAdminViewModal() {
    const modal = document.getElementById('adminViewModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) { modal.style.display = 'none'; modal.classList.remove('active'); }
    if (overlay) { overlay.style.display = 'none'; overlay.classList.remove('active'); }
    document.body.style.overflow = '';
    currentViewingAdminId = null;
}

function editAdminFromView() {
    if (currentViewingAdminId) {
        closeAdminViewModal();
        editAdmin(currentViewingAdminId);
    }
}

function closeAllModals() {
    closeAdminViewModal();
    closeAdminFormModal();
}

async function editAdmin(adminId) {
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/admin')) ?>?id=' + adminId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const admin = result.data;
            document.getElementById('adminForm').elements['first_name'].value = admin.first_name || '';
            document.getElementById('adminForm').elements['last_name'].value = admin.last_name || '';
            document.getElementById('adminForm').elements['email'].value = admin.email || '';
            document.getElementById('adminForm').elements['phone'].value = admin.phone || '';
            document.getElementById('adminForm').elements['admin_level'].value = admin.admin_level || 'standard';
            document.getElementById('adminId').value = admin.admin_id;
            document.getElementById('formAction').value = 'update';
            document.getElementById('adminModalTitle').textContent = 'Edit Admin';
            
            const formModal = document.getElementById('adminFormModal');
            const overlay = document.getElementById('modalOverlay');
            if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
            formModal.style.display = 'flex';
            formModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            if (typeof showToastifyNotification !== 'undefined') {
                showToastifyNotification(result.message || 'Failed to load admin data', 'error');
            } else {
                alert(result.message || 'Failed to load admin data');
            }
        }
    } catch (error) {
        console.error('Error loading admin:', error);
        if (typeof showToastifyNotification !== 'undefined') {
            showToastifyNotification('An error occurred while loading admin data', 'error');
        } else {
            alert('An error occurred while loading admin data');
        }
    }
}

function deleteAdmin(adminId) {
    if (confirm('Are you sure you want to delete this administrator? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= htmlspecialchars($url('admin/manage-admin')) ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'admin_id';
        idInput.value = adminId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function addAdmin() {
    document.getElementById('adminForm').reset();
    document.getElementById('adminId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('adminModalTitle').textContent = 'Add Admin';
    
    const formModal = document.getElementById('adminFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
    formModal.style.display = 'flex';
    formModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAdminFormModal() {
    const modal = document.getElementById('adminFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) { modal.style.display = 'none'; modal.classList.remove('active'); }
    if (overlay) { overlay.style.display = 'none'; overlay.classList.remove('active'); }
    document.body.style.overflow = '';
}

function handleAdminFormSubmit(e) {
    // Form validation is handled by HTML5 required attributes
    // The form will submit normally to the controller
    return true;
}

// Auto-populate form if editing
<?php if ($editAdmin): ?>
document.addEventListener('DOMContentLoaded', function() {
    const admin = <?php echo json_encode($editAdmin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (admin) {
        document.getElementById('adminForm').elements['first_name'].value = admin.first_name || '';
        document.getElementById('adminForm').elements['last_name'].value = admin.last_name || '';
        document.getElementById('adminForm').elements['email'].value = admin.email || '';
        document.getElementById('adminForm').elements['phone'].value = admin.phone || '';
        document.getElementById('adminId').value = admin.admin_id;
        document.getElementById('formAction').value = 'update';
        document.getElementById('adminModalTitle').textContent = 'Edit Admin';
        
        // Open modal
        addAdmin();
    }
});
<?php endif; ?>
</script>
