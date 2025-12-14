<?php
$section = $section ?? null;
$students = $students ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="attendance-container">
    <div class="attendance-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><i class="fas fa-calendar-check"></i> Take Attendance</h1>
                <p>
                    <?= htmlspecialchars($section['course_code'] ?? '') ?> - 
                    <?= htmlspecialchars($section['section_name'] ?? $section['section_number'] ?? 'Section') ?>
                </p>
            </div>
            <a href="<?= htmlspecialchars($url('doctor/attendance')) ?>" class="btn btn-outline">
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

    <?php if (empty($students)): ?>
        <div class="card text-center" style="padding: 3rem;">
            <i class="fas fa-users-slash" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <p style="color: var(--text-secondary);">No students enrolled in this section yet.</p>
            <a href="<?= htmlspecialchars($url('doctor/attendance')) ?>" class="btn btn-outline" style="margin-top: 1rem;">
                <i class="fas fa-arrow-left"></i> Back to Attendance
            </a>
        </div>
    <?php else: ?>
        <form method="POST" action="<?= htmlspecialchars($url('doctor/take-attendance?section_id=' . ($section['section_id'] ?? ''))) ?>" id="attendanceForm" onsubmit="return validateAttendanceForm(event)">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-calendar-alt" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        Attendance Form
                    </h2>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label class="form-label">Attendance Date *</label>
                        <input type="date" name="attendance_date" class="form-input" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="students-list">
                        <h3 style="margin-bottom: 1rem; color: var(--text-color);">
                            <i class="fas fa-user-graduate"></i> Students (<?= count($students) ?>)
                        </h3>
                        <div class="attendance-table">
                            <div class="table-header">
                                <div class="col-student">Student</div>
                                <div class="col-status">Status</div>
                                <div class="col-notes">Notes</div>
                            </div>
                            <?php foreach ($students as $student): ?>
                                <div class="table-row">
                                    <div class="col-student">
                                        <div class="student-info">
                                            <div class="student-name">
                                                <?= htmlspecialchars($student['first_name'] ?? '') ?> 
                                                <?= htmlspecialchars($student['last_name'] ?? '') ?>
                                            </div>
                                            <div class="student-number">
                                                <?= htmlspecialchars($student['student_number'] ?? 'N/A') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-status">
                                        <div class="status-buttons">
                                            <label class="status-option">
                                                <input type="radio" name="attendance[<?= $student['student_id'] ?? '' ?>]" 
                                                       value="present" <?= ($student['attendance_status'] ?? 'present') === 'present' ? 'checked' : '' ?>>
                                                <span class="status-badge status-present">
                                                    <i class="fas fa-check-circle"></i> Present
                                                </span>
                                            </label>
                                            <label class="status-option">
                                                <input type="radio" name="attendance[<?= $student['student_id'] ?? '' ?>]" 
                                                       value="absent" <?= ($student['attendance_status'] ?? '') === 'absent' ? 'checked' : '' ?>>
                                                <span class="status-badge status-absent">
                                                    <i class="fas fa-times-circle"></i> Absent
                                                </span>
                                            </label>
                                            <label class="status-option">
                                                <input type="radio" name="attendance[<?= $student['student_id'] ?? '' ?>]" 
                                                       value="late" <?= ($student['attendance_status'] ?? '') === 'late' ? 'checked' : '' ?>>
                                                <span class="status-badge status-late">
                                                    <i class="fas fa-clock"></i> Late
                                                </span>
                                            </label>
                                            <label class="status-option">
                                                <input type="radio" name="attendance[<?= $student['student_id'] ?? '' ?>]" 
                                                       value="excused" <?= ($student['attendance_status'] ?? '') === 'excused' ? 'checked' : '' ?>>
                                                <span class="status-badge status-excused">
                                                    <i class="fas fa-user-check"></i> Excused
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-notes">
                                        <input type="text" name="notes[<?= $student['student_id'] ?? '' ?>]" 
                                               class="form-input form-input-sm" 
                                               placeholder="Optional notes..."
                                               value="<?= htmlspecialchars($student['attendance_notes'] ?? '') ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="form-actions" style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color);">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Attendance
                    </button>
                    <button type="reset" class="btn btn-outline" onclick="resetForm()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                    <a href="<?= htmlspecialchars($url('doctor/attendance')) ?>" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
