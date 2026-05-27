<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../lib/initialize_logic.php';
session_start();
if (!isset($_SESSION['company_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }
$company_id = $_SESSION['company_id'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$carrier_name = $_POST['carrier_name'] ?? '';
$driver_name  = $_POST['driver_name'] ?? 'John Doe';
$driver_phone = trim((string)($_POST['driver_phone'] ?? ''));
$driver_email = trim((string)($_POST['driver_email'] ?? ''));
$load_number  = $_POST['load_number'] ?? '';
if (empty($carrier_name) || empty($load_number)) {
  echo json_encode(['success'=>false,'message'=>'Missing required fields']);
  exit;
}
$init = run_initialize([
  'load_number' => $load_number,
  'carrier_name' => $carrier_name,
  'driver_name' => $driver_name,
  'driver_phone' => $driver_phone,
  'driver_email' => $driver_email,
], $company_id, $pdo);
if (!$init['success']) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>$init['message'] ?? 'Unable to initialize load']);
  exit;
}
// INSERT stops with full address
for ($i = 0; isset($_POST["stop[$i][address]"]); $i++) {
  $address = $_POST["stop[$i][address]"] ?? '';
  $city = $_POST["stop[$i][city]"] ?? 'Unknown City';
  $state = $_POST["stop[$i][state]"] ?? 'US';
  $zip = $_POST["stop[$i][zip]"] ?? '';
  $milestone = $_POST["stop[$i][milestone]"] ?? 'transit';
  $stmt2 = $pdo->prepare("INSERT INTO track_load_stops (company_id, load_number, stop_sequence, milestone_type, address1, city, state, zip) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt2->execute([$company_id, $load_number, $i+1, $milestone, $address, $city, $state, $zip]);
}
echo json_encode(['success'=>true, 'message'=>'Load created and text sent to end user', 'tracking_url' => $init['tracking_url'] ?? '', 'sms_sent' => (bool)($init['sms_sent'] ?? false)]);
?>
