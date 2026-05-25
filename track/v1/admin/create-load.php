<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../lib/initialize_logic.php';
header('Content-Type: application/json');

if (!isset($_SESSION['company_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$companyId = (int)$_SESSION['company_id'];

$stmt = $pdo->prepare("SELECT credential_value FROM app_credentials WHERE service_name='checkpoint' AND credential_key='load_entry_mode' LIMIT 1");
$stmt->execute();
$mode = strtolower(trim((string)$stmt->fetchColumn()));
if ($mode === 'webhook') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Manual load entry disabled']);
    exit;
}

$input = $_POST;
Logger::write('app.log', 'INFO', 'Manual load creation requested', ['checkpoint' => 1, 'company_id' => $companyId, 'user_id' => $_SESSION['user_id'] ?? null]);


$carrierName = trim((string)($input['carrier_name'] ?? ''));
$loadNumber = trim((string)($input['load_number'] ?? ''));
if ($carrierName === '' || $loadNumber === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Carrier Name and Load Number are required']);
    exit;
}

$driverPhone = trim((string)($input['driver_phone'] ?? ''));
if (!preg_match('/^\+[1-9]\d{1,14}$/', $driverPhone)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid driver_phone format']);
    exit;
}

$stops = $input['stop'] ?? [];
if (!is_array($stops) || count($stops) < 2) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'At least 2 stops are required']);
    exit;
}
$deliveryCount = 0;
foreach ($stops as $k => $s) {
    if (($s['milestone'] ?? '') === 'delivery') $deliveryCount++;
    $stops[$k]['city'] = trim((string)($s['city'] ?? '')) !== '' ? trim((string)$s['city']) : 'Unknown City';
    $stops[$k]['state'] = trim((string)($s['state'] ?? '')) !== '' ? trim((string)$s['state']) : 'US';
}

if ($deliveryCount !== 1) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Exactly one delivery stop is required']);
    exit;
}

$carrierId = (int)($input['carrier_id'] ?? 0);
if ($carrierId === 0) {
    $stmt = $pdo->prepare("INSERT INTO track_carriers (company_id, carrier_name, dot_number, source, created_at) VALUES (?, ?, ?, 'manual', NOW())");
    $stmt->execute([$companyId, $carrierName, trim((string)($input['dot_number'] ?? ''))]);
    $carrierId = (int)$pdo->lastInsertId();
    Logger::write('app.log', 'INFO', 'Carrier created', ['checkpoint' => 3, 'company_id' => $companyId, 'carrier_id' => $carrierId]);
}

$driverId = (int)($input['driver_id'] ?? 0);
if ($driverId === 0) {
    $stmt = $pdo->prepare("INSERT INTO track_carrier_drivers (company_id, carrier_id, driver_name, driver_phone, driver_email, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
    $stmt->execute([$companyId, $carrierId, trim((string)$input['driver_name']), $driverPhone, trim((string)($input['driver_email'] ?? ''))]);
    $driverId = (int)$pdo->lastInsertId();
    Logger::write('app.log', 'INFO', 'Driver created', ['checkpoint' => 4, 'company_id' => $companyId, 'driver_id' => $driverId]);
}

$payload = [
    'company_id' => $companyId,
    'load_number' => $loadNumber,
    'carrier_name' => $carrierName,
    'driver_name' => trim((string)($input['driver_name'] ?? '')),
    'driver_phone' => $driverPhone,
    'driver_email' => trim((string)($input['driver_email'] ?? '')),
    'stops' => array_values($stops),
    'carrier_id' => $carrierId,
    'driver_id' => $driverId,
];

$result = run_initialize($payload, $companyId, $pdo);
if (!empty($result['success'])) {
    $eventStmt = $pdo->prepare("INSERT INTO track_event_log (company_id, load_number, event_type, event_payload, created_at) VALUES (?, ?, 'manual_load_created', ?, NOW())");
    $eventStmt->execute([$companyId, $payload['load_number'], json_encode(['carrier_id' => $carrierId, 'driver_id' => $driverId])]);
    Logger::write('app.log', 'INFO', 'Manual load creation complete', ['checkpoint' => 5, 'company_id' => $companyId, 'load' => $payload['load_number']]);
}

http_response_code(!empty($result['success']) ? 200 : 422);
echo json_encode($result);
