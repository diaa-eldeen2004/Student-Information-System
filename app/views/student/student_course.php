<?php
$student = $student ?? null;
$enrolledCourses = $enrolledCourses ?? [];
$selectedCourse = $selectedCourse ?? null;
$materials = $materials ?? [];
$assignments = $assignments ?? [];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-book"></i> My Courses</h1>
        <p>View course materials and assignments</p>
    </div>

    <div style="display: flex; gap: 2rem;">
        <div style="flex: 0 0 300px;">
            <div class="card">
                <div class="card-header">
                    <h3>Enrolled Courses</h3>
                </div>
                <div style="padding: 1rem;">
                    <?php if (empty($enrolledCourses)): ?>
                        <p class="text-muted">No courses enrolled</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <?php foreach ($enrolledCourses as $course): ?>
                                <a href="<?= htmlspecialchars($url('student/course?course_id=' . $course['course_id'])) ?>" 
                                   class="btn <?= $selectedCourse && $selectedCourse['course_id'] == $course['course_id'] ? 'btn-primary' : 'btn-outline' ?>"
                                   style="text-align: left; text-decoration: none;">
                                    <strong><?= htmlspecialchars($course['course_code'] ?? '') ?></strong><br>
                                    <small><?= htmlspecialchars($course['course_name'] ?? '') ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="flex: 1;">
            <?php if ($selectedCourse): ?>
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h2><?= htmlspecialchars($selectedCourse['course_code'] ?? '') ?> - <?= htmlspecialchars($selectedCourse['course_name'] ?? '') ?></h2>
                        <p style="margin: 0;">
                            Section: <?= htmlspecialchars($selectedCourse['section_number'] ?? 'N/A') ?> | 
                            Instructor: <?= htmlspecialchars($selectedCourse['doctor_first_name'] ?? '') ?> <?= htmlspecialchars($selectedCourse['doctor_last_name'] ?? '') ?>
                            <?php if (!empty($selectedCourse['day_of_week']) && !empty($selectedCourse['start_time'])): ?>
                                | <?= htmlspecialchars($selectedCourse['day_of_week']) ?> <?= htmlspecialchars($selectedCourse['start_time']) ?>-<?= htmlspecialchars($selectedCourse['end_time'] ?? '') ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div style="padding: 1rem;">
                        <?php if (!empty($selectedCourse['description'])): ?>
                            <p><?= htmlspecialchars($selectedCourse['description']) ?></p>
                        <?php endif; ?>
                        <p><strong>Credit Hours:</strong> <?= htmlspecialchars($selectedCourse['credit_hours'] ?? 'N/A') ?></p>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt"></i> Course Materials</h3>
                    </div>
                    <div style="padding: 1rem;">
                        <?php if (empty($materials)): ?>
                            <p class="text-muted">No materials available</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Uploaded</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($materials as $material): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($material['title'] ?? '') ?></strong>
                                                    <?php if (!empty($material['description'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars(substr($material['description'], 0, 100)) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($material['material_type'] ?? 'other') ?></td>
                                                <td><?= !empty($material['created_at']) ? date('M d, Y', strtotime($material['created_at'])) : 'N/A' ?></td>
                                                <td>
                                                    <?php if (!empty($material['file_path'])): ?>
                                                        <a href="<?= htmlspecialchars($asset($material['file_path'])) ?>" target="_blank" class="btn btn-primary">
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-tasks"></i> Assignments</h3>
                    </div>
                    <div style="padding: 1rem;">
                        <?php if (empty($assignments)): ?>
                            <p class="text-muted">No assignments available</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Grade</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($assignment['title'] ?? '') ?></strong>
                                                    <?php if (!empty($assignment['description'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars(substr($assignment['description'], 0, 100)) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($assignment['due_date'])): ?>
                                                        <?= date('M d, Y H:i', strtotime($assignment['due_date'])) ?>
                                                    <?php else: ?>
                                                        No due date
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($assignment['submission_id'])): ?>
                                                        <?php if (!empty($assignment['grade'])): ?>
                                                            <span class="badge badge-success">Graded</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-info">Submitted</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Not Submitted</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($assignment['grade'])): ?>
                                                        <strong><?= number_format($assignment['grade'], 1) ?></strong> / <?= htmlspecialchars($assignment['max_points'] ?? 100) ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="<?= htmlspecialchars($url('student/assignments')) ?>" class="btn btn-primary">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div style="padding: 3rem; text-align: center;">
                        <i class="fas fa-book fa-3x" style="color: var(--text-secondary); margin-bottom: 1rem;"></i>
                        <h3>Select a Course</h3>
                        <p class="text-muted">Choose a course from the list to view materials and assignments</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

