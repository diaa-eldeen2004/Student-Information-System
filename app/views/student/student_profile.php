<?php
$student = $student ?? null;
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-user"></i> My Profile</h1>
        <p>View your profile information</p>
    </div>

    <?php if (!$student): ?>
        <div class="card">
            <div style="padding: 3rem; text-align: center;">
                <p class="text-muted">Student information not found.</p>
            </div>
        </div>
    <?php else: ?>
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Personal Information</h3>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div style="margin-bottom: 1.5rem;">
                            <strong>First Name:</strong><br>
                            <span style="color: var(--text-secondary);"><?= htmlspecialchars($student['first_name'] ?? 'N/A') ?></span>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <strong>Last Name:</strong><br>
                            <span style="color: var(--text-secondary);"><?= htmlspecialchars($student['last_name'] ?? 'N/A') ?></span>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <strong>Email:</strong><br>
                            <span style="color: var(--text-secondary);"><?= htmlspecialchars($student['email'] ?? 'N/A') ?></span>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <strong>Phone:</strong><br>
                            <span style="color: var(--text-secondary);"><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Academic Information</h3>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div style="margin-bottom: 1.5rem;">
                            <strong>Student Number:</strong><br>
                            <span style="color: var(--text-secondary);"><?= htmlspecialchars($student['student_number'] ?? 'N/A') ?></span>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <strong>GPA:</strong><br>
                            <span style="color: var(--primary-color); font-size: 1.25rem; font-weight: 600;">
                                <?= number_format($student['gpa'] ?? 0.00, 2) ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <strong>Major:</strong><br>
                            <span style="color: var(--text-secondary);"><?= htmlspecialchars($student['major'] ?? 'N/A') ?></span>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <strong>Minor:</strong><br>
                            <span style="color: var(--text-secondary);"><?= htmlspecialchars($student['minor'] ?? 'N/A') ?></span>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <strong>Admission Date:</strong><br>
                            <span style="color: var(--text-secondary);">
                                <?= !empty($student['admission_date']) ? date('M d, Y', strtotime($student['admission_date'])) : 'N/A' ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <strong>Status:</strong><br>
                            <?php
                            $status = $student['status'] ?? 'active';
                            $badgeColor = $status === 'active' ? 'var(--success-color)' : 'var(--secondary-color)';
                            ?>
                            <span class="badge" style="background-color: <?= $badgeColor ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">
                                <?= ucfirst(htmlspecialchars($status)) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div style="flex: 0 0 300px;">
                <div class="card">
                    <div class="card-header">
                        <h3>Quick Stats</h3>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <div style="font-size: 3rem; font-weight: bold; color: var(--primary-color);">
                                <?= number_format($student['gpa'] ?? 0.00, 2) ?>
                            </div>
                            <p class="text-muted" style="margin: 0.5rem 0 0 0;">Current GPA</p>
                        </div>
                        <hr style="border: none; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">
                        <div style="margin-bottom: 1rem;">
                            <strong>Student ID:</strong><br>
                            <span class="text-muted"><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></span>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <strong>Member Since:</strong><br>
                            <span class="text-muted">
                                <?= !empty($student['admission_date']) ? date('Y', strtotime($student['admission_date'])) : 'N/A' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
