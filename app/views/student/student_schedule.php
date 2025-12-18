<?php
$student = $student ?? null;
$schedule = $schedule ?? [];
$weeklySchedule = $weeklySchedule ?? [];
$availableSections = $availableSections ?? [];
$enrollmentRequests = $enrollmentRequests ?? [];
$requestedSectionIds = $requestedSectionIds ?? [];
$enrolledScheduleIds = $enrolledScheduleIds ?? [];
$isEnrolledInAnySchedule = $isEnrolledInAnySchedule ?? false;
$semester = $semester ?? 'Fall';
$academicYear = $academicYear ?? date('Y');
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-calendar-alt"></i> My Schedule</h1>
        <p><?= htmlspecialchars($semester) ?> <?= htmlspecialchars($academicYear) ?></p>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h3>Weekly Schedule</h3>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <form method="GET" action="<?= htmlspecialchars($url('student/schedule')) ?>" style="display: flex; gap: 0.5rem; align-items: center;">
                        <select name="semester" class="form-input" style="width: auto;">
                            <option value="Fall" <?= $semester === 'Fall' ? 'selected' : '' ?>>Fall</option>
                            <option value="Spring" <?= $semester === 'Spring' ? 'selected' : '' ?>>Spring</option>
                            <option value="Summer" <?= $semester === 'Summer' ? 'selected' : '' ?>>Summer</option>
                        </select>
                        <input type="number" name="year" value="<?= htmlspecialchars($academicYear) ?>" class="form-input" style="width: 100px;" min="2020" max="2030">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                </div>
            </div>
        </div>
        <div style="padding: 1.5rem;">
            <?php if (empty($schedule)): ?>
                <p class="text-muted text-center" style="padding: 3rem 0;">No courses scheduled for this semester.</p>
                <div class="text-center">
                    <a href="<?= htmlspecialchars($url('student/schedule')) ?>" class="btn btn-primary">Browse Available Courses</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Monday</th>
                                <th>Tuesday</th>
                                <th>Wednesday</th>
                                <th>Thursday</th>
                                <th>Friday</th>
                                <th>Saturday</th>
                                <th>Sunday</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Generate time slots (8 AM to 6 PM)
                            $timeSlots = [];
                            for ($hour = 8; $hour <= 18; $hour++) {
                                $timeSlots[] = sprintf('%02d:00', $hour);
                            }
                            
                            foreach ($timeSlots as $timeSlot): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($timeSlot) ?></strong></td>
                                    <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                                        <td>
                                            <?php foreach ($weeklySchedule[$day] ?? [] as $entry): ?>
                                                <?php
                                                $startTime = $entry['start_time'] ?? '';
                                                $endTime = $entry['end_time'] ?? '';
                                                $slotStart = strtotime($timeSlot);
                                                $slotEnd = strtotime($timeSlot) + 3600; // 1 hour slot
                                                $entryStart = strtotime($startTime);
                                                $entryEnd = strtotime($endTime);
                                                
                                                // Check if this entry overlaps with this time slot
                                                if ($entryStart < $slotEnd && $entryEnd > $slotStart):
                                                ?>
                                                    <div class="schedule-entry" style="background-color: #3b82f6; color: white; padding: 5px; margin: 2px 0; border-radius: 4px; font-size: 0.85rem;">
                                                        <strong><?= htmlspecialchars($entry['course_code'] ?? '') ?></strong><br>
                                                        <small><?= htmlspecialchars($entry['course_name'] ?? '') ?></small><br>
                                                        <small>Section: <?= htmlspecialchars($entry['section_number'] ?? '') ?></small><br>
                                                        <small><?= htmlspecialchars($startTime) ?> - <?= htmlspecialchars($endTime) ?></small><br>
                                                        <small>Room: <?= htmlspecialchars($entry['room'] ?? 'N/A') ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3>My Enrolled Courses</h3>
        </div>
        <div style="padding: 1.5rem;">
            <?php if (empty($schedule)): ?>
                <p class="text-muted">No courses enrolled</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Section</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Room</th>
                                <th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedule as $entry): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($entry['course_code'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($entry['course_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($entry['section_number'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($entry['day_of_week'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= htmlspecialchars($entry['start_time'] ?? '') ?> - 
                                        <?= htmlspecialchars($entry['end_time'] ?? '') ?>
                                    </td>
                                    <td><?= htmlspecialchars($entry['room'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= htmlspecialchars($entry['doctor_first_name'] ?? '') ?> 
                                        <?= htmlspecialchars($entry['doctor_last_name'] ?? '') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($availableSections)): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> Available Courses for Enrollment</h3>
            </div>
            <div style="padding: 1.5rem;">
                <?php if ($isEnrolledInAnySchedule): ?>
                    <div class="alert" style="background-color: #fef3c7; color: #92400e; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #f59e0b;">
                        <i class="fas fa-info-circle"></i> <strong>Notice:</strong> You are already enrolled in a schedule for <?= htmlspecialchars($semester) ?> <?= htmlspecialchars($academicYear) ?>. You cannot enroll in multiple schedules for the same semester.
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Section</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Room</th>
                                <th>Instructor</th>
                                <th>Capacity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($availableSections as $section): 
                                $scheduleId = $section['schedule_id'] ?? $section['section_id'] ?? null;
                                $isEnrolled = in_array($scheduleId, $enrolledScheduleIds);
                                $isRequested = !$isEnrolled && in_array($scheduleId, $requestedSectionIds);
                                $isFull = ($section['current_enrollment'] ?? 0) >= ($section['capacity'] ?? 0);
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($section['course_code'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($section['course_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($section['section_number'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($section['day_of_week'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= htmlspecialchars($section['start_time'] ?? '') ?> - 
                                        <?= htmlspecialchars($section['end_time'] ?? '') ?>
                                    </td>
                                    <td><?= htmlspecialchars($section['room'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= htmlspecialchars($section['doctor_first_name'] ?? '') ?> 
                                        <?= htmlspecialchars($section['doctor_last_name'] ?? '') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($section['current_enrollment'] ?? 0) ?> / 
                                        <?= htmlspecialchars($section['capacity'] ?? 0) ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                            <button type="button" class="btn btn-sm btn-outline" onclick="previewTimetable(<?= htmlspecialchars($scheduleId ?? 0) ?>)" title="Preview Timetable">
                                                <i class="fas fa-calendar-week"></i> Preview
                                            </button>
                                            <?php if ($isEnrolled): ?>
                                                <span class="badge" style="background-color: var(--success-color, #10b981); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">
                                                    <i class="fas fa-check-circle"></i> Enrolled
                                                </span>
                                            <?php elseif ($isEnrolledInAnySchedule): ?>
                                                <span class="badge" style="background-color: var(--warning-color, #f59e0b); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">
                                                    <i class="fas fa-info-circle"></i> Already Enrolled in Another Schedule
                                                </span>
                                            <?php elseif ($isRequested): ?>
                                                <span class="badge" style="background-color: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">
                                                    <i class="fas fa-clock"></i> Request Pending
                                                </span>
                                            <?php elseif ($isFull): ?>
                                                <span class="badge" style="background-color: var(--error-color); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">
                                                    <i class="fas fa-times-circle"></i> Full
                                                </span>
                                            <?php else: ?>
                                                <form method="POST" action="<?= htmlspecialchars($url('student/enroll')) ?>" style="display: inline;">
                                                    <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($scheduleId ?? $section['schedule_id'] ?? $section['section_id'] ?? '') ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Submit enrollment request for this schedule?')">
                                                        <i class="fas fa-plus"></i> Request Enrollment
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Show success/error messages
<?php if (isset($_SESSION['success'])): ?>
    if (typeof showNotification === 'function') {
        showNotification('<?= htmlspecialchars($_SESSION['success']) ?>', 'success');
    }
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    if (typeof showNotification === 'function') {
        showNotification('<?= htmlspecialchars($_SESSION['error']) ?>', 'error');
    }
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
</script>

<!-- Timetable Preview Modal -->
<div id="timetablePreviewModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 90%; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h2><i class="fas fa-calendar-week"></i> Schedule Timetable Preview</h2>
            <span class="close" onclick="closeTimetablePreview()">&times;</span>
        </div>
        <div class="modal-body" id="timetablePreviewContent">
            <div class="text-center" style="padding: 2rem;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Loading timetable...</p>
            </div>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: var(--bg-color, #fff);
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color, #e0e0e0);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 1.5rem;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: #000;
}

.timetable-preview-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.timetable-preview-table th,
.timetable-preview-table td {
    border: 1px solid var(--border-color, #e0e0e0);
    padding: 0.75rem;
    text-align: left;
}

.timetable-preview-table th {
    background-color: var(--primary-color, #3b82f6);
    color: white;
    font-weight: bold;
}

.timetable-preview-table .time-col {
    font-weight: bold;
    background-color: var(--bg-secondary, #f5f5f5);
    width: 100px;
}

.timetable-preview-cell {
    min-height: 60px;
    vertical-align: top;
}

.timetable-preview-entry {
    background-color: #3b82f6;
    color: white;
    padding: 0.5rem;
    margin: 0.25rem 0;
    border-radius: 4px;
    font-size: 0.875rem;
}

.timetable-preview-entry strong {
    display: block;
    margin-bottom: 0.25rem;
}
</style>

<script>
function previewTimetable(scheduleId) {
    if (!scheduleId) {
        alert('Invalid schedule ID');
        return;
    }
    
    // Show modal
    const modal = document.getElementById('timetablePreviewModal');
    const content = document.getElementById('timetablePreviewContent');
    modal.style.display = 'block';
    content.innerHTML = '<div class="text-center" style="padding: 2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading timetable...</p></div>';
    
    // Fetch timetable data
    fetch('<?= htmlspecialchars($url('student/preview-timetable')) ?>?schedule_id=' + scheduleId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTimetablePreview(data.timetable, data.schedule);
            } else {
                content.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ' + (data.error || 'Failed to load timetable') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> An error occurred while loading the timetable.</div>';
        });
}

function displayTimetablePreview(timetable, schedule) {
    const content = document.getElementById('timetablePreviewContent');
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    const timeSlots = [];
    for (let hour = 8; hour <= 20; hour++) {
        timeSlots.push(String(hour).padStart(2, '0') + ':00');
    }
    
    let html = '<h3>' + (schedule.course_code || 'Schedule') + ' - ' + (schedule.section_number || '') + '</h3>';
    html += '<p><strong>Semester:</strong> ' + (schedule.semester || '') + ' ' + (schedule.academic_year || '') + '</p>';
    html += '<div class="table-responsive">';
    html += '<table class="timetable-preview-table">';
    html += '<thead><tr><th class="time-col">Time</th>';
    days.forEach(day => {
        html += '<th>' + day + '</th>';
    });
    html += '</tr></thead><tbody>';
    
    timeSlots.forEach(timeSlot => {
        html += '<tr><td class="time-col"><strong>' + timeSlot + '</strong></td>';
        days.forEach(day => {
            html += '<td class="timetable-preview-cell">';
            if (timetable[day] && timetable[day].length > 0) {
                timetable[day].forEach(entry => {
                    const startTime = entry.start_time || '';
                    const endTime = entry.end_time || '';
                    const slotStart = new Date('1970-01-01T' + timeSlot + ':00').getTime();
                    const slotEnd = slotStart + 3600000;
                    const entryStart = new Date('1970-01-01T' + startTime).getTime();
                    const entryEnd = new Date('1970-01-01T' + endTime).getTime();
                    
                    if (entryStart < slotEnd && entryEnd > slotStart) {
                        html += '<div class="timetable-preview-entry">';
                        html += '<strong>' + (entry.course_code || '') + ' - ' + (entry.section_number || '') + '</strong>';
                        html += '<div>' + (entry.course_name || '') + '</div>';
                        html += '<div><i class="fas fa-clock"></i> ' + formatTime(startTime) + ' - ' + formatTime(endTime) + '</div>';
                        html += '<div><i class="fas fa-door-open"></i> ' + (entry.room || 'TBA') + '</div>';
                        html += '</div>';
                    }
                });
            }
            html += '</td>';
        });
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    content.innerHTML = html;
}

function formatTime(timeStr) {
    if (!timeStr) return '';
    const time = timeStr.substring(0, 5);
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return displayHour + ':' + minutes + ' ' + ampm;
}

function closeTimetablePreview() {
    document.getElementById('timetablePreviewModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('timetablePreviewModal');
    if (event.target == modal) {
        closeTimetablePreview();
    }
}
</script>
