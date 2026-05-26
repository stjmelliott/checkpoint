<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
session_start();
if (!isset($_SESSION['company_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit; }

header('Content-Type: application/json');

$company_id = (int)$_SESSION['company_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

$carrier_name = trim((string)($_POST['carrier_name'] ?? ''));
$driver_name  = trim((string)($_POST['driver_name'] ?? ''));
$driver_phone = preg_replace('/\D+/', '', (string)($_POST['driver_phone'] ?? '17634446474'));
$driver_email = trim((string)($_POST['driver_email'] ?? ''));
$load_number  = trim((string)($_POST['load_number'] ?? ''));

if ($driver_phone === '') $driver_phone = '17634446474';

if ($carrier_name === '' || $driver_phone === '' || $load_number === '') {
  echo json_encode(['success'=>false,'message'=>'Missing required fields']);
  exit;
}

$pdo->beginTransaction();
try {
  $stmt = $pdo->prepare("INSERT INTO track_load_snapshots (company_id, load_number, carrier_name, driver_name, driver_phone, driver_email, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
  $stmt->execute([$company_id, $load_number, $carrier_name, $driver_name, $driver_phone, $driver_email]);

  $insertStop = $pdo->prepare("INSERT INTO track_load_stops (company_id, load_number, address, city, state, zip, milestone, scheduled_at, stop_sequence) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

  $stops = $_POST['stop'] ?? [];
  if (is_array($stops)) {
    foreach ($stops as $idx => $stop) {
      if (!is_array($stop)) continue;
      $address = trim((string)($stop['address'] ?? ''));
      $city = trim((string)($stop['city'] ?? ''));
      $state = trim((string)($stop['state'] ?? ''));
      $zip = trim((string)($stop['zip'] ?? ''));
      $milestone = trim((string)($stop['milestone'] ?? 'pickup'));
      $scheduled_at = trim((string)($stop['scheduled_at'] ?? ''));
      if ($address === '' && $city === '' && $state === '' && $zip === '') continue;
      $insertStop->execute([$company_id, $load_number, $address, $city, $state, $zip, $milestone, $scheduled_at ?: null, ((int)$idx + 1)]);
    }
  }

  $pdo->commit();
  // SMS target is defaulted by driver_phone input to 17634446474 in UI.
  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Failed to create load']);
}
