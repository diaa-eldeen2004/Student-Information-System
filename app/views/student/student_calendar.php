<?php
$student = $student ?? null;
$events = $events ?? [];
$upcomingEvents = $upcomingEvents ?? [];
$month = $month ?? date('n');
$year = $year ?? date('Y');
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-calendar"></i> Calendar</h1>
        <p>View upcoming events and important dates</p>
    </div>

    <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px;">
            <div class="card mb-4">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h3><?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></h3>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <?php
                            // Calculate previous and next month/year
                            $prevMonth = $month - 1;
                            $prevYear = $year;
                            if ($prevMonth < 1) {
                                $prevMonth = 12;
                                $prevYear--;
                            }
                            $nextMonth = $month + 1;
                            $nextYear = $year;
                            if ($nextMonth > 12) {
                                $nextMonth = 1;
                                $nextYear++;
                            }
                            ?>
                            <a href="<?= htmlspecialchars($url('student/calendar') . '?month=' . $prevMonth . '&year=' . $prevYear) ?>" class="btn btn-outline" style="padding: 0.5rem 1rem;">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <form method="GET" action="<?= htmlspecialchars($url('student/calendar')) ?>" style="display: flex; gap: 0.5rem; align-items: center;">
                                <select name="month" class="form-input" style="width: auto;">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>>
                                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <input type="number" name="year" value="<?= htmlspecialchars($year) ?>" class="form-input" style="width: 100px;" min="2020" max="2030">
                                <button type="submit" class="btn btn-primary">Go</button>
                            </form>
                            <a href="<?= htmlspecialchars($url('student/calendar') . '?month=' . $nextMonth . '&year=' . $nextYear) ?>" class="btn btn-outline" style="padding: 0.5rem 1rem;">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div style="padding: 1.5rem;">
                    <?php
                    // Organize events by day
                    $eventsByDay = [];
                    foreach ($events as $event) {
                        if (!empty($event['start_date'])) {
                            $eventDate = date('Y-m-d', strtotime($event['start_date']));
                            $eventDay = (int)date('j', strtotime($event['start_date']));
                            
                            // Check if event spans multiple days
                            if (!empty($event['end_date']) && $event['end_date'] != $event['start_date']) {
                                $start = strtotime($event['start_date']);
                                $end = strtotime($event['end_date']);
                                $current = $start;
                                while ($current <= $end) {
                                    $day = (int)date('j', $current);
                                    $monthOfEvent = (int)date('n', $current);
                                    $yearOfEvent = (int)date('Y', $current);
                                    
                                    // Only add if it's in the current month
                                    if ($monthOfEvent == $month && $yearOfEvent == $year) {
                                        if (!isset($eventsByDay[$day])) {
                                            $eventsByDay[$day] = [];
                                        }
                                        $eventsByDay[$day][] = $event;
                                    }
                                    $current = strtotime('+1 day', $current);
                                }
                            } else {
                                // Single day event
                                $monthOfEvent = (int)date('n', strtotime($event['start_date']));
                                $yearOfEvent = (int)date('Y', strtotime($event['start_date']));
                                
                                if ($monthOfEvent == $month && $yearOfEvent == $year) {
                                    if (!isset($eventsByDay[$eventDay])) {
                                        $eventsByDay[$eventDay] = [];
                                    }
                                    $eventsByDay[$eventDay][] = $event;
                                }
                            }
                        }
                    }
                    
                    // Get first day of month and number of days
                    $firstDayOfMonth = date('w', mktime(0, 0, 0, $month, 1, $year)); // 0 = Sunday, 1 = Monday, etc.
                    $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));
                    $today = date('j');
                    $currentMonth = (int)date('n');
                    $currentYear = (int)date('Y');
                    $isCurrentMonth = ($month == $currentMonth && $year == $currentYear);
                    ?>
                    <div class="calendar-grid" style="width: 100%;">
                        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background-color: #e2e8f0; border: 1px solid #e2e8f0;">
                            <!-- Day headers -->
                            <?php $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; ?>
                            <?php foreach ($dayNames as $dayName): ?>
                                <div style="background-color: var(--primary-color, #2563eb); color: white; padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem;">
                                    <?= $dayName ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Empty cells for days before month starts -->
                            <?php for ($i = 0; $i < $firstDayOfMonth; $i++): ?>
                                <div style="background-color: #f8fafc; min-height: 100px; padding: 0.5rem;"></div>
                            <?php endfor; ?>
                            
                            <!-- Days of the month -->
                            <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                                <?php
                                $isToday = $isCurrentMonth && ($day == $today);
                                $dayEvents = $eventsByDay[$day] ?? [];
                                ?>
                                <div style="background-color: <?= $isToday ? '#dbeafe' : 'white' ?>; min-height: 100px; padding: 0.5rem; border: <?= $isToday ? '2px solid var(--primary-color, #2563eb)' : '1px solid #e2e8f0' ?>; position: relative;">
                                    <div style="font-weight: <?= $isToday ? '700' : '600' ?>; margin-bottom: 0.5rem; color: <?= $isToday ? 'var(--primary-color, #2563eb)' : 'var(--text-primary, #1e293b)' ?>;">
                                        <?= $day ?>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem; max-height: 70px; overflow-y: auto;">
                                        <?php foreach (array_slice($dayEvents, 0, 3) as $event): ?>
                                            <?php
                                            $eventType = $event['event_type'] ?? 'event';
                                            $eventColor = '#2563eb';
                                            if ($eventType === 'assignment') {
                                                $eventColor = '#f59e0b';
                                                if (!empty($event['is_graded'])) {
                                                    $eventColor = '#10b981';
                                                } elseif (!empty($event['is_submitted'])) {
                                                    $eventColor = '#3b82f6';
                                                }
                                            } elseif ($eventType === 'exam') {
                                                $eventColor = '#ef4444';
                                            }
                                            ?>
                                            <div style="background-color: <?= $eventColor ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" 
                                                 title="<?= htmlspecialchars($event['title'] ?? '') ?>"
                                                 onclick='showEventDetails(<?= json_encode($event, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                <?= htmlspecialchars(substr($event['title'] ?? '', 0, 20)) ?><?= strlen($event['title'] ?? '') > 20 ? '...' : '' ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($dayEvents) > 3): ?>
                                            <div style="font-size: 0.7rem; color: var(--text-secondary, #64748b); text-align: center; padding: 0.25rem;">
                                                +<?= count($dayEvents) - 3 ?> more
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                            
                            <!-- Empty cells for days after month ends -->
                            <?php
                            $totalCells = $firstDayOfMonth + $daysInMonth;
                            $remainingCells = 7 - ($totalCells % 7);
                            if ($remainingCells < 7) {
                                for ($i = 0; $i < $remainingCells; $i++):
                            ?>
                                <div style="background-color: #f8fafc; min-height: 100px; padding: 0.5rem;"></div>
                            <?php
                                endfor;
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="flex: 0 0 300px;">
            <div class="card">
                <div class="card-header">
                    <h3>Upcoming Events</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <?php if (empty($upcomingEvents)): ?>
                        <p class="text-muted">No upcoming events</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($upcomingEvents as $event): ?>
                                <?php
                                $eventType = $event['event_type'] ?? 'event';
                                $borderColor = 'var(--primary-color)';
                                if ($eventType === 'assignment') {
                                    $borderColor = 'var(--warning-color)';
                                    if (!empty($event['is_graded'])) {
                                        $borderColor = 'var(--success-color)';
                                    } elseif (!empty($event['is_submitted'])) {
                                        $borderColor = 'var(--info-color)';
                                    }
                                } elseif ($eventType === 'exam') {
                                    $borderColor = 'var(--error-color)';
                                }
                                ?>
                                <div style="padding: 1rem; background-color: var(--background-color); border-radius: 8px; border-left: 4px solid <?= $borderColor ?>;">
                                    <strong><?= htmlspecialchars($event['title'] ?? '') ?></strong><br>
                                    <small class="text-muted">
                                        <?= !empty($event['start_date']) ? date('M d, Y', strtotime($event['start_date'])) : 'N/A' ?>
                                        <?php if (isset($event['course_code']) && !empty($event['course_code'])): ?>
                                            | <?= htmlspecialchars($event['course_code']) ?>
                                        <?php endif; ?>
                                    </small>
                                    <?php if (isset($event['event_type']) && $event['event_type'] === 'assignment' && !empty($event['assignment_id'])): ?>
                                        <br><a href="<?= htmlspecialchars($url('student/assignments')) ?>" style="font-size: 0.75rem; margin-top: 0.25rem; display: inline-block;">
                                            <i class="fas fa-external-link-alt"></i> View
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div id="eventModal" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: white; margin: 5% auto; padding: 2rem; border-radius: 12px; max-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); position: relative;">
        <span onclick="closeEventModal()" style="position: absolute; right: 1rem; top: 1rem; font-size: 2rem; font-weight: bold; color: #999; cursor: pointer;">&times;</span>
        <div id="eventModalContent"></div>
    </div>
