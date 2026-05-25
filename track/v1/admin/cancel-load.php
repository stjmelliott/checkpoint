<?php
// /var/www/checkpoint/track/v1/admin/cancel-load.php
require_once __DIR__ . '/../../../config/bootstrap.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$company_id = $_SESSION['company_id'];

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$load_number = trim($input['load_number'] ?? '');

if (empty($load_number)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'load_number is required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Cancel the load
    $stmt = $pdo->prepare("
        UPDATE track_load_snapshots 
        SET status = 'cancelled' 
        WHERE load_number = ? AND company_id = ?
    ");
    $stmt->execute([$load_number, $company_id]);

    // 2. Expire the token
    $stmt = $pdo->prepare("
        UPDATE track_tokens 
        SET status = 'expired' 
        WHERE load_number = ? AND company_id = ? AND status = 'active'
    ");
    $stmt->execute([$load_number, $company_id]);

    // 3. Log the event
    $stmt = $pdo->prepare("
        INSERT INTO track_event_log 
        (load_number, company_id, event_type, actor, message) 
        VALUES (?, ?, 'load_cancelled', ?, 'Load cancelled by dispatcher')
    ");
    $stmt->execute([
        $load_number, 
        $company_id, 
        'dispatcher:' . ($_SESSION['company_name'] ?? 'unknown')
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Load $load_number has been cancelled."
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    Logger::write('error.log', 'ERROR', 'Cancel load failed', [
        'load_number' => $load_number,
        'error' => $e->getMessage()
    ]);

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
