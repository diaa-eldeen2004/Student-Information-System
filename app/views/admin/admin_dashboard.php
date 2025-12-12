<?php
// Ensure variables exist with defaults
$totalStudents = $totalStudents ?? 0;
$studentsThisMonth = $studentsThisMonth ?? 0;
$totalDoctors = $totalDoctors ?? 0;
$doctorsThisMonth = $doctorsThisMonth ?? 0;
$totalCourses = $totalCourses ?? 0;
$coursesThisSemester = $coursesThisSemester ?? 0;
$systemAlerts = $systemAlerts ?? [];
$recentActivity = $recentActivity ?? [];
$courseDistribution = $courseDistribution ?? [];
$userDistribution = $userDistribution ?? [];

// Calculate total users for pie chart
$totalUsers = array_sum(array_column($userDistribution, 'count'));
?>

<div class="admin-container">
    <!-- Content Header -->
    <header class="content-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="margin: 0; color: var(--text-primary);"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            </div>
        </div>
    </header>

    <div class="content-body">
        <!-- System Overview -->
        <section class="system-overview" style="margin-bottom: 2rem;">
            <div class="grid grid-3">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalStudents) ?></div>
                    <div style="color: var(--text-secondary);">Total Students</div>
                    <?php if ($studentsThisMonth > 0): ?>
                        <div style="font-size: 0.8rem; color: var(--success-color); margin-top: 0.25rem;">+<?= htmlspecialchars($studentsThisMonth) ?> this month</div>
                    <?php endif; ?>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--accent-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalDoctors) ?></div>
                    <div style="color: var(--text-secondary);">Faculty Members</div>
                    <?php if ($doctorsThisMonth > 0): ?>
                        <div style="font-size: 0.8rem; color: var(--success-color); margin-top: 0.25rem;">+<?= htmlspecialchars($doctorsThisMonth) ?> this month</div>
                    <?php endif; ?>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-book"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalCourses) ?></div>
                    <div style="color: var(--text-secondary);">Active Courses</div>
                    <?php if ($coursesThisSemester > 0): ?>
                        <div style="font-size: 0.8rem; color: var(--success-color); margin-top: 0.25rem;"><?= htmlspecialchars($coursesThisSemester) ?> this semester</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Recent Activity -->
        <section class="recent-activity" style="margin-bottom: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-history" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        Recent System Activity
                    </h2>
                </div>
                <div class="activity-list">
                    <?php if (!empty($recentActivity)): ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                <div style="width: 40px; height: 40px; background-color: <?= htmlspecialchars($activity['color']) ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas <?= htmlspecialchars($activity['icon']) ?>"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 0.25rem 0; color: var(--text-primary);"><?= htmlspecialchars($activity['title']) ?></h4>
                                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;"><?= htmlspecialchars($activity['message']) ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($activity['time']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-history" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                            <p>No recent activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Quick Management -->
        <section class="quick-management" style="margin-top: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-cogs" style="color: var(--accent-color); margin-right: 0.5rem;"></i>
                        Quick Management
                    </h2>
                </div>
                <div class="grid grid-4">
                    <a href="<?= htmlspecialchars($url('admin/manage-student')) ?>" class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; text-decoration: none;">
                        <i class="fas fa-user-graduate" style="font-size: 2rem;"></i>
                        <span>Manage Students</span>
                    </a>
                    <a href="<?= htmlspecialchars($url('admin/manage-doctor')) ?>" class="btn btn-outline" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; text-decoration: none;">
                        <i class="fas fa-chalkboard-teacher" style="font-size: 2rem;"></i>
                        <span>Manage Doctors</span>
                    </a>
                    <a href="<?= htmlspecialchars($url('admin/manage-advisor')) ?>" class="btn btn-outline" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; text-decoration: none;">
                        <i class="fas fa-user-tie" style="font-size: 2rem;"></i>
                        <span>Manage Advisors</span>
                    </a>
                    <a href="<?= htmlspecialchars($url('admin/reports')) ?>" class="btn btn-outline" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; text-decoration: none;">
                        <i class="fas fa-chart-bar" style="font-size: 2rem;"></i>
                        <span>View Reports</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- System Statistics -->
        <section class="system-statistics" style="margin-top: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-chart-pie" style="color: var(--success-color); margin-right: 0.5rem;"></i>
                        System Statistics
                    </h2>
                </div>
                <div class="grid grid-2">
                    <!-- User Distribution -->
                    <div style="text-align: center;">
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">User Distribution</h3>
                        <?php if ($totalUsers > 0): ?>
                            <?php
                            // Calculate percentages
                            $studentCount = 0;
                            $doctorCount = 0;
                            $advisorCount = 0;
                            $itCount = 0;
                            $adminCount = 0;
                            foreach ($userDistribution as $dist) {
                                switch ($dist['role']) {
                                    case 'student':
                                        $studentCount = (int)$dist['count'];
                                        break;
                                    case 'doctor':
                                        $doctorCount = (int)$dist['count'];
                                        break;
                                    case 'advisor':
                                        $advisorCount = (int)$dist['count'];
                                        break;
                                    case 'it':
                                        $itCount = (int)$dist['count'];
                                        break;
                                    case 'admin':
                                        $adminCount = (int)$dist['count'];
                                        break;
                                }
                            }
                            $studentDeg = ($studentCount / $totalUsers) * 360;
                            $doctorDeg = ($doctorCount / $totalUsers) * 360;
                            $advisorDeg = ($advisorCount / $totalUsers) * 360;
                            $itDeg = ($itCount / $totalUsers) * 360;
                            $cumulative = 0;
                            ?>
                            <div style="width: 150px; height: 150px; margin: 0 auto; background: conic-gradient(
                                var(--primary-color) 0deg <?= $cumulative += $studentDeg ?>deg,
                                var(--accent-color) <?= $cumulative ?>deg <?= $cumulative += $doctorDeg ?>deg,
                                var(--success-color) <?= $cumulative ?>deg <?= $cumulative += $advisorDeg ?>deg,
                                var(--warning-color) <?= $cumulative ?>deg <?= $cumulative += $itDeg ?>deg,
                                var(--error-color) <?= $cumulative ?>deg 360deg
                            ); border-radius: 50%; position: relative;">
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: var(--surface-color); width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <span style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($totalUsers) ?></span>
                                </div>
                            </div>
                            <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.25rem;">
                                <?php if ($studentCount > 0): ?>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                        <div style="width: 12px; height: 12px; background-color: var(--primary-color); border-radius: 2px;"></div>
                                        <span style="font-size: 0.9rem; color: var(--text-secondary);">Students: <?= htmlspecialchars($studentCount) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($doctorCount > 0): ?>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                        <div style="width: 12px; height: 12px; background-color: var(--accent-color); border-radius: 2px;"></div>
                                        <span style="font-size: 0.9rem; color: var(--text-secondary);">Doctors: <?= htmlspecialchars($doctorCount) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($advisorCount > 0): ?>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                        <div style="width: 12px; height: 12px; background-color: var(--success-color); border-radius: 2px;"></div>
                                        <span style="font-size: 0.9rem; color: var(--text-secondary);">Advisors: <?= htmlspecialchars($advisorCount) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($itCount > 0): ?>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                        <div style="width: 12px; height: 12px; background-color: var(--warning-color); border-radius: 2px;"></div>
                                        <span style="font-size: 0.9rem; color: var(--text-secondary);">IT Officers: <?= htmlspecialchars($itCount) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($adminCount > 0): ?>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                        <div style="width: 12px; height: 12px; background-color: var(--error-color); border-radius: 2px;"></div>
                                        <span style="font-size: 0.9rem; color: var(--text-secondary);">Admins: <?= htmlspecialchars($adminCount) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div style="width: 150px; height: 150px; margin: 0 auto; background: var(--surface-color); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <span style="color: var(--text-secondary);">No data</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Course Distribution -->
                    <div style="text-align: center;">
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Course Distribution</h3>
                        <?php if (!empty($courseDistribution)): ?>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php
                                $colors = ['var(--primary-color)', 'var(--accent-color)', 'var(--success-color)', 'var(--error-color)', 'var(--warning-color)'];
                                $maxCount = max(array_column($courseDistribution, 'count'));
                                foreach (array_slice($courseDistribution, 0, 5) as $index => $dept):
                                    $percentage = $maxCount > 0 ? ($dept['count'] / $maxCount) * 100 : 0;
                                    $color = $colors[$index % count($colors)];
                                ?>
                                    <div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                            <span style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($dept['department']) ?></span>
                                            <span style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($dept['count']) ?></span>
                                        </div>
                                        <div class="progress" style="height: 8px; background-color: var(--border-color); border-radius: 4px; overflow: hidden;">
                                            <div class="progress-bar" style="width: <?= $percentage ?>%; background-color: <?= $color ?>; height: 100%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                                <i class="fas fa-book" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                <p>No course data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

