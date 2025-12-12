<?php
// Ensure variables exist with defaults
$reports = $reports ?? [];
$totalReports = $totalReports ?? 0;
$reportsToday = $reportsToday ?? 0;
$scheduledReports = $scheduledReports ?? 0;
$totalDownloads = $totalDownloads ?? 0;
$reportsByType = $reportsByType ?? [
    'academic' => 0,
    'attendance' => 0,
    'financial' => 0,
    'system' => 0,
    'other' => 0
];
$reportsByStatus = $reportsByStatus ?? [
    'completed' => 0,
    'generating' => 0,
    'scheduled' => 0,
    'failed' => 0
];
$search = $search ?? '';
$typeFilter = $typeFilter ?? '';
$periodFilter = $periodFilter ?? '';
$statusFilter = $statusFilter ?? '';
$message = $message ?? null;
$messageType = $messageType ?? 'info';
$editReport = $editReport ?? null;
$tableExists = $tableExists ?? false;
?>

<div class="admin-container">
    <div class="admin-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="<?= htmlspecialchars($url('admin/reports')) ?>" class="btn btn-outline">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
                <button class="btn btn-primary" onclick="generateReport()">
                    <i class="fas fa-plus"></i> Generate Report
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
                        <h3 style="margin: 0 0 0.5rem 0; color: var(--error-color);">Reports Table Not Found</h3>
                        <p style="margin: 0; color: var(--text-secondary);">The reports table doesn't exist yet. Please create it in your database to use this feature.</p>
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

        <!-- Report Statistics -->
        <section class="report-stats" style="margin-bottom: 2rem;">
            <div class="grid grid-4">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalReports) ?></div>
                    <div style="color: var(--text-secondary);">Total Reports</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($reportsToday) ?></div>
                    <div style="color: var(--text-secondary);">Generated Today</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--warning-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($scheduledReports) ?></div>
                    <div style="color: var(--text-secondary);">Scheduled</div>
                </div>
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2.5rem; color: var(--accent-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-download"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= htmlspecialchars($totalDownloads) ?></div>
                    <div style="color: var(--text-secondary);">Downloads</div>
                </div>
            </div>
        </section>

        <!-- Report Filter -->
        <section class="report-filter" style="margin-bottom: 2rem;">
            <div class="card">
                <form method="GET" action="<?= htmlspecialchars($url('admin/reports')) ?>" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="search" class="form-input" placeholder="Search reports..." value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter'){this.form.submit();}">
                    </div>
                    <div>
                        <select name="type" class="form-input" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="academic" <?= $typeFilter === 'academic' ? 'selected' : '' ?>>Academic</option>
                            <option value="attendance" <?= $typeFilter === 'attendance' ? 'selected' : '' ?>>Attendance</option>
                            <option value="financial" <?= $typeFilter === 'financial' ? 'selected' : '' ?>>Financial</option>
                            <option value="system" <?= $typeFilter === 'system' ? 'selected' : '' ?>>System</option>
                            <option value="other" <?= $typeFilter === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <select name="period" class="form-input" onchange="this.form.submit()">
                            <option value="">All Periods</option>
                            <option value="daily" <?= $periodFilter === 'daily' ? 'selected' : '' ?>>Daily</option>
                            <option value="weekly" <?= $periodFilter === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= $periodFilter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= $periodFilter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                            <option value="on_demand" <?= $periodFilter === 'on_demand' ? 'selected' : '' ?>>On Demand</option>
                        </select>
                    </div>
                    <div>
                        <select name="status" class="form-input" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="generating" <?= $statusFilter === 'generating' ? 'selected' : '' ?>>Generating</option>
                            <option value="scheduled" <?= $statusFilter === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?= htmlspecialchars($url('admin/reports')) ?>" class="btn btn-outline">Clear</a>
                    </div>
                </form>
            </div>
        </section>

        <!-- Quick Reports -->
        <section class="quick-reports" style="margin-bottom: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-bolt" style="color: var(--accent-color); margin-right: 0.5rem;"></i>
                        Quick Reports
                    </h2>
                </div>
                <div class="grid grid-4">
                    <button class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="generateStudentReport()">
                        <i class="fas fa-user-graduate" style="font-size: 2rem;"></i>
                        <span>Student Report</span>
                    </button>
                    <button class="btn btn-outline" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="generateAttendanceReport()">
                        <i class="fas fa-calendar-check" style="font-size: 2rem;"></i>
                        <span>Attendance Report</span>
                    </button>
                    <button class="btn btn-outline" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="generateGradeReport()">
                        <i class="fas fa-chart-line" style="font-size: 2rem;"></i>
                        <span>Grade Report</span>
                    </button>
                    <button class="btn btn-outline" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;" onclick="generateSystemReport()">
                        <i class="fas fa-server" style="font-size: 2rem;"></i>
                        <span>System Report</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Recent Reports -->
        <section class="recent-reports">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-history" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                        Recent Reports
                    </h2>
                </div>

                <!-- Reports List -->
                <div class="reports-list">
                    <?php if (empty($reports) && $tableExists): ?>
                        <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No reports found. Generate your first report to get started.</p>
                        </div>
                    <?php elseif (!empty($reports)): ?>
                        <?php foreach ($reports as $report): 
                            $status = $report['status'] ?? 'completed';
                            $type = $report['type'] ?? 'other';
                            $period = $report['period'] ?? 'on_demand';
                            
                            // Status colors
                            $statusColors = [
                                'completed' => 'var(--success-color)',
                                'generating' => 'var(--warning-color)',
                                'scheduled' => 'var(--primary-color)',
                                'failed' => 'var(--error-color)'
                            ];
                            $statusColor = $statusColors[$status] ?? 'var(--text-secondary)';
                            
                            // Status icons
                            $statusIcons = [
                                'completed' => 'fa-file-alt',
                                'generating' => 'fa-spinner fa-spin',
                                'scheduled' => 'fa-clock',
                                'failed' => 'fa-exclamation-triangle'
                            ];
                            $statusIcon = $statusIcons[$status] ?? 'fa-file-alt';
                            
                            // Format date
                            $genDate = $report['created_at'] ?? '';
                            $timeAgo = '';
                            if ($genDate) {
                                $date = new \DateTime($genDate);
                                $now = new \DateTime();
                                $diff = $now->diff($date);
                                if ($diff->days > 0) {
                                    $timeAgo = $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
                                } elseif ($diff->h > 0) {
                                    $timeAgo = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                                } elseif ($diff->i > 0) {
                                    $timeAgo = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
                                } else {
                                    $timeAgo = 'Just now';
                                }
                            }
                        ?>
                            <div class="report-item" data-type="<?= htmlspecialchars($type) ?>" data-period="<?= htmlspecialchars($period) ?>" data-status="<?= htmlspecialchars($status) ?>" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-bottom: 1px solid var(--border-color);">
                                <div style="width: 40px; height: 40px; background-color: <?= $statusColor ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas <?= $statusIcon ?>"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                        <h4 style="margin: 0; color: var(--text-primary);"><?= htmlspecialchars($report['title'] ?? 'Untitled Report') ?></h4>
                                        <span style="background-color: <?= $statusColor ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; text-transform: capitalize;"><?= htmlspecialchars($status) ?></span>
                                    </div>
                                    <p style="margin: 0 0 0.5rem 0; color: var(--text-secondary);"><?= htmlspecialchars(ucfirst($type) . ' report - ' . ucfirst(str_replace('_', ' ', $period)) . ' period') ?></p>
                                    <div style="display: flex; gap: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
                                        <span><i class="fas fa-tag" style="margin-right: 0.25rem;"></i><?= htmlspecialchars(ucfirst($type)) ?></span>
                                        <span><i class="fas fa-calendar" style="margin-right: 0.25rem;"></i><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $period))) ?></span>
                                        <span><i class="fas fa-clock" style="margin-right: 0.25rem;"></i><?= $timeAgo ?: 'N/A' ?></span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.25rem;">
                                    <?php if ($status === 'completed'): ?>
                                        <button class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="viewReport(<?= htmlspecialchars($report['id'] ?? '') ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="downloadReport(<?= htmlspecialchars($report['id'] ?? '') ?>)">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    <?php elseif ($status === 'generating'): ?>
                                        <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="cancelReport(<?= htmlspecialchars($report['id'] ?? '') ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php elseif ($status === 'scheduled'): ?>
                                        <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editReport(<?= htmlspecialchars($report['id'] ?? '') ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="cancelSchedule(<?= htmlspecialchars($report['id'] ?? '') ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php elseif ($status === 'failed'): ?>
                                        <button class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="retryReport(<?= htmlspecialchars($report['id'] ?? '') ?>)">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="editReport(<?= htmlspecialchars($report['id'] ?? '') ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="deleteReport(<?= htmlspecialchars($report['id'] ?? '') ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Analytics Dashboard -->
        <section class="analytics-dashboard" style="margin-top: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-chart-pie" style="color: var(--accent-color); margin-right: 0.5rem;"></i>
                        Analytics Dashboard
                    </h2>
                </div>
                <div class="grid grid-3">
                    <!-- Report Types Chart -->
                    <div style="text-align: center;">
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Report Types</h3>
                        <?php if ($totalReports > 0): ?>
                            <?php
                            $totalTypeCount = array_sum($reportsByType);
                            $academicDeg = ($reportsByType['academic'] / $totalTypeCount) * 360;
                            $attendanceDeg = ($reportsByType['attendance'] / $totalTypeCount) * 360;
                            $financialDeg = ($reportsByType['financial'] / $totalTypeCount) * 360;
                            $systemDeg = ($reportsByType['system'] / $totalTypeCount) * 360;
                            $otherDeg = ($reportsByType['other'] / $totalTypeCount) * 360;
                            $cumulative = 0;
                            ?>
                            <div style="width: 200px; height: 200px; margin: 0 auto; background: conic-gradient(
                                var(--primary-color) 0deg <?= $cumulative += $academicDeg ?>deg,
                                var(--accent-color) <?= $cumulative ?>deg <?= $cumulative += $attendanceDeg ?>deg,
                                var(--success-color) <?= $cumulative ?>deg <?= $cumulative += $financialDeg ?>deg,
                                var(--warning-color) <?= $cumulative ?>deg <?= $cumulative += $systemDeg ?>deg,
                                var(--text-secondary) <?= $cumulative ?>deg 360deg
                            ); border-radius: 50%; position: relative;">
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: var(--surface-color); width: 120px; height: 120px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <span style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($totalTypeCount) ?></span>
                                </div>
                            </div>
                            <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.25rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                    <div style="width: 12px; height: 12px; background-color: var(--primary-color); border-radius: 2px;"></div>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">Academic: <?= htmlspecialchars($reportsByType['academic']) ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                    <div style="width: 12px; height: 12px; background-color: var(--accent-color); border-radius: 2px;"></div>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">Attendance: <?= htmlspecialchars($reportsByType['attendance']) ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                    <div style="width: 12px; height: 12px; background-color: var(--success-color); border-radius: 2px;"></div>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">Financial: <?= htmlspecialchars($reportsByType['financial']) ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                    <div style="width: 12px; height: 12px; background-color: var(--warning-color); border-radius: 2px;"></div>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">System: <?= htmlspecialchars($reportsByType['system']) ?></span>
                                </div>
                                <?php if ($reportsByType['other'] > 0): ?>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                        <div style="width: 12px; height: 12px; background-color: var(--text-secondary); border-radius: 2px;"></div>
                                        <span style="font-size: 0.9rem; color: var(--text-secondary);">Other: <?= htmlspecialchars($reportsByType['other']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div style="width: 200px; height: 200px; margin: 0 auto; background: var(--surface-color); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <span style="color: var(--text-secondary);">No data</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Report Status Chart -->
                    <div style="text-align: center;">
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Report Status</h3>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">Completed</span>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($reportsByStatus['completed']) ?></span>
                                </div>
                                <div class="progress" style="height: 8px; background-color: var(--border-color); border-radius: 4px; overflow: hidden;">
                                    <div class="progress-bar" style="width: <?= $totalReports > 0 ? ($reportsByStatus['completed'] / $totalReports * 100) : 0 ?>%; background-color: var(--success-color); height: 100%;"></div>
                                </div>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">Generating</span>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($reportsByStatus['generating']) ?></span>
                                </div>
                                <div class="progress" style="height: 8px; background-color: var(--border-color); border-radius: 4px; overflow: hidden;">
                                    <div class="progress-bar" style="width: <?= $totalReports > 0 ? ($reportsByStatus['generating'] / $totalReports * 100) : 0 ?>%; background-color: var(--warning-color); height: 100%;"></div>
                                </div>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">Scheduled</span>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($reportsByStatus['scheduled']) ?></span>
                                </div>
                                <div class="progress" style="height: 8px; background-color: var(--border-color); border-radius: 4px; overflow: hidden;">
                                    <div class="progress-bar" style="width: <?= $totalReports > 0 ? ($reportsByStatus['scheduled'] / $totalReports * 100) : 0 ?>%; background-color: var(--primary-color); height: 100%;"></div>
                                </div>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">Failed</span>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($reportsByStatus['failed']) ?></span>
                                </div>
                                <div class="progress" style="height: 8px; background-color: var(--border-color); border-radius: 4px; overflow: hidden;">
                                    <div class="progress-bar" style="width: <?= $totalReports > 0 ? ($reportsByStatus['failed'] / $totalReports * 100) : 0 ?>%; background-color: var(--error-color); height: 100%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div style="text-align: center;">
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">Performance Metrics</h3>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">Avg Generation Time</span>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">2.3 min</span>
                                </div>
                                <div class="progress" style="height: 8px; background-color: var(--border-color); border-radius: 4px; overflow: hidden;">
                                    <div class="progress-bar" style="width: 46%; background-color: var(--success-color); height: 100%;"></div>
                                </div>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">Success Rate</span>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">94%</span>
                                </div>
                                <div class="progress" style="height: 8px; background-color: var(--border-color); border-radius: 4px; overflow: hidden;">
                                    <div class="progress-bar" style="width: 94%; background-color: var(--success-color); height: 100%;"></div>
                                </div>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">Storage Used</span>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);">68%</span>
                                </div>
                                <div class="progress" style="height: 8px; background-color: var(--border-color); border-radius: 4px; overflow: hidden;">
                                    <div class="progress-bar" style="width: 68%; background-color: var(--warning-color); height: 100%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Add/Edit Report Modal -->
<div id="reportFormModal" class="modal" data-header-style="primary">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 id="reportModalTitle">Generate Report</h2>
            <button class="modal-close" onclick="closeReportFormModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="reportForm" method="POST" action="<?= htmlspecialchars($url('admin/reports')) ?>" onsubmit="return handleReportFormSubmit(event)">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="report_id" id="reportId" value="">
            <div class="form-group">
                <label class="form-label">Report Name *</label>
                <input type="text" name="title" id="reportName" class="form-input" placeholder="e.g., Student Enrollment Report" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Report Type *</label>
                    <select name="type" id="reportType" class="form-input" required>
                        <option value="">Select Type</option>
                        <option value="academic">Academic</option>
                        <option value="attendance">Attendance</option>
                        <option value="financial">Financial</option>
                        <option value="system">System</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Report Period *</label>
                    <select name="period" id="reportPeriod" class="form-input" required>
                        <option value="">Select Period</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                        <option value="on_demand">On Demand</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="reportStatus" class="form-input">
                    <option value="generating">Generating</option>
                    <option value="completed">Completed</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">File Path (optional)</label>
                <input type="text" name="file_path" id="filePath" class="form-input" placeholder="e.g., /reports/enrollment_2024.pdf">
            </div>
            <div class="form-group">
                <label class="form-label">Parameters (JSON, optional)</label>
                <textarea name="parameters" id="reportParameters" class="form-input" rows="3" placeholder='{"department": "Computer Science", "year": 2024}'></textarea>
                <small style="color: var(--text-secondary); font-size: 0.9rem;">Enter JSON format parameters for the report</small>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Save Report
                </button>
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeReportFormModal()">
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

// Report actions
function viewReport(reportId) {
    if (typeof showNotification !== 'undefined') {
        showNotification(`Viewing report ${reportId}...`, 'info');
    }
}

function editReport(reportId) {
    // Redirect to edit page with report ID
    const editUrl = '<?= htmlspecialchars($url('admin/reports')) ?>?edit=' + reportId;
    window.location.href = editUrl;
}

function downloadReport(reportId) {
    if (typeof showNotification !== 'undefined') {
        showNotification(`Downloading report ${reportId}...`, 'info');
    }
}

function cancelReport(reportId) {
    if (confirm('Are you sure you want to cancel this report generation?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= htmlspecialchars($url('admin/reports')) ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'report_id';
        idInput.value = reportId;
        form.appendChild(idInput);
        
        const titleInput = document.createElement('input');
        titleInput.type = 'hidden';
        titleInput.name = 'title';
        titleInput.value = 'Cancelled Report';
        form.appendChild(titleInput);
        
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = 'other';
        form.appendChild(typeInput);
        
        const periodInput = document.createElement('input');
        periodInput.type = 'hidden';
        periodInput.name = 'period';
        periodInput.value = 'on_demand';
        form.appendChild(periodInput);
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = 'failed';
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function cancelSchedule(reportId) {
    if (confirm('Are you sure you want to cancel this scheduled report?')) {
        cancelReport(reportId);
    }
}

function retryReport(reportId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= htmlspecialchars($url('admin/reports')) ?>';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'update';
    form.appendChild(actionInput);
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'report_id';
    idInput.value = reportId;
    form.appendChild(idInput);
    
    const titleInput = document.createElement('input');
    titleInput.type = 'hidden';
    titleInput.name = 'title';
    titleInput.value = 'Retry Report';
    form.appendChild(titleInput);
    
    const typeInput = document.createElement('input');
    typeInput.type = 'hidden';
    typeInput.name = 'type';
    typeInput.value = 'other';
    form.appendChild(typeInput);
    
    const periodInput = document.createElement('input');
    periodInput.type = 'hidden';
    periodInput.name = 'period';
    periodInput.value = 'on_demand';
    form.appendChild(periodInput);
    
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = 'generating';
    form.appendChild(statusInput);
    
    document.body.appendChild(form);
    form.submit();
}

function deleteReport(reportId) {
    if (confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= htmlspecialchars($url('admin/reports')) ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'report_id';
        idInput.value = reportId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Quick report generation
function generateStudentReport() {
    if (typeof showNotification !== 'undefined') {
        showNotification('Generating student report...', 'info');
    }
}

function generateAttendanceReport() {
    if (typeof showNotification !== 'undefined') {
        showNotification('Generating attendance report...', 'info');
    }
}

function generateGradeReport() {
    if (typeof showNotification !== 'undefined') {
        showNotification('Generating grade report...', 'info');
    }
}

function generateSystemReport() {
    if (typeof showNotification !== 'undefined') {
        showNotification('Generating system report...', 'info');
    }
}

function generateReport() {
    document.getElementById('reportForm').reset();
    document.getElementById('reportId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('reportModalTitle').textContent = 'Generate Report';
    document.getElementById('reportStatus').value = 'generating';
    if (typeof showModal !== 'undefined') {
        showModal(document.getElementById('reportFormModal'));
    } else {
        document.getElementById('reportFormModal').classList.add('active');
        document.getElementById('reportFormModal').style.display = 'flex';
    }
}

function closeReportFormModal() {
    const modal = document.getElementById('reportFormModal');
    if (typeof hideModal !== 'undefined') {
        hideModal(modal);
    } else {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

function handleReportFormSubmit(e) {
    // Form validation is handled by HTML5 required attributes
    // The form will submit normally to the controller
    return true;
}

// Auto-populate form if editing
<?php if ($editReport): ?>
document.addEventListener('DOMContentLoaded', function() {
    const report = <?php echo json_encode($editReport, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (report) {
        document.getElementById('reportForm').elements['title'].value = report.title || '';
        document.getElementById('reportForm').elements['type'].value = report.type || '';
        document.getElementById('reportForm').elements['period'].value = report.period || '';
        document.getElementById('reportForm').elements['status'].value = report.status || 'generating';
        document.getElementById('reportForm').elements['file_path'].value = report.file_path || '';
        
        // Handle parameters
        if (report.parameters) {
            const params = typeof report.parameters === 'string' ? report.parameters : JSON.stringify(report.parameters, null, 2);
            document.getElementById('reportForm').elements['parameters'].value = params;
        }
        
        document.getElementById('reportId').value = report.id;
        document.getElementById('formAction').value = 'update';
        document.getElementById('reportModalTitle').textContent = 'Edit Report';
        
        // Open modal
        generateReport();
    }
});
<?php endif; ?>
</script>
