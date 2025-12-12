<?php
// Ensure variables exist with defaults
$doctors = $doctors ?? [];
$totalDoctors = $totalDoctors ?? 0;
$doctorsThisMonth = $doctorsThisMonth ?? 0;
$activeDoctors = $activeDoctors ?? 0;
$departments = $departments ?? [];
$search = $search ?? '';
$departmentFilter = $departmentFilter ?? '';
$message = $message ?? null;
$messageType = $messageType ?? 'info';
$editDoctor = $editDoctor ?? null;
?>

<div class="admin-container">
    <div class="admin-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-chalkboard-teacher"></i> Manage Doctors</h1>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('admin/manage-doctor')) ?>" class="btn btn-outline">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
                <button class="btn btn-primary" onclick="addDoctor()">
                    <i class="fas fa-plus"></i> Add Doctor
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

        <!-- Doctor Statistics -->
        <section class="doctor-stats" style="margin-bottom: 2rem;">
            <div class="grid grid-4">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalDoctors) ?></div>
                    <div style="color: var(--text-secondary);">Total Faculty</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($activeDoctors) ?></div>
                    <div style="color: var(--text-secondary);">Active Faculty</div>
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
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($doctorsThisMonth) ?></div>
                    <div style="color: var(--text-secondary);">New This Month</div>
                </div>
            </div>
        </section>

        <!-- Doctor Filter -->
        <section class="doctor-filter" style="margin-bottom: 2rem;">
            <div class="card">
                <form method="GET" action="<?= htmlspecialchars($url('admin/manage-doctor')) ?>" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="search" class="form-input" placeholder="Search doctors..." value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter'){this.form.submit();}">
                    </div>
                    <div>
                        <select name="department" class="form-input" onchange="this.form.submit()">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" <?= $departmentFilter === $dept ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="Computer Science" <?= $departmentFilter === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                            <option value="Mathematics" <?= $departmentFilter === 'Mathematics' ? 'selected' : '' ?>>Mathematics</option>
                            <option value="Physics" <?= $departmentFilter === 'Physics' ? 'selected' : '' ?>>Physics</option>
                            <option value="Engineering" <?= $departmentFilter === 'Engineering' ? 'selected' : '' ?>>Engineering</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?= htmlspecialchars($url('admin/manage-doctor')) ?>" class="btn btn-outline">Clear</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- Doctors List -->
        <section class="doctors-list">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-chalkboard-teacher" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        Faculty Directory
                    </h2>
                </div>

                <!-- Doctors Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th>Doctor</th>
                                <th>ID</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($doctors)): ?>
                                <?php foreach ($doctors as $d): ?>
                                    <tr>
                                        <td><input type="checkbox" class="doctor-checkbox" value="<?= htmlspecialchars($d['doctor_id'] ?? '') ?>"></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 40px; height: 40px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                                    <i class="fas fa-user-md"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')) ?></div>
                                                    <div style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($d['email'] ?? '') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($d['doctor_id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($d['department'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($d['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($d['created_at'] ?? 'now'))) ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.25rem;">
                                                <button class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="viewDoctor(<?= htmlspecialchars($d['doctor_id'] ?? '') ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editDoctor(<?= htmlspecialchars($d['doctor_id'] ?? '') ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="deleteDoctor(<?= htmlspecialchars($d['doctor_id'] ?? '') ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                        <i class="fas fa-chalkboard-teacher" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                        <div>No doctors found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">
                        Showing <?= count($doctors) ?> of <?= htmlspecialchars($totalDoctors) ?> faculty members
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
                    <button class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="addDoctor()">
                        <i class="fas fa-plus" style="font-size: 2rem;"></i>
                        <span>Add Doctor</span>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Add/Edit Doctor Modal -->
<div id="doctorFormModal" class="modal" data-header-style="primary">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 id="doctorModalTitle">Add Doctor</h2>
            <button class="modal-close" onclick="closeDoctorFormModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="doctorForm" method="POST" action="<?= htmlspecialchars($url('admin/manage-doctor')) ?>" onsubmit="return handleDoctorFormSubmit(event)">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="doctor_id" id="doctorId" value="">
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
                <label class="form-label">Bio</label>
                <textarea name="bio" class="form-input" rows="3" placeholder="Brief biography..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Leave blank to auto-generate">
                <small style="color: var(--text-secondary); font-size: 0.9rem;">Leave blank for new doctors to auto-generate a password</small>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Doctor
                </button>
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeDoctorFormModal()">
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
    const doctorCheckboxes = document.querySelectorAll('.doctor-checkbox');
    doctorCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Doctor actions
function viewDoctor(doctorId) {
    if (typeof showNotification !== 'undefined') {
        showNotification(`Viewing doctor ${doctorId}...`, 'info');
    }
}

function editDoctor(doctorId) {
    // Redirect to edit page with doctor ID
    const editUrl = '<?= htmlspecialchars($url('admin/manage-doctor')) ?>?edit=' + doctorId;
    window.location.href = editUrl;
}

function deleteDoctor(doctorId) {
    if (confirm('Are you sure you want to delete this doctor? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= htmlspecialchars($url('admin/manage-doctor')) ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'doctor_id';
        idInput.value = doctorId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function addDoctor() {
    document.getElementById('doctorForm').reset();
    document.getElementById('doctorId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('doctorModalTitle').textContent = 'Add Doctor';
    if (typeof showModal !== 'undefined') {
        showModal(document.getElementById('doctorFormModal'));
    } else {
        document.getElementById('doctorFormModal').classList.add('active');
        document.getElementById('doctorFormModal').style.display = 'flex';
    }
}

function closeDoctorFormModal() {
    const modal = document.getElementById('doctorFormModal');
    if (typeof hideModal !== 'undefined') {
        hideModal(modal);
    } else {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

function handleDoctorFormSubmit(e) {
    // Form validation is handled by HTML5 required attributes
    // The form will submit normally to the controller
    return true;
}

// Auto-populate form if editing
<?php if ($editDoctor): ?>
document.addEventListener('DOMContentLoaded', function() {
    const doctor = <?php echo json_encode($editDoctor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (doctor) {
        document.getElementById('doctorForm').elements['first_name'].value = doctor.first_name || '';
        document.getElementById('doctorForm').elements['last_name'].value = doctor.last_name || '';
        document.getElementById('doctorForm').elements['email'].value = doctor.email || '';
        document.getElementById('doctorForm').elements['phone'].value = doctor.phone || '';
        document.getElementById('doctorForm').elements['department'].value = doctor.department || '';
        document.getElementById('doctorForm').elements['bio'].value = doctor.bio || '';
        document.getElementById('doctorId').value = doctor.doctor_id;
        document.getElementById('formAction').value = 'update';
        document.getElementById('doctorModalTitle').textContent = 'Edit Doctor';
        
        // Open modal
        addDoctor();
    }
});
<?php endif; ?>
</script>
