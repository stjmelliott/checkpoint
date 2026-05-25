<?php
require_once __DIR__ . '/../config/bootstrap.php';
echo "<h2>Task 6 — Ingestion Layer (initialize.php) Test</h2><pre>";
$testLoad = "TEST-T6-" . date('His');
$payload = json_encode([
    'load_number' => $testLoad,
    'carrier_name' => 'Test Carrier',
    'driver_name' => 'Test Driver',
    'driver_phone' => '+17634446474',
    'driver_email' => 'selliott@strongtco.com'
]);
$response = file_get_contents("https://exspeeditecheckpoint.com/v1/tracking/initialize.php", false, stream_context_create([
    'http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $payload]
]));
$data = json_decode($response, true);
echo "initialize.php called with test load: $testLoad\n";
echo "Success: " . ($data['success'] ? "✅ Yes" : "❌ No") . "\n";
echo "sms_sent: " . ($data['sms_sent'] ? "✅ True" : "❌ False") . "\n";
echo "email_sent: " . ($data['email_sent'] ? "✅ True" : "❌ False") . "\n";
echo "tracking_url present: " . (isset($data['tracking_url']) ? "✅ Yes" : "❌ No") . "\n";
echo "</pre>";
?>
