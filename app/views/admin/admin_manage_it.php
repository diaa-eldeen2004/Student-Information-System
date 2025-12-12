<?php
// Ensure variables exist with defaults
$itOfficers = $itOfficers ?? [];
$totalIT = $totalIT ?? 0;
$itThisMonth = $itThisMonth ?? 0;
$search = $search ?? '';
$message = $message ?? null;
$messageType = $messageType ?? 'info';
$editIT = $editIT ?? null;
?>

<div class="admin-container">
    <div class="admin-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-laptop-code"></i> Manage IT Officers</h1>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('admin/manage-it')) ?>" class="btn btn-outline">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
                <button class="btn btn-primary" onclick="addIT()">
                    <i class="fas fa-plus"></i> Add IT Officer
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

        <!-- IT Statistics -->
        <section class="it-stats" style="margin-bottom: 2rem;">
            <div class="grid grid-4">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalIT) ?></div>
                    <div style="color: var(--text-secondary);">Total IT Officers</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalIT) ?></div>
                    <div style="color: var(--text-secondary);">Active IT Officers</div>
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
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($itThisMonth) ?></div>
                    <div style="color: var(--text-secondary);">New This Month</div>
                </div>
            </div>
        </section>

        <!-- IT Filter -->
        <section class="it-filter" style="margin-bottom: 2rem;">
            <div class="card">
                <form method="GET" action="<?= htmlspecialchars($url('admin/manage-it')) ?>" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="search" class="form-input" placeholder="Search IT officers..." value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter'){this.form.submit();}">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?= htmlspecialchars($url('admin/manage-it')) ?>" class="btn btn-outline">Clear</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- IT Officers List -->
        <section class="it-list">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-laptop-code" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        IT Officers Directory
                    </h2>
                </div>

                <!-- IT Officers Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th>IT Officer</th>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($itOfficers)): ?>
                                <?php foreach ($itOfficers as $it): ?>
                                    <tr>
                                        <td><input type="checkbox" class="it-checkbox" value="<?= htmlspecialchars($it['it_id'] ?? '') ?>"></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 40px; height: 40px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                                    <i class="fas fa-laptop-code"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars(($it['first_name'] ?? '') . ' ' . ($it['last_name'] ?? '')) ?></div>
                                                    <div style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($it['email'] ?? '') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($it['it_id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($it['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($it['phone'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($it['created_at'] ?? 'now'))) ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.25rem;">
                                                <button class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="viewIT(<?= htmlspecialchars($it['it_id'] ?? '') ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editIT(<?= htmlspecialchars($it['it_id'] ?? '') ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="deleteIT(<?= htmlspecialchars($it['it_id'] ?? '') ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                        <i class="fas fa-laptop-code" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                        <div>No IT officers found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">
                        Showing <?= count($itOfficers) ?> of <?= htmlspecialchars($totalIT) ?> IT officers
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
                    <button class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="addIT()">
                        <i class="fas fa-plus" style="font-size: 2rem;"></i>
                        <span>Add IT Officer</span>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeAllModals()" style="display: none;"></div>

<!-- View IT Officer Details Modal -->
<div id="itViewModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2><i class="fas fa-laptop-code"></i> IT Officer Details</h2>
            <button class="modal-close" onclick="closeITViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="itViewContent" style="padding: 1.5rem;">
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                <p style="margin-top: 1rem; color: var(--text-secondary);">Loading IT officer details...</p>
            </div>
        </div>
        <div class="modal-footer" style="padding: 1rem; border-top: 1px solid var(--border-color); display: flex; gap: 1rem; justify-content: flex-end;">
            <button class="btn btn-outline" onclick="closeITViewModal()">Close</button>
            <button class="btn btn-primary" onclick="editITFromView()">
                <i class="fas fa-edit"></i> Edit IT Officer
            </button>
        </div>
    </div>
</div>

<!-- Add/Edit IT Officer Modal -->
<div id="itFormModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 id="itModalTitle">Add IT Officer</h2>
            <button class="modal-close" onclick="closeITFormModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="itForm" method="POST" action="<?= htmlspecialchars($url('admin/manage-it')) ?>" onsubmit="return handleITFormSubmit(event)">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="it_id" id="itId" value="">
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
                <small style="color: var(--text-secondary); font-size: 0.9rem;">Leave blank for new IT officers to auto-generate a password</small>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save IT Officer
                </button>
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeITFormModal()">
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
    const itCheckboxes = document.querySelectorAll('.it-checkbox');
    itCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Store current viewing IT ID
let currentViewingITId = null;

// Helper function to escape HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

async function viewIT(itId) {
    currentViewingITId = itId;
    const modal = document.getElementById('itViewModal');
    const content = document.getElementById('itViewContent');
    
    content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i><p style="margin-top: 1rem; color: var(--text-secondary);">Loading IT officer details...</p></div>`;
    
    const overlay = document.getElementById('modalOverlay');
    if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
    modal.style.display = 'flex';
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/it')) ?>?id=' + itId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const it = result.data;
            content.innerHTML = `
                <div style="display: grid; gap: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background-color: var(--surface-color); border-radius: 8px;">
                        <div style="width: 80px; height: 80px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; color: var(--text-primary);">${escapeHtml(it.first_name || '')} ${escapeHtml(it.last_name || '')}</h3>
                            <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary);">${escapeHtml(it.email || '')}</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">IT Officer ID</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(it.it_id || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Phone</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(it.phone || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Email</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(it.email || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Created</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(it.created_at ? new Date(it.created_at).toLocaleDateString() : 'N/A')}</div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i><p style="color: var(--text-secondary);">${escapeHtml(result.message || 'Failed to load IT officer details')}</p></div>`;
        }
    } catch (error) {
        content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i><p style="color: var(--text-secondary);">An error occurred while loading IT officer details.</p></div>`;
        console.error('Error loading IT officer:', error);
    }
}

function closeITViewModal() {
    const modal = document.getElementById('itViewModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) { modal.style.display = 'none'; modal.classList.remove('active'); }
    if (overlay) { overlay.style.display = 'none'; overlay.classList.remove('active'); }
    document.body.style.overflow = '';
    currentViewingITId = null;
}

function editITFromView() {
    if (currentViewingITId) {
        closeITViewModal();
        editIT(currentViewingITId);
    }
}

function closeAllModals() {
    closeITViewModal();
    closeITFormModal();
}

async function editIT(itId) {
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/it')) ?>?id=' + itId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const it = result.data;
            document.getElementById('itForm').elements['first_name'].value = it.first_name || '';
            document.getElementById('itForm').elements['last_name'].value = it.last_name || '';
            document.getElementById('itForm').elements['email'].value = it.email || '';
            document.getElementById('itForm').elements['phone'].value = it.phone || '';
            document.getElementById('itId').value = it.it_id;
            document.getElementById('formAction').value = 'update';
            document.getElementById('itModalTitle').textContent = 'Edit IT Officer';
            
            const formModal = document.getElementById('itFormModal');
            const overlay = document.getElementById('modalOverlay');
            if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
            formModal.style.display = 'flex';
            formModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            if (typeof showToastifyNotification !== 'undefined') {
                showToastifyNotification(result.message || 'Failed to load IT officer data', 'error');
            } else {
                alert(result.message || 'Failed to load IT officer data');
            }
        }
    } catch (error) {
        console.error('Error loading IT officer:', error);
        if (typeof showToastifyNotification !== 'undefined') {
            showToastifyNotification('An error occurred while loading IT officer data', 'error');
        } else {
            alert('An error occurred while loading IT officer data');
        }
    }
}

function deleteIT(itId) {
    if (confirm('Are you sure you want to delete this IT officer? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= htmlspecialchars($url('admin/manage-it')) ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'it_id';
        idInput.value = itId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function addIT() {
    document.getElementById('itForm').reset();
    document.getElementById('itId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('itModalTitle').textContent = 'Add IT Officer';
    
    const formModal = document.getElementById('itFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
    formModal.style.display = 'flex';
    formModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeITFormModal() {
    const modal = document.getElementById('itFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) { modal.style.display = 'none'; modal.classList.remove('active'); }
    if (overlay) { overlay.style.display = 'none'; overlay.classList.remove('active'); }
    document.body.style.overflow = '';
}

function handleITFormSubmit(e) {
    // Form validation is handled by HTML5 required attributes
    // The form will submit normally to the controller
    return true;
}

// Auto-populate form if editing
<?php if ($editIT): ?>
document.addEventListener('DOMContentLoaded', function() {
    const it = <?php echo json_encode($editIT, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (it) {
        document.getElementById('itForm').elements['first_name'].value = it.first_name || '';
        document.getElementById('itForm').elements['last_name'].value = it.last_name || '';
        document.getElementById('itForm').elements['email'].value = it.email || '';
        document.getElementById('itForm').elements['phone'].value = it.phone || '';
        document.getElementById('itId').value = it.it_id;
        document.getElementById('formAction').value = 'update';
        document.getElementById('itModalTitle').textContent = 'Edit IT Officer';
        
        // Open modal
        addIT();
    }
});
<?php endif; ?>
</script>
