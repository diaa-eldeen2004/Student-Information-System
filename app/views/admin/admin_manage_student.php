<?php
// Ensure variables exist with defaults
$students = $students ?? [];
$totalStudents = $totalStudents ?? 0;
$studentsThisMonth = $studentsThisMonth ?? 0;
$activeStudents = $activeStudents ?? 0;
$majors = $majors ?? [];
$years = $years ?? [];
$search = $search ?? '';
$yearFilter = $yearFilter ?? '';
$statusFilter = $statusFilter ?? '';
$programFilter = $programFilter ?? '';
$message = $message ?? null;
$messageType = $messageType ?? 'info';
$editStudent = $editStudent ?? null;
$missingColumns = $missingColumns ?? [];
$needsMigration = !empty($missingColumns);
?>

<div class="admin-container">
    <div class="admin-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-user-graduate"></i> Manage Students</h1>
                <p>Add, update, and manage student accounts and information.</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('admin/manage-student')) ?>" class="btn btn-outline">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
                <button class="btn btn-primary" onclick="addStudent()">
                    <i class="fas fa-plus"></i> Add Student
                </button>
            </div>
        </div>
    </div>

    <div class="admin-content">
        <!-- Migration Alert -->
        <?php if ($needsMigration): ?>
            <div class="alert alert-warning" style="margin-bottom: 1.5rem; padding: 1.5rem; border-radius: 8px; background-color: #fff3cd; border-left: 4px solid #ffc107; display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem; color: #ffc107;"></i>
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 0.5rem 0; color: #856404;">Database Migration Required</h3>
                        <p style="margin: 0; color: #856404;">
                            The following columns are missing from the students table: <strong><?= htmlspecialchars(implode(', ', $missingColumns)) ?></strong>
                        </p>
                        <p style="margin: 0.5rem 0 0 0; color: #856404; font-size: 0.9rem;">
                            Please run the migration to add these columns before creating students.
                        </p>
                    </div>
                </div>
                <div>
                    <button class="btn btn-warning" onclick="runMigration('add_student_fields.sql')" style="font-weight: 600;">
                        <i class="fas fa-database"></i> Run Migration Now
                    </button>
                    <a href="<?= htmlspecialchars($url('admin/manage-student')) ?>" class="btn btn-outline" style="margin-left: 0.5rem;">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : ($messageType === 'warning' ? 'warning' : 'info')) ?>" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Student Statistics -->
        <section class="student-stats" style="margin-bottom: 2rem;">
            <div class="grid grid-4">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalStudents) ?></div>
                    <div style="color: var(--text-secondary);">Total Students</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($activeStudents) ?></div>
                    <div style="color: var(--text-secondary);">Active Students</div>
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
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($studentsThisMonth) ?></div>
                    <div style="color: var(--text-secondary);">New This Month</div>
                </div>
            </div>
        </section>

        <!-- Student Filter -->
        <section class="student-filter" style="margin-bottom: 2rem;">
            <div class="card">
                <form method="GET" action="<?= htmlspecialchars($url('admin/manage-student')) ?>" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="search" class="form-input" placeholder="Search students..." value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter'){this.form.submit();}">
                    </div>
                    <div>
                        <select name="program" class="form-input" onchange="this.form.submit()">
                            <option value="">All Programs</option>
                            <?php foreach ($majors as $major): ?>
                                <option value="<?= htmlspecialchars($major) ?>" <?= $programFilter === $major ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($major) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="year" class="form-input" onchange="this.form.submit()">
                            <option value="">All Years</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= htmlspecialchars($year) ?>" <?= $yearFilter === (string)$year ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="2024" <?= $yearFilter === '2024' ? 'selected' : '' ?>>2024</option>
                            <option value="2023" <?= $yearFilter === '2023' ? 'selected' : '' ?>>2023</option>
                            <option value="2022" <?= $yearFilter === '2022' ? 'selected' : '' ?>>2022</option>
                            <option value="2021" <?= $yearFilter === '2021' ? 'selected' : '' ?>>2021</option>
                        </select>
                    </div>
                    <div>
                        <select name="status" class="form-input" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?= htmlspecialchars($url('admin/manage-student')) ?>" class="btn btn-outline">Clear</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- Students List -->
        <section class="students-list">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-user-graduate" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        Student Directory
                    </h2>
                </div>

                <!-- Students Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th>Student</th>
                                <th>Student Number</th>
                                <th>Email</th>
                                <th>Major</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td><input type="checkbox" class="student-checkbox" value="<?= htmlspecialchars($s['student_id'] ?? '') ?>"></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 40px; height: 40px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?></div>
                                                    <div style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($s['email'] ?? '') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($s['student_number'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($s['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($s['major'] ?? 'N/A') ?></td>
                                        <td>
                                            <span style="background-color: <?php 
                                                echo ($s['status'] ?? 'active') === 'active' ? 'var(--success-color)' : 
                                                    (($s['status'] ?? '') === 'suspended' ? 'var(--error-color)' : 'var(--warning-color)'); 
                                            ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                                <?= ucfirst($s['status'] ?? 'active') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($s['created_at'] ?? 'now'))) ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.25rem;">
                                                <button class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="viewStudent(<?= htmlspecialchars($s['student_id'] ?? '') ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editStudent(<?= htmlspecialchars($s['student_id'] ?? '') ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="deleteStudent(<?= htmlspecialchars($s['student_id'] ?? '') ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                        <i class="fas fa-user-graduate" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                        <div>No students found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">
                        Showing <?= count($students) ?> of <?= htmlspecialchars($totalStudents) ?> students
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
                    <button class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="addStudent()">
                        <i class="fas fa-plus" style="font-size: 2rem;"></i>
                        <span>Add Student</span>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Add/Edit Student Modal -->
<div id="studentFormModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 id="studentModalTitle">Add Student</h2>
            <button class="modal-close" onclick="closeStudentFormModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="studentForm" method="POST" action="<?= htmlspecialchars($url('admin/manage-student')) ?>" onsubmit="return handleStudentFormSubmit(event)">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="student_id" id="studentId" value="">
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
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-input" placeholder="e.g., +1234567890">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Leave blank to auto-generate">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Student Number</label>
                    <input type="text" name="student_number" class="form-input" placeholder="e.g., 2025001">
                </div>
                <div class="form-group">
                    <label class="form-label">Year Enrolled</label>
                    <input type="number" name="year_enrolled" class="form-input" min="2000" max="2099" placeholder="e.g., 2024">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Major</label>
                    <input type="text" name="major" class="form-input" placeholder="e.g., Computer Science">
                </div>
                <div class="form-group">
                    <label class="form-label">Minor</label>
                    <input type="text" name="minor" class="form-input" placeholder="Optional">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Midterm Quiz Password</label>
                    <input type="password" name="midterm_cardinality" class="form-input" placeholder="Password for midterm quiz access">
                    <small style="color: var(--text-secondary);">Password required to access midterm quiz page</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Final Quiz Password</label>
                    <input type="password" name="final_cardinality" class="form-input" placeholder="Password for final quiz access">
                    <small style="color: var(--text-secondary);">Password required to access final quiz page</small>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">GPA</label>
                    <input type="number" name="gpa" class="form-input" step="0.01" min="0" max="4" placeholder="e.g., 3.5">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Student
                </button>
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeStudentFormModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeAllModals()" style="display: none;"></div>

<!-- View Student Details Modal -->
<div id="studentViewModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2><i class="fas fa-user-graduate"></i> Student Details</h2>
            <button class="modal-close" onclick="closeStudentViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="studentViewContent" style="padding: 1.5rem;">
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                <p style="margin-top: 1rem; color: var(--text-secondary);">Loading student details...</p>
            </div>
        </div>
        <div class="modal-footer" style="padding: 1rem; border-top: 1px solid var(--border-color); display: flex; gap: 1rem; justify-content: flex-end;">
            <button class="btn btn-outline" onclick="closeStudentViewModal()">Close</button>
            <button class="btn btn-primary" id="editFromViewBtn" onclick="editStudentFromView()">
                <i class="fas fa-edit"></i> Edit Student
            </button>
        </div>
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
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    studentCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Student actions
// Store current viewing student ID for edit from view
let currentViewingStudentId = null;

async function viewStudent(studentId) {
    currentViewingStudentId = studentId;
    const modal = document.getElementById('studentViewModal');
    const content = document.getElementById('studentViewContent');
    
    // Show loading state
    content.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
            <p style="margin-top: 1rem; color: var(--text-secondary);">Loading student details...</p>
        </div>
    `;
    
    // Open modal - ensure overlay is also shown
    const overlay = document.getElementById('modalOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        overlay.classList.add('active');
    }
    modal.style.display = 'flex';
    modal.classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    // Fetch student details
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/student')) ?>?id=' + studentId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const s = result.data;
            const admissionYear = s.admission_date ? new Date(s.admission_date).getFullYear() : 'N/A';
            
            content.innerHTML = `
                <div style="display: grid; gap: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background-color: var(--surface-color); border-radius: 8px;">
                        <div style="width: 80px; height: 80px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; color: var(--text-primary);">${escapeHtml(s.first_name || '')} ${escapeHtml(s.last_name || '')}</h3>
                            <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary);">${escapeHtml(s.email || '')}</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Student Number</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(s.student_number || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Phone</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(s.phone || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Major</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(s.major || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Minor</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(s.minor || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Year Enrolled</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${admissionYear}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">GPA</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(s.gpa || '0.00')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Status</label>
                            <span style="background-color: ${(s.status || 'active') === 'active' ? 'var(--success-color)' : ((s.status || '') === 'suspended' ? 'var(--error-color)' : 'var(--warning-color)')}; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                ${escapeHtml((s.status || 'active').charAt(0).toUpperCase() + (s.status || 'active').slice(1))}
                            </span>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Created</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(s.created_at ? new Date(s.created_at).toLocaleDateString() : 'N/A')}</div>
                        </div>
                    </div>
                    
                    <div style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--text-primary);">Quiz Access</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="info-group">
                                <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Midterm Quiz Password</label>
                                <div style="color: var(--text-primary); font-weight: 500; font-family: monospace;">${escapeHtml(s.midterm_cardinality ? '••••••••' : 'Not set')}</div>
                            </div>
                            <div class="info-group">
                                <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Final Quiz Password</label>
                                <div style="color: var(--text-primary); font-weight: 500; font-family: monospace;">${escapeHtml(s.final_cardinality ? '••••••••' : 'Not set')}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i>
                    <p style="color: var(--text-secondary);">${escapeHtml(result.message || 'Failed to load student details')}</p>
                </div>
            `;
        }
    } catch (error) {
        content.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary);">An error occurred while loading student details.</p>
            </div>
        `;
        console.error('Error loading student:', error);
    }
}

