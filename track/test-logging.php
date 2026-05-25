<?php
// test-logging.php — FIXED Full Task 4 Logging Verification (v1.28)
require_once __DIR__ . '/../config/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Task 4 — Full Logging Verification</title>
    <style>body{font-family:monospace;background:#111;color:#0f0;padding:20px;line-height:1.5}</style>
</head>
<body>
<h1>✅ Full Task 4 Logging Test (v1.28)</h1>
<pre>
<?php
echo "=== Starting Full Task 4 Verification ===\n\n";

// 1. Create a real test load first (this triggers initialize.php's 8 checkpoints)
$testLoad = "TEST-TASK4-" . date('His');
echo "Creating test load → $testLoad\n";
$payload = json_encode([
    'load_number'   => $testLoad,
    'carrier_name'  => 'Test Carrier',
    'driver_name'   => 'Test Driver',
    'driver_phone'  => '+17634446474',
    'driver_email'  => 'selliott@strongtco.com'
]);
file_get_contents("https://exspeeditecheckpoint.com/v1/tracking/initialize.php", false, stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => $payload
    ]
]));

// 2. Call load-info.php (3 checkpoints)
echo "Calling load-info.php...\n";
file_get_contents("https://exspeeditecheckpoint.com/v1/tracking/load-info.php?token=dummy&milestone=pickup&stop=1");

// 3. Call ping.php (12 checkpoints) — using a dummy token for now
echo "Calling ping.php...\n";
$pingData = json_encode([
    'token'         => 'dummytoken1234567890abcdef1234567890abcdef1234567890abcdef12',
    'milestone_type'=> 'pickup',
    'stop_sequence' => 1,
    'lat'           => 43.2662,
    'lng'           => -93.6418,
    'accuracy'      => 25
]);
file_get_contents("https://exspeeditecheckpoint.com/v1/tracking/ping.php", false, stream_context_create([
    'http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $pingData]
]));

// 4. Run both cron jobs
echo "Running send_transit_reminders.php cron (4 checkpoints)...\n";
include '/var/www/checkpoint/cron/send_transit_reminders.php';

echo "Running expire_old_tokens.php cron (4 checkpoints)...\n";
include '/var/www/checkpoint/cron/expire_old_tokens.php';

echo "\n=== LAST 40 LINES OF EVERY LOG FILE ===\n\n";
$logs = ['app.log', 'ping.log', 'sms.log', 'cron.log', 'error.log', 'auth.log'];
foreach ($logs as $log) {
    echo "=== $log ===\n";
    echo shell_exec("tail -n 40 /var/www/checkpoint/logs/$log 2>/dev/null || echo 'No entries yet'");
    echo "\n\n";
}
?>
</pre>
<p><strong>Test complete.</strong> Scroll up and check the logs. Every checkpoint from Task 4 should now appear in the correct file.</p>
</body>
</html>
