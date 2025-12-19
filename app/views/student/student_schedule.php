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
                    <form method="GET" action="<?= htmlspecialchars($url('student/schedule')) ?>" id="scheduleFilterForm" style="display: flex; gap: 0.5rem; align-items: center;">
                        <select name="semester" id="filterSemester" class="form-input" style="width: auto;" onchange="document.getElementById('scheduleFilterForm').submit();">
                            <option value="Fall" <?= $semester === 'Fall' ? 'selected' : '' ?>>Fall</option>
                            <option value="Spring" <?= $semester === 'Spring' ? 'selected' : '' ?>>Spring</option>
                            <option value="Summer" <?= $semester === 'Summer' ? 'selected' : '' ?>>Summer</option>
                        </select>
                        <input type="number" name="year" id="filterYear" value="<?= htmlspecialchars($academicYear) ?>" class="form-input" style="width: 100px;" min="2020" max="2030" onchange="document.getElementById('scheduleFilterForm').submit();">
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
                                        <td style="position: relative;">
                                            <div class="schedule-entries-container" style="display: flex; flex-wrap: wrap; gap: 2px;">
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
                                                        <div class="schedule-entry" style="background-color: #3b82f6; color: white; padding: 5px; border-radius: 4px; font-size: 0.85rem; flex: 1 1 auto; min-width: 0;">
                                                            <strong><?= htmlspecialchars($entry['course_code'] ?? '') ?></strong><br>
                                                            <small><?= htmlspecialchars($entry['course_name'] ?? '') ?></small><br>
                                                            <small>Section: <?= htmlspecialchars($entry['section_number'] ?? '') ?></small><br>
                                                            <small><?= htmlspecialchars($startTime) ?> - <?= htmlspecialchars($endTime) ?></small><br>
                                                            <small>Room: <?= htmlspecialchars($entry['room'] ?? 'N/A') ?></small>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
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

    <?php if (!empty($availableSections)): 
        // Group sections by schedule_id to get unique schedules
        $schedulesById = [];
        foreach ($availableSections as $section) {
            $scheduleId = $section['schedule_id'] ?? $section['section_id'] ?? null;
            if ($scheduleId && !isset($schedulesById[$scheduleId])) {
                $schedulesById[$scheduleId] = $section;
            }
        }
        
        // Get timetable for each schedule using the Schedule model
        $scheduleModel = new \models\Schedule();
    ?>
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
                
                <div style="display: flex; flex-direction: column; gap: 2rem;">
                    <?php 
                    $scheduleCounter = 0;
                    foreach ($schedulesById as $scheduleId => $scheduleInfo): 
                        $scheduleCounter++;
                        // Get the full timetable for this schedule
                        $scheduleTimetable = $scheduleModel->getScheduleTimetable((int)$scheduleId);
                        $scheduleDetails = $scheduleModel->findById((int)$scheduleId);
                        
                        // Use scheduleInfo (from availableSections) for course details, fallback to scheduleDetails
                        $courseCode = $scheduleInfo['course_code'] ?? ($scheduleDetails['course_code'] ?? '');
                        $courseName = $scheduleInfo['course_name'] ?? ($scheduleDetails['course_name'] ?? '');
                        $sectionNumber = $scheduleInfo['section_number'] ?? ($scheduleDetails['section_number'] ?? '');
                        $doctorFirstName = $scheduleInfo['doctor_first_name'] ?? ($scheduleDetails['doctor_first_name'] ?? '');
                        $doctorLastName = $scheduleInfo['doctor_last_name'] ?? ($scheduleDetails['doctor_last_name'] ?? '');
                        $scheduleSemester = $scheduleInfo['semester'] ?? ($scheduleDetails['semester'] ?? '');
                        $scheduleAcademicYear = $scheduleInfo['academic_year'] ?? ($scheduleDetails['academic_year'] ?? '');
                        
                        if (empty($courseCode) && empty($courseName)) {
                            continue;
                        }
                        
                        $isEnrolled = in_array($scheduleId, $enrolledScheduleIds);
                        $isRequested = !$isEnrolled && in_array($scheduleId, $requestedSectionIds);
                        $isFull = ($scheduleInfo['current_enrollment'] ?? 0) >= ($scheduleInfo['capacity'] ?? 0);
                    ?>
                        <div class="schedule-timetable-card" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; background: var(--bg-secondary, #f8fafc);">
                            <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid #e2e8f0;">
                                <h4 style="margin: 0; color: var(--text-primary, #1e293b);">
                                    <i class="fas fa-calendar-alt" style="color: #10b981;"></i> 
                                    Schedule <?= $scheduleCounter ?>
                                </h4>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table" style="margin-bottom: 1rem;">
                                    <thead>
                                        <tr>
                                            <th style="width: 100px;">Time</th>
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
                                        // Generate time slots (8 AM to 8 PM)
                                        $timeSlots = [];
                                        for ($hour = 8; $hour <= 20; $hour++) {
                                            $timeSlots[] = sprintf('%02d:00', $hour);
                                        }
                                        
                                        foreach ($timeSlots as $timeSlot): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($timeSlot) ?></strong></td>
                                                <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                                                    <td style="position: relative; vertical-align: top; min-height: 60px;">
                                                        <?php 
                                                        $hasEntry = false;
                                                        foreach ($scheduleTimetable[$day] ?? [] as $entry): 
                                                            $startTime = $entry['start_time'] ?? '';
                                                            $endTime = $entry['end_time'] ?? '';
                                                            
                                                            if (empty($startTime) || empty($endTime)) {
                                                                continue;
                                                            }
                                                            
                                                            $startTimeFormatted = substr($startTime, 0, 5);
                                                            $endTimeFormatted = substr($endTime, 0, 5);
                                                            
                                                            $slotTime = strtotime($timeSlot);
                                                            $slotEnd = $slotTime + 3600;
                                                            $entryStart = strtotime($startTimeFormatted);
                                                            $entryEnd = strtotime($endTimeFormatted);
                                                            
                                                            if ($entryStart < $slotEnd && $entryEnd > $slotTime):
                                                                $hasEntry = true;
                                                        ?>
                                                            <div class="schedule-entry" style="background-color: #10b981; color: white; padding: 8px; border-radius: 6px; font-size: 0.85rem; margin: 2px 0;">
                                                                <strong><?= htmlspecialchars($entry['course_code'] ?? $courseCode) ?></strong>
                                                                <?php if (!empty($entry['section_number'])): ?>
                                                                    <br><small>Sec <?= htmlspecialchars($entry['section_number']) ?></small>
                                                                <?php elseif (!empty($sectionNumber)): ?>
                                                                    <br><small>Sec <?= htmlspecialchars($sectionNumber) ?></small>
                                                                <?php endif; ?>
                                                                <br><small><?= htmlspecialchars($startTimeFormatted) ?> - <?= htmlspecialchars($endTimeFormatted) ?></small>
                                                                <?php if (!empty($entry['room'])): ?>
                                                                    <br><small><i class="fas fa-door-open"></i> <?= htmlspecialchars($entry['room']) ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php 
                                                            endif;
                                                        endforeach; 
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div style="display: flex; justify-content: center; padding-top: 1rem; border-top: 2px solid #e2e8f0;">
                                <?php if ($isEnrolled): ?>
                                    <span class="badge" style="background-color: #10b981; color: white; padding: 0.5rem 1.5rem; border-radius: 8px; font-size: 1rem;">
                                        <i class="fas fa-check-circle"></i> Enrolled
                                    </span>
                                <?php elseif ($isEnrolledInAnySchedule): ?>
                                    <span class="badge" style="background-color: #f59e0b; color: white; padding: 0.5rem 1.5rem; border-radius: 8px; font-size: 1rem;">
                                        <i class="fas fa-info-circle"></i> Already Enrolled in Another Schedule
                                    </span>
                                <?php elseif ($isRequested): ?>
                                    <span class="badge" style="background-color: #3b82f6; color: white; padding: 0.5rem 1.5rem; border-radius: 8px; font-size: 1rem;">
                                        <i class="fas fa-clock"></i> Request Pending
                                    </span>
                                <?php elseif ($isFull): ?>
                                    <span class="badge" style="background-color: #ef4444; color: white; padding: 0.5rem 1.5rem; border-radius: 8px; font-size: 1rem;">
                                        <i class="fas fa-times-circle"></i> Full
                                    </span>
                                <?php else: ?>
                                    <form method="POST" action="<?= htmlspecialchars($url('student/enroll')) ?>" style="margin: 0;">
                                        <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($scheduleId) ?>">
                                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem; font-weight: 600;" onclick="return confirm('Submit enrollment request for <?= htmlspecialchars($courseCode) ?> - Section <?= htmlspecialchars($sectionNumber) ?>?')">
                                            <i class="fas fa-plus"></i> Enroll
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background-color: var(--bg-color, #fff);
    margin: 2% auto;
    padding: 0;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    animation: slideDown 0.3s;
    max-width: 95%;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
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
// Get base URL from PHP - it's already available in the view
const baseUrl = '<?php echo isset($baseUrl) ? rtrim($baseUrl, "/") : "http://localhost/Student-Information-System/public"; ?>';

console.log('Base URL loaded:', baseUrl);

// Make previewTimetable available globally
function previewTimetable(scheduleId) {
    console.log('=== previewTimetable function called ===');
    console.log('Schedule ID:', scheduleId);
    console.log('Type of scheduleId:', typeof scheduleId);
    
    if (!scheduleId || scheduleId === 0) {
        alert('Invalid schedule ID: ' + scheduleId);
        return;
    }
    
    // Show modal
    const modal = document.getElementById('timetablePreviewModal');
    const content = document.getElementById('timetablePreviewContent');
    
    if (!modal) {
        console.error('Modal element not found');
        alert('Modal element not found');
        return;
    }
    
    if (!content) {
        console.error('Content element not found');
        alert('Content element not found');
        return;
    }
    
    console.log('Showing modal');
    modal.style.display = 'block';
    modal.style.zIndex = '9999';
    content.innerHTML = '<div class="text-center" style="padding: 2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading timetable for schedule ID: ' + scheduleId + '...</p></div>';
    
    // Build URL using base URL from config
    const url = baseUrl + '/student/preview-timetable?schedule_id=' + encodeURIComponent(scheduleId);
    
    console.log('=== URL Construction ===');
    console.log('Base URL from config:', baseUrl);
    console.log('Schedule ID:', scheduleId);
    console.log('Final URL:', url);
    console.log('========================');
    
    // Test if URL is valid
    try {
        new URL(url);
        console.log('URL is valid');
    } catch (e) {
        console.error('Invalid URL:', e);
        alert('Invalid URL: ' + url);
        return;
    }
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response status text:', response.statusText);
            console.log('Response headers:', [...response.headers.entries()]);
            
            // Get response as text first to see what we're getting
            return response.text().then(text => {
                console.log('Raw response text:', text);
                
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 200));
                }
                
                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed JSON data:', data);
                    return data;
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    console.error('Response text was:', text);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            console.log('Response data:', data);
            if (data && data.success) {
                displayTimetablePreview(data.timetable, data.schedule);
            } else {
                const errorMsg = data && data.error ? data.error : 'Failed to load timetable';
                content.innerHTML = '<div class="alert" style="background-color: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #dc2626;"><i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> ' + errorMsg + '</div>';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            console.error('Error stack:', error.stack);
            content.innerHTML = '<div class="alert" style="background-color: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #dc2626;"><i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> An error occurred while loading the timetable: ' + error.message + '<br><small>Check the browser console for more details.</small></div>';
        });
}

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Attach event listeners to all preview buttons
    const previewButtons = document.querySelectorAll('.preview-timetable-btn');
    console.log('Found preview buttons:', previewButtons.length);
    
    previewButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const scheduleId = this.getAttribute('data-schedule-id');
            console.log('Preview button clicked, schedule ID:', scheduleId);
            
            if (!scheduleId || scheduleId === '0' || scheduleId === '') {
                alert('Invalid schedule ID');
                return false;
            }
            
            previewTimetable(parseInt(scheduleId));
            return false;
        });
    });
    
    // Also use event delegation as a fallback
    document.addEventListener('click', function(e) {
        if (e.target.closest('.preview-timetable-btn')) {
            e.preventDefault();
            e.stopPropagation();
            const button = e.target.closest('.preview-timetable-btn');
            const scheduleId = button.getAttribute('data-schedule-id');
            if (scheduleId && scheduleId !== '0' && scheduleId !== '') {
                previewTimetable(parseInt(scheduleId));
            }
        }
    });
});

