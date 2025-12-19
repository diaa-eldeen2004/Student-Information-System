<?php
$logs = $logs ?? [];
$stats = $stats ?? ['total' => 0, 'errors' => 0, 'warnings' => 0, 'info' => 0, 'success' => 0, 'critical' => 0];
$filters = $filters ?? ['action' => '', 'entity_type' => '', 'dateRange' => 'month', 'search' => ''];
$entityTypes = $entityTypes ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'info';
?>

<div class="logs-container">
    <div class="logs-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <h1><i class="fas fa-file-alt"></i> System Logs</h1>
                <p>View and monitor system activity logs.</p>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="<?= htmlspecialchars($url('it/logs')) ?>" class="btn btn-primary">
                    <i class="fas fa-sync"></i> Refresh
                </a>
                <form method="POST" action="<?= htmlspecialchars($url('it/logs')) ?>" style="display: inline;" onsubmit="return confirm('Export logs as CSV?');">
                    <input type="hidden" name="action" value="export-logs">
                    <input type="hidden" name="action_filter" value="<?= htmlspecialchars($filters['action']) ?>">
                    <input type="hidden" name="entity_type" value="<?= htmlspecialchars($filters['entity_type']) ?>">
                    <input type="hidden" name="dateRange" value="<?= htmlspecialchars($filters['dateRange']) ?>">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($filters['search']) ?>">
                    <button type="submit" class="btn btn-outline">
                        <i class="fas fa-download"></i> Export
                    </button>
                </form>
                <form method="POST" action="<?= htmlspecialchars($url('it/logs')) ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear all logs? This action cannot be undone.');">
                    <input type="hidden" name="action" value="clear-logs">
                    <button type="submit" class="btn btn-outline">
                        <i class="fas fa-trash"></i> Clear
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Log Stats -->
    <section class="log-stats" style="margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: var(--error-color); margin-bottom: 0.5rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= $stats['errors'] ?></div>
                <div style="color: var(--text-secondary);">Errors</div>
            </div>
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: var(--warning-color); margin-bottom: 0.5rem;">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= $stats['warnings'] ?></div>
                <div style="color: var(--text-secondary);">Warnings</div>
            </div>
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= $stats['info'] ?></div>
                <div style="color: var(--text-secondary);">Info</div>
            </div>
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: var(--success-color); margin-bottom: 0.5rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;"><?= $stats['total'] ?></div>
                <div style="color: var(--text-secondary);">Total Logs</div>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="log-filters" style="margin-bottom: 2rem;">
        <div class="card">
            <form method="GET" action="<?= htmlspecialchars($url('it/logs')) ?>" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; padding: 1.5rem;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-input" placeholder="Search logs..." value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
                <div class="form-group" style="min-width: 150px;">
                    <label class="form-label">Action</label>
                    <input type="text" name="action" class="form-input" placeholder="Filter by action..." value="<?= htmlspecialchars($filters['action']) ?>">
                </div>
                <div class="form-group" style="min-width: 150px;">
                    <label class="form-label">Date Range</label>
                    <select name="dateRange" class="form-input" onchange="this.form.submit()">
                        <option value="today" <?= $filters['dateRange'] === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $filters['dateRange'] === 'week' ? 'selected' : '' ?>>Last Week</option>
                        <option value="month" <?= $filters['dateRange'] === 'month' ? 'selected' : '' ?>>Last Month</option>
                        <option value="all" <?= $filters['dateRange'] === 'all' ? 'selected' : '' ?>>All Time</option>
                    </select>
                </div>
                <div class="form-group" style="min-width: 150px;">
                    <label class="form-label">Entity Type</label>
                    <select name="entity_type" class="form-input" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <?php foreach ($entityTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $filters['entity_type'] === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?= htmlspecialchars($url('it/logs')) ?>" class="btn btn-outline">Clear</a>
                </div>
            </form>
        </div>
    </section>

    <!-- Logs Display -->
    <section class="logs-display">
        <div class="card">
            <div class="card-header" style="padding: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <h2 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-list" style="color: var(--primary-color);"></i>
                    System Logs
                </h2>
                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 0.5rem;">
                    Showing <?= count($logs) ?> log(s)
                </div>
            </div>
            <div id="logsContainer" style="max-height: 600px; overflow-y: auto; padding: 1rem;">
                <?php if (empty($logs)): ?>
                    <div id="noLogs" style="padding: 3rem; text-align: center;">
                        <i class="fas fa-file-alt" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p style="color: var(--text-secondary);">No logs found for the selected filters.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        // Determine log level based on action
                        $level = 'info';
                        $actionLower = strtolower($log['action'] ?? '');
                        if (strpos($actionLower, 'error') !== false || strpos($actionLower, 'failed') !== false) {
                            $level = 'error';
                        } elseif (strpos($actionLower, 'warning') !== false || strpos($actionLower, 'reject') !== false) {
                            $level = 'warning';
                        } elseif (strpos($actionLower, 'success') !== false || strpos($actionLower, 'approve') !== false || strpos($actionLower, 'create') !== false) {
                            $level = 'success';
                        } elseif (strpos($actionLower, 'critical') !== false) {
                            $level = 'critical';
                        }
                        ?>
                        <div class="log-entry <?= $level ?>">
                            <span class="log-timestamp"><?= date('M j, Y g:i:s A', strtotime($log['created_at'] ?? 'now')) ?></span>
                            <span class="log-level <?= $level ?>"><?= strtoupper($level) ?></span>
                            <span class="log-source">[<?= htmlspecialchars($log['action'] ?? 'system') ?>]</span>
                            <?php if (!empty($log['user_role'])): ?>
                                <span style="color: var(--primary-color); font-size: 0.8rem;">[<?= htmlspecialchars($log['user_role']) ?>]</span>
                            <?php endif; ?>
                            <span style="color: var(--text-primary);">
                                <?php if ($log['first_name']): ?>
                                    <?= htmlspecialchars($log['first_name']) ?> <?= htmlspecialchars($log['last_name']) ?> - 
                                <?php endif; ?>
                                <?= htmlspecialchars($log['action'] ?? 'Action') ?>
                                <?php if ($log['entity_type']): ?>
                                    on <?= htmlspecialchars($log['entity_type']) ?>
                                    <?php if ($log['entity_id']): ?>
                                        #<?= $log['entity_id'] ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </span>
                            <?php if (!empty($log['details'])): ?>
                                <div style="margin-top: 0.5rem; padding-left: 2rem; color: var(--text-secondary); font-size: 0.8rem; white-space: pre-wrap;">
                                    <?= htmlspecialchars($log['details']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($log['ip_address'])): ?>
                                <div style="margin-top: 0.25rem; padding-left: 2rem; color: var(--text-secondary); font-size: 0.75rem;">
                                    <i class="fas fa-network-wired"></i> <?= htmlspecialchars($log['ip_address']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<style>
/* Light Mode CSS Variables (Default) */
:root {
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --bg-tertiary: #f1f5f9;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border-color: #e2e8f0;
    --border-light: #cbd5e1;
    --primary-color: #3b82f6;
    --primary-hover: #2563eb;
    --success-color: #10b981;
    --error-color: #ef4444;
    --warning-color: #f59e0b;
    --shadow-sm: rgba(0, 0, 0, 0.1);
    --shadow-md: rgba(0, 0, 0, 0.15);
    --shadow-lg: rgba(0, 0, 0, 0.2);
}

/* Dark Mode CSS Variables */
[data-theme="dark"] {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-tertiary: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --border-color: #334155;
    --border-light: #475569;
    --primary-color: #3b82f6;
    --primary-hover: #2563eb;
    --success-color: #10b981;
    --error-color: #ef4444;
    --warning-color: #f59e0b;
    --shadow-sm: rgba(0, 0, 0, 0.3);
    --shadow-md: rgba(0, 0, 0, 0.4);
    --shadow-lg: rgba(0, 0, 0, 0.5);
}

.logs-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    background: var(--bg-primary);
    min-height: 100vh;
    color: var(--text-primary);
}

.logs-header {
    margin-bottom: 2.5rem;
    padding: 2rem;
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
    border-radius: 16px;
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 12px var(--shadow-md);
}

.logs-header h1 {
    font-size: 2.5rem;
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--text-primary);
}

