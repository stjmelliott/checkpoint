<?php
require_once __DIR__ . '/../config/bootstrap.php';

$script = $_GET['script'] ?? '';
$allowed = ['send_transit_reminders', 'expire_old_tokens'];

if (!in_array($script, $allowed)) {
    echo "Invalid script";
    exit;
}

$path = __DIR__ . '/../cron/' . $script . '.php';

if (!file_exists($path)) {
    echo "Script file not found.";
    exit;
}

echo "=== Running $script.php ===\n\n";
echo shell_exec("php " . escapeshellarg($path) . " 2>&1");
?>