<?php
// Ensure variables exist with defaults
$advisors = $advisors ?? [];
$totalAdvisors = $totalAdvisors ?? 0;
$advisorsThisMonth = $advisorsThisMonth ?? 0;
$departments = $departments ?? [];
$search = $search ?? '';
$departmentFilter = $departmentFilter ?? '';
$message = $message ?? null;
$messageType = $messageType ?? 'info';
$editAdvisor = $editAdvisor ?? null;
?>

<div class="admin-container">
    <div class="admin-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-user-tie"></i> Manage Advisors</h1>
                
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('admin/manage-advisor')) ?>" class="btn btn-outline">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
                <button class="btn btn-primary" onclick="addAdvisor()">
                    <i class="fas fa-plus"></i> Add Advisor
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

        <!-- Advisor Statistics -->
        <section class="advisor-stats" style="margin-bottom: 2rem;">
            <div class="grid grid-4">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalAdvisors) ?></div>
                    <div style="color: var(--text-secondary);">Total Advisors</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalAdvisors) ?></div>
                    <div style="color: var(--text-secondary);">Active Advisors</div>
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
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($advisorsThisMonth) ?></div>
                    <div style="color: var(--text-secondary);">New This Month</div>
                </div>
            </div>
        </section>

        <!-- Advisor Filter -->
        <section class="advisor-filter" style="margin-bottom: 2rem;">
            <div class="card">
                <form method="GET" action="<?= htmlspecialchars($url('admin/manage-advisor')) ?>" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="search" class="form-input" placeholder="Search advisors..." value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter'){this.form.submit();}">
                    </div>
                    <div>
                        <select name="department" class="form-input" onchange="this.form.submit()">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" <?= $departmentFilter === $dept ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?= htmlspecialchars($url('admin/manage-advisor')) ?>" class="btn btn-outline">Clear</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- Advisors List -->
        <section class="advisors-list">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-user-tie" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        Advisor Directory
                    </h2>
                </div>

                <!-- Advisors Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th>Advisor</th>
                                <th>ID</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($advisors)): ?>
                                <?php foreach ($advisors as $a): ?>
                                    <tr>
                                        <td><input type="checkbox" class="advisor-checkbox" value="<?= htmlspecialchars($a['advisor_id']) ?>"></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 40px; height: 40px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                                    <i class="fas fa-user-tie"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')) ?></div>
                                                    <div style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($a['email'] ?? '') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($a['advisor_id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($a['department'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($a['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($a['created_at'] ?? 'now'))) ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.25rem;">
                                                <button class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="viewAdvisor(<?= htmlspecialchars($a['advisor_id']) ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editAdvisor(<?= htmlspecialchars($a['advisor_id']) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="deleteAdvisor(<?= htmlspecialchars($a['advisor_id']) ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                        <i class="fas fa-user-tie" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                        <div>No advisors found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">
                        Showing <?= count($advisors) ?> of <?= htmlspecialchars($totalAdvisors) ?> advisors
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
                    <button class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="addAdvisor()">
                        <i class="fas fa-plus" style="font-size: 2rem;"></i>
                        <span>Add Advisor</span>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeAllModals()" style="display: none;"></div>

<!-- View Advisor Details Modal -->
<div id="advisorViewModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2><i class="fas fa-user-tie"></i> Advisor Details</h2>
            <button class="modal-close" onclick="closeAdvisorViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="advisorViewContent" style="padding: 1.5rem;">
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                <p style="margin-top: 1rem; color: var(--text-secondary);">Loading advisor details...</p>
            </div>
        </div>
        <div class="modal-footer" style="padding: 1rem; border-top: 1px solid var(--border-color); display: flex; gap: 1rem; justify-content: flex-end;">
            <button class="btn btn-outline" onclick="closeAdvisorViewModal()">Close</button>
            <button class="btn btn-primary" onclick="editAdvisorFromView()">
                <i class="fas fa-edit"></i> Edit Advisor
            </button>
        </div>
    </div>
</div>

<!-- Add/Edit Advisor Modal -->
<div id="advisorFormModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 id="advisorModalTitle">Add Advisor</h2>
            <button class="modal-close" onclick="closeAdvisorFormModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="advisorForm" method="POST" action="<?= htmlspecialchars($url('admin/manage-advisor')) ?>" onsubmit="return handleAdvisorFormSubmit(event)">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="advisor_id" id="advisorId" value="">
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
                <label class="form-label">Department</label>
                <select name="department" class="form-input">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Mathematics">Mathematics</option>
                    <option value="Physics">Physics</option>
                    <option value="Engineering">Engineering</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Leave blank to auto-generate">
                <small style="color: var(--text-secondary); font-size: 0.9rem;">Leave blank for new advisors to auto-generate a password</small>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Advisor
                </button>
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeAdvisorFormModal()">
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
    const advisorCheckboxes = document.querySelectorAll('.advisor-checkbox');
    advisorCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Store current viewing advisor ID
let currentViewingAdvisorId = null;

// Helper function to escape HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

async function viewAdvisor(advisorId) {
    currentViewingAdvisorId = advisorId;
    const modal = document.getElementById('advisorViewModal');
    const content = document.getElementById('advisorViewContent');
    
    // Show loading state
    content.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
            <p style="margin-top: 1rem; color: var(--text-secondary);">Loading advisor details...</p>
        </div>
    `;
    
    // Open modal
    const overlay = document.getElementById('modalOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        overlay.classList.add('active');
    }
    modal.style.display = 'flex';
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Fetch advisor details
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/advisor')) ?>?id=' + advisorId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const a = result.data;
            
            content.innerHTML = `
                <div style="display: grid; gap: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background-color: var(--surface-color); border-radius: 8px;">
                        <div style="width: 80px; height: 80px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; color: var(--text-primary);">${escapeHtml(a.first_name || '')} ${escapeHtml(a.last_name || '')}</h3>
                            <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary);">${escapeHtml(a.email || '')}</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Advisor ID</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(a.advisor_id || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Phone</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(a.phone || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Department</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(a.department || 'N/A')}</div>
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
            content.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i>
                    <p style="color: var(--text-secondary);">${escapeHtml(result.message || 'Failed to load advisor details')}</p>
                </div>
            `;
        }
    } catch (error) {
        content.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary);">An error occurred while loading advisor details.</p>
            </div>
        `;
        console.error('Error loading advisor:', error);
    }
}