.logs-header h1 i {
    font-size: 2rem;
}

.logs-header > div {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    width: 100%;
}

.logs-header p {
    font-size: 1.1rem;
    margin: 0;
    opacity: 0.95;
    color: var(--text-secondary);
}

.card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    box-shadow: 0 4px 12px var(--shadow-md);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card:hover {
    box-shadow: 0 8px 24px var(--shadow-lg);
    transform: translateY(-2px);
}

.log-entry {
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    padding: 0.75rem;
    border-left: 3px solid;
    margin-bottom: 0.5rem;
    background-color: var(--bg-tertiary);
    border-radius: 4px;
    transition: background-color 0.2s;
    color: var(--text-primary);
}

.log-entry:hover {
    background-color: rgba(59, 130, 246, 0.15);
}

.log-entry.error { 
    border-color: var(--error-color); 
}

.log-entry.warning { 
    border-color: var(--warning-color); 
}

.log-entry.info { 
    border-color: var(--primary-color); 
}

.log-entry.success { 
    border-color: var(--success-color); 
}

.log-entry.critical { 
    border-color: var(--error-color); 
    background-color: rgba(239, 68, 68, 0.2); 
}

.log-timestamp {
    color: var(--text-secondary);
    font-weight: 600;
    margin-right: 1rem;
    font-size: 0.8rem;
}

