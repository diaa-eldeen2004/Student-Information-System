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
        <form method="POST" action="<?= htmlspecialchars($url('doctor/take-attendance?section_id=' . ($section['schedule_id'] ?? $section['section_id'] ?? ''))) ?>" id="attendanceForm" onsubmit="return validateAttendanceForm(event)">
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
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                            <h3 style="margin: 0; color: var(--text-color);">
                                <i class="fas fa-user-graduate"></i> Students (<?= count($students) ?>)
                            </h3>
                            <div class="quick-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <button type="button" class="btn btn-sm btn-success" onclick="markAllPresent()" title="Mark all students as present">
                                    <i class="fas fa-check-double"></i> All Present
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="markAllAbsent()" title="Mark all students as absent">
                                    <i class="fas fa-times"></i> All Absent
                                </button>
                                <button type="button" class="btn btn-sm btn-warning" onclick="markAllLate()" title="Mark all students as late">
                                    <i class="fas fa-clock"></i> All Late
                                </button>
                                <button type="button" class="btn btn-sm btn-info" onclick="clearAll()" title="Clear all selections">
                                    <i class="fas fa-eraser"></i> Clear All
                                </button>
                            </div>
                        </div>
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
                                        <div class="status-buttons" data-student-id="<?= $student['student_id'] ?? '' ?>">
                                            <label class="status-option" data-status="present">
                                                <input type="radio" name="attendance[<?= $student['student_id'] ?? '' ?>]" 
                                                       value="present" <?= ($student['attendance_status'] ?? 'present') === 'present' ? 'checked' : '' ?>>
                                                <span class="status-badge status-present">
                                                    <i class="fas fa-check-circle"></i> Present
                                                </span>
                                            </label>
                                            <label class="status-option" data-status="absent">
                                                <input type="radio" name="attendance[<?= $student['student_id'] ?? '' ?>]" 
                                                       value="absent" <?= ($student['attendance_status'] ?? '') === 'absent' ? 'checked' : '' ?>>
                                                <span class="status-badge status-absent">
                                                    <i class="fas fa-times-circle"></i> Absent
                                                </span>
                                            </label>
                                            <label class="status-option" data-status="late">
                                                <input type="radio" name="attendance[<?= $student['student_id'] ?? '' ?>]" 
                                                       value="late" <?= ($student['attendance_status'] ?? '') === 'late' ? 'checked' : '' ?>>
                                                <span class="status-badge status-late">
                                                    <i class="fas fa-clock"></i> Late
                                                </span>
                                            </label>
                                            <label class="status-option" data-status="excused">
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
    position: relative;
    user-select: none;
}

.status-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    border: 2px solid transparent;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.9rem;
    font-weight: 600;
    position: relative;
    overflow: hidden;
}

.status-badge::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.3s, height 0.3s;
}

.status-option:active .status-badge::before {
    width: 300px;
    height: 300px;
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
    transform: scale(1.08);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    border-width: 3px;
    font-weight: 700;
}

.status-option input[type="radio"]:checked + .status-present {
    background: #10b981;
    color: white;
    border-color: #059669;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.status-option input[type="radio"]:checked + .status-absent {
    background: #ef4444;
    color: white;
    border-color: #dc2626;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.status-option input[type="radio"]:checked + .status-late {
    background: #f59e0b;
    color: white;
    border-color: #d97706;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.status-option input[type="radio"]:checked + .status-excused {
    background: #6366f1;
    color: white;
    border-color: #4f46e5;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.status-option:hover .status-badge {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.status-option:hover .status-present {
    background: #d1fae5;
    border-color: #10b981;
}

.status-option:hover .status-absent {
    background: #fee2e2;
    border-color: #ef4444;
}

.status-option:hover .status-late {
    background: #fef3c7;
    border-color: #f59e0b;
}

.status-option:hover .status-excused {
    background: #e0e7ff;
    border-color: #6366f1;
}

/* Disable hover effect when checked */
.status-option input[type="radio"]:checked + .status-badge:hover {
    transform: scale(1.08);
}

.form-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-warning:hover {
    background: #d97706;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.btn-info {
    background: #6366f1;
    color: white;
}

