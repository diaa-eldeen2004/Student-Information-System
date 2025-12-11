<?php
// Ensure variables exist with defaults
$courses = $courses ?? [];
$doctors = $doctors ?? [];
$students = $students ?? [];
$departments = $departments ?? [];
$search = $search ?? '';
$departmentFilter = $departmentFilter ?? '';
$statusFilter = $statusFilter ?? '';
?>

<div class="course-container">
    <div class="course-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-book"></i> Course Management</h1>
                <p>Manage course assignments, assign doctors and enroll students to courses.</p>
            </div>
            <a href="<?= htmlspecialchars($url('it/course')) ?>" class="btn btn-primary">
                <i class="fas fa-sync"></i> Refresh
            </a>
        </div>
    </div>

    <div class="course-content">
        <!-- Filters -->
        <section class="mb-4">
            <div class="card">
                <form method="GET" action="<?= htmlspecialchars($url('it/course')) ?>" class="grid grid-3" style="align-items: end;">
                    <div class="form-group">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-input" placeholder="Search by course code, name..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-input" onchange="this.form.submit()">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" <?= $departmentFilter === $dept ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?= htmlspecialchars($url('it/course')) ?>" class="btn btn-outline">Clear</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- Courses List -->
        <section class="courses-list">
            <?php if (empty($courses)): ?>
                <div class="card text-center" style="padding: 3rem;">
                    <i class="fas fa-book" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                    <p style="color: var(--text-secondary);">No courses found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="card">
                        <div class="card-header">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                <div>
                                    <h2 style="margin: 0; color: var(--text-primary);">
                                        <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['name']) ?>
                                    </h2>
                                    <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 0.9rem;">
                                        <?= htmlspecialchars($course['department'] ?? 'N/A') ?> • <?= htmlspecialchars($course['credit_hours'] ?? 0) ?> Credits
                                        <span class="badge" style="background-color: var(--success-color); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; margin-left: 0.5rem;">
                                            Active
                                        </span>
                                    </p>
                                </div>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button class="btn btn-primary" onclick="showAssignDoctorModal(<?= $course['course_id'] ?>)">
                                        <i class="fas fa-user-md"></i> Assign Doctor
                                    </button>
                                    <button class="btn btn-success" onclick="showEnrollStudentModal(<?= $course['course_id'] ?>)">
                                        <i class="fas fa-user-plus"></i> Enroll Student(s)
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <?php if (!empty($course['description'])): ?>
                                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;"><?= htmlspecialchars($course['description']) ?></p>
                            <?php endif; ?>
                            
                            <div class="grid grid-2" style="gap: 2rem;">
                                <!-- Assigned Doctors -->
                                <div>
                                    <h3 style="margin-bottom: 1rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-user-md" style="color: var(--primary-color);"></i>
                                        Assigned Doctors
                                        <span class="badge" style="background-color: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; margin-left: 0.5rem;">
                                            <?= count($course['assigned_doctors'] ?? []) ?>
                                        </span>
                                    </h3>
                                    <div style="min-height: 100px; max-height: 300px; overflow-y: auto;">
                                        <?php if (!empty($course['assigned_doctors'])): ?>
                                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                            <?php foreach ($course['assigned_doctors'] as $doctor): ?>
                                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background-color: var(--surface-color); border-radius: 8px;">
                                                    <div>
                                                        <div style="font-weight: 500; color: var(--text-primary);">Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?></div>
                                                        <div style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($doctor['department'] ?? 'N/A') ?> • <?= htmlspecialchars($doctor['email'] ?? '') ?></div>
                                                    </div>
                                                    <form method="POST" action="<?= htmlspecialchars($url('it/course')) ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this doctor?');">
                                                        <input type="hidden" name="action" value="remove-doctor">
                                                        <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                                                        <input type="hidden" name="doctor_id" value="<?= $doctor['doctor_id'] ?>">
                                                        <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                                            <i class="fas fa-times"></i> Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center" style="padding: 2rem; background-color: var(--surface-color); border-radius: 8px; border: 2px dashed var(--border-color);">
                                                <i class="fas fa-user-md" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                                <p style="color: var(--text-secondary); margin: 0;">No doctors assigned</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Enrolled Students -->
                                <div>
                                    <h3 style="margin-bottom: 1rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-user-graduate" style="color: var(--success-color);"></i>
                                        Enrolled Students
                                        <span class="badge" style="background-color: var(--success-color); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; margin-left: 0.5rem;">
                                            <?= count($course['enrolled_students'] ?? []) ?>
                                        </span>
                                    </h3>
                                    <div style="min-height: 100px; max-height: 300px; overflow-y: auto;">
                                        <?php if (!empty($course['enrolled_students'])): ?>
                                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                            <?php foreach ($course['enrolled_students'] as $student): ?>
                                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background-color: var(--surface-color); border-radius: 8px;">
                                                    <div>
                                                        <div style="font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                                        <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                                            <?= htmlspecialchars($student['student_number'] ?? 'N/A') ?> • <?= htmlspecialchars($student['email'] ?? '') ?>
                                                            <?php if (!empty($student['status'])): ?>
                                                                • <span class="badge" style="background-color: var(--primary-color); color: white; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem;"><?= htmlspecialchars($student['status']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <form method="POST" action="<?= htmlspecialchars($url('it/course')) ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this student?');">
                                                        <input type="hidden" name="action" value="remove-student">
                                                        <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                                                        <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                                                        <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                                            <i class="fas fa-times"></i> Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center" style="padding: 2rem; background-color: var(--surface-color); border-radius: 8px; border: 2px dashed var(--border-color);">
                                                <i class="fas fa-user-graduate" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                                <p style="color: var(--text-secondary); margin: 0;">No students enrolled</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<!-- Assign Doctor Modal -->
<div id="assignDoctorModal" class="modal">
    <div class="modal-content" style="max-width: 500px; width: 90%;">
        <div class="modal-header">
            <h2 style="margin: 0;">Assign Doctor to Course</h2>
            <button class="modal-close" onclick="closeAssignDoctorModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 1.5rem;">
            <form id="assignDoctorForm" method="POST" action="<?= htmlspecialchars($url('it/course')) ?>">
                <input type="hidden" name="action" value="assign-doctor">
                <input type="hidden" id="assignDoctorCourseId" name="course_id">
                <div class="form-group">
                    <label class="form-label">Select Doctor <span style="color: var(--error-color);">*</span></label>
                    <select name="doctor_id" class="form-input" required>
                        <option value="">Select a doctor...</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= $doctor['doctor_id'] ?>">
                                Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?> - <?= htmlspecialchars($doctor['department'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeAssignDoctorModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Enroll Student Modal -->
<div id="enrollStudentModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px; width: 90%;">
        <div class="modal-header">
            <h2 style="margin: 0;">Enroll Student(s) to Course</h2>
            <button class="modal-close" onclick="closeEnrollStudentModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 1.5rem;">
            <form id="enrollStudentForm" method="POST" action="<?= htmlspecialchars($url('it/course')) ?>">
                <input type="hidden" name="action" value="enroll-student">
                <input type="hidden" id="enrollStudentCourseId" name="course_id">
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label class="form-label" style="margin: 0;">Select Student(s) <span style="color: var(--error-color);">*</span></label>
                        <button type="button" class="btn btn-outline" onclick="toggleSelectAllStudents()" style="padding: 0.25rem 0.75rem; font-size: 0.85rem;">
                            <i class="fas fa-check-square" id="selectAllIcon"></i> <span id="selectAllText">Select All</span>
                        </button>
                    </div>
                    <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; max-height: 400px; overflow-y: auto; background-color: var(--surface-color);">
                        <?php if (empty($students)): ?>
                            <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">No students available</p>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s;" 
                                       onmouseover="this.style.backgroundColor='rgba(37, 99, 235, 0.1)'"
                                       onmouseout="this.style.backgroundColor='transparent'">
                                    <input type="checkbox" name="student_id[]" value="<?= $student['student_id'] ?>" class="student-checkbox" onchange="updateSelectedCount()" style="width: 18px; height: 18px; cursor: pointer;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 500; color: var(--text-primary);">
                                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                            ID: <?= htmlspecialchars($student['student_number'] ?? 'N/A') ?> 
                                            • <?= htmlspecialchars($student['email'] ?? '') ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <small style="display: block; color: var(--text-secondary); margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i> Selected: <strong id="selectedCount" style="color: var(--primary-color);">0</strong> student(s)
                    </small>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="enrollment_status" class="form-input">
                        <option value="taking" selected>Taking (Currently Enrolled)</option>
                        <option value="taken">Taken (Completed)</option>
                    </select>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeEnrollStudentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="enrollSubmitBtn" disabled>
                        <i class="fas fa-user-plus"></i> Enroll Student(s)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show toast notification on page load if there's a message
<?php if (!empty($message)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const messageType = '<?php echo htmlspecialchars($messageType, ENT_QUOTES); ?>';
    const message = <?php echo json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    
    let backgroundColor = '#2563eb'; // default blue
    if (messageType === 'success') {
        backgroundColor = '#10b981'; // green
    } else if (messageType === 'error') {
        backgroundColor = '#ef4444'; // red
    } else if (messageType === 'warning') {
        backgroundColor = '#f59e0b'; // orange
    }
    
    if (typeof Toastify !== 'undefined') {
        Toastify({
            text: message,
            duration: 5000,
            gravity: "top",
            position: "right",
            style: {
                background: backgroundColor,
            },
            close: true,
        }).showToast();
        
        // Clean URL by removing message parameters
        if (window.location.search.includes('message=')) {
            const url = new URL(window.location);
            url.searchParams.delete('message');
            url.searchParams.delete('type');
            window.history.replaceState({}, '', url);
        }
    }
});
<?php endif; ?>

// Ensure these functions are globally available
window.showAssignDoctorModal = function(courseId) {
    const modal = document.getElementById('assignDoctorModal');
    const courseIdInput = document.getElementById('assignDoctorCourseId');
    if (modal && courseIdInput) {
        courseIdInput.value = courseId;
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
};

window.closeAssignDoctorModal = function() {
    const modal = document.getElementById('assignDoctorModal');
    const form = document.getElementById('assignDoctorForm');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (form) form.reset();
    }
};

window.showEnrollStudentModal = function(courseId) {
    const modal = document.getElementById('enrollStudentModal');
    const courseIdInput = document.getElementById('enrollStudentCourseId');
    if (modal && courseIdInput) {
        courseIdInput.value = courseId;
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
};

window.closeEnrollStudentModal = function() {
    const modal = document.getElementById('enrollStudentModal');
    const form = document.getElementById('enrollStudentForm');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (form) {
            form.reset();
            const countElement = document.getElementById('selectedCount');
            if (countElement) countElement.textContent = '0';
            const submitBtn = document.getElementById('enrollSubmitBtn');
            if (submitBtn) submitBtn.disabled = true;
            // Reset checkboxes
            document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
        }
        allStudentsSelected = false;
        updateSelectAllButton();
    }
};

let allStudentsSelected = false;

function toggleSelectAllStudents() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    allStudentsSelected = !allStudentsSelected;
    
    checkboxes.forEach(cb => {
        cb.checked = allStudentsSelected;
    });
    
    updateSelectedCount();
    updateSelectAllButton();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.student-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    
    // Enable/disable submit button
    const submitBtn = document.getElementById('enrollSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = count === 0;
    }
    
    // Update select all button state
    const allCheckboxes = document.querySelectorAll('.student-checkbox');
    if (allCheckboxes.length > 0) {
        allStudentsSelected = count === allCheckboxes.length;
        updateSelectAllButton();
    }
}

function updateSelectAllButton() {
    const icon = document.getElementById('selectAllIcon');
    const text = document.getElementById('selectAllText');
    if (icon && text) {
        if (allStudentsSelected) {
            icon.className = 'fas fa-square';
            text.textContent = 'Deselect All';
        } else {
            icon.className = 'fas fa-check-square';
            text.textContent = 'Select All';
        }
    }
}

// Initialize count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
    
    // Add form validation
    const enrollForm = document.getElementById('enrollStudentForm');
    if (enrollForm) {
        enrollForm.addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                if (typeof Toastify !== 'undefined') {
                    Toastify({
                        text: 'Please select at least one student to enroll',
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: {
                            background: '#f59e0b',
                        },
                        close: true,
                    }).showToast();
                }
                return false;
            }
        });
    }
});

// Close modals on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeAssignDoctorModal();
        closeEnrollStudentModal();
    }
});
</script>

<style>
.course-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    background-color: var(--background-color);
}

.course-header {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background-color: var(--surface-color);
    border-radius: 8px;
    box-shadow: 0 2px 8px var(--shadow-color);
}

.course-header h1 {
    font-size: 2rem;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.course-header p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 1rem;
}

.course-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.courses-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Ensure buttons are styled */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-success {
    background-color: var(--success-color);
    color: white;
}

.btn-success:hover {
    background-color: #059669;
}

.btn-outline {
    background-color: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-outline:hover {
    background-color: var(--primary-color);
    color: white;
}

@media (max-width: 768px) {
    .course-container {
        padding: 1rem;
    }
    
    .course-header {
        padding: 1rem;
    }
    
    .course-header h1 {
        font-size: 1.5rem;
    }
    
    .grid-3 {
        grid-template-columns: 1fr !important;
    }
}
</style>
