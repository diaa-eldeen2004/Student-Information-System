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
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button class="btn btn-success" onclick="showCreateCourseModal()">
                    <i class="fas fa-plus"></i> Create Course
                </button>
                <a href="<?= htmlspecialchars($url('it/schedule')) ?>" class="btn btn-primary">
                    <i class="fas fa-calendar-alt"></i> Build Schedule
                </a>
                <a href="<?= htmlspecialchars($url('it/course')) ?>" class="btn btn-outline">
                    <i class="fas fa-sync"></i> Refresh
                </a>
            </div>
        </div>
    </div>

    <div class="course-content">
        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : ($messageType === 'warning' ? 'warning' : 'info')) ?>" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
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
                            
                            <!-- Pending Enrollment Requests -->
                            <?php if (!empty($course['pending_requests'])): ?>
                                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid var(--border-color);">
                                    <h3 style="margin-bottom: 1rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-clock" style="color: var(--warning-color);"></i>
                                        Pending Enrollment Requests
                                        <span class="badge" style="background-color: var(--warning-color); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; margin-left: 0.5rem;">
                                            <?= count($course['pending_requests']) ?>
                                        </span>
                                    </h3>
                                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        <?php foreach ($course['pending_requests'] as $request): ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background-color: var(--surface-color); border-radius: 8px; border-left: 4px solid var(--warning-color);">
                                                <div>
                                                    <div style="font-weight: 500; color: var(--text-primary);">
                                                        <?= htmlspecialchars($request['student_first_name'] ?? '') ?> <?= htmlspecialchars($request['student_last_name'] ?? '') ?>
                                                    </div>
                                                    <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                                        Section <?= htmlspecialchars($request['section_number'] ?? 'N/A') ?> • 
                                                        Requested: <?= date('M d, Y', strtotime($request['requested_at'] ?? 'now')) ?>
                                                    </div>
                                                </div>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <form method="POST" action="<?= htmlspecialchars($url('it/enrollments/approve')) ?>" style="display: inline;">
                                                        <input type="hidden" name="request_id" value="<?= $request['request_id'] ?? '' ?>">
                                                        <button type="submit" class="btn btn-success" style="padding: 0.5rem 1rem; font-size: 0.85rem;" onclick="return confirm('Approve this enrollment request?');">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.85rem;" onclick="showRejectModal(<?= $request['request_id'] ?? 0 ?>)">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<!-- Assign Doctor Modal -->
<div id="assignDoctorModal" class="modal">
    <div class="modal-content" style="max-width: 600px; width: 90%;">
        <div class="modal-header">
            <h2 style="margin: 0;"><i class="fas fa-user-md"></i> Assign Doctor(s) to Course</h2>
            <button class="modal-close" onclick="closeAssignDoctorModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 1.5rem;">
            <form id="assignDoctorForm" method="POST" action="<?= htmlspecialchars($url('it/course')) ?>">
                <input type="hidden" name="action" value="assign-doctors">
                <input type="hidden" id="assignDoctorCourseId" name="course_id">
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label class="form-label" style="margin: 0;">Select Doctor(s) <span style="color: var(--error-color);">*</span></label>
                        <button type="button" class="btn btn-outline" onclick="toggleSelectAllDoctors()" style="padding: 0.25rem 0.75rem; font-size: 0.85rem;">
                            <i class="fas fa-check-square" id="selectAllDoctorIcon"></i> <span id="selectAllDoctorText">Select All</span>
                        </button>
                    </div>
                    <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; max-height: 400px; overflow-y: auto; background-color: var(--surface-color);">
                        <?php if (empty($doctors)): ?>
                            <p class="text-center" style="color: var(--text-secondary); padding: 2rem;">No doctors available</p>
                        <?php else: ?>
                            <?php foreach ($doctors as $doctor): ?>
                                <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s;" 
                                       onmouseover="this.style.backgroundColor='rgba(37, 99, 235, 0.1)'"
                                       onmouseout="this.style.backgroundColor='transparent'">
                                    <input type="checkbox" name="doctor_id[]" value="<?= $doctor['doctor_id'] ?>" class="doctor-checkbox" onchange="updateSelectedDoctorCount()" style="width: 18px; height: 18px; cursor: pointer;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 500; color: var(--text-primary);">
                                            Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                            <?= htmlspecialchars($doctor['department'] ?? 'N/A') ?> • <?= htmlspecialchars($doctor['email'] ?? '') ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <small style="display: block; color: var(--text-secondary); margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i> Selected: <strong id="selectedDoctorCount" style="color: var(--primary-color);">0</strong> doctor(s)
                    </small>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeAssignDoctorModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="assignDoctorSubmitBtn" disabled>
                        <i class="fas fa-user-md"></i> Assign Doctor(s)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Course Modal -->
