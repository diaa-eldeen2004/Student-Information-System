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
        <div>
            <h1><i class="fas fa-calendar-alt"></i> Manage Semester Schedule</h1>
            <p>Create and manage semester schedules for courses and sections</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline" onclick="checkDatabaseTables()" style="padding: 0.75rem 1.5rem;">
                <i class="fas fa-database"></i> Check Database
            </button>
            <button type="button" class="btn btn-primary" onclick="runMigration()" style="padding: 0.75rem 1.5rem;">
                <i class="fas fa-sync"></i> Run Migration
            </button>
            <button type="button" class="btn btn-danger" onclick="clearAllSchedules()" style="padding: 0.75rem 1.5rem;">
                <i class="fas fa-trash-alt"></i> Clear All Schedules
            </button>
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

    <!-- Schedule Form Section (First) -->
    <div class="schedule-form-section">
        <div class="section-header">
            <h2>Create Schedule Entry</h2>
            <div class="mode-switcher">
                <button type="button" class="btn btn-mode" id="singleModeBtn" onclick="switchMode('single')">
                    <i class="fas fa-plus"></i> Single Entry
                </button>
                <button type="button" class="btn btn-mode" id="quickModeBtn" onclick="switchMode('quick')">
                    <i class="fas fa-bolt"></i> Quick Schedule
                </button>
                <button type="button" class="btn btn-mode active" id="bulkModeBtn" onclick="switchMode('bulk')">
                    <i class="fas fa-layer-group"></i> Bulk Schedule
                </button>
            </div>
        </div>
        
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Core Concept:</strong> A semester schedule contains many courses. Each course can have multiple sessions (lecture, lab, tutorial, etc.). Multiple courses can be scheduled on the same day as long as their time slots don't conflict. Each session is a weekly recurring time block.
            </div>
        </div>
        
        <!-- Single Entry Form -->
        <form method="post" action="<?= htmlspecialchars($url('it/schedule')) ?>" class="section-form" id="singleEntryForm" onsubmit="return validateSingleForm(event);">
            <div id="coursesContainer">
                <!-- Course entries will be added here dynamically -->
            </div>
            
            <button type="button" class="btn btn-outline" onclick="addCourseEntry()" style="margin-bottom: 1.5rem; width: 100%;">
                <i class="fas fa-plus"></i> Add Another Course
            </button>
            
            <div class="form-group">
                <label for="semester" class="form-label">Semester *</label>
                <select id="semester" name="semester" class="form-input" required>
                    <option value="Fall">Fall</option>
                    <option value="Spring">Spring</option>
                    <option value="Summer">Summer</option>
                </select>
            </div>

            <div class="form-group">
                <label for="academic_year" class="form-label">Academic Year *</label>
                <input type="text" id="academic_year" name="academic_year" class="form-input" required 
                       value="<?= htmlspecialchars($currentYear) ?>" placeholder="e.g., 2024-2025">
            </div>
            

            <button type="submit" class="btn btn-primary" id="submitScheduleBtn">
                <i class="fas fa-plus"></i> Create Schedule Entry
            </button>
            
            <div class="help-box">
                <h4><i class="fas fa-lightbulb"></i> How It Works:</h4>
                <ul>
                    <li><strong>One Entry Per Day:</strong> Each selected day creates a separate schedule entry</li>
                    <li><strong>Multiple Sessions:</strong> A course can have multiple sessions - create them separately</li>
                    <li><strong>Same Day Sessions:</strong> Multiple sessions can be on the same day if times don't conflict</li>
                    <li><strong>Conflict Detection:</strong> System checks for room conflicts and doctor availability</li>
                </ul>
            </div>
        </form>
        
        <!-- Quick Schedule Form - Multiple courses for same day(s) -->
        <form method="post" action="<?= htmlspecialchars($url('it/schedule')) ?>" class="section-form" id="quickEntryForm" style="display: none;" onsubmit="return validateQuickForm(event); return false;">
            <input type="hidden" name="quick_mode" value="1">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="quick_semester" class="form-label">Semester *</label>
                    <select id="quick_semester" name="quick_semester" class="form-input" required>
                        <option value="Fall" <?= $currentSemester === 'Fall' ? 'selected' : '' ?>>Fall</option>
                        <option value="Spring" <?= $currentSemester === 'Spring' ? 'selected' : '' ?>>Spring</option>
                        <option value="Summer" <?= $currentSemester === 'Summer' ? 'selected' : '' ?>>Summer</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quick_academic_year" class="form-label">Academic Year *</label>
                    <input type="text" id="quick_academic_year" name="quick_academic_year" class="form-input" required value="<?= htmlspecialchars($currentYear) ?>">
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Schedule Days *</label>
                    <div class="days-selector">
                        <?php 
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($days as $day): ?>
                            <div class="day-option">
                                <input type="checkbox" name="quick_days[]" value="<?= $day ?>" id="quick_day_<?= strtolower($day) ?>" class="day-checkbox">
                                <label for="quick_day_<?= strtolower($day) ?>" class="day-label">
                                    <span class="day-name"><?= $day ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="form-hint">Select one or more days. All courses below will be scheduled on the selected day(s).</small>
                </div>
            </div>
            
            <div class="quick-schedule-table-container" style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="margin: 0; color: var(--text-primary); font-size: 1.25rem;">
                        <i class="fas fa-table"></i> Schedule Entries
                    </h3>
                    <button type="button" class="btn btn-primary" onclick="addQuickScheduleRow()">
                        <i class="fas fa-plus"></i> Add Course
                    </button>
                </div>
                
                <div class="quick-schedule-table-wrapper" style="overflow-x: auto; border: 1px solid var(--border-light); border-radius: 8px;">
                    <table class="quick-schedule-table" style="width: 100%; border-collapse: collapse; min-width: 1200px;">
                        <thead>
                            <tr style="background: var(--bg-tertiary);">
                                <th style="padding: 1rem; text-align: left; color: var(--text-primary); font-weight: 600; border-bottom: 2px solid var(--border-color);">Course *</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-primary); font-weight: 600; border-bottom: 2px solid var(--border-color);">Doctor *</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-primary); font-weight: 600; border-bottom: 2px solid var(--border-color);">Section *</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-primary); font-weight: 600; border-bottom: 2px solid var(--border-color);">Type</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-primary); font-weight: 600; border-bottom: 2px solid var(--border-color);">Room *</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-primary); font-weight: 600; border-bottom: 2px solid var(--border-color);">Start Time *</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-primary); font-weight: 600; border-bottom: 2px solid var(--border-color);">End Time *</th>
                                <th style="padding: 1rem; text-align: left; color: var(--text-primary); font-weight: 600; border-bottom: 2px solid var(--border-color);">Capacity</th>
                                <th style="padding: 1rem; text-align: center; color: var(--text-primary); font-weight: 600; border-bottom: 2px solid var(--border-color); width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="quickScheduleRows">
                            <!-- Rows will be added dynamically -->
                        </tbody>
                    </table>
                </div>
                <small class="form-hint" style="margin-top: 0.5rem; display: block;">
                    <i class="fas fa-info-circle"></i> Add multiple courses to schedule them on the same day(s). Each row creates a separate schedule entry.
                </small>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">
                <i class="fas fa-save"></i> Create All Schedule Entries
            </button>
            
            <div class="help-box">
                <h4><i class="fas fa-lightbulb"></i> Quick Schedule Tips:</h4>
                <ul>
                    <li><strong>Same Day, Different Times:</strong> Schedule multiple courses on the same day with different time slots</li>
                    <li><strong>Same Time, Different Rooms:</strong> Schedule courses at the same time in different rooms</li>
                    <li><strong>Multiple Sections:</strong> Create multiple sections of the same course easily</li>
                    <li><strong>Conflict Detection:</strong> System automatically checks for room and doctor conflicts</li>
                </ul>
            </div>
        </form>
        
        <!-- Bulk Schedule Form -->
        <form method="post" action="<?= htmlspecialchars($url('it/schedule')) ?>" class="section-form" id="bulkEntryForm" style="display: none;">
            <input type="hidden" name="bulk_mode" value="1">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label class="form-label">Select Multiple Courses *</label>
                    <div class="bulk-courses-list">
                        <?php foreach ($courses as $course): ?>
                            <div class="bulk-course-item">
                                <label class="bulk-course-label">
                                    <input type="checkbox" name="bulk_courses[]" value="<?= $course['course_id'] ?>" class="bulk-course-checkbox" onchange="updateBulkCourseFields()">
                                    <span><strong><?= htmlspecialchars($course['course_code']) ?></strong> - <?= htmlspecialchars($course['name']) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="form-hint">Select multiple courses to schedule on the same day(s). Each course will need its own doctor, section number, room, and time slot.</small>
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
                
                <div class="form-group full-width">
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
            
            <div id="bulkCourseFields" class="bulk-fields-container">
                <p class="bulk-placeholder">
                    <i class="fas fa-info-circle"></i> Select courses above to configure their schedule details
                </p>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-layer-group"></i> Create All Schedule Entries
            </button>
            
            <div class="help-box">
                <h4><i class="fas fa-lightbulb"></i> Bulk Schedule Tips:</h4>
                <ul>
                    <li><strong>Multiple Courses:</strong> Schedule multiple courses on the same day(s) with different times</li>
                    <li><strong>Conflict Detection:</strong> System will check for room and doctor conflicts before creating entries</li>
                    <li><strong>Same Day:</strong> All selected courses will be scheduled on the same selected day(s)</li>
                    <li><strong>Different Times:</strong> Each course must have a unique time slot to avoid conflicts</li>
                </ul>
            </div>
        </form>
    </div>

    <!-- Schedule List Section (Second - Below Form) -->
    <div class="schedule-list-section">
        <div class="section-header">
            <h2>Weekly Timetable (<?= htmlspecialchars($currentSemester) ?> <?= htmlspecialchars($currentYear) ?>)</h2>
            <div class="view-switcher">
                <button type="button" class="btn btn-view active" onclick="toggleView('timetable')" id="viewTimetableBtn">
                    <i class="fas fa-calendar-week"></i> Timetable View
                </button>
                <button type="button" class="btn btn-view" onclick="toggleView('list')" id="viewListBtn">
                    <i class="fas fa-list"></i> List View
                </button>
            </div>
        </div>
        
        <!-- Weekly Timetable View -->
        <div id="timetableView" class="timetable-container">
            <?php if (empty($sections)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>No schedule entries for this semester.</p>
                </div>
            <?php else: ?>
                <?php
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                $weeklyTimetable = $weeklyTimetable ?? [];
                
                // Debug: Log timetable data (remove in production)
                if (empty($weeklyTimetable)) {
                    error_log("Weekly timetable is empty. Sections count: " . count($sections ?? []));
                } else {
                    foreach ($weeklyTimetable as $day => $entries) {
                        if (!empty($entries)) {
                            error_log("Day {$day} has " . count($entries) . " entries");
                            foreach ($entries as $idx => $entry) {
                                error_log("  Entry {$idx}: " . ($entry['course_code'] ?? 'N/A') . " - " . ($entry['start_time'] ?? 'N/A') . " - " . ($entry['day_of_week'] ?? 'N/A'));
                            }
                        }
                    }
                }
                ?>
                <div class="timetable-wrapper">
                    <table class="timetable-table">
                        <thead>
                            <tr>
                                <th class="time-col">Time</th>
                                <?php foreach ($days as $day): ?>
                                    <th><?= $day ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            for ($hour = 8; $hour <= 20; $hour++):
                                $timeSlot = sprintf('%02d:00', $hour);
                                $timeDisplay = date('g:i A', strtotime($timeSlot));
                            ?>
                                <tr>
                                    <td class="time-col"><?= $timeDisplay ?></td>
                                    <?php foreach ($days as $day): ?>
                                        <td class="timetable-cell">
                                            <?php
                                            $hourEntries = [];
                                            foreach ($weeklyTimetable[$day] ?? [] as $entry) {
                                                if (empty($entry['start_time'])) continue;
                                                
                                                // Parse start time - handle various formats
                                                $startTime = $entry['start_time'];
                                                if (strlen($startTime) == 5) {
                                                    $startTime .= ':00'; // Add seconds if missing
                                                }
                                                
                                                $entryStart = strtotime($startTime);
                                                if ($entryStart === false) {
                                                    // Try alternative parsing
                                                    $entryStart = strtotime('1970-01-01 ' . $startTime);
                                                }
                                                
                                                if ($entryStart !== false) {
                                                    $entryHour = (int)date('G', $entryStart);
                                                    // Check if this entry falls within this hour slot
                                                    // (entry starts at this hour OR spans this hour)
                                                    $entryEndTime = $entry['end_time'] ?? '';
                                                    if (strlen($entryEndTime) == 5) {
                                                        $entryEndTime .= ':00';
                                                    }
                                                    $entryEnd = strtotime($entryEndTime);
                                                    if ($entryEnd === false) {
                                                        $entryEnd = strtotime('1970-01-01 ' . $entryEndTime);
                                                    }
                                                    
                                                    if ($entryEnd !== false) {
                                                        $entryEndHour = (int)date('G', $entryEnd);
                                                        // Include if starts at this hour or spans this hour
                                                        if ($entryHour == $hour || ($entryHour < $hour && $entryEndHour >= $hour)) {
                                                            $hourEntries[] = $entry;
                                                        }
                                                    } else if ($entryHour == $hour) {
                                                        $hourEntries[] = $entry;
                                                    }
                                                }
                                            }
                                            
                                            foreach ($hourEntries as $entry):
                                                $startTime = $entry['start_time'] ?? '00:00:00';
                                                $endTime = $entry['end_time'] ?? '00:00:00';
                                                if (strlen($startTime) == 5) $startTime .= ':00';
                                                if (strlen($endTime) == 5) $endTime .= ':00';
                                                $entryStart = strtotime($startTime);
                                                $entryEnd = strtotime($endTime);
                                                if ($entryStart === false) $entryStart = strtotime('1970-01-01 ' . $startTime);
                                                if ($entryEnd === false) $entryEnd = strtotime('1970-01-01 ' . $endTime);
                                            ?>
                                                <div class="timetable-entry">
                                                    <div class="entry-header">
                                                        <strong><?= htmlspecialchars($entry['course_code'] ?? '') ?> - <?= htmlspecialchars($entry['section_number'] ?? '') ?></strong>
                                                    </div>
                                                    <div class="entry-body">
                                                        <?= htmlspecialchars(substr($entry['course_name'] ?? '', 0, 30)) ?><?= strlen($entry['course_name'] ?? '') > 30 ? '...' : '' ?>
                                                    </div>
                                                    <div class="entry-footer">
                                                        <div><i class="fas fa-clock"></i> <?= date('g:i A', $entryStart) ?> - <?= date('g:i A', $entryEnd) ?></div>
                                                        <div><i class="fas fa-door-open"></i> <?= htmlspecialchars($entry['room'] ?? 'TBA') ?></div>
                                                        <div><i class="fas fa-users"></i> <?= $entry['current_enrollment'] ?? 0 ?>/<?= $entry['capacity'] ?? 30 ?></div>
                                                        <div><i class="fas fa-user-md"></i> <?= htmlspecialchars($entry['doctor_first_name'] ?? '') ?> <?= htmlspecialchars($entry['doctor_last_name'] ?? '') ?></div>
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
                <div class="empty-state">
                    <i class="fas fa-list"></i>
                    <p>No schedule entries for this semester.</p>
                </div>
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
                                        <span class="badge badge-type"><?= htmlspecialchars(ucfirst($section['session_type'] ?? 'Section')) ?></span>
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

<style>
/* Dark Mode CSS Variables */
:root {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-tertiary: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --border-color: #334155;
    --border-light: #475569;
    --primary-color: #3b82f6;
    --primary-hover: #2563eb;
    --success-color: #10b981;
    --error-color: #ef4444;
    --warning-color: #f59e0b;
    --shadow-sm: rgba(0, 0, 0, 0.3);
    --shadow-md: rgba(0, 0, 0, 0.4);
    --shadow-lg: rgba(0, 0, 0, 0.5);
}

/* Base Styles */
.schedule-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    background: var(--bg-primary);
    min-height: 100vh;
    color: var(--text-primary);
}

