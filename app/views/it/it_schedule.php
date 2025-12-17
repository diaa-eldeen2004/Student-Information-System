<?php
$sections = $sections ?? [];
$courses = $courses ?? [];
$doctors = $doctors ?? [];
$currentSemester = $currentSemester ?? 'Fall';
$currentYear = $currentYear ?? date('Y');
$error = $error ?? null;
$success = $success ?? null;
$historyBySemester = $historyBySemester ?? [];
?>

<div class="schedule-container">
    <div class="schedule-header">
        <h1><i class="fas fa-calendar-alt"></i> Manage Semester Schedule</h1>
        <div class="semester-filter">
            <form method="get" action="<?= htmlspecialchars($url('it/schedule')) ?>" class="filter-form">
                <select name="semester" class="form-select">
                    <option value="Fall" <?= $currentSemester === 'Fall' ? 'selected' : '' ?>>Fall</option>
                    <option value="Spring" <?= $currentSemester === 'Spring' ? 'selected' : '' ?>>Spring</option>
                    <option value="Summer" <?= $currentSemester === 'Summer' ? 'selected' : '' ?>>Summer</option>
                </select>
                <input type="text" name="year" value="<?= htmlspecialchars($currentYear) ?>" class="form-input" placeholder="Year">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars(urldecode($_GET['success'])) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars(urldecode($_GET['error'])) ?>
        </div>
    <?php endif; ?>

    <div class="schedule-content">
        <div class="schedule-form-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2>Create Schedule Entry</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-outline" id="singleModeBtn" onclick="switchMode('single')">
                        <i class="fas fa-plus"></i> Single Entry
                    </button>
                    <button type="button" class="btn btn-primary" id="bulkModeBtn" onclick="switchMode('bulk')">
                        <i class="fas fa-layer-group"></i> Bulk Schedule (Multiple Courses)
                    </button>
                </div>
            </div>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.95rem; padding: 1rem; background: var(--surface-color); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                <i class="fas fa-info-circle"></i> <strong>Core Concept:</strong> A semester schedule contains many courses. Each course can have multiple sessions (lecture, lab, tutorial, etc.). Multiple courses can be scheduled on the same day as long as their time slots don't conflict. Each session is a weekly recurring time block.
            </p>
            
            <!-- Single Entry Form -->
            <form method="post" action="<?= htmlspecialchars($url('it/schedule')) ?>" class="section-form" id="singleEntryForm" onsubmit="return validateSingleForm(event)">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="course_id" class="form-label">Course *</label>
                        <select id="course_id" name="course_id" class="form-input" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>">
                                    <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="doctor_id" class="form-label">Doctor (Instructor) *</label>
                        <select id="doctor_id" name="doctor_id" class="form-input" required>
                            <option value="">Select Doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?= $doctor['doctor_id'] ?>">
                                    <?= htmlspecialchars($doctor['first_name']) ?> <?= htmlspecialchars($doctor['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="section_number" class="form-label">Section/Session Number *</label>
                        <input type="text" id="section_number" name="section_number" class="form-input" required placeholder="e.g., 001, L01, LAB01">
                        <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                            Unique identifier for this session (e.g., 001 for section, L01 for lecture, LAB01 for lab)
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="semester" class="form-label">Semester *</label>
                        <select id="semester" name="semester" class="form-input" required>
                            <option value="Fall" <?= $currentSemester === 'Fall' ? 'selected' : '' ?>>Fall</option>
                            <option value="Spring" <?= $currentSemester === 'Spring' ? 'selected' : '' ?>>Spring</option>
                            <option value="Summer" <?= $currentSemester === 'Summer' ? 'selected' : '' ?>>Summer</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="academic_year" class="form-label">Academic Year *</label>
                        <input type="text" id="academic_year" name="academic_year" class="form-input" required value="<?= htmlspecialchars($currentYear) ?>">
                    </div>

                    <div class="form-group">
                        <label for="session_type" class="form-label">Session Type *</label>
                        <select id="session_type" name="session_type" class="form-input" required>
                            <option value="lecture">Lecture</option>
                            <option value="lab">Lab</option>
                            <option value="tutorial">Tutorial</option>
                            <option value="section">Section</option>
                            <option value="seminar">Seminar</option>
                            <option value="workshop">Workshop</option>
                        </select>
                        <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                            Type of session. A course can have multiple sessions (e.g., lecture + lab) on the same or different days.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="room" class="form-label">Room *</label>
                        <input type="text" id="room" name="room" class="form-input" required placeholder="e.g., A101">
                        <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                            Required for conflict detection
                        </small>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Schedule Days *</label>
                        <div class="days-selector">
                            <?php 
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            foreach ($days as $day): ?>
                                <div class="day-option">
                                    <input type="checkbox" name="days[]" value="<?= $day ?>" id="day_<?= strtolower($day) ?>" class="day-checkbox" onchange="updateDaySchedule('<?= $day ?>')">
                                    <label for="day_<?= strtolower($day) ?>" class="day-label">
                                        <span class="day-name"><?= $day ?></span>
                                    </label>
                                    <div class="day-schedule" id="schedule_<?= strtolower($day) ?>" style="display: none; margin-top: 0.5rem; padding: 0.75rem; background: var(--surface-color); border-radius: 6px;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                            <div>
                                                <label style="font-size: 0.85rem; color: var(--text-secondary);">Start Time *</label>
                                                <input type="time" name="start_time[<?= $day ?>]" class="form-input" style="padding: 0.5rem;" required>
                                            </div>
                                            <div>
                                                <label style="font-size: 0.85rem; color: var(--text-secondary);">End Time *</label>
                                                <input type="time" name="end_time[<?= $day ?>]" class="form-input" style="padding: 0.5rem;" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small style="color: var(--text-secondary); margin-top: 0.5rem; display: block;">
                            <i class="fas fa-info-circle"></i> <strong>Multiple Sessions:</strong> You can create multiple sessions for the same course (e.g., lecture on Mon/Wed and lab on Tue/Thu). Each selected day creates a separate schedule entry. Multiple sessions can occur on the same day if their time slots don't conflict.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="capacity" class="form-label">Capacity *</label>
                        <input type="number" id="capacity" name="capacity" class="form-input" required value="30" min="1">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Schedule Entry
                </button>
                <div style="margin-top: 1rem; padding: 1rem; background: var(--surface-color); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--text-primary); font-size: 0.95rem;">
                        <i class="fas fa-lightbulb"></i> How It Works:
                    </h4>
                    <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        <li><strong>One Entry Per Day:</strong> Each selected day creates a separate schedule entry</li>
                        <li><strong>Multiple Sessions:</strong> A course can have multiple sessions (lecture, lab, tutorial) - create them separately</li>
                        <li><strong>Same Day Sessions:</strong> Multiple sessions can be on the same day if times don't conflict</li>
                        <li><strong>Conflict Detection:</strong> System checks for room conflicts and doctor availability</li>
                        <li><strong>Days Off:</strong> Days without entries are automatically "off" (no record needed)</li>
                    </ul>
                </div>
            </form>
            
            <!-- Bulk Schedule Form -->
            <form method="post" action="<?= htmlspecialchars($url('it/schedule')) ?>" class="section-form" id="bulkEntryForm" style="display: none;">
                <input type="hidden" name="bulk_mode" value="1">
                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Select Multiple Courses *</label>
                        <div style="max-height: 200px; overflow-y: auto; border: 2px solid var(--border-color); border-radius: 6px; padding: 0.75rem; background: var(--background-color);">
                            <?php foreach ($courses as $course): ?>
                                <div style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                        <input type="checkbox" name="bulk_courses[]" value="<?= $course['course_id'] ?>" class="bulk-course-checkbox" onchange="updateBulkCourseFields()">
                                        <span><strong><?= htmlspecialchars($course['course_code']) ?></strong> - <?= htmlspecialchars($course['name']) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                            Select multiple courses to schedule on the same day(s). Each course will need its own doctor, section number, room, and time slot.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk_semester" class="form-label">Semester *</label>
                        <select id="bulk_semester" name="bulk_semester" class="form-input" required>
                            <option value="Fall" <?= $currentSemester === 'Fall' ? 'selected' : '' ?>>Fall</option>
                            <option value="Spring" <?= $currentSemester === 'Spring' ? 'selected' : '' ?>>Spring</option>
                            <option value="Summer" <?= $currentSemester === 'Summer' ? 'selected' : '' ?>>Summer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk_academic_year" class="form-label">Academic Year *</label>
                        <input type="text" id="bulk_academic_year" name="bulk_academic_year" class="form-input" required value="<?= htmlspecialchars($currentYear) ?>">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Schedule Days *</label>
                        <div class="days-selector">
                            <?php 
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            foreach ($days as $day): ?>
                                <div class="day-option">
                                    <input type="checkbox" name="bulk_days[]" value="<?= $day ?>" id="bulk_day_<?= strtolower($day) ?>" class="day-checkbox" onchange="updateBulkDaySchedule('<?= $day ?>')">
                                    <label for="bulk_day_<?= strtolower($day) ?>" class="day-label">
                                        <span class="day-name"><?= $day ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Dynamic course fields container -->
                <div id="bulkCourseFields" style="margin-top: 1.5rem;">
                    <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
                        <i class="fas fa-info-circle"></i> Select courses above to configure their schedule details
                    </p>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">
                    <i class="fas fa-layer-group"></i> Create All Schedule Entries
                </button>
                <div style="margin-top: 1rem; padding: 1rem; background: var(--surface-color); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--text-primary); font-size: 0.95rem;">
                        <i class="fas fa-lightbulb"></i> Bulk Schedule Tips:
                    </h4>
                    <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        <li><strong>Multiple Courses:</strong> Schedule multiple courses on the same day(s) with different times</li>
                        <li><strong>Conflict Detection:</strong> System will check for room and doctor conflicts before creating entries</li>
                        <li><strong>Same Day:</strong> All selected courses will be scheduled on the same selected day(s)</li>
                        <li><strong>Different Times:</strong> Each course must have a unique time slot to avoid conflicts</li>
                        <li><strong>Partial Success:</strong> If some entries fail due to conflicts, successful ones will still be created</li>
                    </ul>
                </div>
            </form>
        </div>

        <div class="schedule-list-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2>Weekly Timetable (<?= htmlspecialchars($currentSemester) ?> <?= htmlspecialchars($currentYear) ?>)</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-outline" onclick="toggleView('timetable')" id="viewTimetableBtn">
                        <i class="fas fa-calendar-week"></i> Timetable View
                    </button>
                    <button type="button" class="btn btn-outline" onclick="toggleView('list')" id="viewListBtn">
                        <i class="fas fa-list"></i> List View
                    </button>
                </div>
            </div>
            
            <!-- Weekly Timetable View -->
            <div id="timetableView" class="timetable-container">
                <?php if (empty($sections)): ?>
                    <p class="text-muted">No schedule entries for this semester.</p>
                <?php else: ?>
                    <?php
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    $weeklyTimetable = $weeklyTimetable ?? [];
                    ?>
                    <div class="timetable-wrapper" style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                        <table class="timetable-table">
                            <thead>
                                <tr>
                                    <th style="width: 100px; position: sticky; left: 0; background: var(--primary-color); color: white; z-index: 10; padding: 0.75rem;">Time</th>
                                    <?php foreach ($days as $day): ?>
                                        <th style="min-width: 180px; padding: 0.75rem;"><?= $day ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Generate hourly time slots from 8 AM to 8 PM
                                for ($hour = 8; $hour <= 20; $hour++):
                                    $timeSlot = sprintf('%02d:00', $hour);
                                    $timeDisplay = date('g:i A', strtotime($timeSlot));
                                ?>
                                    <tr>
                                        <td style="position: sticky; left: 0; background: var(--surface-color); z-index: 5; font-weight: 600; padding: 0.75rem; border-right: 2px solid var(--border-color);">
                                            <?= $timeDisplay ?>
                                        </td>
                                        <?php foreach ($days as $day): ?>
                                            <td class="timetable-cell" style="padding: 0.5rem; vertical-align: top; min-height: 100px;">
                                                <?php
                                                // Find entries that start in this hour
                                                $hourEntries = [];
                                                foreach ($weeklyTimetable[$day] ?? [] as $entry) {
                                                    if (empty($entry['start_time'])) continue;
                                                    
                                                    $entryStart = strtotime($entry['start_time']);
                                                    $entryHour = (int)date('G', $entryStart);
                                                    
                                                    // Show entry in the hour it starts
                                                    if ($entryHour == $hour) {
                                                        $hourEntries[] = $entry;
                                                    }
                                                }
                                                
                                                // Display entries
                                                foreach ($hourEntries as $entry):
                                                    $entryStart = strtotime($entry['start_time'] ?? '00:00:00');
                                                    $entryEnd = strtotime($entry['end_time'] ?? '00:00:00');
                                                ?>
                                                    <div class="timetable-entry" style="
                                                        background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%);
                                                        color: white;
                                                        padding: 0.75rem;
                                                        border-radius: 6px;
                                                        margin-bottom: 0.5rem;
                                                        font-size: 0.85rem;
                                                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                                        cursor: pointer;
                                                        transition: all 0.2s;
                                                    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                                                        <div style="font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem;">
                                                            <?= htmlspecialchars($entry['course_code'] ?? '') ?> - <?= htmlspecialchars($entry['section_number'] ?? '') ?>
                                                            <?php
                                                            // Show session type if present in section number
                                                            $sectionNum = $entry['section_number'] ?? '';
                                                            if (preg_match('/-(Lecture|Lab|Tutorial|Section|Seminar|Workshop)$/i', $sectionNum, $matches)) {
                                                                echo '<br><small style="opacity: 0.9;"><i class="fas fa-tag"></i> ' . htmlspecialchars(ucfirst(strtolower($matches[1]))) . '</small>';
                                                            }
                                                            ?>
                                                        </div>
                                                        <div style="font-size: 0.75rem; opacity: 0.9; margin-bottom: 0.5rem; line-height: 1.3;">
                                                            <?= htmlspecialchars(substr($entry['course_name'] ?? '', 0, 30)) ?><?= strlen($entry['course_name'] ?? '') > 30 ? '...' : '' ?>
                                                        </div>
                                                        <div style="font-size: 0.7rem; opacity: 0.85; margin-bottom: 0.25rem;">
                                                            <i class="fas fa-clock"></i> <?= date('g:i A', $entryStart) ?> - <?= date('g:i A', $entryEnd) ?>
                                                        </div>
                                                        <div style="font-size: 0.7rem; opacity: 0.85; display: flex; justify-content: space-between; align-items: center;">
                                                            <span><i class="fas fa-door-open"></i> <?= htmlspecialchars($entry['room'] ?? 'TBA') ?></span>
                                                            <span><i class="fas fa-users"></i> <?= $entry['current_enrollment'] ?? 0 ?>/<?= $entry['capacity'] ?? 30 ?></span>
                                                        </div>
                                                        <div style="font-size: 0.7rem; opacity: 0.85; margin-top: 0.25rem; padding-top: 0.25rem; border-top: 1px solid rgba(255,255,255,0.2);">
                                                            <i class="fas fa-user-md"></i> <?= htmlspecialchars($entry['doctor_first_name'] ?? '') ?> <?= htmlspecialchars($entry['doctor_last_name'] ?? '') ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- List View -->
            <div id="listView" class="list-view" style="display: none;">
                <?php if (empty($sections)): ?>
                    <p class="text-muted">No schedule entries for this semester.</p>
                <?php else: ?>
                    <div class="sections-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Section/Session</th>
                                    <th>Type</th>
                                    <th>Doctor</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Room</th>
                                    <th>Enrollment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sections as $section): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($section['course_code']) ?></strong><br>
                                            <small><?= htmlspecialchars($section['course_name']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($section['section_number']) ?></td>
                                        <td>
                                            <?php
                                            // Extract session type from section number if present
                                            $sectionNum = $section['section_number'] ?? '';
                                            if (preg_match('/-(Lecture|Lab|Tutorial|Section|Seminar|Workshop)$/i', $sectionNum, $matches)) {
                                                $sessionType = strtolower($matches[1]);
                                                echo '<span class="badge" style="background: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">' . htmlspecialchars(ucfirst($sessionType)) . '</span>';
                                            } else {
                                                echo '<span class="badge" style="background: var(--text-secondary); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Section</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($section['doctor_first_name']) ?> <?= htmlspecialchars($section['doctor_last_name']) ?></td>
                                        <td><?= htmlspecialchars($section['day_of_week'] ?? '') ?></td>
                                        <td>
                                            <?= htmlspecialchars($section['start_time'] ?? '') ?> - <?= htmlspecialchars($section['end_time'] ?? '') ?>
                                        </td>
                                        <td><?= htmlspecialchars($section['room'] ?? 'TBA') ?></td>
                                        <td>
                                            <span class="enrollment-badge">
                                                <?= $section['current_enrollment'] ?? 0 ?> / <?= $section['capacity'] ?? 30 ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.schedule-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.schedule-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.schedule-header h1 {
    font-size: 2rem;
    color: var(--text-primary);
}

.filter-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.form-select, .form-input {
    padding: 0.5rem;
    border: 2px solid var(--border-color);
    border-radius: 6px;
    background: var(--background-color);
    color: var(--text-primary);
}

.schedule-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.schedule-form-section, .schedule-list-section {
    background: var(--surface-color);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px var(--shadow-color);
}

.schedule-form-section h2, .schedule-list-section h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--text-primary);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-primary);
}

