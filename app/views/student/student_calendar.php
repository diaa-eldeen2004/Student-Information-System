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
                        </div>
                    </div>
                </div>
                <div style="padding: 1.5rem;">
                    <?php if (empty($events)): ?>
                        <p class="text-muted text-center" style="padding: 3rem 0;">No events scheduled for this month.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Event</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events as $event): ?>
                                        <tr>
                                            <td>
                                                <strong><?= !empty($event['start_date']) ? date('M d, Y', strtotime($event['start_date'])) : 'N/A' ?></strong>
                                                <?php if (!empty($event['end_date']) && $event['end_date'] != $event['start_date']): ?>
                                                    <br><small>to <?= date('M d, Y', strtotime($event['end_date'])) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($event['title'] ?? '') ?></strong>
                                                <?php if (!empty($event['description'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars(substr($event['description'], 0, 100)) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">
                                                    <?= htmlspecialchars($event['event_type'] ?? 'event') ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($event['location'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
                                <div style="padding: 1rem; background-color: var(--background-color); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                                    <strong><?= htmlspecialchars($event['title'] ?? '') ?></strong><br>
                                    <small class="text-muted">
                                        <?= !empty($event['start_date']) ? date('M d, Y', strtotime($event['start_date'])) : 'N/A' ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
