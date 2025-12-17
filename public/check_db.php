<?php
/**
 * Database Health Check - Web Interface
 * Access via: http://localhost/Student-Information-System/public/check_db.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Health Check</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f9fafb;
            border-radius: 6px;
            border-left: 4px solid #2563eb;
        }
        .success {
            color: #10b981;
            font-weight: bold;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .warning {
            color: #f59e0b;
            font-weight: bold;
        }
        .info {
            color: #3b82f6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f3f4f6;
            font-weight: 600;
        }
        .status-ok { color: #10b981; }
        .status-missing { color: #ef4444; }
        .status-warning { color: #f59e0b; }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Health Check</h1>

        <?php
        // Load database configuration
        $configFile = __DIR__ . '/../app/config/database.php';
        if (!file_exists($configFile)) {
            echo '<div class="section error">❌ ERROR: Database config file not found: ' . htmlspecialchars($configFile) . '</div>';
            exit;
        }

        $config = require $configFile;

        echo '<div class="section">';
        echo '<h2>📋 Configuration</h2>';
        echo '<table>';
        echo '<tr><th>Setting</th><th>Value</th></tr>';
        echo '<tr><td>Host</td><td><code>' . htmlspecialchars($config['host']) . '</code></td></tr>';
        echo '<tr><td>Port</td><td><code>' . htmlspecialchars($config['port']) . '</code></td></tr>';
        echo '<tr><td>Database</td><td><code>' . htmlspecialchars($config['database']) . '</code></td></tr>';
        echo '<tr><td>Username</td><td><code>' . htmlspecialchars($config['username']) . '</code></td></tr>';
        echo '<tr><td>Password</td><td><code>' . (empty($config['password']) ? '(empty)' : '***') . '</code></td></tr>';
        echo '</table>';
        echo '</div>';

        // Test connection
        try {
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            $pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            echo '<div class="section success">';
            echo '<h2>✅ Connection Status</h2>';
            echo '<p class="success">✓ Database connection successful!</p>';

            // Get database info
            $stmt = $pdo->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
            $dbInfo = $stmt->fetch();
            echo '<p><strong>Current Database:</strong> <code>' . htmlspecialchars($dbInfo['current_db']) . '</code></p>';
            echo '<p><strong>MySQL Version:</strong> <code>' . htmlspecialchars($dbInfo['mysql_version']) . '</code></p>';
            echo '</div>';

            // Check required tables
            echo '<div class="section">';
            echo '<h2>📊 Required Tables</h2>';
            
            $requiredTables = [
                'users' => ['id', 'email', 'password', 'role'],
                'courses' => ['course_id', 'course_code', 'name'],
                'sections' => ['section_id', 'course_id', 'doctor_id'],
                'enrollments' => ['enrollment_id', 'student_id', 'section_id'],
                'enrollment_requests' => ['request_id', 'student_id', 'section_id', 'status'],
                'doctors' => ['doctor_id', 'user_id'],
                'students' => ['student_id', 'user_id'],
                'it_officers' => ['it_id', 'user_id'],
                'audit_logs' => ['log_id', 'user_id', 'action'],
                'notifications' => ['notification_id', 'user_id', 'title', 'message'],
            ];

            $stmt = $pdo->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $issues = [];
            $warnings = [];
            
            echo '<table>';
            echo '<tr><th>Table</th><th>Status</th><th>Columns Check</th></tr>';
            
            foreach ($requiredTables as $table => $requiredColumns) {
                if (in_array($table, $existingTables)) {
                    // Check columns
                    $stmt = $pdo->query("DESCRIBE `{$table}`");
                    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $missingColumns = [];
                    foreach ($requiredColumns as $col) {
                        if (!in_array($col, $columns)) {
                            $missingColumns[] = $col;
                        }
                    }
                    
                    if (!empty($missingColumns)) {
                        $warnings[] = "Table '{$table}' is missing columns: " . implode(', ', $missingColumns);
                        echo '<tr>';
                        echo '<td><code>' . htmlspecialchars($table) . '</code></td>';
                        echo '<td class="status-warning">⚠ Exists</td>';
                        echo '<td class="status-warning">Missing: ' . htmlspecialchars(implode(', ', $missingColumns)) . '</td>';
                        echo '</tr>';
                    } else {
                        echo '<tr>';
                        echo '<td><code>' . htmlspecialchars($table) . '</code></td>';
                        echo '<td class="status-ok">✓ Exists</td>';
                        echo '<td class="status-ok">✓ OK</td>';
                        echo '</tr>';
                    }
                } else {
                    $issues[] = "Table '{$table}' is MISSING";
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($table) . '</code></td>';
                    echo '<td class="status-missing">✗ Missing</td>';
                    echo '<td class="status-missing">-</td>';
                    echo '</tr>';
                }
            }
            echo '</table>';
            echo '</div>';

            // Check sample data
            echo '<div class="section">';
            echo '<h2>📈 Sample Data</h2>';
            try {
                $userCount = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
                $courseCount = $pdo->query("SELECT COUNT(*) as count FROM courses")->fetch()['count'];
                $doctorCount = $pdo->query("SELECT COUNT(*) as count FROM doctors")->fetch()['count'];
                $studentCount = $pdo->query("SELECT COUNT(*) as count FROM students")->fetch()['count'];
                
                echo '<table>';
                echo '<tr><th>Entity</th><th>Count</th></tr>';
                echo '<tr><td>Users</td><td>' . $userCount . '</td></tr>';
                echo '<tr><td>Courses</td><td>' . $courseCount . '</td></tr>';
                echo '<tr><td>Doctors</td><td>' . $doctorCount . '</td></tr>';
                echo '<tr><td>Students</td><td>' . $studentCount . '</td></tr>';
                echo '</table>';
                
                if ($userCount == 0) {
                    $warnings[] = "No users found in database. You may need to create user accounts.";
                }
            } catch (PDOException $e) {
                $warnings[] = "Could not check sample data: " . $e->getMessage();
                echo '<p class="warning">⚠ Could not check sample data</p>';
            }
            echo '</div>';

            // Summary
            echo '<div class="section">';
            echo '<h2>📝 Summary</h2>';
            
            if (empty($issues) && empty($warnings)) {
                echo '<p class="success">✅ All checks passed! Database is healthy.</p>';
            } else {
                if (!empty($issues)) {
                    echo '<h3 class="error">❌ Critical Issues:</h3>';
                    echo '<ul>';
                    foreach ($issues as $issue) {
                        echo '<li class="error">' . htmlspecialchars($issue) . '</li>';
                    }
                    echo '</ul>';
                }
                
                if (!empty($warnings)) {
                    echo '<h3 class="warning">⚠ Warnings:</h3>';
                    echo '<ul>';
                    foreach ($warnings as $warning) {
                        echo '<li class="warning">' . htmlspecialchars($warning) . '</li>';
                    }
                    echo '</ul>';
                }
                
                echo '<h3 class="info">💡 Recommendations:</h3>';
                if (!empty($issues)) {
                    echo '<ol>';
                    echo '<li>Run the migration script: <code>php database/migrate.php</code></li>';
                    echo '<li>Or import the schema using phpMyAdmin: Import <code>database/schema.sql</code></li>';
                    echo '<li>Or use command line: <code>mysql -u root swe_app < database/schema.sql</code></li>';
                    echo '</ol>';
                }
            }
            echo '</div>';

        } catch (PDOException $e) {
            echo '<div class="section error">';
            echo '<h2>❌ Connection Failed</h2>';
            echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            
            echo '<h3>Common Solutions:</h3>';
            echo '<ol>';
            echo '<li>Check if MySQL is running (XAMPP Control Panel)</li>';
            echo '<li>Verify database name in <code>app/config/database.php</code></li>';
            echo '<li>Create the database if it doesn\'t exist:<br>';
            echo '<code>CREATE DATABASE ' . htmlspecialchars($config['database']) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code></li>';
            echo '<li>Check username/password in <code>app/config/database.php</code></li>';
            echo '<li>Verify host and port settings</li>';
            echo '</ol>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>

