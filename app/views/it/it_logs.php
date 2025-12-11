<?php
$logs = $logs ?? [];
?>

<div class="logs-container">
    <div class="logs-header">
        <h1><i class="fas fa-file-alt"></i> Audit Logs</h1>
        <p>System activity and user actions</p>
    </div>

    <div class="logs-content">
        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No logs found.</p>
            </div>
        <?php else: ?>
            <div class="logs-table">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <?= date('M d, Y', strtotime($log['created_at'])) ?><br>
                                    <small><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($log['first_name']): ?>
                                        <strong><?= htmlspecialchars($log['first_name']) ?> <?= htmlspecialchars($log['last_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($log['email']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="action-badge"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td>
                                    <?php if ($log['entity_type']): ?>
                                        <?= htmlspecialchars($log['entity_type']) ?>
                                        <?php if ($log['entity_id']): ?>
                                            #<?= $log['entity_id'] ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['details']): ?>
                                        <span class="log-details" title="<?= htmlspecialchars($log['details']) ?>">
                                            <?= htmlspecialchars(substr($log['details'], 0, 50)) ?>
                                            <?= strlen($log['details']) > 50 ? '...' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.logs-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.logs-header h1 {
    font-size: 2rem;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.logs-header p {
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

.logs-table {
    background: var(--surface-color);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px var(--shadow-color);
    overflow-x: auto;
}

.logs-table table {
    width: 100%;
    border-collapse: collapse;
}

.logs-table th {
    background: var(--background-color);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid var(--border-color);
    position: sticky;
    top: 0;
}

.logs-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.action-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: var(--primary-color);
    color: white;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
}

.log-details {
    cursor: help;
    color: var(--text-primary);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.text-muted {
    color: var(--text-secondary);
}
</style>
