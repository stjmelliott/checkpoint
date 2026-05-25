<?php
require_once __DIR__ . '/../config/bootstrap.php';
echo "<h2>Task 1 — Project Setup & Infrastructure Test</h2><pre>";
echo "Logs directory exists: " . (is_dir('/var/www/checkpoint/logs') ? "✅ Yes" : "❌ No") . "\n";
echo "Logger class loaded: " . (class_exists('Logger') ? "✅ Yes" : "❌ No") . "\n";
echo "Bootstrap loaded: " . (file_exists('/var/www/checkpoint/config/bootstrap.php') ? "✅ Yes" : "❌ No") . "\n";
echo "UTC timezone set: " . (date_default_timezone_get() === 'UTC' ? "✅ Yes" : "❌ No") . "\n";
echo "Logrotate config present: " . (file_exists('/etc/logrotate.d/exspeedite-checkpoint') ? "✅ Yes" : "❌ No") . "\n";
echo "\nRecent log entries:\n";
echo shell_exec("tail -n 8 /var/www/checkpoint/logs/app.log 2>/dev/null || echo 'No entries yet'");
echo "</pre>";
?>
