<?php
// Simple test to see if PHPUnit produces output
chdir(__DIR__ . '/..');

$phpunit = __DIR__ . '/../vendor/bin/phpunit';
$command = "php \"{$phpunit}\" --testdox tests/Unit/Core/ModelTest.php 2>&1";

echo "Running: $command\n";
echo "Output:\n";
echo str_repeat("=", 50) . "\n";

passthru($command, $exitCode);

echo "\n" . str_repeat("=", 50) . "\n";
echo "Exit code: $exitCode\n";