function displayTimetablePreview(timetable, schedule) {
    const content = document.getElementById('timetablePreviewContent');
    if (!content) {
        console.error('Content element not found');
        return;
    }
    
    if (!timetable || typeof timetable !== 'object') {
        timetable = {};
    }
    
    if (!schedule || typeof schedule !== 'object') {
        schedule = {};
    }
    
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    const timeSlots = [];
    for (let hour = 8; hour <= 20; hour++) {
        timeSlots.push(String(hour).padStart(2, '0') + ':00');
    }
    
    let html = '<h3>' + (schedule.course_code || schedule.title || 'Schedule') + ' - ' + (schedule.section_number || '') + '</h3>';
    if (schedule.semester || schedule.academic_year) {
        html += '<p><strong>Semester:</strong> ' + (schedule.semester || '') + ' ' + (schedule.academic_year || '') + '</p>';
    }
    if (schedule.doctor_first_name || schedule.doctor_last_name) {
        html += '<p><strong>Instructor:</strong> ' + (schedule.doctor_first_name || '') + ' ' + (schedule.doctor_last_name || '') + '</p>';
    }
    
    // Check if timetable has any entries
    let hasEntries = false;
    for (const day of days) {
        if (timetable[day] && Array.isArray(timetable[day]) && timetable[day].length > 0) {
            hasEntries = true;
            break;
        }
    }
    
    if (!hasEntries) {
        html += '<div class="alert" style="background-color: #fef3c7; color: #92400e; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #f59e0b;"><i class="fas fa-info-circle"></i> No schedule entries found for this timetable.</div>';
        content.innerHTML = html;
        return;
    }
    
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
            if (timetable[day] && Array.isArray(timetable[day]) && timetable[day].length > 0) {
                timetable[day].forEach(entry => {
                    if (!entry || typeof entry !== 'object') return;
                    
                    const startTime = (entry.start_time || '').toString().substring(0, 5);
                    const endTime = (entry.end_time || '').toString().substring(0, 5);
                    
                    if (!startTime || !endTime || startTime.length < 5 || endTime.length < 5) {
                        console.log('Skipping entry with invalid time:', entry);
                        return;
                    }
                    
                    try {
                        // Parse times for comparison (HH:MM format)
                        const parseTime = (timeStr) => {
                            const parts = timeStr.split(':');
                            if (parts.length < 2) return null;
                            return parseInt(parts[0]) * 60 + parseInt(parts[1]);
                        };
                        
                        const slotTime = parseTime(timeSlot);
                        const entryStart = parseTime(startTime);
                        const entryEnd = parseTime(endTime);
                        
                        if (slotTime === null || entryStart === null || entryEnd === null) {
                            return;
                        }
                        
                        const slotEnd = slotTime + 60; // 1 hour slot
                        
                        // Check if entry overlaps with time slot
                        if (entryStart < slotEnd && entryEnd > slotTime) {
                            html += '<div class="timetable-preview-entry">';
                            html += '<strong>' + escapeHtml(entry.course_code || '') + (entry.section_number ? ' - ' + escapeHtml(entry.section_number) : '') + '</strong>';
                            html += '<div>' + escapeHtml(entry.course_name || '') + '</div>';
                            html += '<div><i class="fas fa-clock"></i> ' + formatTime(startTime) + ' - ' + formatTime(endTime) + '</div>';
                            html += '<div><i class="fas fa-door-open"></i> ' + escapeHtml(entry.room || 'TBA') + '</div>';
                            html += '</div>';
                        }
                    } catch (e) {
                        console.error('Error processing timetable entry:', e, entry);
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
    const time = timeStr.toString().substring(0, 5);
    const parts = time.split(':');
    if (parts.length < 2) return timeStr;
    const hour = parseInt(parts[0]);
    const minutes = parts[1];
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return displayHour + ':' + minutes + ' ' + ampm;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function closeTimetablePreview() {
    const modal = document.getElementById('timetablePreviewModal');
    if (modal) {
        modal.style.display = 'none';
        // Clear content when closing
        const content = document.getElementById('timetablePreviewContent');
        if (content) {
            content.innerHTML = '<div class="text-center" style="padding: 2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading timetable...</p></div>';
        }
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('timetablePreviewModal');
    if (modal && event.target === modal) {
        closeTimetablePreview();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('timetablePreviewModal');
        if (modal && modal.style.display !== 'none') {
            closeTimetablePreview();
        }
    }
});
</script>
