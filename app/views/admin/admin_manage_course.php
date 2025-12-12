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
?>

<div class="admin-container">
    <div class="admin-header">
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

    <div class="admin-content">
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

<!-- Add/Edit Course Modal -->
<div id="courseFormModal" class="modal" data-header-style="primary">
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

// Course actions
function viewCourse(courseId) {
    if (typeof showNotification !== 'undefined') {
        showNotification(`Viewing course ${courseId}...`, 'info');
    }
}

function editCourse(courseId) {
    // Redirect to edit page with course ID
    const editUrl = '<?= htmlspecialchars($url('admin/manage-course')) ?>?edit=' + courseId;
    window.location.href = editUrl;
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
    if (typeof showModal !== 'undefined') {
        showModal(document.getElementById('courseFormModal'));
    } else {
        document.getElementById('courseFormModal').classList.add('active');
        document.getElementById('courseFormModal').style.display = 'flex';
    }
}

function closeCourseFormModal() {
    const modal = document.getElementById('courseFormModal');
    if (typeof hideModal !== 'undefined') {
        hideModal(modal);
    } else {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
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
