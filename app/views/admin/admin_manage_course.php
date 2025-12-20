<?php
// Ensure variables exist with defaults
$courses = $courses ?? [];
$totalCourses = $totalCourses ?? 0;
$coursesThisSemester = $coursesThisSemester ?? 0;
$activeCourses = $activeCourses ?? 0;
$departments = $departments ?? [];
$search = $search ?? '';
$departmentFilter = $departmentFilter ?? '';
$message = $message ?? null;
$messageType = $messageType ?? 'info';
$editCourse = $editCourse ?? null;
$sectionsTableExists = $sectionsTableExists ?? true;
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-book"></i> Manage Courses</h1>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('admin/manage-course')) ?>" class="btn btn-outline">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
                <button class="btn btn-primary" onclick="createCourse()">
                    <i class="fas fa-plus"></i> Create Course
                </button>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <!-- Error Message if sections table doesn't exist -->
        <?php if (!$sectionsTableExists): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem; padding: 2rem; border-radius: 8px; background-color: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--error-color);">
                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: var(--error-color);"></i>
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 0.5rem 0; color: var(--error-color);">Sections Table Not Found</h3>
                        <p style="margin: 0 0 1rem 0; color: var(--text-secondary);">The sections table doesn't exist yet. Please create it to use course management features.</p>
                        <button class="btn btn-primary" onclick="runMigration('create_sections_table.sql')" style="margin-top: 0.5rem;">
                            <i class="fas fa-database"></i> Create Sections Table
                        </button>
                    </div>
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

        <!-- Course Statistics -->
        <section class="course-stats" style="margin-bottom: 2rem;">
            <div class="grid grid-4">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-book"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalCourses) ?></div>
                    <div style="color: var(--text-secondary);">Total Courses</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($activeCourses) ?></div>
                    <div style="color: var(--text-secondary);">Active Courses</div>
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
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($coursesThisSemester) ?></div>
                    <div style="color: var(--text-secondary);">New This Semester</div>
                </div>
            </div>
        </section>

        <!-- Course Filter -->
        <section class="course-filter" style="margin-bottom: 2rem;">
            <div class="card">
                <form method="GET" action="<?= htmlspecialchars($url('admin/manage-course')) ?>" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="search" class="form-input" placeholder="Search courses..." value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter'){this.form.submit();}">
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
                        <a href="<?= htmlspecialchars($url('admin/manage-course')) ?>" class="btn btn-outline">Clear</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- Courses List -->
        <section class="courses-list">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-book" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        Course Directory
                    </h2>
                </div>

                <!-- Courses Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th>Course</th>
                                <th>Code</th>
                                <th>Department</th>
                                <th>Instructor</th>
                                <th>Students</th>
                                <th>Credits</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($courses)): ?>
                                <?php foreach ($courses as $c): ?>
                                    <?php
                                    $instructorName = !empty($c['doctors']) ? 'Dr. ' . $c['doctors'] : 'Not Assigned';
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" class="course-checkbox" value="<?= htmlspecialchars($c['course_id']) ?>"></td>
                                        <td>
                                            <div>
                                                <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($c['name'] ?? 'N/A') ?></div>
                                                <div style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars(substr($c['description'] ?? 'No description', 0, 60)) . (strlen($c['description'] ?? '') > 60 ? '...' : '') ?></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($c['course_code'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($c['department'] ?? 'N/A') ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <div style="width: 30px; height: 30px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <span style="font-size: 0.9rem;"><?= htmlspecialchars($instructorName) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <span style="font-size: 0.9rem; color: var(--primary-color); font-weight: 500;"><?= htmlspecialchars($c['student_count'] ?? 0) ?></span>
                                                <span style="font-size: 0.8rem; color: var(--text-secondary);">students</span>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($c['credit_hours'] ?? 3) ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.25rem;">
                                                <button class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="viewCourse(<?= htmlspecialchars($c['course_id']) ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editCourse(<?= htmlspecialchars($c['course_id']) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="deleteCourse(<?= htmlspecialchars($c['course_id']) ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                        <i class="fas fa-book" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                        <div>No courses found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">
                        Showing <?= count($courses) ?> of <?= htmlspecialchars($totalCourses) ?> courses
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
                    <button class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="createCourse()">
                        <i class="fas fa-plus" style="font-size: 2rem;"></i>
                        <span>Create Course</span>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeAllModals()" style="display: none;"></div>

<!-- View Course Details Modal -->
<div id="courseViewModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2><i class="fas fa-book"></i> Course Details</h2>
            <button class="modal-close" onclick="closeCourseViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="courseViewContent" style="padding: 1.5rem;">
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                <p style="margin-top: 1rem; color: var(--text-secondary);">Loading course details...</p>
            </div>
        </div>
        <div class="modal-footer" style="padding: 1rem; border-top: 1px solid var(--border-color); display: flex; gap: 1rem; justify-content: flex-end;">
            <button class="btn btn-outline" onclick="closeCourseViewModal()">Close</button>
            <button class="btn btn-primary" onclick="editCourseFromView()">
                <i class="fas fa-edit"></i> Edit Course
            </button>
        </div>
    </div>
</div>

<!-- Add/Edit Course Modal -->
<div id="courseFormModal" class="modal" data-header-style="primary" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 id="courseModalTitle">Create Course</h2>
            <button class="modal-close" onclick="closeCourseFormModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="courseForm" method="POST" action="<?= htmlspecialchars($url('admin/manage-course')) ?>" onsubmit="return handleCourseFormSubmit(event)">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="course_id" id="courseId" value="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Course Code *</label>
                    <input type="text" name="course_code" class="form-input" placeholder="e.g., CS101" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Credits</label>
                    <input type="number" name="credits" class="form-input" placeholder="3" min="1" max="6" value="3">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Course Name *</label>
                <input type="text" name="course_name" class="form-input" placeholder="e.g., Introduction to Programming" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3" placeholder="Course description..."></textarea>
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
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Course
                </button>
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeCourseFormModal()">
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
    const courseCheckboxes = document.querySelectorAll('.course-checkbox');
    courseCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

// Store current viewing course ID
let currentViewingCourseId = null;

// Helper function to escape HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

async function viewCourse(courseId) {
    currentViewingCourseId = courseId;
    const modal = document.getElementById('courseViewModal');
    const content = document.getElementById('courseViewContent');
    
    content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i><p style="margin-top: 1rem; color: var(--text-secondary);">Loading course details...</p></div>`;
    
    const overlay = document.getElementById('modalOverlay');
    if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
    modal.style.display = 'flex';
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/course')) ?>?id=' + courseId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const c = result.data;
            content.innerHTML = `
                <div style="display: grid; gap: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background-color: var(--surface-color); border-radius: 8px;">
                        <div style="width: 80px; height: 80px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            <i class="fas fa-book"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; color: var(--text-primary);">${escapeHtml(c.name || 'N/A')}</h3>
                            <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary);">${escapeHtml(c.course_code || '')}</p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Course Code</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(c.course_code || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Credit Hours</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(c.credit_hours || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Department</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(c.department || 'N/A')}</div>
                        </div>
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Course ID</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(c.course_id || 'N/A')}</div>
                        </div>
                        ${c.description ? `<div class="info-group" style="grid-column: 1 / -1;">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Description</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(c.description)}</div>
                        </div>` : ''}
                        <div class="info-group">
                            <label style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">Created</label>
                            <div style="color: var(--text-primary); font-weight: 500;">${escapeHtml(c.created_at ? new Date(c.created_at).toLocaleDateString() : 'N/A')}</div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i><p style="color: var(--text-secondary);">${escapeHtml(result.message || 'Failed to load course details')}</p></div>`;
        }
    } catch (error) {
        content.innerHTML = `<div style="text-align: center; padding: 2rem;"><i class="fas fa-exclamation-circle" style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;"></i><p style="color: var(--text-secondary);">An error occurred while loading course details.</p></div>`;
        console.error('Error loading course:', error);
    }
}

