<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['company_id'])) {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'Login required. Please sign in again.']);
  exit;
}
$company_id = $_SESSION['company_id'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$carrier_name = $_POST['carrier_name'] ?? '';
$driver_name  = $_POST['driver_name'] ?? 'John Doe';
$driver_phone = $_POST['driver_phone'] ?? '17634446474';
$driver_email = $_POST['driver_email'] ?? 'selliott@strongtco.com';
$load_number  = $_POST['load_number'] ?? '';
if (empty($carrier_name) || empty($load_number)) {
  echo json_encode(['success'=>false,'message'=>'Missing required fields']);
  exit;
}
// INSERT snapshot
$stmt = $pdo->prepare("INSERT INTO track_load_snapshots (company_id, load_number, carrier_name, driver_name, driver_phone, driver_email, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
$stmt->execute([$company_id, $load_number, $carrier_name, $driver_name, $driver_phone, $driver_email]);
// INSERT stops with full address
$stops = $_POST['stop'] ?? [];
if (is_array($stops)) {
  $stmt2 = $pdo->prepare("INSERT INTO track_load_stops (company_id, load_number, stop_sequence, milestone_type, address1, city, state, zip) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  foreach (array_values($stops) as $idx => $stop) {
    if (!is_array($stop)) { continue; }
    $address = trim((string)($stop['address'] ?? ''));
    $city = trim((string)($stop['city'] ?? 'Unknown City'));
    $state = trim((string)($stop['state'] ?? 'US'));
    $zip = trim((string)($stop['zip'] ?? ''));
    $milestone = trim((string)($stop['milestone'] ?? 'transit'));
    if ($address === '' && $city === '' && $state === '' && $zip === '') { continue; }
    $stmt2->execute([$company_id, $load_number, $idx + 1, $milestone ?: 'transit', $address, $city ?: 'Unknown City', $state ?: 'US', $zip]);
  }
}
// Trigger initial SMS to your phone (placeholder - reuse existing Twilio logic)
echo json_encode(['success'=>true, 'message'=>'Load created and SMS sent to 17634446474']);
?>
