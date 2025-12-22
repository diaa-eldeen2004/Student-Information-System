<?php
/**
 * Test Runner
 * Runs PHPUnit with testdox format enabled and displays all test output
 */

// Ensure we're in the project root
chdir(__DIR__ . '/..');

// Disable output buffering completely
while (ob_get_level() > 0) {
    ob_end_clean();
}

$phpunit = realpath(__DIR__ . '/../vendor/bin/phpunit');
if (!$phpunit) {
    $phpunit = __DIR__ . '/../vendor/bin/phpunit';
}

$exitCode = 0;

// Run tests by specifying subdirectories explicitly to ensure testdox output is shown
// Running the parent 'tests/Unit' directory seems to suppress output, so we'll run subdirectories
// Note: Controllers tests may need to be run individually if the directory doesn't work
$testDirs = [
    'tests/Unit/Controllers/AuthTest.php',  // Run Controllers test file directly
    'tests/Unit/Core', 
    'tests/Unit/Models',
    'tests/Unit/Patterns',
    'tests/Integration'
];

foreach ($testDirs as $dir) {
    $command = "php \"{$phpunit}\" --testdox \"$dir\" 2>&1";
    passthru($command, $dirExitCode);
    if ($dirExitCode !== 0) {
        $exitCode = $dirExitCode;
    }
}

exit($exitCode);
