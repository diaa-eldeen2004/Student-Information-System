<?php
$courses = $courses ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="material-container">
    <div class="material-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-upload"></i> Upload Course Material</h1>
                <p>Upload files to your courses</p>
            </div>
            <a href="<?= htmlspecialchars($url('doctor/course')) ?>" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="material-content">
        <div class="card">
            <form method="POST" action="<?= htmlspecialchars($url('doctor/upload-material')) ?>" enctype="multipart/form-data">
                <div style="padding: 1.5rem;">
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
                        <label class="form-label">Section (Optional)</label>
                        <select class="form-input" name="section_id" id="section_id">
                            <option value="">All sections</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Material Title *</label>
                        <input type="text" class="form-input" name="title" placeholder="Enter material title" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-input" name="description" rows="3" placeholder="Enter description (optional)"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Material Type *</label>
                        <select class="form-input" name="material_type" required>
                            <option value="lecture">Lecture</option>
                            <option value="handout">Handout</option>
                            <option value="reference">Reference</option>
                            <option value="syllabus">Syllabus</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">File *</label>
                        <input type="file" class="form-input" name="material_file" required accept=".pdf,.doc,.docx,.txt,.zip,.rar,.ppt,.pptx">
                        <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                            Upload file (PDF, DOC, DOCX, TXT, ZIP, RAR, PPT, PPTX)
                        </small>
                    </div>
                    <div class="form-group" id="fileNameGroup" style="display: none;">
                        <label class="form-label">File Name (Optional - Custom Name)</label>
                        <input type="text" class="form-input" name="file_name" id="file_name" placeholder="Enter custom file name">
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; padding: 1.5rem; border-top: 1px solid var(--border-color);">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Material
                    </button>
                    <button type="reset" class="btn btn-outline">
                        <i class="fas fa-redo"></i> Clear
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const coursesData = <?= json_encode($courses) ?>;

function updateSections() {
    const courseId = document.getElementById('course_id').value;
    const sectionSelect = document.getElementById('section_id');
    sectionSelect.innerHTML = '<option value="">All sections</option>';
    
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

document.querySelector('input[name="material_file"]')?.addEventListener('change', function(e) {
    const fileNameGroup = document.getElementById('fileNameGroup');
    if (e.target.files.length > 0) {
        fileNameGroup.style.display = 'block';
        const originalName = e.target.files[0].name;
        const nameWithoutExt = originalName.replace(/\.[^/.]+$/, '');
        document.getElementById('file_name').value = nameWithoutExt;
    } else {
        fileNameGroup.style.display = 'none';
    }
});
</script>

