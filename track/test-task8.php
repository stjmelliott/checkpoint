<?php
require_once __DIR__ . '/../config/bootstrap.php';
echo "<h2>Task 8 — ping.php (Critical Path) Test</h2><pre>";

$testLoad = "TEST-T8-" . date('His');

// Step 1: Create real test load
$payload = json_encode([
    'load_number' => $testLoad,
    'carrier_name' => 'Test Carrier',
    'driver_name' => 'Test Driver',
    'driver_phone' => '+17634446474',
    'driver_email' => 'selliott@strongtco.com'
]);
file_get_contents("https://exspeeditecheckpoint.com/v1/tracking/initialize.php", false, stream_context_create([
    'http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $payload]
]));

// Step 2: Manually insert matching stop row (so validation passes)
$pdo->prepare("INSERT INTO track_load_stops (company_id, load_number, stop_sequence, milestone_type, location_name, city, state) 
               VALUES (1, ?, 1, 'pickup', 'Test Warehouse', 'Forest City', 'IA')")
     ->execute([$testLoad]);

echo "✅ Test load created + stop row inserted\n\n";

// Step 3: Get real token from the load
$stmt = $pdo->prepare("SELECT token FROM track_tokens WHERE load_number = ? AND status = 'active' LIMIT 1");
$stmt->execute([$testLoad]);
$realToken = $stmt->fetchColumn();

echo "Using real token: " . substr($realToken, 0, 8) . "...\n\n";

// Step 4: Perform real ping
$pingPayload = json_encode([
    'token' => $realToken,
    'milestone_type' => 'pickup',
    'stop_sequence' => 1,
    'lat' => 43.2662,
    'lng' => -93.6418,
    'accuracy' => 25
]);
$pingResponse = file_get_contents("https://exspeeditecheckpoint.com/v1/tracking/ping.php", false, stream_context_create([
    'http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $pingPayload]
]));
$pingData = json_decode($pingResponse, true);

echo "ping.php full response:\n" . json_encode($pingData, JSON_PRETTY_PRINT) . "\n";
echo "Success: " . ($pingData['success'] ? "✅ TRUE" : "❌ FALSE") . "\n";
echo "</pre>";
?>