.btn-info:hover {
    background: #4f46e5;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
}

.quick-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
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
        // Clear all notes
        document.querySelectorAll('input[name^="notes"]').forEach(input => {
            input.value = '';
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
    
    // Validate date
    const dateInput = form.querySelector('input[name="attendance_date"]');
    if (!dateInput || !dateInput.value) {
        event.preventDefault();
        alert('Please select an attendance date.');
        return false;
    }
    
    return true;
}

function markAllPresent() {
    document.querySelectorAll('input[type="radio"][value="present"]').forEach(radio => {
        radio.checked = true;
        // Trigger change event to update visual state
        radio.dispatchEvent(new Event('change'));
    });
    showToast('All students marked as Present', 'success');
}

function markAllAbsent() {
    document.querySelectorAll('input[type="radio"][value="absent"]').forEach(radio => {
        radio.checked = true;
        radio.dispatchEvent(new Event('change'));
    });
    showToast('All students marked as Absent', 'error');
}

function markAllLate() {
    document.querySelectorAll('input[type="radio"][value="late"]').forEach(radio => {
        radio.checked = true;
        radio.dispatchEvent(new Event('change'));
    });
    showToast('All students marked as Late', 'warning');
}

function clearAll() {
    if (confirm('Clear all attendance selections?')) {
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.checked = false;
        });
        document.querySelectorAll('input[name^="notes"]').forEach(input => {
            input.value = '';
        });
        showToast('All selections cleared', 'info');
    }
}

function showToast(message, type = 'info') {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 6px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    `;
    
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };
    
    toast.style.background = colors[type] || colors.info;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    .status-option.selected {
        z-index: 1;
    }
    .status-option.selected .status-badge {
        animation: pulse 0.3s ease-out;
    }
    @keyframes pulse {
        0%, 100% {
            transform: scale(1.08);
        }
        50% {
            transform: scale(1.12);
        }
    }
`;
document.head.appendChild(style);

// Add visual feedback when radio buttons change
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced button click handling
    document.querySelectorAll('.status-option').forEach(option => {
        const radio = option.querySelector('input[type="radio"]');
        const badge = option.querySelector('.status-badge');
        
        // Add click animation
        option.addEventListener('click', function(e) {
            // Prevent double-triggering
            if (radio.checked) return;
            
            // Add ripple effect
            const ripple = document.createElement('span');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            `;
            
            const rect = badge.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
            
            badge.style.position = 'relative';
            badge.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
            
            // Play sound effect (optional - can be removed if not needed)
            // const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZURAJR6Hh8sBrJAUwgM/z1oQ4CBxrvO3knlEQCEWf4fLDbCIFMIfR89OCMwYebsDv45lREAlHoOHywGskBTCAz/PWhDgIHGu87eSeURAIRZ/h8sNsIgUwh9Hz04IzBh5uwO/jmVEQCUeg4fLAayQF');
            // audio.play().catch(() => {}); // Ignore errors
        });
        
        // Add change event listener
        radio.addEventListener('change', function() {
            // Update visual state
            const statusButtons = this.closest('.status-buttons');
            if (statusButtons) {
                // Remove checked class from all options in this group
                statusButtons.querySelectorAll('.status-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                // Add selected class to current option
                option.classList.add('selected');
            }
            
            // Show confirmation for certain statuses
            const status = this.value;
            const studentId = this.name.match(/\[(\d+)\]/)?.[1];
            
            if (status === 'absent' || status === 'excused') {
                // Optional: Show a brief confirmation
                const statusText = status.charAt(0).toUpperCase() + status.slice(1);
                // You can add a toast notification here if needed
            }
        });
    });
    
    // Initialize selected states
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        const option = radio.closest('.status-option');
        if (option) {
            option.classList.add('selected');
        }
    });
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + P: Mark all present
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            markAllPresent();
        }
        // Ctrl/Cmd + A: Mark all absent
        if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
            e.preventDefault();
            markAllAbsent();
        }
        // Ctrl/Cmd + L: Mark all late
        if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
            e.preventDefault();
            markAllLate();
        }
    });
});
</script>
