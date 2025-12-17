<?php
// Quick script to show error log locations
echo "=== PHP Error Log Locations ===\n\n";

// Check php.ini settings
$errorLog = ini_get('error_log');
echo "1. PHP error_log setting: " . ($errorLog ?: 'Not set (defaults to system error log)') . "\n";

// Common XAMPP locations
$commonPaths = [
    'D:/xampp/apache/logs/error.log',
    'D:/xampp/apache/logs/access.log',
    'D:/xampp/php/logs/php_error_log',
    'C:/xampp/apache/logs/error.log',
    'C:/xampp/apache/logs/access.log',
    'C:/xampp/php/logs/php_error_log',
];

echo "\n2. Common XAMPP Log File Locations:\n";
foreach ($commonPaths as $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        $date = date('Y-m-d H:i:s', filemtime($path));
        echo "   ✓ EXISTS: $path\n";
        echo "     Size: " . number_format($size) . " bytes | Last modified: $date\n";
    } else {
        echo "   ✗ NOT FOUND: $path\n";
    }
}

// Show current error reporting
echo "\n3. Current Error Reporting Settings:\n";
echo "   error_reporting: " . error_reporting() . "\n";
echo "   display_errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "\n";
echo "   log_errors: " . (ini_get('log_errors') ? 'On' : 'Off') . "\n";

// Try to find Apache error log from common locations
echo "\n4. Recommended locations to check:\n";
echo "   - D:\\xampp\\apache\\logs\\error.log\n";
echo "   - C:\\xampp\\apache\\logs\\error.log\n";
echo "   - Windows Event Viewer > Windows Logs > Application\n";

?>