function closeCourseViewModal() {
    const modal = document.getElementById('courseViewModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) { modal.style.display = 'none'; modal.classList.remove('active'); }
    if (overlay) { overlay.style.display = 'none'; overlay.classList.remove('active'); }
    document.body.style.overflow = '';
    currentViewingCourseId = null;
}

function editCourseFromView() {
    if (currentViewingCourseId) {
        closeCourseViewModal();
        editCourse(currentViewingCourseId);
    }
}

function closeAllModals() {
    closeCourseViewModal();
    closeCourseFormModal();
}

async function editCourse(courseId) {
    try {
        const response = await fetch('<?= htmlspecialchars($url('admin/api/course')) ?>?id=' + courseId);
        const result = await response.json();
        
        if (result.success && result.data) {
            const course = result.data;
            document.getElementById('courseForm').elements['course_code'].value = course.course_code || '';
            document.getElementById('courseForm').elements['course_name'].value = course.name || '';
            document.getElementById('courseForm').elements['description'].value = course.description || '';
            document.getElementById('courseForm').elements['department'].value = course.department || '';
            document.getElementById('courseForm').elements['credits'].value = course.credit_hours || 3;
            document.getElementById('courseId').value = course.course_id;
            document.getElementById('formAction').value = 'update';
            document.getElementById('courseModalTitle').textContent = 'Edit Course';
            
            const formModal = document.getElementById('courseFormModal');
            const overlay = document.getElementById('modalOverlay');
            if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
            formModal.style.display = 'flex';
            formModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            if (typeof showToastifyNotification !== 'undefined') {
                showToastifyNotification(result.message || 'Failed to load course data', 'error');
            } else {
                alert(result.message || 'Failed to load course data');
            }
        }
    } catch (error) {
        console.error('Error loading course:', error);
        if (typeof showToastifyNotification !== 'undefined') {
            showToastifyNotification('An error occurred while loading course data', 'error');
        } else {
            alert('An error occurred while loading course data');
        }
    }
}

function deleteCourse(courseId) {
    if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= htmlspecialchars($url('admin/manage-course')) ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'course_id';
        idInput.value = courseId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function createCourse() {
    document.getElementById('courseForm').reset();
    document.getElementById('courseId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('courseModalTitle').textContent = 'Create Course';
    
    const formModal = document.getElementById('courseFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (overlay) { overlay.style.display = 'flex'; overlay.classList.add('active'); }
    formModal.style.display = 'flex';
    formModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCourseFormModal() {
    const modal = document.getElementById('courseFormModal');
    const overlay = document.getElementById('modalOverlay');
    if (modal) { modal.style.display = 'none'; modal.classList.remove('active'); }
    if (overlay) { overlay.style.display = 'none'; overlay.classList.remove('active'); }
    document.body.style.overflow = '';
}

function handleCourseFormSubmit(e) {
    // Form validation is handled by HTML5 required attributes
    // The form will submit normally to the controller
    return true;
}

// Auto-populate form if editing
<?php if ($editCourse): ?>
document.addEventListener('DOMContentLoaded', function() {
    const course = <?php echo json_encode($editCourse, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (course) {
        document.getElementById('courseForm').elements['course_code'].value = course.course_code || '';
        document.getElementById('courseForm').elements['course_name'].value = course.name || '';
        document.getElementById('courseForm').elements['description'].value = course.description || '';
        document.getElementById('courseForm').elements['department'].value = course.department || '';
        document.getElementById('courseForm').elements['credits'].value = course.credit_hours || 3;
        document.getElementById('courseId').value = course.course_id;
        document.getElementById('formAction').value = 'update';
        document.getElementById('courseModalTitle').textContent = 'Edit Course';
        
        // Open modal
        createCourse();
    }
});
<?php endif; ?>
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

<script>
function runMigration(migrationFile) {
    if (!confirm('This will create the required database table. Continue?')) {
        return;
    }
    
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running Migration...';
    
    fetch('<?= htmlspecialchars($url('admin/api/run-migration')) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'file=' + encodeURIComponent(migrationFile)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Migration completed successfully! The page will reload.');
            window.location.reload();
        } else {
            alert('Migration failed: ' + (data.messages ? data.messages.join('\n') : data.message || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('Error running migration: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>
