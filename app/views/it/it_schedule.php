<?php
$sections = $sections ?? [];
$courses = $courses ?? [];
$doctors = $doctors ?? [];
$currentSemester = $currentSemester ?? 'Fall';
$currentYear = $currentYear ?? date('Y');
$error = $error ?? null;
$success = $success ?? null;
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
            <i class="fas fa-check-circle"></i> Section created successfully!
        </div>
    <?php endif; ?>

    <div class="schedule-content">
        <div class="schedule-form-section">
            <h2>Create New Section</h2>
            <form method="post" action="<?= htmlspecialchars($url('it/schedule')) ?>" class="section-form">
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
                        <label for="section_number" class="form-label">Section Number *</label>
                        <input type="text" id="section_number" name="section_number" class="form-input" required placeholder="e.g., 001">
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
                        <label for="room" class="form-label">Room</label>
                        <input type="text" id="room" name="room" class="form-input" placeholder="e.g., A101">
                    </div>

                    <div class="form-group">
                        <label for="day_of_week" class="form-label">Day of Week *</label>
                        <select id="day_of_week" name="day_of_week" class="form-input" required>
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_time" class="form-label">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="end_time" class="form-label">End Time *</label>
                        <input type="time" id="end_time" name="end_time" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="capacity" class="form-label">Capacity *</label>
                        <input type="number" id="capacity" name="capacity" class="form-input" required value="30" min="1">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Section
                </button>
            </form>
        </div>

        <div class="schedule-list-section">
            <h2>Current Schedule (<?= htmlspecialchars($currentSemester) ?> <?= htmlspecialchars($currentYear) ?>)</h2>
            <?php if (empty($sections)): ?>
                <p class="text-muted">No sections scheduled for this semester.</p>
            <?php else: ?>
                <div class="sections-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Section</th>
                                <th>Doctor</th>
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
                                    <td><?= htmlspecialchars($section['doctor_first_name']) ?> <?= htmlspecialchars($section['doctor_last_name']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($section['day_of_week'] ?? '') ?><br>
                                        <small><?= htmlspecialchars($section['start_time'] ?? '') ?> - <?= htmlspecialchars($section['end_time'] ?? '') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($section['room'] ?? 'TBA') ?></td>
                                    <td>
                                        <span class="enrollment-badge">
                                            <?= $section['current_enrollment'] ?> / <?= $section['capacity'] ?>
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

@media (max-width: 1024px) {
    .schedule-content {
        grid-template-columns: 1fr;
    }
}
</style>