</div>

<script>
function showEventDetails(event) {
    const modal = document.getElementById('eventModal');
    const content = document.getElementById('eventModalContent');
    
    if (!event) return;
    
    let eventType = event.event_type || 'event';
    let badgeColor = '#2563eb';
    let badgeText = 'Event';
    
    if (eventType === 'assignment') {
        badgeColor = '#f59e0b';
        badgeText = 'Assignment (Due)';
        if (event.is_graded) {
            badgeColor = '#10b981';
            badgeText = 'Assignment (Graded)';
        } else if (event.is_submitted) {
            badgeColor = '#3b82f6';
            badgeText = 'Assignment (Submitted)';
        }
    } else if (eventType === 'exam') {
        badgeColor = '#ef4444';
        badgeText = 'Exam';
    }
    
    const startDate = event.start_date ? new Date(event.start_date).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    }) : 'N/A';
    
    const endDate = event.end_date && event.end_date !== event.start_date 
        ? new Date(event.end_date).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        }) 
        : null;
    
    content.innerHTML = `
        <h2 style="margin-top: 0; margin-bottom: 1rem; color: var(--text-primary, #1e293b);">
            ${escapeHtml(event.title || 'Untitled Event')}
        </h2>
        <div style="margin-bottom: 1rem;">
            <span style="background-color: ${badgeColor}; color: white; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600;">
                ${badgeText}
            </span>
        </div>
        <div style="margin-bottom: 1rem; color: var(--text-secondary, #64748b);">
            <p><strong>Date:</strong> ${startDate}${endDate ? ' to ' + endDate : ''}</p>
            ${event.location ? `<p><strong>Location:</strong> ${escapeHtml(event.location)}</p>` : ''}
            ${event.course_code ? `<p><strong>Course:</strong> ${escapeHtml(event.course_code)}</p>` : ''}
        </div>
        ${event.description ? `
            <div style="margin-bottom: 1rem;">
                <strong>Description:</strong>
                <p style="color: var(--text-secondary, #64748b); margin-top: 0.5rem;">${escapeHtml(event.description)}</p>
            </div>
        ` : ''}
        ${event.event_type === 'assignment' && event.assignment_id ? `
            <div style="margin-top: 1.5rem;">
                <a href="<?= htmlspecialchars($url('student/assignments')) ?>" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> View Assignment
                </a>
            </div>
        ` : ''}
    `;
    
    modal.style.display = 'block';
}

function closeEventModal() {
    document.getElementById('eventModal').style.display = 'none';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('eventModal');
    if (event.target == modal) {
        closeEventModal();
    }
}
</script>
