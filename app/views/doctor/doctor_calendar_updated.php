<?php
$sections = $sections ?? [];
$sectionsByDay = $sectionsByDay ?? [];
$unscheduledSections = $unscheduledSections ?? [];
$assignments = $assignments ?? [];
?>

<div class="calendar-container">
    <div class="calendar-header">
        <div>
            <h1><i class="fas fa-calendar-alt"></i> My Assigned Courses</h1>
            <p>View all courses assigned to you. Courses appear automatically when assigned by IT.</p>
        </div>
    </div>

    <div class="calendar-content">
        <div class="calendar-section">
            <h2><i class="fas fa-book"></i> All Assigned Courses</h2>
            <div class="schedule-list">
                <?php if (empty($sections)): ?>
                    <div class="empty-state" style="text-align: center; padding: 3rem;">
                        <i class="fas fa-calendar-times" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                        <p class="text-muted" style="font-size: 1.1rem;">No assigned courses</p>
                        <p class="text-muted" style="font-size: 0.9rem; margin-top: 0.5rem;">
                            Courses will appear here automatically when IT assigns them to you.
                        </p>
                    </div>
                <?php else: ?>
                    <!-- Summary -->
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-info-circle" style="color: var(--primary-color); font-size: 1.2rem;"></i>
                            <div>
                                <strong style="color: var(--text-color);">Total Assigned Courses: <?= count($sections) ?></strong>
                                <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary); font-size: 0.9rem;">
                                    Courses appear here automatically when assigned by IT. Schedule information will be added when available.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Scheduled Courses by Day -->
                    <?php 
                    $hasScheduled = false;
                    foreach ($sectionsByDay as $daySections) {
                        if (!empty($daySections)) {
                            $hasScheduled = true;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($hasScheduled): ?>
                        <h3 style="margin: 1.5rem 0 1rem 0; color: var(--text-color); font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-calendar-check"></i> Scheduled Courses
                        </h3>
                        <?php 
                        $daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($daysOrder as $day): 
                            if (empty($sectionsByDay[$day])) continue;
                        ?>
                            <div class="day-section" style="margin-bottom: 2rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                                <h3 style="margin: 0 0 1rem 0; color: var(--primary-color); font-size: 1.2rem; border-bottom: 2px solid var(--primary-color); padding-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-calendar-day"></i> <?= $day ?>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary); font-weight: normal;">
                                        (<?= count($sectionsByDay[$day]) ?> lecture<?= count($sectionsByDay[$day]) > 1 ? 's' : '' ?>)
                                    </span>
                                </h3>
                                <?php foreach ($sectionsByDay[$day] as $section): ?>
                                    <div class="schedule-item" style="margin-bottom: 1rem; padding: 1.25rem; background: white; border-radius: 8px; border: 1px solid var(--border-color); transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);" 
                                         onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'; this.style.transform='translateX(4px)'"
                                         onmouseout="this.style.boxShadow='0 2px 4px rgba(0,0,0,0.05)'; this.style.transform='translateX(0)'">
                                        <div style="display: flex; align-items: start; gap: 1rem;">
                                            <div class="schedule-icon" style="flex-shrink: 0; width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <div class="schedule-content" style="flex: 1;">
                                                <h3 style="margin: 0 0 0.75rem 0; font-size: 1.15rem; color: var(--text-color); font-weight: 700;">
                                                    <?= htmlspecialchars($section['course_code'] ?? 'N/A') ?> - <?= htmlspecialchars($section['course_name'] ?? 'N/A') ?>
                                                </h3>
                                                
                                                <!-- Time Display -->
                                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding: 0.75rem; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 6px; border-left: 3px solid var(--primary-color);">
                                                    <div style="width: 40px; height: 40px; border-radius: 8px; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                        <i class="fas fa-clock"></i>
                                                    </div>
                                                    <div style="flex: 1;">
                                                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Lecture Time</div>
                                                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-color);">
                                                            <?= htmlspecialchars($section['start_time'] ?? 'TBA') ?> - <?= htmlspecialchars($section['end_time'] ?? 'TBA') ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Room Display -->
                                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding: 0.75rem; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 6px; border-left: 3px solid #10b981;">
                                                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #10b981; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                    </div>
                                                    <div style="flex: 1;">
                                                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Room Location</div>
                                                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-color);">
                                                            <?= htmlspecialchars($section['room'] ?? 'TBA') ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Date/Semester Display -->
                                                <?php if (!empty($section['semester']) && !empty($section['academic_year'])): ?>
                                                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding: 0.75rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 6px; border-left: 3px solid #f59e0b;">
                                                        <div style="width: 40px; height: 40px; border-radius: 8px; background: #f59e0b; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                            <i class="fas fa-calendar-alt"></i>
                                                        </div>
                                                        <div style="flex: 1;">
                                                            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Semester & Academic Year</div>
                                                            <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-color);">
                                                                <?= htmlspecialchars($section['semester']) ?> <?= htmlspecialchars($section['academic_year']) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Section Info -->
                                                <div style="display: flex; align-items: center; gap: 0.5rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                                                    <i class="fas fa-users" style="color: var(--text-secondary);"></i>
                                                    <span style="color: var(--text-secondary); font-size: 0.9rem;">
                                                        Section: <?= htmlspecialchars($section['section_name'] ?? $section['section_number'] ?? 'N/A') ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- All Assigned Courses (including unscheduled) -->
                    <h3 style="margin: 1.5rem 0 1rem 0; color: var(--text-color); font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-list"></i> Complete Course List
                    </h3>
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($sections as $section): ?>
                            <?php 
                            // Check if this section is already shown in scheduled section
                            $isScheduled = false;
                            foreach ($sectionsByDay as $daySections) {
                                foreach ($daySections as $scheduledSection) {
                                    if ($scheduledSection['section_id'] == $section['section_id']) {
                                        $isScheduled = true;
                                        break 2;
                                    }
                                }
                            }
                            ?>
                            <div class="assigned-course-card" style="padding: 1.25rem; background: white; border-radius: 8px; border: 2px solid <?= $isScheduled ? 'var(--primary-color)' : 'var(--border-color)' ?>; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);" 
                                 onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.style.borderColor='var(--primary-color)'"
                                 onmouseout="this.style.boxShadow='0 2px 4px rgba(0,0,0,0.05)'; this.style.borderColor='<?= $isScheduled ? 'var(--primary-color)' : 'var(--border-color)' ?>'">
                                <div style="display: flex; align-items: start; gap: 1rem;">
                                    <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1.15rem; color: var(--text-color); font-weight: 700;">
                                            <?= htmlspecialchars($section['course_code'] ?? 'N/A') ?> - <?= htmlspecialchars($section['course_name'] ?? 'N/A') ?>
                                        </h4>
                                        
                                        <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 0.75rem;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <i class="fas fa-users" style="color: var(--text-secondary);"></i>
                                                <span style="color: var(--text-secondary); font-size: 0.9rem;">
                                                    Section: <?= htmlspecialchars($section['section_name'] ?? $section['section_number'] ?? 'N/A') ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($section['semester']) && !empty($section['academic_year'])): ?>
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <i class="fas fa-calendar-alt" style="color: var(--text-secondary);"></i>
                                                    <span style="color: var(--text-secondary); font-size: 0.9rem;">
                                                        <?= htmlspecialchars($section['semester']) ?> <?= htmlspecialchars($section['academic_year']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!$isScheduled || empty($section['day_of_week']) || empty($section['start_time'])): ?>
                                            <div style="padding: 0.75rem; background: #fff3cd; border-radius: 6px; border-left: 3px solid #ffc107;">
                                                <p style="margin: 0; color: #856404; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
                                                    <i class="fas fa-info-circle"></i>
                                                    <?= $isScheduled ? 'Schedule information pending.' : 'Course assigned. Schedule information will be added when available.' ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="calendar-section">
            <h2><i class="fas fa-tasks"></i> Upcoming Assignments</h2>
            <div class="assignments-list">
                <?php if (empty($assignments)): ?>
                    <p class="text-muted">No upcoming assignments</p>
                <?php else: ?>
                    <?php 
                    // Sort assignments by due date
                    usort($assignments, function($a, $b) {
                        return strtotime($a['due_date'] ?? '9999-12-31') - strtotime($b['due_date'] ?? '9999-12-31');
                    });
                    ?>
                    <?php foreach (array_slice($assignments, 0, 10) as $assignment): ?>
                        <div class="assignment-item">
                            <div class="assignment-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="assignment-content">
                                <h3><?= htmlspecialchars($assignment['title'] ?? 'Assignment') ?></h3>
                                <p class="assignment-meta">
                                    <?= htmlspecialchars($assignment['course_code'] ?? 'N/A') ?> • 
                                    Due: <?= date('M d, Y', strtotime($assignment['due_date'] ?? 'now')) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.calendar-header h1 {
    font-size: 2rem;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.calendar-header p {
    color: var(--text-muted);
}

.calendar-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.calendar-section {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1.5rem;
}

.calendar-section h2 {
    font-size: 1.3rem;
    color: var(--text-color);
    margin-bottom: 1rem;
}

.schedule-list, .assignments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.schedule-item, .assignment-item {
    display: flex;
    align-items: start;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.schedule-icon, .assignment-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.schedule-content h3, .assignment-content h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    color: var(--text-color);
}

.schedule-meta, .assignment-meta {
    margin: 0.25rem 0;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.text-muted {
    color: var(--text-muted);
    font-style: italic;
}
</style>