function closeAdvisorViewModal() {
    const modal = document.getElementById('advisorViewModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
    if (overlay) {
        overlay.style.display = 'none';
        overlay.classList.remove('active');
    }
    document.body.style.overflow = '';
    currentViewingAdvisorId = null;
}

function editAdvisorFromView() {
    if (currentViewingAdvisorId) {
        closeAdvisorViewModal();
        editAdvisor(currentViewingAdvisorId);
    }
}

function closeAllModals() {
    closeAdvisorViewModal();
    closeAdvisorFormModal();
}

async function editAdvisor(advisorId) {
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/advisor')) ?>?id=' + advisorId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const advisor = result.data;
            
            // Populate form fields
            document.getElementById('advisorForm').elements['first_name'].value = advisor.first_name || '';
            document.getElementById('advisorForm').elements['last_name'].value = advisor.last_name || '';
            document.getElementById('advisorForm').elements['email'].value = advisor.email || '';
            document.getElementById('advisorForm').elements['phone'].value = advisor.phone || '';
            document.getElementById('advisorForm').elements['department'].value = advisor.department || '';
            
            document.getElementById('advisorId').value = advisor.advisor_id;
            document.getElementById('formAction').value = 'update';
            document.getElementById('advisorModalTitle').textContent = 'Edit Advisor';
            
            // Open modal
            const formModal = document.getElementById('advisorFormModal');
            const overlay = document.getElementById('modalOverlay');
            if (overlay) {
                overlay.style.display = 'flex';
                overlay.classList.add('active');
            }
            formModal.style.display = 'flex';
            formModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            if (typeof showToastifyNotification !== 'undefined') {
                showToastifyNotification(result.message || 'Failed to load advisor data', 'error');
            } else {
                alert(result.message || 'Failed to load advisor data');
            }
        }
    } catch (error) {
        console.error('Error loading advisor:', error);
        if (typeof showToastifyNotification !== 'undefined') {
            showToastifyNotification('An error occurred while loading advisor data', 'error');
        } else {
            alert('An error occurred while loading advisor data');
        }
    }
}

function deleteAdvisor(advisorId) {
    if (confirm('Are you sure you want to delete this advisor? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= htmlspecialchars($url('admin/manage-advisor')) ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'advisor_id';
        idInput.value = advisorId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function addAdvisor() {
    document.getElementById('advisorForm').reset();
    document.getElementById('advisorId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('advisorModalTitle').textContent = 'Add Advisor';
    
    const formModal = document.getElementById('advisorFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        overlay.classList.add('active');
    }
    formModal.style.display = 'flex';
    formModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAdvisorFormModal() {
    const modal = document.getElementById('advisorFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
    if (overlay) {
        overlay.style.display = 'none';
        overlay.classList.remove('active');
    }
    document.body.style.overflow = '';
}

function handleAdvisorFormSubmit(e) {
    // Form validation is handled by HTML5 required attributes
    // The form will submit normally to the controller
    return true;
}

// Auto-populate form if editing
<?php if ($editAdvisor): ?>
document.addEventListener('DOMContentLoaded', function() {
    const advisor = <?php echo json_encode($editAdvisor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (advisor) {
        document.getElementById('advisorForm').elements['first_name'].value = advisor.first_name || '';
        document.getElementById('advisorForm').elements['last_name'].value = advisor.last_name || '';
        document.getElementById('advisorForm').elements['email'].value = advisor.email || '';
        document.getElementById('advisorForm').elements['phone'].value = advisor.phone || '';
        document.getElementById('advisorForm').elements['department'].value = advisor.department || '';
        document.getElementById('advisorId').value = advisor.advisor_id;
        document.getElementById('formAction').value = 'update';
        document.getElementById('advisorModalTitle').textContent = 'Edit Advisor';
        
        // Open modal
        addAdvisor();
    }
});
<?php endif; ?>
</script>