.sections-table {
    overflow-x: auto;
}

.sections-table table {
    width: 100%;
    border-collapse: collapse;
}

.sections-table th {
    background: var(--background-color);
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid var(--border-color);
}

.sections-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.enrollment-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: var(--primary-color);
    color: white;
    border-radius: 12px;
    font-size: 0.9rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.alert-error {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
}

.alert-success {
    background: #efe;
    color: #3c3;
    border: 1px solid #cfc;
}

.days-selector {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.day-option {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 0.75rem;
    background: var(--background-color);
    transition: all 0.2s;
}

.day-option:hover {
    border-color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
}

.day-checkbox {
    display: none;
}

.day-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.day-name {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 1rem;
}

.day-checkbox:checked + .day-label .day-name {
    color: var(--primary-color);
    font-weight: 600;
}

.btn-day-off {
    padding: 0.375rem 0.75rem;
    background: var(--surface-color);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    color: var(--text-secondary);
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-day-off:hover {
    background: var(--error-color);
    color: white;
    border-color: var(--error-color);
}

.day-schedule {
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced Modern Styles */
.schedule-container {
    background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
    min-height: 100vh;
}

.schedule-form-section, .schedule-list-section, .schedule-history-section {
    transition: all 0.3s ease;
    background: white;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.schedule-form-section:hover, .schedule-list-section:hover, .schedule-history-section:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.form-input, .form-select {
    transition: all 0.3s ease;
    border: 2px solid #e2e8f0;
    background: white;
}

.form-input:focus, .form-select:focus {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
    border-color: var(--primary-color);
    outline: none;
}

.btn-primary {
    transition: all 0.3s ease;
    background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%);
    border: none;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
}

.btn-outline {
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
    background: transparent;
    transition: all 0.3s ease;
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

.sections-table tbody tr {
    transition: all 0.2s ease;
}

.sections-table tbody tr:hover {
    background: #f8fafc;
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.day-option {
    background: white;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
}

.day-option:hover {
    border-color: var(--primary-color);
    background: #f0f7ff;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
}

.day-checkbox:checked + .day-label .day-name {
    color: var(--primary-color);
    font-weight: 700;
}

.timetable-entry {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border: none;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.timetable-entry:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.4);
}

.alert {
    border-radius: 10px;
    border-left: 4px solid;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-left-color: #10b981;
}

.alert-error {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-left-color: #ef4444;
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 1024px) {
    .schedule-content {
        grid-template-columns: 1fr;
    }
    
    .schedule-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}
</style>

<style>
.timetable-container {
    margin-top: 1rem;
}

.timetable-wrapper {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: auto;
    max-height: 600px;
}

.timetable-table {
    width: 100%;
    border-collapse: collapse;
    position: relative;
}

.timetable-table th {
    background: var(--primary-color);
    color: white;
    padding: 0.75rem;
    text-align: center;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 10;
}

.timetable-table td {
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    min-height: 80px;
    position: relative;
    min-width: 150px;
    vertical-align: top;
}

.timetable-cell {
    background: var(--background-color);
}

.timetable-entry {
    cursor: pointer;
    transition: all 0.2s;
}

.timetable-entry:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.list-view {
    margin-top: 1rem;
}
</style>

<script>
function updateDaySchedule(day) {
    const checkbox = document.getElementById('day_' + day.toLowerCase());
    const schedule = document.getElementById('schedule_' + day.toLowerCase());
    
    if (checkbox.checked) {
        schedule.style.display = 'block';
    } else {
        schedule.style.display = 'none';
        // Clear time inputs when unchecked
        const startTime = schedule.querySelector('input[name="start_time[' + day + ']"]');
        const endTime = schedule.querySelector('input[name="end_time[' + day + ']"]');
        if (startTime) startTime.value = '';
        if (endTime) endTime.value = '';
    }
}

function toggleView(view) {
    const timetableView = document.getElementById('timetableView');
    const listView = document.getElementById('listView');
    const timetableBtn = document.getElementById('viewTimetableBtn');
    const listBtn = document.getElementById('viewListBtn');
    
    if (view === 'timetable') {
        timetableView.style.display = 'block';
        listView.style.display = 'none';
        timetableBtn.classList.add('btn-primary');
        timetableBtn.classList.remove('btn-outline');
        listBtn.classList.remove('btn-primary');
        listBtn.classList.add('btn-outline');
    } else {
        timetableView.style.display = 'none';
        listView.style.display = 'block';
        listBtn.classList.add('btn-primary');
        listBtn.classList.remove('btn-outline');
        timetableBtn.classList.remove('btn-primary');
        timetableBtn.classList.add('btn-outline');
    }
}

// Initialize view
document.addEventListener('DOMContentLoaded', function() {
    toggleView('timetable');
    switchMode('single'); // Default to single entry mode
});

// Switch between single and bulk mode
function switchMode(mode) {
    const singleForm = document.getElementById('singleEntryForm');
    const bulkForm = document.getElementById('bulkEntryForm');
    const singleBtn = document.getElementById('singleModeBtn');
    const bulkBtn = document.getElementById('bulkModeBtn');
    
    if (mode === 'single') {
        singleForm.style.display = 'block';
        bulkForm.style.display = 'none';
        singleBtn.classList.remove('btn-outline');
        singleBtn.classList.add('btn-primary');
        bulkBtn.classList.remove('btn-primary');
        bulkBtn.classList.add('btn-outline');
    } else {
        singleForm.style.display = 'none';
        bulkForm.style.display = 'block';
        bulkBtn.classList.remove('btn-outline');
        bulkBtn.classList.add('btn-primary');
        singleBtn.classList.remove('btn-primary');
        singleBtn.classList.add('btn-outline');
    }
}

// Update bulk course fields when courses are selected
function updateBulkCourseFields() {
    const selectedCourses = Array.from(document.querySelectorAll('.bulk-course-checkbox:checked'));
    const container = document.getElementById('bulkCourseFields');
    
    if (selectedCourses.length === 0) {
        container.innerHTML = '<p style="color: var(--text-secondary); text-align: center; padding: 2rem;"><i class="fas fa-info-circle"></i> Select courses above to configure their schedule details</p>';
        return;
    }
    
    const courses = <?= json_encode($courses) ?>;
    const doctors = <?= json_encode($doctors) ?>;
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    let html = '<div style="display: grid; gap: 1.5rem;">';
    html += '<h3 style="margin-bottom: 1rem; color: var(--text-primary);">Course Configuration</h3>';
    
    selectedCourses.forEach((checkbox, index) => {
        const courseId = checkbox.value;
        const course = courses.find(c => c.course_id == courseId);
        if (!course) return;
        
        html += `<div style="border: 2px solid var(--border-color); border-radius: 8px; padding: 1.5rem; background: var(--surface-color);">`;
        html += `<h4 style="margin: 0 0 1rem 0; color: var(--primary-color);"><i class="fas fa-book"></i> ${course.course_code} - ${course.name}</h4>`;
        html += `<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">`;
        
        // Doctor selection
        html += `<div class="form-group">`;
        html += `<label class="form-label">Doctor (Instructor) *</label>`;
        html += `<select name="bulk_doctor[${courseId}]" class="form-input" required>`;
        html += `<option value="">Select Doctor</option>`;
        doctors.forEach(doctor => {
            html += `<option value="${doctor.doctor_id}">${doctor.first_name} ${doctor.last_name}</option>`;
        });
        html += `</select>`;
        html += `</div>`;
        
        // Section number
        html += `<div class="form-group">`;
        html += `<label class="form-label">Section/Session Number *</label>`;
        html += `<input type="text" name="bulk_section[${courseId}]" class="form-input" required placeholder="e.g., 001, L01">`;
        html += `</div>`;
        
        // Session type
        html += `<div class="form-group">`;
        html += `<label class="form-label">Session Type *</label>`;
        html += `<select name="bulk_session_type[${courseId}]" class="form-input" required>`;
        html += `<option value="lecture">Lecture</option>`;
        html += `<option value="lab">Lab</option>`;
        html += `<option value="tutorial">Tutorial</option>`;
        html += `<option value="section">Section</option>`;
        html += `<option value="seminar">Seminar</option>`;
        html += `<option value="workshop">Workshop</option>`;
        html += `</select>`;
        html += `</div>`;
        
        // Room
        html += `<div class="form-group">`;
        html += `<label class="form-label">Room *</label>`;
        html += `<input type="text" name="bulk_room[${courseId}]" class="form-input" required placeholder="e.g., A101">`;
        html += `</div>`;
        
        // Capacity
        html += `<div class="form-group">`;
        html += `<label class="form-label">Capacity *</label>`;
        html += `<input type="number" name="bulk_capacity[${courseId}]" class="form-input" required value="30" min="1">`;
        html += `</div>`;
        
        html += `</div>`;
        
        // Time slots for each selected day
        html += `<div style="margin-top: 1rem;">`;
        html += `<label class="form-label">Time Slots for Selected Days *</label>`;
        html += `<div style="display: grid; gap: 0.75rem; margin-top: 0.5rem;">`;
        
        days.forEach(day => {
            const dayLower = day.toLowerCase();
            html += `<div id="bulk_time_${dayLower}_${courseId}" style="display: none; padding: 0.75rem; background: var(--background-color); border-radius: 6px; border: 1px solid var(--border-color);">`;
            html += `<div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">`;
            html += `<strong style="min-width: 100px;">${day}:</strong>`;
            html += `<input type="time" name="bulk_start_time[${courseId}][${day}]" class="form-input" style="flex: 1; padding: 0.5rem;" placeholder="Start Time">`;
            html += `<span>to</span>`;
            html += `<input type="time" name="bulk_end_time[${courseId}][${day}]" class="form-input" style="flex: 1; padding: 0.5rem;" placeholder="End Time">`;
            html += `</div>`;
            html += `</div>`;
        });
        
        html += `</div>`;
        html += `</div>`;
        html += `</div>`;
    });
    
    html += '</div>';
    container.innerHTML = html;
    
    // Update time slot visibility based on selected days
    updateBulkTimeSlots();
}

// Update bulk day schedule visibility
function updateBulkDaySchedule(day) {
    const checkbox = document.getElementById('bulk_day_' + day.toLowerCase());
    updateBulkTimeSlots();
}

// Update time slot visibility for bulk mode
function updateBulkTimeSlots() {
    const selectedDays = Array.from(document.querySelectorAll('input[name="bulk_days[]"]:checked'));
    const selectedCourses = Array.from(document.querySelectorAll('.bulk-course-checkbox:checked'));
    
    selectedDays.forEach(dayCheckbox => {
        const day = dayCheckbox.value;
        const dayLower = day.toLowerCase();
        
        selectedCourses.forEach(courseCheckbox => {
            const courseId = courseCheckbox.value;
            const timeDiv = document.getElementById(`bulk_time_${dayLower}_${courseId}`);
            if (timeDiv) {
                timeDiv.style.display = 'block';
                // Make time inputs required
                const startInput = timeDiv.querySelector(`input[name="bulk_start_time[${courseId}][${day}]"]`);
                const endInput = timeDiv.querySelector(`input[name="bulk_end_time[${courseId}][${day}]"]`);
                if (startInput) startInput.required = true;
                if (endInput) endInput.required = true;
            }
        });
    });
    
    // Hide time slots for unselected days
    const allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    const selectedDayValues = selectedDays.map(d => d.value);
    
    allDays.forEach(day => {
        if (!selectedDayValues.includes(day)) {
            const dayLower = day.toLowerCase();
            selectedCourses.forEach(courseCheckbox => {
                const courseId = courseCheckbox.value;
                const timeDiv = document.getElementById(`bulk_time_${dayLower}_${courseId}`);
                if (timeDiv) {
                    timeDiv.style.display = 'none';
                    const startInput = timeDiv.querySelector(`input[name="bulk_start_time[${courseId}][${day}]"]`);
                    const endInput = timeDiv.querySelector(`input[name="bulk_end_time[${courseId}][${day}]"]`);
                    if (startInput) startInput.required = false;
                    if (endInput) endInput.required = false;
                }
            });
        }
    });
}

// Validate single entry form before submission
function validateSingleForm(event) {
    const form = document.getElementById('singleEntryForm');
    if (!form) return true;
    
    const selectedDays = Array.from(form.querySelectorAll('input[name="days[]"]:checked'));
    
    if (selectedDays.length === 0) {
        event.preventDefault();
        alert('Please select at least one day for the schedule.');
        return false;
    }
    
    // Check if all selected days have time inputs filled
    let allTimesValid = true;
    let missingTimes = [];
    
    selectedDays.forEach(dayCheckbox => {
        const day = dayCheckbox.value;
        const startTime = form.querySelector(`input[name="start_time[${day}]"]`);
        const endTime = form.querySelector(`input[name="end_time[${day}]"]`);
        
        if (!startTime || !startTime.value || !endTime || !endTime.value) {
            allTimesValid = false;
            missingTimes.push(day);
        }
    });
    
    if (!allTimesValid) {
        event.preventDefault();
        alert('Please fill in start time and end time for all selected days: ' + missingTimes.join(', '));
        return false;
    }
    
    // Additional validation
    const courseId = form.querySelector('#course_id')?.value;
    const doctorId = form.querySelector('#doctor_id')?.value;
    const sectionNumber = form.querySelector('#section_number')?.value;
    const room = form.querySelector('#room')?.value;
    
    if (!courseId || !doctorId || !sectionNumber || !room) {
        event.preventDefault();
        alert('Please fill in all required fields (Course, Doctor, Section Number, and Room).');
        return false;
    }
    
    return true;
}
</script>
