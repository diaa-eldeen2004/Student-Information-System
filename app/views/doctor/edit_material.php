<?php
$material = $material ?? null;
$message = $message ?? null;
$messageType = $messageType ?? 'info';

if (!$material) {
    echo '<div class="alert alert-error">Material not found</div>';
    return;
}
?>

<div class="material-container">
    <div class="material-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-edit"></i> Edit Material</h1>
                <p>Edit material details and file</p>
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
            <form method="POST" action="<?= htmlspecialchars($url('doctor/edit-material?id=' . $material['material_id'])) ?>" enctype="multipart/form-data">
                <div style="padding: 1.5rem;">
                    <div class="form-group">
                        <label class="form-label">Material Title *</label>
                        <input type="text" class="form-input" name="title" value="<?= htmlspecialchars($material['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-input" name="description" rows="3"><?= htmlspecialchars($material['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Material Type *</label>
                        <select class="form-input" name="material_type" required>
                            <option value="lecture" <?= $material['material_type'] === 'lecture' ? 'selected' : '' ?>>Lecture</option>
                            <option value="handout" <?= $material['material_type'] === 'handout' ? 'selected' : '' ?>>Handout</option>
                            <option value="reference" <?= $material['material_type'] === 'reference' ? 'selected' : '' ?>>Reference</option>
                            <option value="syllabus" <?= $material['material_type'] === 'syllabus' ? 'selected' : '' ?>>Syllabus</option>
                            <option value="other" <?= $material['material_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">File (Optional - Upload new file to replace)</label>
                        <input type="file" class="form-input" name="material_file" id="material_file" accept=".pdf,.doc,.docx,.txt,.zip,.rar,.ppt,.pptx">
                        <?php if (!empty($material['file_name'])): ?>
                            <div style="margin-top: 0.5rem; padding: 0.75rem; background: var(--bg-secondary); border-radius: 6px;">
                                <strong>Current File:</strong> 
                                <a href="<?= htmlspecialchars($material['file_path'] ?? '#') ?>" target="_blank" style="color: var(--primary-color);">
                                    <?= htmlspecialchars($material['file_name']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group" id="fileNameGroup" style="display: none;">
                        <label class="form-label">File Name (Optional - Custom Name)</label>
                        <input type="text" class="form-input" name="file_name" id="file_name" placeholder="Enter custom file name">
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; padding: 1.5rem; border-top: 1px solid var(--border-color);">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="<?= htmlspecialchars($url('doctor/course')) ?>" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('material_file')?.addEventListener('change', function(e) {
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