function closeAllModals() {
    closeStudentViewModal();
    closeStudentFormModal();
}

function closeStudentViewModal() {
    const modal = document.getElementById('studentViewModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
    if (overlay) {
        overlay.style.display = 'none';
        overlay.classList.remove('active');
    }
    document.body.style.overflow = ''; // Restore scrolling
    currentViewingStudentId = null;
}

function editStudentFromView() {
    if (currentViewingStudentId) {
        closeStudentViewModal();
        editStudent(currentViewingStudentId);
    }
}

async function editStudent(studentId) {
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/student')) ?>?id=' + studentId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const student = result.data;
            
            // Populate form fields
            document.getElementById('studentForm').elements['first_name'].value = student.first_name || '';
            document.getElementById('studentForm').elements['last_name'].value = student.last_name || '';
            document.getElementById('studentForm').elements['email'].value = student.email || '';
            document.getElementById('studentForm').elements['phone'].value = student.phone || '';
            document.getElementById('studentForm').elements['student_number'].value = student.student_number || '';
            
            // Extract year from admission_date
            if (student.admission_date) {
                const year = new Date(student.admission_date).getFullYear();
                document.getElementById('studentForm').elements['year_enrolled'].value = year || '';
            } else {
                document.getElementById('studentForm').elements['year_enrolled'].value = '';
            }
            
            document.getElementById('studentForm').elements['major'].value = student.major || '';
            document.getElementById('studentForm').elements['minor'].value = student.minor || '';
            document.getElementById('studentForm').elements['gpa'].value = student.gpa || '';
            document.getElementById('studentForm').elements['status'].value = student.status || 'active';
            
            // Password fields - leave blank (user must re-enter if changing)
            const midtermField = document.getElementById('studentForm').elements['midterm_cardinality'];
            const finalField = document.getElementById('studentForm').elements['final_cardinality'];
            midtermField.value = '';
            finalField.value = '';
            midtermField.placeholder = 'Leave blank to keep current password';
            finalField.placeholder = 'Leave blank to keep current password';
            
            document.getElementById('studentId').value = student.student_id;
            document.getElementById('formAction').value = 'update';
            document.getElementById('studentModalTitle').textContent = 'Edit Student';
            
            // Open modal
            const formModal = document.getElementById('studentFormModal');
            const overlay = document.getElementById('modalOverlay');
            if (overlay) {
                overlay.style.display = 'flex';
                overlay.classList.add('active');
            }
            formModal.style.display = 'flex';
            formModal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        } else {
            if (typeof showToastifyNotification !== 'undefined') {
                showToastifyNotification(result.message || 'Failed to load student data', 'error');
            } else {
                alert(result.message || 'Failed to load student data');
            }
        }
    } catch (error) {
        console.error('Error loading student:', error);
        if (typeof showToastifyNotification !== 'undefined') {
            showToastifyNotification('An error occurred while loading student data', 'error');
        } else {
            alert('An error occurred while loading student data');
        }
    }
}

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