<div id="createCourseModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px; width: 90%;">
        <div class="modal-header">
            <h2 style="margin: 0;"><i class="fas fa-plus-circle"></i> Create New Course</h2>
            <button class="modal-close" onclick="closeCreateCourseModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 1.5rem;">
            <form id="createCourseForm" method="POST" action="<?= htmlspecialchars($url('it/course')) ?>">
                <input type="hidden" name="action" value="create-course">
                <div class="form-group">
                    <label class="form-label">Course Code <span style="color: var(--error-color);">*</span></label>
                    <input type="text" name="course_code" class="form-input" placeholder="e.g., SWE123" required>
                    <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">Unique identifier for the course</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Course Name <span style="color: var(--error-color);">*</span></label>
                    <input type="text" name="course_name" class="form-input" placeholder="e.g., Software Engineering" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Course description..."></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Credit Hours</label>
                        <input type="number" name="credit_hours" class="form-input" value="3" min="1" max="6" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-input" placeholder="e.g., Computer Science">
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeCreateCourseModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create Course
                    </button>
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

// Create Course Modal Functions
window.showCreateCourseModal = function() {
    const modal = document.getElementById('createCourseModal');
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
};

window.closeCreateCourseModal = function() {
    const modal = document.getElementById('createCourseModal');
    const form = document.getElementById('createCourseForm');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (form) form.reset();
    }
};

// Ensure these functions are globally available
let allDoctorsSelected = false;

window.showAssignDoctorModal = function(courseId) {
    const modal = document.getElementById('assignDoctorModal');
    const courseIdInput = document.getElementById('assignDoctorCourseId');
    if (modal && courseIdInput) {
        courseIdInput.value = courseId;
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        updateSelectedDoctorCount();
    }
};

window.closeAssignDoctorModal = function() {
    const modal = document.getElementById('assignDoctorModal');
    const form = document.getElementById('assignDoctorForm');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (form) {
            form.reset();
            document.querySelectorAll('.doctor-checkbox').forEach(cb => cb.checked = false);
            updateSelectedDoctorCount();
        }
        allDoctorsSelected = false;
        updateSelectAllDoctorButton();
    }
};

function toggleSelectAllDoctors() {
    const checkboxes = document.querySelectorAll('.doctor-checkbox');
    allDoctorsSelected = !allDoctorsSelected;
    
    checkboxes.forEach(cb => {
        cb.checked = allDoctorsSelected;
    });
    
    updateSelectedDoctorCount();
    updateSelectAllDoctorButton();
}

function updateSelectedDoctorCount() {
    const checkboxes = document.querySelectorAll('.doctor-checkbox:checked');
    const count = checkboxes.length;
    const countElement = document.getElementById('selectedDoctorCount');
    if (countElement) countElement.textContent = count;
    
    // Enable/disable submit button
    const submitBtn = document.getElementById('assignDoctorSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = count === 0;
    }
    
    // Update select all button state
    const allCheckboxes = document.querySelectorAll('.doctor-checkbox');
    if (allCheckboxes.length > 0) {
        allDoctorsSelected = count === allCheckboxes.length;
        updateSelectAllDoctorButton();
    }
}

function updateSelectAllDoctorButton() {
    const icon = document.getElementById('selectAllDoctorIcon');
    const text = document.getElementById('selectAllDoctorText');
    if (icon && text) {
        if (allDoctorsSelected) {
            icon.className = 'fas fa-square';
            text.textContent = 'Deselect All';
        } else {
            icon.className = 'fas fa-check-square';
            text.textContent = 'Select All';
        }
    }
}

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

// Reject Modal Function (for course page)
function showRejectModal(requestId) {
    const reason = prompt('Enter rejection reason (optional):');
    if (reason !== null) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= htmlspecialchars($url('it/enrollments/reject')) ?>';
        
        const requestIdInput = document.createElement('input');
        requestIdInput.type = 'hidden';
        requestIdInput.name = 'request_id';
        requestIdInput.value = requestId;
        form.appendChild(requestIdInput);
        
        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'reason';
        reasonInput.value = reason;
        form.appendChild(reasonInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modals on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeCreateCourseModal();
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

.alert {
    padding: 1rem;
    border-radius: 6px;
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

/* Enhanced Card Styles */
.card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: box-shadow 0.3s ease, transform 0.2s ease;
}

.card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: linear-gradient(135deg, var(--surface-color) 0%, var(--card-bg) 100%);
}

.card-body {
    padding: 1.5rem;
}

/* Enhanced Form Styles */
.form-group {
    margin-bottom: 1.25rem;
}

.form-input, .form-select {
    transition: all 0.2s ease;
}

.form-input:focus, .form-select:focus {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
}

/* Enhanced Grid */
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

/* Enhanced Badge */
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

/* Enhanced Modal */
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

/* Enhanced Table Styles */
table {
    border-collapse: separate;
    border-spacing: 0;
}

table tbody tr {
    transition: all 0.2s ease;
}

table tbody tr:hover {
    background: var(--surface-color);
    transform: scale(1.01);
}

/* Enhanced Button Hover Effects */
.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-outline:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Responsive Improvements */
@media (max-width: 1024px) {
    .grid-2, .grid-3 {
        grid-template-columns: 1fr;
    }
    
    .course-header {
        padding: 1rem;
    }
    
    .card-header {
        padding: 1rem;
    }
}
</style>