.attendance-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.attendance-header h1 {
    font-size: 2rem;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.attendance-header p {
    color: var(--text-muted);
    font-size: 1.1rem;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
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

.alert-info {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.card-title {
    margin: 0;
    font-size: 1.2rem;
    color: var(--text-color);
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-color);
}

.form-input {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--card-bg);
    color: var(--text-color);
    font-size: 1rem;
}

.form-input-sm {
    padding: 0.5rem;
    font-size: 0.9rem;
}

.attendance-table {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
}

.table-header {
    display: grid;
    grid-template-columns: 2fr 3fr 2fr;
    background: var(--bg-secondary);
    padding: 1rem;
    font-weight: 600;
    color: var(--text-color);
    border-bottom: 2px solid var(--border-color);
}

.table-row {
    display: grid;
    grid-template-columns: 2fr 3fr 2fr;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    align-items: center;
}

.table-row:last-child {
    border-bottom: none;
}

.table-row:hover {
    background: var(--bg-secondary);
}

.student-info {
    display: flex;
    flex-direction: column;
}

.student-name {
    font-weight: 500;
    color: var(--text-color);
    margin-bottom: 0.25rem;
}

.student-number {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.status-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.status-option {
    cursor: pointer;
    margin: 0;
}

.status-option input[type="radio"] {
    display: none;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    border: 2px solid transparent;
    transition: all 0.2s;
    font-size: 0.9rem;
    font-weight: 500;
}

.status-present {
    background: #d1fae5;
    color: #065f46;
    border-color: #10b981;
}

.status-absent {
    background: #fee2e2;
    color: #991b1b;
    border-color: #ef4444;
}

.status-late {
    background: #fef3c7;
    color: #92400e;
    border-color: #f59e0b;
}

.status-excused {
    background: #e0e7ff;
    color: #3730a3;
    border-color: #6366f1;
}

.status-option input[type="radio"]:checked + .status-badge {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.status-option input[type="radio"]:checked + .status-present {
    background: #10b981;
    color: white;
}

.status-option input[type="radio"]:checked + .status-absent {
    background: #ef4444;
    color: white;
}

.status-option input[type="radio"]:checked + .status-late {
    background: #f59e0b;
    color: white;
}

.status-option input[type="radio"]:checked + .status-excused {
    background: #6366f1;
    color: white;
}

.form-actions {
    display: flex;
    gap: 1rem;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    opacity: 0.9;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

.btn-outline:hover {
    background: var(--bg-secondary);
}

.text-center {
    text-align: center;
}
</style>

<script>
function resetForm() {
    if (confirm('Are you sure you want to reset the form? All changes will be lost.')) {
        document.getElementById('attendanceForm').reset();
        // Reset all radio buttons to "present"
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            if (radio.value === 'present') {
                radio.checked = true;
            }
        });
    }
}

function validateAttendanceForm(event) {
    const form = document.getElementById('attendanceForm');
    const students = form.querySelectorAll('input[type="radio"]');
    let allMarked = true;
    
    // Group radio buttons by student
    const studentGroups = {};
    students.forEach(radio => {
        const name = radio.name;
        if (!studentGroups[name]) {
            studentGroups[name] = [];
        }
        studentGroups[name].push(radio);
    });
    
    // Check if at least one is checked per student
    for (const group of Object.values(studentGroups)) {
        const checked = group.some(r => r.checked);
        if (!checked) {
            allMarked = false;
            break;
        }
    }
    
    if (!allMarked) {
        event.preventDefault();
        alert('Please mark attendance for all students.');
        return false;
    }
    
    return true;
}
</script>
