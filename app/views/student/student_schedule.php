<?php
$student = $student ?? null;
$schedule = $schedule ?? [];
$weeklySchedule = $weeklySchedule ?? [];
$availableSections = $availableSections ?? [];
$enrollmentRequests = $enrollmentRequests ?? [];
$requestedSectionIds = $requestedSectionIds ?? [];
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
                                $isRequested = in_array($section['section_id'], $requestedSectionIds);
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
                                        <?php if ($isRequested): ?>
                                            <span class="badge" style="background-color: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">Request Pending</span>
                                        <?php elseif ($isFull): ?>
                                            <span class="badge" style="background-color: var(--error-color); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">Full</span>
                                        <?php else: ?>
                                            <form method="POST" action="<?= htmlspecialchars($url('student/enroll')) ?>" style="display: inline;">
                                                <input type="hidden" name="section_id" value="<?= htmlspecialchars($section['section_id']) ?>">
                                                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Submit enrollment request for this section?')">
                                                    <i class="fas fa-plus"></i> Request Enrollment
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
