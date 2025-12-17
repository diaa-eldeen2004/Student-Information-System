<?php
$student = $student ?? null;
$attendanceData = $attendanceData ?? [];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-calendar-check"></i> My Attendance</h1>
        <p>View your attendance records</p>
    </div>

    <?php if (empty($attendanceData)): ?>
        <div class="card">
            <div style="padding: 3rem; text-align: center;">
                <i class="fas fa-calendar-check fa-3x" style="color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <h3>No Attendance Data</h3>
                <p class="text-muted">You don't have any attendance records yet.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($attendanceData as $sectionId => $data): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h3>
                                <?= htmlspecialchars($data['course']['course_code'] ?? '') ?> - 
                                <?= htmlspecialchars($data['course']['course_name'] ?? '') ?>
                            </h3>
                            <p style="margin: 0;">
                                Section: <?= htmlspecialchars($data['course']['section_number'] ?? 'N/A') ?> | 
                                <?= htmlspecialchars($data['course']['doctor_first_name'] ?? '') ?> 
                                <?= htmlspecialchars($data['course']['doctor_last_name'] ?? '') ?>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <div style="margin-bottom: 0.5rem;">
                                <?php
                                $percentage = $data['percentage'] ?? 0;
                                $colorClass = $percentage >= 80 ? 'var(--success-color)' : ($percentage >= 60 ? 'var(--warning-color)' : 'var(--error-color)');
                                ?>
                                <h2 style="margin: 0; color: <?= $colorClass ?>;">
                                    <?= number_format($percentage, 1) ?>%
                                </h2>
                                <small class="text-muted">Attendance Rate</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="padding: 1.5rem;">
                    <?php if (empty($data['attendance'])): ?>
                        <p class="text-muted">No attendance records available</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['attendance'] as $record): ?>
                                        <tr>
                                            <td><?= !empty($record['attendance_date']) ? date('M d, Y', strtotime($record['attendance_date'])) : 'N/A' ?></td>
                                            <td>
                                                <?php
                                                $status = $record['status'] ?? '';
                                                $badgeColor = 'var(--secondary-color)';
                                                if ($status === 'present') $badgeColor = 'var(--success-color)';
                                                elseif ($status === 'absent') $badgeColor = 'var(--error-color)';
                                                elseif ($status === 'late') $badgeColor = 'var(--warning-color)';
                                                elseif ($status === 'excused') $badgeColor = 'var(--primary-color)';
                                                ?>
                                                <span class="badge" style="background-color: <?= $badgeColor ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">
                                                    <?= ucfirst(htmlspecialchars($status)) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($record['notes'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
