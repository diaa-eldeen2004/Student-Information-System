<?php
$courses = $courses ?? [];
$recentAssignments = $recentAssignments ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="assignment-container">
    <div class="assignment-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-plus-circle"></i> Create Assignment</h1>
                <p>Create and publish new assignments for your students.</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('doctor/assignments')) ?>" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="assignment-content">
        <!-- Assignment Form -->
        <section class="assignment-form">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-plus-circle" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        Assignment Details
                    </h2>
                </div>
                <form class="assignment-form-content" id="assignmentForm" method="POST" action="<?= htmlspecialchars($url('doctor/create-assignment')) ?>" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1.5rem;">
                        <!-- Left Column -->
                        <div>
                            <div class="form-group">
                                <label class="form-label">Course *</label>
                                <select class="form-input" name="course_id" id="course_id" required onchange="updateSections()">
                                    <option value="">Select course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?= $course['course_id'] ?>">
                                            <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Section *</label>
                                <select class="form-input" name="section_id" id="section_id" required>
                                    <option value="">Select section</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Assignment Title *</label>
                                <input type="text" class="form-input" name="title" placeholder="Enter assignment title" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Assignment Type *</label>
                                <select class="form-input" name="type" required>
                                    <option value="">Select type</option>
                                    <option value="homework">Homework</option>
                                    <option value="project">Project</option>
                                    <option value="quiz">Quiz</option>
                                    <option value="lab">Lab Assignment</option>
                                    <option value="exam">Exam</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Due Date & Time *</label>
                                <input type="datetime-local" class="form-input" name="due_date" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Points *</label>
                                <input type="number" class="form-input" name="points" placeholder="100" min="1" max="1000" value="100" required>
                            </div>
                        </div>
                        <!-- Right Column -->
                        <div>
                            <div class="form-group">
                                <label class="form-label">Assignment Description *</label>
                                <textarea class="form-input" name="description" rows="6" placeholder="Describe the assignment requirements, objectives, and instructions..." required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Assignment File (Optional)</label>
                                <input type="file" class="form-input" name="assignment_file" id="assignment_file" accept=".pdf,.doc,.docx,.txt,.zip,.rar">
                                <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                                    Upload assignment file (PDF, DOC, DOCX, TXT, ZIP, RAR)
                                </small>
                            </div>
                            
                            <div class="form-group" id="fileNameGroup" style="display: none;">
                                <label class="form-label">File Name (Optional - Custom Name)</label>
                                <input type="text" class="form-input" name="file_name" id="file_name" placeholder="Enter custom file name">
                                <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                                    Leave empty to use original file name
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Visibility Settings</label>
                                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                        <input type="checkbox" name="is_visible" value="1" checked>
                                        <span>Visible to students</span>
                                    </label>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <label style="flex: 1;">Show for:</label>
                                        <input type="number" name="duration" class="form-input" style="width: 80px;" min="1" value="7">
                                        <select name="duration_type" class="form-input" style="width: 100px;">
                                            <option value="hours">Hours</option>
                                            <option value="days" selected>Days</option>
                                        </select>
                                    </div>
                                    <small style="color: var(--text-secondary); font-size: 0.85rem;">
                                        Assignment will be visible to students for the specified duration
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 1rem; padding: 1.5rem; border-top: 1px solid var(--border-color);">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Publish Assignment
                        </button>
                        <button type="button" class="btn btn-outline" onclick="clearForm()">
                            <i class="fas fa-trash"></i> Clear Form
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Recent Assignments -->
        <?php if (!empty($recentAssignments)): ?>
        <section class="recent-assignments" style="margin-top: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-history" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        Recent Assignments
                    </h2>
                </div>
                <div class="assignments-list">
                    <?php foreach (array_slice($recentAssignments, 0, 5) as $assignment): ?>
                        <div class="assignment-item" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-bottom: 1px solid var(--border-color);">
                            <div style="width: 40px; height: 40px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 0.25rem 0; color: var(--text-primary);"><?= htmlspecialchars($assignment['title']) ?></h4>
                                <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">
                                    <?= htmlspecialchars($assignment['course_code'] ?? 'N/A') ?> • 
                                    Due: <?= date('M d, Y', strtotime($assignment['due_date'])) ?> • 
                                    <?= htmlspecialchars($assignment['max_points']) ?> points
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<script>
const coursesData = <?= json_encode($courses) ?>;

function updateSections() {
    const courseId = document.getElementById('course_id').value;
    const sectionSelect = document.getElementById('section_id');
    sectionSelect.innerHTML = '<option value="">Select section</option>';
    
    if (courseId && coursesData) {
        const course = coursesData.find(c => c.course_id == courseId);
        if (course && course.sections) {
            course.sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section.section_id;
                option.textContent = `Section ${section.section_number} - ${section.semester} ${section.academic_year}`;
                sectionSelect.appendChild(option);
            });
        }
    }
}

function clearForm() {
    if (confirm('Are you sure you want to clear the form? All unsaved changes will be lost.')) {
        document.getElementById('assignmentForm').reset();
        document.getElementById('section_id').innerHTML = '<option value="">Select section</option>';
        document.getElementById('fileNameGroup').style.display = 'none';
    }
}

// Show file name input when file is selected
document.getElementById('assignment_file')?.addEventListener('change', function(e) {
    const fileNameGroup = document.getElementById('fileNameGroup');
    if (e.target.files.length > 0) {
        fileNameGroup.style.display = 'block';
        // Pre-fill with original filename (without extension)
        const originalName = e.target.files[0].name;
        const nameWithoutExt = originalName.replace(/\.[^/.]+$/, '');
        document.getElementById('file_name').value = nameWithoutExt;
    } else {
        fileNameGroup.style.display = 'none';
    }
});
</script>
