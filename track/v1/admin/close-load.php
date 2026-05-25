<?php
// v1/admin/close-load.php — Task 9.3 COMPLETE
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

Logger::write('app.log', 'INFO', 'Close load request received', [
    'load' => $load_number,
    'company_id' => $_SESSION['company_id'],
    'dispatcher' => $_SESSION['company_name'] ?? 'dispatcher'
]);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE track_load_snapshots SET status = 'completed' WHERE load_number = ? AND company_id = ?");
    $stmt->execute([$load_number, $_SESSION['company_id']]);

    $stmt = $pdo->prepare("UPDATE track_tokens SET status = 'expired' WHERE load_number = ? AND company_id = ? AND status = 'active'");
    $stmt->execute([$load_number, $_SESSION['company_id']]);

    $stmt = $pdo->prepare("INSERT INTO track_event_log (load_number, company_id, event_type, actor) VALUES (?, ?, 'manual_close', ?)");
    $stmt->execute([$load_number, $_SESSION['company_id'], $_SESSION['company_name'] ?? 'dispatcher']);

    $pdo->commit();

    Logger::write('app.log', 'INFO', 'Load status update [committed]', ['load' => $load_number, 'new_status' => 'completed']);

    echo json_encode(['success' => true, 'message' => 'Load closed successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    Logger::write('error.log', 'ERROR', 'Close load failed', ['load' => $load_number, 'msg' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
