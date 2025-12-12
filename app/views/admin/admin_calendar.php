<?php
// Ensure variables exist with defaults
$tableExists = $tableExists ?? false;
$eventsThisMonth = $eventsThisMonth ?? 0;
$examsScheduled = $examsScheduled ?? 0;
$conflicts = $conflicts ?? 0;
$peopleAffected = $peopleAffected ?? 0;
$events = $events ?? [];
$upcomingEvents = $upcomingEvents ?? [];
$eventsByDay = $eventsByDay ?? [];
$departments = $departments ?? [];
$currentMonth = $currentMonth ?? (int)date('n');
$currentYear = $currentYear ?? (int)date('Y');
$monthName = $monthName ?? date('F');
$daysInMonth = $daysInMonth ?? date('t');
$dayOfWeek = $dayOfWeek ?? date('w');
$firstDay = $firstDay ?? mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$search = $search ?? '';
$eventTypeFilter = $eventTypeFilter ?? '';
$departmentFilter = $departmentFilter ?? '';
$monthFilter = $monthFilter ?? '';
$message = $message ?? null;
$messageType = $messageType ?? 'info';
$editEvent = $editEvent ?? null;
?>

<div class="admin-container">
    <div class="admin-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-calendar-alt"></i> Calendar Management</h1>
               
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('admin/calendar')) ?>" class="btn btn-outline">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
                <button class="btn btn-primary" onclick="createEvent()">
                    <i class="fas fa-plus"></i> Create Event
                </button>
            </div>
        </div>
    </div>

    <div class="admin-content">
        <!-- Error Message if table doesn't exist -->
        <?php if (!$tableExists): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem; padding: 2rem; border-radius: 8px; background-color: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--error-color);">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: var(--error-color);"></i>
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0; color: var(--error-color);">Calendar Events Table Not Found</h3>
                        <p style="margin: 0; color: var(--text-secondary);">The calendar_events table doesn't exist yet. Please create it in your database to use this feature.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : ($messageType === 'warning' ? 'warning' : 'info')) ?>" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Calendar Overview -->
        <section class="calendar-overview" style="margin-bottom: 2rem;">
            <div class="grid grid-4">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($eventsThisMonth) ?></div>
                    <div style="color: var(--text-secondary);">This Month</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($examsScheduled) ?></div>
                    <div style="color: var(--text-secondary);">Exams Scheduled</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--warning-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($conflicts) ?></div>
                    <div style="color: var(--text-secondary);">Conflicts</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--accent-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($peopleAffected) ?></div>
                    <div style="color: var(--text-secondary);">People Affected</div>
                </div>
            </div>
        </section>

        <!-- Calendar Filter -->
        <section class="calendar-filter" style="margin-bottom: 2rem;">
            <div class="card">
                <form method="GET" action="<?= htmlspecialchars($url('admin/calendar')) ?>" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($currentMonth) ?>">
                    <input type="hidden" name="year" value="<?= htmlspecialchars($currentYear) ?>">
                    <div style="flex: 1; min-width: 200px;">
                        <select name="eventType" class="form-input" onchange="this.form.submit()">
                            <option value="">All Event Types</option>
                            <option value="exam" <?= $eventTypeFilter === 'exam' ? 'selected' : '' ?>>Exam</option>
                            <option value="assignment" <?= $eventTypeFilter === 'assignment' ? 'selected' : '' ?>>Assignment Due</option>
                            <option value="holiday" <?= $eventTypeFilter === 'holiday' ? 'selected' : '' ?>>Holiday</option>
                            <option value="meeting" <?= $eventTypeFilter === 'meeting' ? 'selected' : '' ?>>Meeting</option>
                            <option value="university_event" <?= $eventTypeFilter === 'university_event' ? 'selected' : '' ?>>University Event</option>
                            <option value="other" <?= $eventTypeFilter === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <select name="department" class="form-input" onchange="this.form.submit()">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" <?= $departmentFilter === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?= htmlspecialchars($url('admin/calendar')) ?>" class="btn btn-outline">Reset</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- Calendar View -->
        <section class="calendar-view">
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <h2 class="card-title">
                        <i class="fas fa-calendar-alt" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        <?= htmlspecialchars($monthName . ' ' . $currentYear) ?> Calendar
                    </h2>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-outline" onclick="previousMonth()">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="btn btn-outline" onclick="nextMonth()">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="btn btn-primary" onclick="goToToday()">
                            <i class="fas fa-calendar-day"></i> Today
                        </button>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="calendar-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background-color: var(--border-color);">
                    <!-- Calendar Headers -->
                    <div style="background-color: var(--surface-color); padding: 1rem; text-align: center; font-weight: 600; color: var(--text-primary);">Sun</div>
                    <div style="background-color: var(--surface-color); padding: 1rem; text-align: center; font-weight: 600; color: var(--text-primary);">Mon</div>
                    <div style="background-color: var(--surface-color); padding: 1rem; text-align: center; font-weight: 600; color: var(--text-primary);">Tue</div>
                    <div style="background-color: var(--surface-color); padding: 1rem; text-align: center; font-weight: 600; color: var(--text-primary);">Wed</div>
                    <div style="background-color: var(--surface-color); padding: 1rem; text-align: center; font-weight: 600; color: var(--text-primary);">Thu</div>
                    <div style="background-color: var(--surface-color); padding: 1rem; text-align: center; font-weight: 600; color: var(--text-primary);">Fri</div>
                    <div style="background-color: var(--surface-color); padding: 1rem; text-align: center; font-weight: 600; color: var(--text-primary);">Sat</div>

                    <!-- Calendar Days -->
                    <?php
                    // Event type colors
                    $eventColors = [
                        'exam' => 'var(--warning-color)',
                        'assignment' => 'var(--error-color)',
                        'holiday' => 'var(--success-color)',
                        'meeting' => 'var(--accent-color)',
                        'university_event' => 'var(--primary-color)',
                        'other' => 'var(--text-secondary)'
                    ];
                    
                    // Print empty cells for days before the first day of the month
                    for ($i = 0; $i < $dayOfWeek; $i++) {
                        echo '<div style="background-color: var(--surface-color); padding: 0.5rem; min-height: 120px; border: 1px solid var(--border-color);"></div>';
                    }
                    
                    // Print days of the month
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $isToday = ($day == date('j') && $currentMonth == date('n') && $currentYear == date('Y'));
                        $dayEvents = isset($eventsByDay[$day]) ? $eventsByDay[$day] : [];
                        
                        echo '<div style="background-color: ' . ($isToday ? 'var(--primary-color)' : 'var(--surface-color)') . '; padding: 0.5rem; min-height: 120px; border: 1px solid var(--border-color);">';
                        echo '<div style="font-weight: 600; margin-bottom: 0.5rem; color: ' . ($isToday ? 'white' : 'var(--text-primary)') . ';">' . $day . '</div>';
                        
                        // Display events for this day (limit to 3 visible)
                        $displayed = 0;
                        foreach (array_slice($dayEvents, 0, 3) as $event) {
                            $eventType = $event['event_type'] ?? 'other';
                            $color = $eventColors[$eventType] ?? 'var(--text-secondary)';
                            $title = htmlspecialchars(substr($event['title'] ?? 'Event', 0, 20));
                            $fullTitle = htmlspecialchars($event['title'] ?? 'Event');
                            echo '<div style="background-color: ' . $color . '; color: white; padding: 0.25rem; border-radius: 4px; font-size: 0.8rem; margin-bottom: 0.25rem; cursor: pointer;" onclick="viewEvent(' . ($event['id'] ?? 0) . ')" title="' . $fullTitle . '">' . $title . '</div>';
                            $displayed++;
                        }
                        
                        if (count($dayEvents) > 3) {
                            echo '<div style="font-size: 0.7rem; color: ' . ($isToday ? 'white' : 'var(--text-secondary)') . ';">+' . (count($dayEvents) - 3) . ' more</div>';
                        }
                        
                        echo '</div>';
                    }
                    
                    // Print empty cells for days after the last day of the month
                    $lastDayOfWeek = ($dayOfWeek + $daysInMonth) % 7;
                    if ($lastDayOfWeek > 0) {
                        for ($i = $lastDayOfWeek; $i < 7; $i++) {
                            echo '<div style="background-color: var(--surface-color); padding: 0.5rem; min-height: 120px; border: 1px solid var(--border-color);"></div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- Upcoming Events -->
        <section class="upcoming-events" style="margin-top: 2rem;">
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <h2 class="card-title">
                        <i class="fas fa-clock" style="color: var(--warning-color); margin-right: 0.5rem;"></i>
                        Upcoming Events
                    </h2>
                    <a href="<?= htmlspecialchars($url('admin/calendar')) ?>" class="btn btn-outline">
                        <i class="fas fa-list"></i> View All
                    </a>
                </div>
                <div class="events-list">
                    <?php if (empty($upcomingEvents) && $tableExists): ?>
                        <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No upcoming events in the next 7 days.</p>
                        </div>
                    <?php elseif (!empty($upcomingEvents)): ?>
                        <?php 
                        $eventIcons = [
                            'exam' => 'fa-file-alt',
                            'assignment' => 'fa-tasks',
                            'holiday' => 'fa-calendar-check',
                            'meeting' => 'fa-users',
                            'university_event' => 'fa-star',
                            'other' => 'fa-calendar'
                        ];
                        
                        foreach ($upcomingEvents as $event): 
                            $eventType = $event['event_type'] ?? 'other';
                            $icon = $eventIcons[$eventType] ?? 'fa-calendar';
                            $color = $eventColors[$eventType] ?? 'var(--text-secondary)';
                            $startDate = new \DateTime($event['start_date']);
                            $isToday = $startDate->format('Y-m-d') == date('Y-m-d');
                            $isTomorrow = $startDate->format('Y-m-d') == date('Y-m-d', strtotime('+1 day'));
                            $dateLabel = $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : $startDate->format('M j, Y'));
                        ?>
                            <div class="event-item" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                <div style="width: 40px; height: 40px; background-color: <?= $color ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas <?= $icon ?>"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 0.25rem 0; color: var(--text-primary);"><?= htmlspecialchars($event['title'] ?? 'Untitled Event') ?></h4>
                                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;"><?= htmlspecialchars(substr($event['description'] ?? 'No description', 0, 100)) ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 0.9rem; color: var(--text-secondary);"><?= $dateLabel ?></div>
                                    <div style="font-size: 0.9rem; color: <?= $color ?>; font-weight: 500;"><?= $startDate->format('g:i A') ?></div>
                                </div>
                                <div style="display: flex; gap: 0.25rem;">
                                    <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editEvent(<?= $event['id'] ?? 0 ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="deleteEvent(<?= $event['id'] ?? 0 ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="quick-actions" style="margin-top: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-bolt" style="color: var(--accent-color); margin-right: 0.5rem;"></i>
                        Quick Actions
                    </h2>
                </div>
                <div class="grid grid-4">
                    <button class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="createEvent()">
                        <i class="fas fa-plus" style="font-size: 2rem;"></i>
                        <span>Create Event</span>
                    </button>
                    <button class="btn btn-outline" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="scheduleExam()">
                        <i class="fas fa-file-alt" style="font-size: 2rem;"></i>
                        <span>Schedule Exam</span>
                    </button>
                    <button class="btn btn-outline" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="manageConflicts()">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                        <span>Manage Conflicts</span>
                    </button>
                    <button class="btn btn-outline" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="exportCalendar()">
                        <i class="fas fa-download" style="font-size: 2rem;"></i>
                        <span>Export Calendar</span>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Add/Edit Event Modal -->
<div id="eventFormModal" class="modal" data-header-style="primary">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 id="eventModalTitle">Create Event</h2>
            <button class="modal-close" onclick="closeEventFormModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="eventForm" method="POST" action="<?= htmlspecialchars($url('admin/calendar')) ?>" onsubmit="return handleEventFormSubmit(event)">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="eventId" value="">
            <div class="form-group">
                <label class="form-label">Event Title *</label>
                <input type="text" name="title" id="eventTitle" class="form-input" placeholder="e.g., Midterm Exam - CS101" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="eventDescription" class="form-input" rows="3" placeholder="Event description..."></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Event Type *</label>
                    <select name="event_type" id="eventType" class="form-input" required>
                        <option value="">Select Type</option>
                        <option value="exam">Exam</option>
                        <option value="assignment">Assignment Due</option>
                        <option value="holiday">Holiday</option>
                        <option value="meeting">Meeting</option>
                        <option value="university_event">University Event</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="eventStatus" class="form-input">
                        <option value="active">Active</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="postponed">Postponed</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Start Date & Time *</label>
                    <input type="datetime-local" name="start_date" id="eventStartDate" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">End Date & Time</label>
                    <input type="datetime-local" name="end_date" id="eventEndDate" class="form-input">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department" id="eventDepartment" class="form-input">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" id="eventLocation" class="form-input" placeholder="e.g., Room 101, Building A">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Course ID (optional)</label>
                <input type="number" name="course_id" id="eventCourseId" class="form-input" placeholder="Course ID">
                <small style="color: var(--text-secondary);">Leave blank if not course-specific</small>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Event
                </button>
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeEventFormModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Show toast notification on page load if there's a message
<?php if (!empty($message)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const messageType = '<?php echo htmlspecialchars($messageType, ENT_QUOTES); ?>';
    const message = <?php echo json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    
    if (typeof showToastifyNotification !== 'undefined') {
        showToastifyNotification(message, messageType);
    } else if (typeof showNotification !== 'undefined') {
        showNotification(message, messageType);
    }
});
<?php endif; ?>

// Calendar navigation
function previousMonth() {
    const urlParams = new URLSearchParams(window.location.search);
    let month = parseInt(urlParams.get('month')) || <?= $currentMonth ?>;
    let year = parseInt(urlParams.get('year')) || <?= $currentYear ?>;
    month--;
    if (month < 1) {
        month = 12;
        year--;
    }
    urlParams.set('month', month);
    urlParams.set('year', year);
    window.location.href = '<?= htmlspecialchars($url('admin/calendar')) ?>?' + urlParams.toString();
}

function nextMonth() {
    const urlParams = new URLSearchParams(window.location.search);
    let month = parseInt(urlParams.get('month')) || <?= $currentMonth ?>;
    let year = parseInt(urlParams.get('year')) || <?= $currentYear ?>;
    month++;
    if (month > 12) {
        month = 1;
        year++;
    }
    urlParams.set('month', month);
    urlParams.set('year', year);
    window.location.href = '<?= htmlspecialchars($url('admin/calendar')) ?>?' + urlParams.toString();
}

function goToToday() {
    window.location.href = '<?= htmlspecialchars($url('admin/calendar')) ?>';
}

// Event management
function createEvent() {
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('eventModalTitle').textContent = 'Create Event';
    // Set default start date to today
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('eventStartDate').value = now.toISOString().slice(0, 16);
    if (typeof showModal !== 'undefined') {
        showModal(document.getElementById('eventFormModal'));
    } else {
        document.getElementById('eventFormModal').classList.add('active');
        document.getElementById('eventFormModal').style.display = 'flex';
    }
}

function viewEvent(eventId) {
    editEvent(eventId);
}

function editEvent(eventId) {
    // Fetch event data from server
    const editUrl = '<?= htmlspecialchars($url('admin/calendar')) ?>?edit=' + eventId;
    window.location.href = editUrl;
}

function deleteEvent(eventId) {
    if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= htmlspecialchars($url('admin/calendar')) ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = eventId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function scheduleExam() {
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('eventModalTitle').textContent = 'Schedule Exam';
    document.getElementById('eventType').value = 'exam';
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('eventStartDate').value = now.toISOString().slice(0, 16);
    if (typeof showModal !== 'undefined') {
        showModal(document.getElementById('eventFormModal'));
    } else {
        document.getElementById('eventFormModal').classList.add('active');
        document.getElementById('eventFormModal').style.display = 'flex';
    }
}

function manageConflicts() {
    if (typeof showNotification !== 'undefined') {
        showNotification('Opening conflict management...', 'info');
    }
    // TODO: Implement conflict management
}

function exportCalendar() {
    if (typeof showNotification !== 'undefined') {
        showNotification('Exporting calendar data...', 'info');
    }
    // TODO: Implement calendar export
}

function closeEventFormModal() {
    const modal = document.getElementById('eventFormModal');
    if (typeof hideModal !== 'undefined') {
        hideModal(modal);
    } else {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

function handleEventFormSubmit(e) {
    // Form validation is handled by HTML5 required attributes
    // The form will submit normally to the controller
    return true;
}

// Auto-populate form if editing
<?php if ($editEvent): ?>
document.addEventListener('DOMContentLoaded', function() {
    const event = <?php echo json_encode($editEvent, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (event) {
        document.getElementById('eventId').value = event.id || '';
        document.getElementById('eventTitle').value = event.title || '';
        document.getElementById('eventDescription').value = event.description || '';
        document.getElementById('eventType').value = event.event_type || '';
        document.getElementById('eventStatus').value = event.status || 'active';
        document.getElementById('eventDepartment').value = event.department || '';
        document.getElementById('eventLocation').value = event.location || '';
        document.getElementById('eventCourseId').value = event.course_id || '';
        
        // Format dates for datetime-local input
        if (event.start_date) {
            const startDate = new Date(event.start_date);
            startDate.setMinutes(startDate.getMinutes() - startDate.getTimezoneOffset());
            document.getElementById('eventStartDate').value = startDate.toISOString().slice(0, 16);
        }
        if (event.end_date) {
            const endDate = new Date(event.end_date);
            endDate.setMinutes(endDate.getMinutes() - endDate.getTimezoneOffset());
            document.getElementById('eventEndDate').value = endDate.toISOString().slice(0, 16);
        }
        
        document.getElementById('formAction').value = 'update';
        document.getElementById('eventModalTitle').textContent = 'Edit Event';
        
        // Open modal
        createEvent();
    }
});
<?php endif; ?>
</script>