/* Header */
.schedule-header {
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
    border-radius: 16px;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 12px var(--shadow-md);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.schedule-header h1 {
    font-size: 2.5rem;
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--text-primary);
}

.schedule-header h1 i {
    color: var(--primary-color);
}

.schedule-header p {
    font-size: 1.1rem;
    margin: 0;
    color: var(--text-secondary);
}

.filter-form {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
}

.filter-form .form-select,
.filter-form .form-input {
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    background: var(--bg-tertiary);
    color: var(--text-primary);
    font-size: 0.95rem;
}

.filter-form .form-select:focus,
.filter-form .form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn-filter {
    padding: 0.75rem 1.5rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-filter:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* Alerts */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-left: 4px solid;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border-color: #10b981;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-color: #ef4444;
}

/* Sections */
.schedule-form-section,
.schedule-list-section {
    background: var(--bg-secondary);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 12px var(--shadow-md);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-header h2 {
    font-size: 1.75rem;
    margin: 0;
    color: var(--text-primary);
    font-weight: 700;
}

.mode-switcher,
.view-switcher {
    display: flex;
    gap: 0.5rem;
}

.btn-mode,
.btn-view {
    padding: 0.75rem 1.5rem;
    background: var(--bg-tertiary);
    color: var(--text-secondary);
    border: 1px solid var(--border-light);
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-mode:hover,
.btn-view:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    border-color: var(--primary-color);
}

.btn-mode.active,
.btn-view.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Info Box */
.info-box {
    padding: 1rem 1.5rem;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 1rem;
    color: var(--text-secondary);
}

.info-box i {
    color: var(--primary-color);
    font-size: 1.25rem;
    flex-shrink: 0;
}

/* Forms */
.section-form {
    margin-top: 1.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.95rem;
}

.form-input,
.form-select {
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    background: var(--bg-tertiary);
    color: var(--text-primary);
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-hint {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: var(--text-muted);
}

/* Days Selector */
.days-selector {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 0.75rem;
}

.day-option {
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 1rem;
    background: var(--bg-tertiary);
    transition: all 0.3s ease;
}

.day-option:hover {
    border-color: var(--primary-color);
    background: rgba(59, 130, 246, 0.05);
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
    font-weight: 700;
}

.day-schedule {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--bg-primary);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.time-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.time-inputs label {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    display: block;
}

/* Bulk Courses */
.bulk-courses-list {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 1rem;
    background: var(--bg-primary);
}

.bulk-course-item {
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.bulk-course-item:last-child {
    border-bottom: none;
}

.bulk-course-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    color: var(--text-primary);
}

.bulk-course-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.bulk-fields-container {
    margin-top: 1.5rem;
}

.bulk-placeholder {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* Help Box */
.help-box {
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: var(--bg-primary);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.help-box h4 {
    margin: 0 0 1rem 0;
    color: var(--text-primary);
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.help-box h4 i {
    color: var(--warning-color);
}

.help-box ul {
    margin: 0;
    padding-left: 1.5rem;
    color: var(--text-secondary);
    font-size: 0.9rem;
    line-height: 1.8;
}

.help-box ul li {
    margin-bottom: 0.5rem;
}

/* Timetable */
.timetable-container {
    margin-top: 1.5rem;
}

.timetable-wrapper {
    border: 1px solid var(--border-light);
    border-radius: 8px;
    overflow: auto;
    max-height: 700px;
    background: var(--bg-primary);
}

.timetable-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.timetable-table th {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    padding: 1rem;
    text-align: center;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 10;
    border: 1px solid var(--border-color);
}

.timetable-table .time-col {
    position: sticky;
    left: 0;
    background: var(--bg-tertiary);
    z-index: 11;
    font-weight: 700;
}

.timetable-table td {
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    min-height: 100px;
    vertical-align: top;
    background: var(--bg-primary);
}

.timetable-cell {
    min-width: 180px;
}

.timetable-entry {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: white;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    box-shadow: 0 2px 8px var(--shadow-md);
    cursor: pointer;
    transition: all 0.3s ease;
}

.timetable-entry:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.entry-header {
    font-weight: 700;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.entry-body {
    font-size: 0.8rem;
    opacity: 0.95;
    margin-bottom: 0.5rem;
}

.entry-footer {
    font-size: 0.75rem;
    opacity: 0.9;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.entry-footer div {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* List View */
.list-view {
    margin-top: 1.5rem;
}

.sections-table {
    overflow-x: auto;
    border: 1px solid var(--border-light);
    border-radius: 8px;
}

.sections-table table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

.sections-table th {
    background: var(--bg-tertiary);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid var(--border-color);
}

.sections-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
    background: var(--bg-secondary);
}

.sections-table tbody tr:hover {
    background: var(--bg-tertiary);
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-type {
    background: rgba(59, 130, 246, 0.2);
    color: var(--primary-color);
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.enrollment-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    background: var(--primary-color);
    color: white;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state p {
    font-size: 1.1rem;
    margin: 0;
}

/* Responsive */
@media (max-width: 1200px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group.full-width {
        grid-column: 1;
    }
}

@media (max-width: 768px) {
    .schedule-container {
        padding: 1rem;
    }
    
    .schedule-header {
        padding: 1.5rem;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .schedule-header h1 {
        font-size: 2rem;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .time-inputs {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function updateDaySchedule(day) {
    const checkbox = document.getElementById('day_' + day.toLowerCase());
    const schedule = document.getElementById('schedule_' + day.toLowerCase());
    
    if (checkbox.checked) {
        schedule.style.display = 'block';
        // Add at least one session if none exist
        const sessionsContainer = schedule.querySelector('.sessions-container');
        if (sessionsContainer && sessionsContainer.children.length === 0) {
            addSession(day.toLowerCase());
        }
    } else {
        schedule.style.display = 'none';
        // Clear all sessions when unchecked
        const sessionsContainer = schedule.querySelector('.sessions-container');
        if (sessionsContainer) {
            sessionsContainer.innerHTML = '';
        }
    }
}

function addSession(day) {
    const schedule = document.getElementById('schedule_' + day);
    if (!schedule) return;
    
    const sessionsContainer = schedule.querySelector('.sessions-container');
    if (!sessionsContainer) return;
    
    const sessionIndex = sessionsContainer.children.length;
    const defaultSessionType = 'lecture'; // Default to lecture
    
    const sessionDiv = document.createElement('div');
    sessionDiv.className = 'session-item';
    sessionDiv.style.cssText = 'border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; background: var(--bg-secondary);';
    sessionDiv.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <strong style="color: var(--text-primary);">Session ${sessionIndex + 1}</strong>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeSession(this)" style="padding: 0.25rem 0.5rem;">
                <i class="fas fa-times"></i> Remove
            </button>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem;">
            <div>
                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">Session Type <span style="color: var(--error-color);">*</span></label>
                <select name="session_type[${day}][]" class="form-input" required>
                    <option value="lecture" ${defaultSessionType === 'lecture' ? 'selected' : ''}>Lecture</option>
                    <option value="lab" ${defaultSessionType === 'lab' ? 'selected' : ''}>Lab</option>
                    <option value="tutorial" ${defaultSessionType === 'tutorial' ? 'selected' : ''}>Tutorial</option>
                    <option value="section" ${defaultSessionType === 'section' ? 'selected' : ''}>Section</option>
                    <option value="seminar" ${defaultSessionType === 'seminar' ? 'selected' : ''}>Seminar</option>
                    <option value="workshop" ${defaultSessionType === 'workshop' ? 'selected' : ''}>Workshop</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">Start Time <span style="color: var(--error-color);">*</span></label>
                <input type="time" name="start_time[${day}][]" class="form-input" required>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">End Time <span style="color: var(--error-color);">*</span></label>
                <input type="time" name="end_time[${day}][]" class="form-input" required>
            </div>
        </div>
    `;
    
    sessionsContainer.appendChild(sessionDiv);
}

function removeSession(button) {
    const sessionItem = button.closest('.session-item');
    if (sessionItem) {
        sessionItem.remove();
        // Update session numbers
        const sessionsContainer = sessionItem.parentElement;
        if (sessionsContainer) {
            Array.from(sessionsContainer.children).forEach((item, index) => {
                const strong = item.querySelector('strong');
                if (strong) {
                    strong.textContent = `Session ${index + 1}`;
                }
            });
        }
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
        timetableBtn.classList.add('active');
        listBtn.classList.remove('active');
    } else {
        timetableView.style.display = 'none';
        listView.style.display = 'block';
        listBtn.classList.add('active');
        timetableBtn.classList.remove('active');
    }
}

let courseEntryIndex = 0;

function addCourseEntry() {
    const container = document.getElementById('coursesContainer');
    if (!container) return;
    
    const index = courseEntryIndex++;
    const courses = <?= json_encode($courses) ?>;
    const doctors = <?= json_encode($doctors) ?>;
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    const courseEntry = document.createElement('div');
    courseEntry.className = 'course-entry';
    courseEntry.dataset.courseIndex = index;
    courseEntry.style.cssText = 'border: 2px solid var(--border-color); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; background: var(--bg-secondary);';
    
    let doctorsHtml = '';
    doctors.forEach(doctor => {
        doctorsHtml += `
            <label class="checkbox-item" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s;" 
                   onmouseover="this.style.backgroundColor='rgba(59, 130, 246, 0.2)'"
                   onmouseout="this.style.backgroundColor='transparent'">
                <input type="checkbox" name="courses[${index}][doctor_ids][]" value="${doctor.doctor_id}" class="doctor-checkbox" style="width: 18px; height: 18px; cursor: pointer;">
                <div>
                    <div style="font-weight: 500; color: var(--text-primary);">${doctor.first_name} ${doctor.last_name}</div>
                    ${doctor.email ? `<div style="font-size: 0.85rem; color: var(--text-secondary);">${doctor.email}</div>` : ''}
                </div>
            </label>
        `;
    });
    
    let daysHtml = '';
    days.forEach(day => {
        const dayLower = day.toLowerCase();
        daysHtml += `
            <div class="day-option">
                <input type="checkbox" name="courses[${index}][days][]" value="${day}" 
                       id="day_${index}_${dayLower}" 
                       class="day-checkbox" 
                       onchange="updateDayScheduleForCourse(${index}, '${day}')">
                <label for="day_${index}_${dayLower}" class="day-label">
                    <span class="day-name">${day}</span>
                </label>
                <div class="day-schedule" id="schedule_${index}_${dayLower}" style="display: none;">
                    <div class="sessions-container" data-day="${dayLower}" data-course-index="${index}"></div>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addSessionForCourse(${index}, '${dayLower}')" style="margin-top: 0.5rem; width: 100%;">
                        <i class="fas fa-plus"></i> Add Session
                    </button>
                </div>
            </div>
        `;
    });
    
    let coursesHtml = '<option value="">Select Course</option>';
    courses.forEach(course => {
        coursesHtml += `<option value="${course.course_id}">${course.course_code} - ${course.name}</option>`;
    });
    
    courseEntry.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="margin: 0; color: var(--text-primary);">
                <i class="fas fa-book"></i> Course Entry ${index + 1}
            </h3>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeCourseEntry(this)" style="padding: 0.5rem 1rem;">
                <i class="fas fa-times"></i> Remove Course
            </button>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Course *</label>
                <select name="courses[${index}][course_id]" class="form-input course-select" required onchange="loadSectionsForCourse(${index}, this.value)">
                    ${coursesHtml}
                </select>
            </div>
            <div class="form-group full-width">
                <label class="form-label">Doctors (Instructors) *</label>
                <div class="checkbox-list doctors-list-${index}" style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-tertiary);">
                    ${doctorsHtml}
                </div>
            </div>
            <div class="form-group full-width">
                <label class="form-label">Section/Session Numbers *</label>
                <div class="checkbox-list sections-list-${index}" style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-tertiary);">
                    <div class="section-numbers-placeholder-${index}" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        <i class="fas fa-info-circle"></i> Select a course first to see available section numbers
                    </div>
                </div>
                <input type="text" class="new-section-input-${index} form-input" style="margin-top: 0.5rem;" placeholder="Add new section number (e.g., 001, L01, LAB01)">
                <button type="button" class="btn btn-outline" onclick="addNewSectionNumberForCourse(${index})" style="margin-top: 0.5rem; padding: 0.5rem 1rem;">
                    <i class="fas fa-plus"></i> Add Section Number
                </button>
            </div>
            <div class="form-group">
                <label class="form-label">Room *</label>
                <input type="text" name="courses[${index}][room]" class="form-input" required placeholder="e.g., A101">
            </div>
            <div class="form-group">
                <label class="form-label">Capacity *</label>
                <input type="number" name="courses[${index}][capacity]" class="form-input" required value="30" min="1">
            </div>
            <div class="form-group full-width">
                <label class="form-label">Schedule Days *</label>
                <div class="days-selector">
                    ${daysHtml}
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(courseEntry);
}

function removeCourseEntry(button) {
    const courseEntry = button.closest('.course-entry');
    if (courseEntry) {
        courseEntry.remove();
        // Renumber remaining entries
        const container = document.getElementById('coursesContainer');
        if (container) {
            Array.from(container.children).forEach((entry, index) => {
                const h3 = entry.querySelector('h3');
                if (h3) {
                    h3.innerHTML = `<i class="fas fa-book"></i> Course Entry ${index + 1}`;
                }
            });
        }
    }
}

function updateDayScheduleForCourse(courseIndex, day) {
    const checkbox = document.getElementById(`day_${courseIndex}_${day.toLowerCase()}`);
    const schedule = document.getElementById(`schedule_${courseIndex}_${day.toLowerCase()}`);
    
    if (checkbox.checked) {
        schedule.style.display = 'block';
        const sessionsContainer = schedule.querySelector('.sessions-container');
        if (sessionsContainer && sessionsContainer.children.length === 0) {
            addSessionForCourse(courseIndex, day.toLowerCase());
        }
    } else {
        schedule.style.display = 'none';
        const sessionsContainer = schedule.querySelector('.sessions-container');
        if (sessionsContainer) {
            sessionsContainer.innerHTML = '';
        }
    }
}

function addSessionForCourse(courseIndex, day) {
    const schedule = document.getElementById(`schedule_${courseIndex}_${day}`);
    if (!schedule) return;
    
    const sessionsContainer = schedule.querySelector('.sessions-container');
    if (!sessionsContainer) return;
    
    const sessionIndex = sessionsContainer.children.length;
    const defaultSessionType = 'lecture'; // Default to lecture
    
    const sessionDiv = document.createElement('div');
    sessionDiv.className = 'session-item';
    sessionDiv.style.cssText = 'border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; background: var(--bg-secondary);';
    sessionDiv.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <strong style="color: var(--text-primary);">Session ${sessionIndex + 1}</strong>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeSession(this)" style="padding: 0.25rem 0.5rem;">
                <i class="fas fa-times"></i> Remove
            </button>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem;">
            <div>
                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">Session Type <span style="color: var(--error-color);">*</span></label>
                <select name="courses[${courseIndex}][session_type][${day}][]" class="form-input" required>
                    <option value="lecture" ${defaultSessionType === 'lecture' ? 'selected' : ''}>Lecture</option>
                    <option value="lab" ${defaultSessionType === 'lab' ? 'selected' : ''}>Lab</option>
                    <option value="tutorial" ${defaultSessionType === 'tutorial' ? 'selected' : ''}>Tutorial</option>
                    <option value="section" ${defaultSessionType === 'section' ? 'selected' : ''}>Section</option>
                    <option value="seminar" ${defaultSessionType === 'seminar' ? 'selected' : ''}>Seminar</option>
                    <option value="workshop" ${defaultSessionType === 'workshop' ? 'selected' : ''}>Workshop</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">Start Time <span style="color: var(--error-color);">*</span></label>
                <input type="time" name="courses[${courseIndex}][start_time][${day}][]" class="form-input" required>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500;">End Time <span style="color: var(--error-color);">*</span></label>
                <input type="time" name="courses[${courseIndex}][end_time][${day}][]" class="form-input" required>
            </div>
        </div>
    `;
    
    sessionsContainer.appendChild(sessionDiv);
}

function loadSectionsForCourse(courseIndex, courseId) {
    if (!courseId) return;
    
    const semester = document.getElementById('semester')?.value;
    const academicYear = document.getElementById('academic_year')?.value;
    
    fetch(`<?= htmlspecialchars($url('it/schedule')) ?>?action=get_sections&course_id=${courseId}&semester=${semester}&year=${academicYear}`)
        .then(response => response.json())
        .then(data => {
            const sectionsList = document.querySelector(`.sections-list-${courseIndex}`);
            if (!sectionsList) return;
            
            const placeholder = sectionsList.querySelector(`.section-numbers-placeholder-${courseIndex}`);
            if (placeholder) placeholder.remove();
            
            if (data.sections && data.sections.length > 0) {
                let html = '';
                data.sections.forEach(section => {
                    html += `
                        <label class="checkbox-item" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s;" 
                               onmouseover="this.style.backgroundColor='rgba(59, 130, 246, 0.2)'"
                               onmouseout="this.style.backgroundColor='transparent'">
                            <input type="checkbox" name="courses[${courseIndex}][section_numbers][]" value="${section}" class="section-checkbox" style="width: 18px; height: 18px; cursor: pointer;">
                            <div style="font-weight: 500; color: var(--text-primary);">${section}</div>
                        </label>
                    `;
                });
                sectionsList.innerHTML = html;
            } else {
                sectionsList.innerHTML = `<div class="section-numbers-placeholder-${courseIndex}" style="text-align: center; padding: 2rem; color: var(--text-muted);"><i class="fas fa-info-circle"></i> No existing sections found. Add new section numbers below.</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading sections:', error);
        });
}

function addNewSectionNumberForCourse(courseIndex) {
    const input = document.querySelector(`.new-section-input-${courseIndex}`);
    if (!input) return;
    
    const sectionNumber = input.value.trim();
    if (!sectionNumber) {
        alert('Please enter a section number');
        return;
    }
    
    const sectionsList = document.querySelector(`.sections-list-${courseIndex}`);
    if (!sectionsList) return;
    
    const existingCheckboxes = sectionsList.querySelectorAll(`input[name="courses[${courseIndex}][section_numbers][]"]`);
    for (let checkbox of existingCheckboxes) {
        if (checkbox.value === sectionNumber) {
            alert('This section number already exists');
            input.value = '';
            return;
        }
    }
    
    const placeholder = sectionsList.querySelector(`.section-numbers-placeholder-${courseIndex}`);
    if (placeholder) placeholder.remove();
    
    const label = document.createElement('label');
    label.className = 'checkbox-item';
    label.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s;';
    label.onmouseover = function() { this.style.backgroundColor = 'rgba(59, 130, 246, 0.2)'; };
    label.onmouseout = function() { this.style.backgroundColor = 'transparent'; };
    
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.name = `courses[${courseIndex}][section_numbers][]`;
    checkbox.value = sectionNumber;
    checkbox.className = 'section-checkbox';
    checkbox.style.cssText = 'width: 18px; height: 18px; cursor: pointer;';
    checkbox.checked = true;
    
    const div = document.createElement('div');
    div.style.cssText = 'font-weight: 500; color: var(--text-primary);';
    div.textContent = sectionNumber;
    
    label.appendChild(checkbox);
    label.appendChild(div);
    sectionsList.appendChild(label);
    input.value = '';
}

document.addEventListener('DOMContentLoaded', function() {
    toggleView('timetable');
    switchMode('single');
    
    // Initialize with one course entry
    addCourseEntry();
    
    // Initialize quick schedule mode with one row if needed
    const quickForm = document.getElementById('quickEntryForm');
    if (quickForm && quickForm.style.display !== 'none' && document.getElementById('quickScheduleRows').children.length === 0) {
        addQuickScheduleRow();
    }
    
    // Add direct event listener to submit button as backup
    const submitBtn = document.getElementById('submitScheduleBtn');
    const singleForm = document.getElementById('singleEntryForm');
    
    if (submitBtn && singleForm) {
        submitBtn.addEventListener('click', function(e) {
            console.log('Submit button clicked directly');
            // Let the form's onsubmit handle it, but ensure it's called
            if (!singleForm.onsubmit || singleForm.onsubmit(e) !== false) {
                validateSingleForm(e);
            }
        });
    }
    
    // Also ensure form submission is handled
    if (singleForm) {
        singleForm.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            validateSingleForm(e);
        });
    }
});

function switchMode(mode) {
    const singleForm = document.getElementById('singleEntryForm');
    const quickForm = document.getElementById('quickEntryForm');
    const bulkForm = document.getElementById('bulkEntryForm');
    const singleBtn = document.getElementById('singleModeBtn');
    const quickBtn = document.getElementById('quickModeBtn');
    const bulkBtn = document.getElementById('bulkModeBtn');
    
    // Hide all forms
    singleForm.style.display = 'none';
    quickForm.style.display = 'none';
    bulkForm.style.display = 'none';
    
    // Remove active class from all buttons
    singleBtn.classList.remove('active');
    quickBtn.classList.remove('active');
    bulkBtn.classList.remove('active');
    
    if (mode === 'single') {
        singleForm.style.display = 'block';
        singleBtn.classList.add('active');
    } else if (mode === 'quick') {
        quickForm.style.display = 'block';
        quickBtn.classList.add('active');
        // Initialize with one row if empty
        if (document.getElementById('quickScheduleRows').children.length === 0) {
            addQuickScheduleRow();
        }
    } else {
        bulkForm.style.display = 'block';
        bulkBtn.classList.add('active');
    }
}

function updateBulkCourseFields() {
    const selectedCourses = Array.from(document.querySelectorAll('.bulk-course-checkbox:checked'));
    const container = document.getElementById('bulkCourseFields');
    
    if (selectedCourses.length === 0) {
        container.innerHTML = '<p class="bulk-placeholder"><i class="fas fa-info-circle"></i> Select courses above to configure their schedule details</p>';
        return;
    }
    
    const courses = <?= json_encode($courses) ?>;
    const doctors = <?= json_encode($doctors) ?>;
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    let html = '<div style="display: grid; gap: 1.5rem;">';
    html += '<h3 style="margin-bottom: 1rem; color: var(--text-primary); font-size: 1.25rem;">Course Configuration</h3>';
    
    selectedCourses.forEach((checkbox) => {
        const courseId = checkbox.value;
        const course = courses.find(c => c.course_id == courseId);
        if (!course) return;
        
        html += `<div style="border: 1px solid var(--border-light); border-radius: 8px; padding: 1.5rem; background: var(--bg-primary);">`;
        html += `<h4 style="margin: 0 0 1rem 0; color: var(--primary-color); font-size: 1.1rem;"><i class="fas fa-book"></i> ${course.course_code} - ${course.name}</h4>`;
        html += `<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">`;
        
        html += `<div class="form-group">`;
        html += `<label class="form-label">Doctor (Instructor) *</label>`;
        html += `<select name="bulk_doctor[${courseId}]" class="form-input" required>`;
        html += `<option value="">Select Doctor</option>`;
        doctors.forEach(doctor => {
            html += `<option value="${doctor.doctor_id}">${doctor.first_name} ${doctor.last_name}</option>`;
        });
        html += `</select></div>`;
        
        html += `<div class="form-group">`;
        html += `<label class="form-label">Section/Session Number *</label>`;
        html += `<input type="text" name="bulk_section[${courseId}]" class="form-input" required placeholder="e.g., 001, L01">`;
        html += `</div>`;
        
        html += `<div class="form-group">`;
        html += `<label class="form-label">Session Type *</label>`;
        html += `<select name="bulk_session_type[${courseId}]" class="form-input" required>`;
        html += `<option value="lecture">Lecture</option>`;
        html += `<option value="lab">Lab</option>`;
        html += `<option value="tutorial">Tutorial</option>`;
        html += `<option value="section">Section</option>`;
        html += `<option value="seminar">Seminar</option>`;
        html += `<option value="workshop">Workshop</option>`;
        html += `</select></div>`;
        
        html += `<div class="form-group">`;
        html += `<label class="form-label">Room *</label>`;
        html += `<input type="text" name="bulk_room[${courseId}]" class="form-input" required placeholder="e.g., A101">`;
        html += `</div>`;
        
        html += `<div class="form-group">`;
        html += `<label class="form-label">Capacity *</label>`;
        html += `<input type="number" name="bulk_capacity[${courseId}]" class="form-input" required value="30" min="1">`;
        html += `</div>`;
        
        html += `</div>`;
        
        html += `<div style="margin-top: 1rem;">`;
        html += `<label class="form-label">Time Slots for Selected Days *</label>`;
        html += `<div style="display: grid; gap: 0.75rem; margin-top: 0.5rem;">`;
        
        days.forEach(day => {
            const dayLower = day.toLowerCase();
            html += `<div id="bulk_time_${dayLower}_${courseId}" style="display: none; padding: 0.75rem; background: var(--bg-secondary); border-radius: 6px; border: 1px solid var(--border-light);">`;
            html += `<div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">`;
            html += `<strong style="min-width: 100px; color: var(--text-primary);">${day}:</strong>`;
            html += `<input type="time" name="bulk_start_time[${courseId}][${day}]" class="form-input" style="flex: 1; padding: 0.5rem;" placeholder="Start Time">`;
            html += `<span style="color: var(--text-secondary);">to</span>`;
            html += `<input type="time" name="bulk_end_time[${courseId}][${day}]" class="form-input" style="flex: 1; padding: 0.5rem;" placeholder="End Time">`;
            html += `</div></div>`;
        });
        
        html += `</div></div></div>`;
    });
    
    html += '</div>';
    container.innerHTML = html;
    updateBulkTimeSlots();
}

function updateBulkDaySchedule(day) {
    updateBulkTimeSlots();
}

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
                const startInput = timeDiv.querySelector(`input[name="bulk_start_time[${courseId}][${day}]"]`);
                const endInput = timeDiv.querySelector(`input[name="bulk_end_time[${courseId}][${day}]"]`);
                if (startInput) startInput.required = true;
                if (endInput) endInput.required = true;
            }
        });
    });
    
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

// Load section numbers when course is selected
document.getElementById('course_id')?.addEventListener('change', function() {
    const courseId = this.value;
    const semester = document.getElementById('semester')?.value || '<?= $currentSemester ?>';
    const academicYear = document.getElementById('academic_year')?.value || '<?= $currentYear ?>';
    
    if (!courseId) {
        document.getElementById('sectionsList').innerHTML = '<div id="sectionNumbersPlaceholder" style="text-align: center; padding: 2rem; color: var(--text-muted);"><i class="fas fa-info-circle"></i> Select a course first to see available section numbers</div>';
        return;
    }
    
    // Load section numbers via AJAX
    fetch(`<?= htmlspecialchars($url('it/schedule')) ?>?action=get_sections&course_id=${courseId}&semester=${semester}&year=${academicYear}`)
        .then(response => response.json())
        .then(data => {
            const sectionsList = document.getElementById('sectionsList');
            if (data.sections && data.sections.length > 0) {
                let html = '';
                data.sections.forEach(section => {
                    html += `<label class="checkbox-item" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s;" 
                               onmouseover="this.style.backgroundColor='rgba(59, 130, 246, 0.2)'"
                               onmouseout="this.style.backgroundColor='transparent'">
                                <input type="checkbox" name="section_numbers[]" value="${section}" class="section-checkbox" style="width: 18px; height: 18px; cursor: pointer;">
                                <div style="font-weight: 500; color: var(--text-primary);">${section}</div>
                            </label>`;
                });
                sectionsList.innerHTML = html;
            } else {
                sectionsList.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-muted);"><i class="fas fa-info-circle"></i> No existing sections found. Add new section numbers below.</div>';
            }
        })
        .catch(error => {
            console.error('Error loading sections:', error);
            document.getElementById('sectionsList').innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--error-color);"><i class="fas fa-exclamation-circle"></i> Error loading sections</div>';
        });
});

function addNewSectionNumber() {
    const newSectionInput = document.getElementById('new_section_number');
    const sectionNumber = newSectionInput.value.trim();
    
    if (!sectionNumber) {
        alert('Please enter a section number');
        return;
    }
    
    const sectionsList = document.getElementById('sectionsList');
    const existingCheckboxes = sectionsList.querySelectorAll('input[name="section_numbers[]"]');
    
    // Check if already exists
    for (let checkbox of existingCheckboxes) {
        if (checkbox.value === sectionNumber) {
            alert('This section number already exists');
            newSectionInput.value = '';
            return;
        }
    }
    
    // Add new checkbox
    const label = document.createElement('label');
    label.className = 'checkbox-item';
    label.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s;';
    label.onmouseover = function() { this.style.backgroundColor = 'rgba(59, 130, 246, 0.2)'; };
    label.onmouseout = function() { this.style.backgroundColor = 'transparent'; };
    
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.name = 'section_numbers[]';
    checkbox.value = sectionNumber;
    checkbox.className = 'section-checkbox';
    checkbox.style.cssText = 'width: 18px; height: 18px; cursor: pointer;';
    checkbox.checked = true;
    
    const div = document.createElement('div');
    div.style.cssText = 'font-weight: 500; color: var(--text-primary);';
    div.textContent = sectionNumber;
    
    label.appendChild(checkbox);
    label.appendChild(div);
    
    // Remove placeholder if exists
    const placeholder = sectionsList.querySelector('#sectionNumbersPlaceholder');
    if (placeholder) {
        placeholder.remove();
    }
    
    sectionsList.appendChild(label);
    newSectionInput.value = '';
}

function validateSingleForm(event) {
    console.log('=== validateSingleForm called ===');
    
    // CRITICAL: Always prevent default form submission
    if (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
    }
    
    const form = document.getElementById('singleEntryForm');
    if (!form) {
        console.error('Form not found!');
        alert('Form not found. Please refresh the page.');
        return false;
    }
    
    console.log('Form found, validating...');
    
    // Validate semester and academic year
    const semester = form.querySelector('#semester')?.value;
    const academicYear = form.querySelector('#academic_year')?.value?.trim();
    if (!semester || !academicYear) {
        alert('Please select semester and enter academic year.');
        return false;
    }
    
    // Validate all course entries
    const courseEntries = form.querySelectorAll('.course-entry');
    if (courseEntries.length === 0) {
        alert('Please add at least one course entry.');
        return false;
    }
    
    let allCoursesValid = true;
    let courseErrors = [];
    
    courseEntries.forEach((courseEntry, arrayIndex) => {
        // Get the actual course index from the form field name
        const courseSelect = courseEntry.querySelector('select[name^="courses["][name$="][course_id]"]');
        if (!courseSelect) {
            allCoursesValid = false;
            courseErrors.push(`Course Entry ${arrayIndex + 1}: Course select field not found`);
            return;
        }
        
        // Extract index from name like "courses[0][course_id]"
        const nameMatch = courseSelect.name.match(/courses\[(\d+)\]/);
        const courseIndex = nameMatch ? nameMatch[1] : arrayIndex;
        const courseId = courseSelect.value;
        
        if (!courseId) {
            allCoursesValid = false;
            courseErrors.push(`Course Entry ${arrayIndex + 1}: Please select a course.`);
            return;
        }
        
        const selectedDoctors = Array.from(courseEntry.querySelectorAll(`input[name="courses[${courseIndex}][doctor_ids][]"]:checked`));
        if (selectedDoctors.length === 0) {
            allCoursesValid = false;
            courseErrors.push(`Course Entry ${arrayIndex + 1}: Please select at least one doctor.`);
            return;
        }
        
        const selectedSections = Array.from(courseEntry.querySelectorAll(`input[name="courses[${courseIndex}][section_numbers][]"]:checked`));
        if (selectedSections.length === 0) {
            allCoursesValid = false;
            courseErrors.push(`Course Entry ${arrayIndex + 1}: Please select at least one section number.`);
            return;
        }
        
        const room = courseEntry.querySelector(`input[name="courses[${courseIndex}][room]"]`)?.value?.trim();
        if (!room) {
            allCoursesValid = false;
            courseErrors.push(`Course Entry ${arrayIndex + 1}: Please enter a room number.`);
            return;
        }
        
        const selectedDays = Array.from(courseEntry.querySelectorAll(`input[name="courses[${courseIndex}][days][]"]:checked`));
        if (selectedDays.length === 0) {
            allCoursesValid = false;
            courseErrors.push(`Course Entry ${arrayIndex + 1}: Please select at least one day.`);
            return;
        }
        
        // Validate sessions for each day
        selectedDays.forEach(dayCheckbox => {
            const day = dayCheckbox.value.toLowerCase();
            const sessionsContainer = courseEntry.querySelector(`#schedule_${courseIndex}_${day} .sessions-container`);
            
            if (!sessionsContainer || sessionsContainer.children.length === 0) {
                allCoursesValid = false;
                courseErrors.push(`Course Entry ${arrayIndex + 1} - ${dayCheckbox.value}: No sessions added`);
                return;
            }
            
            const sessionItems = sessionsContainer.querySelectorAll('.session-item');
            sessionItems.forEach((sessionItem, sessionIndex) => {
                const startTime = sessionItem.querySelector(`input[name="courses[${courseIndex}][start_time][${day}][]"]`);
                const endTime = sessionItem.querySelector(`input[name="courses[${courseIndex}][end_time][${day}][]"]`);
                const sessionType = sessionItem.querySelector(`select[name="courses[${courseIndex}][session_type][${day}][]"]`);
                
                if (!startTime || !startTime.value || !endTime || !endTime.value || !sessionType || !sessionType.value) {
                    allCoursesValid = false;
                    courseErrors.push(`Course Entry ${arrayIndex + 1} - ${dayCheckbox.value} - Session ${sessionIndex + 1}: Incomplete`);
                } else if (startTime.value >= endTime.value) {
                    allCoursesValid = false;
                    courseErrors.push(`Course Entry ${arrayIndex + 1} - ${dayCheckbox.value} - Session ${sessionIndex + 1}: End time must be after start time`);
                }
            });
        });
    });
    
    if (!allCoursesValid) {
        alert('Please fix the following errors:\n\n' + courseErrors.join('\n'));
        return false;
    }
    
    console.log('Validation passed, submitting form...');
    
    // Submit form via AJAX
    const formData = new FormData(form);
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]') || document.getElementById('submitScheduleBtn');
    if (!submitBtn) {
        console.error('Submit button not found!');
        alert('Submit button not found. Please refresh the page.');
        return false;
    }
    
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    console.log('Sending AJAX request to:', form.action);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response received:', response.status, response.statusText);
        
        // Check if response is JSON (AJAX) or HTML (redirect)
        const contentType = response.headers.get('content-type') || '';
        console.log('Content-Type:', contentType);
        
        if (contentType.includes('application/json')) {
            return response.json().then(data => {
                console.log('JSON response:', data);
                return { success: data.success, error: data.error };
            });
        } else {
            // If redirect happened, follow it
            if (response.redirected || response.status === 302 || response.status === 301) {
                console.log('Redirect detected');
                // Parse URL for success/error params
                const url = new URL(response.url);
                return {
                    success: url.searchParams.get('success') ? decodeURIComponent(url.searchParams.get('success')) : null,
                    error: url.searchParams.get('error') ? decodeURIComponent(url.searchParams.get('error')) : null
                };
            }
            // Try to parse HTML response
            return response.text().then(html => {
                console.log('HTML response received, parsing...');
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const alertDiv = doc.querySelector('.alert-success, .alert-error');
                if (alertDiv) {
                    return {
                        success: alertDiv.classList.contains('alert-success') ? alertDiv.textContent.trim() : null,
                        error: alertDiv.classList.contains('alert-error') ? alertDiv.textContent.trim() : null
                    };
                }
                return { success: null, error: null };
            });
        }
    })
    .then(data => {
        // Reset button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.schedule-container > .alert, .schedule-form-section .alert');
        existingAlerts.forEach(alert => alert.remove());
        
        // Show success/error message
        if (data && (data.success || data.error)) {
            const messageDiv = document.createElement('div');
            messageDiv.className = data.success ? 'alert alert-success' : 'alert alert-error';
            const icon = data.success ? 'check-circle' : 'exclamation-circle';
            const message = data.success || data.error;
            messageDiv.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;
            messageDiv.style.marginBottom = '1.5rem';
            
            // Insert at the top of schedule container
            const scheduleContainer = document.querySelector('.schedule-container');
            const firstChild = scheduleContainer.firstElementChild;
            scheduleContainer.insertBefore(messageDiv, firstChild);
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // If success, reload after 2 seconds to show new entries
            if (data.success) {
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        } else {
            // If no clear response, reload anyway to check
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        console.error('Error details:', error.message, error.stack);
        
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        
        // Show error notification
        const existingAlerts = document.querySelectorAll('.schedule-container > .alert');
        existingAlerts.forEach(alert => alert.remove());
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'alert alert-error';
        messageDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> An error occurred while saving the schedule: ${error.message}. Please check the browser console for details.`;
        messageDiv.style.marginBottom = '1.5rem';
        
        const scheduleContainer = document.querySelector('.schedule-container');
        if (scheduleContainer) {
            const firstChild = scheduleContainer.firstElementChild;
            scheduleContainer.insertBefore(messageDiv, firstChild);
        }
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    return false;
}

// Quick Schedule Functions
let quickScheduleRowCount = 0;
const courses = <?= json_encode($courses) ?>;
const doctors = <?= json_encode($doctors) ?>;

function addQuickScheduleRow() {
    const tbody = document.getElementById('quickScheduleRows');
    const rowIndex = quickScheduleRowCount++;
    
    const row = document.createElement('tr');
    row.className = 'quick-schedule-row';
    row.style.cssText = 'background: var(--bg-primary); border-bottom: 1px solid var(--border-color);';
    row.innerHTML = `
        <td style="padding: 0.75rem;">
            <select name="quick_course[${rowIndex}]" class="form-input quick-course-select" required style="width: 100%;">
                <option value="">Select Course</option>
                ${courses.map(c => `<option value="${c.course_id}">${c.course_code} - ${c.name}</option>`).join('')}
            </select>
        </td>
        <td style="padding: 0.75rem;">
            <select name="quick_doctor[${rowIndex}]" class="form-input" required style="width: 100%;">
                <option value="">Select Doctor</option>
                ${doctors.map(d => `<option value="${d.doctor_id}">${d.first_name} ${d.last_name}</option>`).join('')}
            </select>
        </td>
        <td style="padding: 0.75rem;">
            <input type="text" name="quick_section[${rowIndex}]" class="form-input" required placeholder="e.g., 001" style="width: 100%;">
        </td>
        <td style="padding: 0.75rem;">
            <select name="quick_session_type[${rowIndex}]" class="form-input" style="width: 100%;">
                <option value="lecture">Lecture</option>
                <option value="lab">Lab</option>
                <option value="tutorial">Tutorial</option>
                <option value="section">Section</option>
                <option value="seminar">Seminar</option>
                <option value="workshop">Workshop</option>
            </select>
        </td>
        <td style="padding: 0.75rem;">
            <input type="text" name="quick_room[${rowIndex}]" class="form-input" required placeholder="e.g., A101" style="width: 100%;">
        </td>
        <td style="padding: 0.75rem;">
            <input type="time" name="quick_start_time[${rowIndex}]" class="form-input" required style="width: 100%;">
        </td>
        <td style="padding: 0.75rem;">
            <input type="time" name="quick_end_time[${rowIndex}]" class="form-input" required style="width: 100%;">
        </td>
        <td style="padding: 0.75rem;">
            <input type="number" name="quick_capacity[${rowIndex}]" class="form-input" value="30" min="1" style="width: 100%;">
        </td>
        <td style="padding: 0.75rem; text-align: center;">
            <button type="button" class="btn btn-outline" onclick="removeQuickScheduleRow(this)" style="padding: 0.5rem; min-width: auto;">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
}

function removeQuickScheduleRow(button) {
    const row = button.closest('tr');
    row.remove();
}

function validateQuickForm(event) {
        event.preventDefault();
    
    const form = document.getElementById('quickEntryForm');
    const selectedDays = Array.from(form.querySelectorAll('input[name="quick_days[]"]:checked'));
    const rows = form.querySelectorAll('.quick-schedule-row');
    
    if (selectedDays.length === 0) {
        alert('Please select at least one day for the schedule.');
        return false;
    }
    
    if (rows.length === 0) {
        alert('Please add at least one course to schedule.');
        return false;
    }
    
    // Validate each row
    let allValid = true;
    let invalidRows = [];
    
    rows.forEach((row, index) => {
        const course = row.querySelector('select[name^="quick_course"]')?.value;
        const doctor = row.querySelector('select[name^="quick_doctor"]')?.value;
        const section = row.querySelector('input[name^="quick_section"]')?.value;
        const room = row.querySelector('input[name^="quick_room"]')?.value;
        const startTime = row.querySelector('input[name^="quick_start_time"]')?.value;
        const endTime = row.querySelector('input[name^="quick_end_time"]')?.value;
        
        if (!course || !doctor || !section || !room || !startTime || !endTime) {
            allValid = false;
            invalidRows.push(index + 1);
        }
        
        // Validate time
        if (startTime && endTime && startTime >= endTime) {
            allValid = false;
            invalidRows.push(index + 1);
        }
    });
    
    if (!allValid) {
        alert(`Please fill in all required fields for all rows. Invalid rows: ${invalidRows.join(', ')}`);
        return false;
    }
    
    // Submit form via AJAX
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            return response.json();
        } else {
            const url = new URL(response.url);
            return {
                success: url.searchParams.get('success') ? decodeURIComponent(url.searchParams.get('success')) : null,
                error: url.searchParams.get('error') ? decodeURIComponent(url.searchParams.get('error')) : null
            };
        }
    })
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        
        const existingAlerts = document.querySelectorAll('.schedule-container > .alert');
        existingAlerts.forEach(alert => alert.remove());
        
        if (data && (data.success || data.error)) {
            const messageDiv = document.createElement('div');
            messageDiv.className = data.success ? 'alert alert-success' : 'alert alert-error';
            messageDiv.innerHTML = `<i class="fas fa-${data.success ? 'check-circle' : 'exclamation-circle'}"></i> ${data.success || data.error}`;
            messageDiv.style.marginBottom = '1.5rem';
            
            const scheduleContainer = document.querySelector('.schedule-container');
            scheduleContainer.insertBefore(messageDiv, scheduleContainer.firstElementChild);
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            if (data.success) {
                setTimeout(() => location.reload(), 2000);
            }
        } else {
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        alert('An error occurred while saving the schedule. Please try again.');
    });
    
    return false;
}

// Database check function
function checkDatabaseTables() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    
    fetch('<?= htmlspecialchars($url('it/schedule')) ?>?action=check_database', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.exists) {
            alert('✓ Database tables are set up correctly!\n\nTables found:\n' + data.tables.join('\n'));
        } else {
            const createTables = confirm('⚠ Some database tables are missing!\n\nMissing: ' + data.missing.join(', ') + '\n\nWould you like to create them now?');
            if (createTables) {
                createDatabaseTables();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Error checking database: ' + error.message);
    });
}

function createDatabaseTables() {
    const btn = document.querySelector('button[onclick="checkDatabaseTables()"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    fetch('<?= htmlspecialchars($url('it/schedule')) ?>?action=create_tables', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✓ Database tables created successfully!');
            location.reload();
        } else {
            alert('✗ Error creating tables: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Error creating database tables: ' + error.message);
    });
}

function clearAllSchedules() {
    if (!confirm('⚠️ WARNING: This will permanently delete ALL schedules from the schedule table!\n\nThis action cannot be undone.\n\nAre you sure you want to continue?')) {
        return;
    }
    
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
    
    fetch('<?= htmlspecialchars($url('it/schedule')) ?>?action=clear_all_schedules', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✓ ' + data.message + '\n\nDeleted: ' + data.deleted_count + ' schedule(s)\nRemaining: ' + data.remaining_count);
            // Reload the page to refresh the schedule list
            window.location.reload();
        } else {
            alert('✗ Error: ' + (data.error || 'Failed to clear schedules'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Error clearing schedules: ' + error.message);
    });
}

function runMigration() {
    if (!confirm('This will:\n1. Create the schedule table\n2. Remove the sections table\n\nContinue?')) {
        return;
    }
    
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running Migration...';
    
    fetch('<?= htmlspecialchars($url('it/schedule')) ?>?action=run_migration', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            let message = '✓ Migration completed successfully!\n\n';
            if (data.table_exists) {
                message += 'Table: schedule\n';
                message += 'Columns: ' + (data.columns ? data.columns.join(', ') : 'N/A') + '\n';
                message += 'Statements executed: ' + (data.executed || 0);
            }
            if (data.sections_removed !== undefined) {
                message += '\n\nSections table: ' + (data.sections_removed ? '✓ Removed' : '⚠ Still exists');
            }
            if (data.errors && data.errors.length > 0) {
                message += '\n\nWarnings:\n' + data.errors.join('\n');
            }
            alert(message);
            location.reload();
        } else {
            alert('✗ Migration failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Error running migration: ' + error.message);
    });
}
</script>
