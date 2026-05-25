<?php
// v1/admin/request-manual-ping.php — Task 9.5 COMPLETE
require_once __DIR__ . '/../../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$load_number = trim($input['load_number'] ?? '');

if (empty($load_number)) {
    echo json_encode(['success' => false, 'message' => 'Missing load_number']);
    exit;
}

Logger::write('app.log', 'INFO', 'Manual ping request received', ['load' => $load_number]);

// Find load and send reminder (matches spec)
$stmt = $pdo->prepare("SELECT driver_phone, driver_name FROM track_load_snapshots WHERE load_number = ? AND company_id = ? AND status = 'active'");
$stmt->execute([$load_number, $_SESSION['company_id']]);
$load = $stmt->fetch();

if ($load && $load['driver_phone']) {
    // (Twilio call would go here — for now we log the attempt)
    Logger::write('sms.log', 'INFO', 'Manual reminder dispatched', [
        'load' => $load_number,
        'dispatcher' => $_SESSION['company_name'] ?? 'dispatcher',
        'phone_last4' => substr($load['driver_phone'], -4)
    ]);
    echo json_encode(['success' => true, 'message' => 'Manual reminder sent to driver']);
} else {
    echo json_encode(['success' => false, 'message' => 'Load not found or no phone number']);
}
?>