.log-level {
    font-weight: 700;
    margin-right: 0.5rem;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    display: inline-block;
    min-width: 60px;
    text-align: center;
}

.log-level.error { 
    background-color: var(--error-color); 
    color: white; 
}

.log-level.warning { 
    background-color: var(--warning-color); 
    color: white; 
}

.log-level.info { 
    background-color: var(--primary-color); 
    color: white; 
}

.log-level.success { 
    background-color: var(--success-color); 
    color: white; 
}

.log-level.critical { 
    background-color: var(--error-color); 
    color: white; 
}

.log-source {
    color: var(--primary-color);
    font-weight: 500;
    margin-right: 0.5rem;
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

.form-input {
    padding: 0.75rem;
    border: 1px solid var(--border-light);
    border-radius: 6px;
    background: var(--bg-tertiary);
    color: var(--text-primary);
    font-size: 1rem;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.alert-error {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.alert-info {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

/* Responsive Design matching Doctor pages */
@media (max-width: 768px) {
    .logs-container {
        padding: 1rem;
    }
    
    .logs-header {
        padding: 1.5rem;
    }
    
    .logs-header h1 {
        font-size: 2rem;
    }
}
</style>

<script>
// Auto-scroll to bottom on page load
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('logsContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
    
    // Show toast notification if message exists
    <?php if ($message): ?>
    if (typeof Toastify !== 'undefined') {
        const messageType = '<?= htmlspecialchars($messageType, ENT_QUOTES) ?>';
        const message = <?= json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        
        let backgroundColor = '#2563eb';
        if (messageType === 'success') {
            backgroundColor = '#10b981';
        } else if (messageType === 'error') {
            backgroundColor = '#ef4444';
        } else if (messageType === 'warning') {
            backgroundColor = '#f59e0b';
        }
        
        Toastify({
            text: message,
            duration: 5000,
            gravity: "top",
            position: "right",
            style: {
                background: backgroundColor,
            },
            close: true,
        }).showToast();
        
        // Clean URL
        if (window.location.search.includes('message=')) {
            const url = new URL(window.location);
            url.searchParams.delete('message');
            url.searchParams.delete('type');
            window.history.replaceState({}, '', url);
        }
    }
    <?php endif; ?>
});
</script>