function deleteStudent(studentId) {
    if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= htmlspecialchars($url('admin/manage-student')) ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'student_id';
        idInput.value = studentId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function addStudent() {
    document.getElementById('studentForm').reset();
    document.getElementById('studentId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('studentModalTitle').textContent = 'Add Student';
    
    // Reset password field placeholders
    const midtermField = document.getElementById('studentForm').elements['midterm_cardinality'];
    const finalField = document.getElementById('studentForm').elements['final_cardinality'];
    if (midtermField) midtermField.placeholder = 'Password for midterm quiz access';
    if (finalField) finalField.placeholder = 'Password for final quiz access';
    
    const formModal = document.getElementById('studentFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        overlay.classList.add('active');
    }
    formModal.style.display = 'flex';
    formModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeStudentFormModal() {
    const modal = document.getElementById('studentFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
    if (overlay) {
        overlay.style.display = 'none';
        overlay.classList.remove('active');
    }
    document.body.style.overflow = ''; // Restore scrolling
}

function handleStudentFormSubmit(e) {
    // Form validation is handled by HTML5 required attributes
    // The form will submit normally to the controller
    return true;
}

// Auto-populate form if editing
<?php if ($editStudent): ?>
document.addEventListener('DOMContentLoaded', function() {
    const student = <?php echo json_encode($editStudent, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (student) {
        document.getElementById('studentForm').elements['first_name'].value = student.first_name || '';
        document.getElementById('studentForm').elements['last_name'].value = student.last_name || '';
        document.getElementById('studentForm').elements['email'].value = student.email || '';
        document.getElementById('studentForm').elements['phone'].value = student.phone || '';
        document.getElementById('studentForm').elements['student_number'].value = student.student_number || '';
        // Extract year from admission_date if it exists
        if (student.admission_date) {
            const year = new Date(student.admission_date).getFullYear();
            document.getElementById('studentForm').elements['year_enrolled'].value = year || '';
        } else {
            document.getElementById('studentForm').elements['year_enrolled'].value = '';
        }
        document.getElementById('studentForm').elements['major'].value = student.major || '';
        document.getElementById('studentForm').elements['minor'].value = student.minor || '';
        // For password fields, we'll leave them empty on edit (user must re-enter if changing)
        // Or show placeholder that leaving blank won't change the password
        const midtermField = document.getElementById('studentForm').elements['midterm_cardinality'];
        const finalField = document.getElementById('studentForm').elements['final_cardinality'];
        midtermField.value = ''; // Don't populate passwords for security
        finalField.value = ''; // Don't populate passwords for security
        midtermField.placeholder = 'Leave blank to keep current password';
        finalField.placeholder = 'Leave blank to keep current password';
        document.getElementById('studentForm').elements['gpa'].value = student.gpa || '';
        document.getElementById('studentForm').elements['status'].value = student.status || 'active';
        document.getElementById('studentId').value = student.student_id;
        document.getElementById('formAction').value = 'update';
        document.getElementById('studentModalTitle').textContent = 'Edit Student';
        
        // Open modal
        addStudent();
    }
});
<?php endif; ?>

// Migration function
function runMigration(migrationFile) {
    if (!confirm('This will run the database migration: ' + migrationFile + '\n\nDo you want to continue?')) {
        return;
    }

    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running Migration...';

    fetch('<?= htmlspecialchars($url('migrate/run')) ?>?file=' + encodeURIComponent(migrationFile), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            if (typeof showToastifyNotification !== 'undefined') {
                showToastifyNotification('Migration completed successfully! Refreshing page...', 'success');
            } else {
                alert('Migration completed successfully!\n\n' + data.messages.join('\n'));
            }
            // Reload page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Show error message
            const errorMsg = data.messages.join('\n');
            if (typeof showToastifyNotification !== 'undefined') {
                showToastifyNotification('Migration failed: ' + errorMsg, 'error');
            } else {
                alert('Migration failed:\n\n' + errorMsg);
            }
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Migration error:', error);
        if (typeof showToastifyNotification !== 'undefined') {
            showToastifyNotification('An error occurred while running migration', 'error');
        } else {
            alert('An error occurred while running migration: ' + error.message);
        }
        button.disabled = false;
        button.innerHTML = originalText;
    });
}
</script>
