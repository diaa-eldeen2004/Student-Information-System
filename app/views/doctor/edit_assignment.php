<?php
$assignment = $assignment ?? null;
$courses = $courses ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'info';

if (!$assignment) {
    echo '<div class="alert alert-error">Assignment not found</div>';
    return;
}
?>

<div class="assignment-container">
    <div class="assignment-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-edit"></i> Edit Assignment</h1>
                <p>Edit assignment details and file</p>
            </div>
            <a href="<?= htmlspecialchars($url('doctor/assignments')) ?>" class="btn btn-outline">
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

    <div class="assignment-content">
        <div class="card">
            <form method="POST" action="<?= htmlspecialchars($url('doctor/edit-assignment?id=' . $assignment['assignment_id'])) ?>" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1.5rem;">
                    <div>
                        <div class="form-group">
                            <label class="form-label">Assignment Title *</label>
                            <input type="text" class="form-input" name="title" value="<?= htmlspecialchars($assignment['title']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assignment Type *</label>
                            <select class="form-input" name="type" required>
                                <option value="homework" <?= $assignment['assignment_type'] === 'homework' ? 'selected' : '' ?>>Homework</option>
                                <option value="project" <?= $assignment['assignment_type'] === 'project' ? 'selected' : '' ?>>Project</option>
                                <option value="quiz" <?= $assignment['assignment_type'] === 'quiz' ? 'selected' : '' ?>>Quiz</option>
                                <option value="lab" <?= $assignment['assignment_type'] === 'lab' ? 'selected' : '' ?>>Lab Assignment</option>
                                <option value="exam" <?= $assignment['assignment_type'] === 'exam' ? 'selected' : '' ?>>Exam</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Due Date & Time *</label>
                            <input type="datetime-local" class="form-input" name="due_date" value="<?= date('Y-m-d\TH:i', strtotime($assignment['due_date'])) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Points *</label>
                            <input type="number" class="form-input" name="points" value="<?= $assignment['max_points'] ?>" min="1" max="1000" required>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label class="form-label">Assignment Description *</label>
                            <textarea class="form-input" name="description" rows="6" required><?= htmlspecialchars($assignment['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assignment File (Optional - Upload new file to replace)</label>
                            <input type="file" class="form-input" name="assignment_file" id="assignment_file" accept=".pdf,.doc,.docx,.txt,.zip,.rar">
                            <?php if (!empty($assignment['file_name'])): ?>
                                <div style="margin-top: 0.5rem; padding: 0.75rem; background: var(--bg-secondary); border-radius: 6px;">
                                    <strong>Current File:</strong> 
                                    <a href="<?= htmlspecialchars($assignment['file_path'] ?? '#') ?>" target="_blank" style="color: var(--primary-color);">
                                        <?= htmlspecialchars($assignment['file_name']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group" id="fileNameGroup" style="display: none;">
                            <label class="form-label">File Name (Optional - Custom Name)</label>
                            <input type="text" class="form-input" name="file_name" id="file_name" placeholder="Enter custom file name">
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; padding: 1.5rem; border-top: 1px solid var(--border-color);">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="<?= htmlspecialchars($url('doctor/assignments')) ?>" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('assignment_file')?.addEventListener('change', function(e) {
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

