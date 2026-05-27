<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_GET;
$token = trim($input['token'] ?? '');
$milestone = trim($input['milestone'] ?? '');
$stopSeq = (int)($input['stop'] ?? 0);

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    echo json_encode(['success' => false, 'message' => 'This tracking link has expired. You can close this page.']);
    exit;
}

$stmt = $pdo->prepare("SELECT company_id, load_number, status, expires_at FROM track_tokens WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tokenRow || $tokenRow['status'] !== 'active' || strtotime((string)$tokenRow['expires_at']) <= time()) {
    echo json_encode(['success' => false, 'message' => 'This tracking link has expired. You can close this page.']);
    exit;
}

$stmt = $pdo->prepare("SELECT location_name, city, state FROM track_load_stops WHERE company_id = ? AND load_number = ? AND stop_sequence = ? AND milestone_type = ? LIMIT 1");
$stmt->execute([(int)$tokenRow['company_id'], (string)$tokenRow['load_number'], $stopSeq, $milestone]);
$stop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$stop) {
    echo json_encode(['success' => false, 'message' => 'This tracking link has expired. You can close this page.']);
    exit;
}

$stmt = $pdo->prepare("SELECT carrier_name FROM track_load_snapshots WHERE company_id = ? AND load_number = ? LIMIT 1");
$stmt->execute([(int)$tokenRow['company_id'], (string)$tokenRow['load_number']]);
$carrier = (string)($stmt->fetchColumn() ?: 'Carrier');

$stmt = $pdo->prepare("SELECT COUNT(*) FROM track_load_stops WHERE company_id = ? AND load_number = ?");
$stmt->execute([(int)$tokenRow['company_id'], (string)$tokenRow['load_number']]);
$totalStops = (int)$stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'carrier_name' => $carrier,
    'location_name' => $stop['location_name'],
    'city' => $stop['city'],
    'state' => $stop['state'],
    'milestone_label' => ucfirst($milestone),
    'stop_number' => $stopSeq,
    'total_stops' => $totalStops,
]);
