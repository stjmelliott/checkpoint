<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
session_start();
if (!isset($_SESSION['company_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

$company_id = $_SESSION['company_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$carrier_name = $_POST['carrier_name'] ?? '';
$driver_name  = $_POST['driver_name'] ?? '';
$driver_phone = $_POST['driver_phone'] ?? '17634446474';
$driver_email = $_POST['driver_email'] ?? '';
$load_number  = $_POST['load_number'] ?? '';

if (empty($carrier_name) || empty($driver_phone) || empty($load_number)) {
  echo json_encode(['success'=>false,'message'=>'Missing required fields']);
  exit;
}

// INSERT snapshot
$stmt = $pdo->prepare("INSERT INTO track_load_snapshots (company_id, load_number, carrier_name, driver_name, driver_phone, driver_email, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
$stmt->execute([$company_id, $load_number, $carrier_name, $driver_name, $driver_phone, $driver_email]);

// INSERT stops with full address
foreach ($_POST as $key => $value) {
  if (strpos($key, 'stop[') === 0) {
    // parse stop data and INSERT into track_load_stops (address1, city, state, zip, etc.)
    // (full parsing logic matching spec — use prepared statements)
  }
}

// Generate token + send initial SMS (reuse logic from initialize.php or call Twilio directly)
// Return success
echo json_encode(['success'=>true]);
?>
