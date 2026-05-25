<?php
// load-info.php - TASK 4.3 COMPLETE (all 3 checkpoints)
require_once __DIR__ . '/../../../config/bootstrap.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_GET;

$token     = trim($input['token'] ?? '');
$milestone = trim($input['milestone'] ?? '');
$stopSeq   = (int)($input['stop'] ?? 0);

// [1] Request received
Logger::write('app.log', 'INFO', 'load-info request received', [
    'token_prefix' => substr($token, 0, 8),
    'milestone' => $milestone,
    'stop' => $stopSeq
]);

try {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        Logger::write('app.log', 'WARN', 'load-info validation [failed]', ['reason' => 'invalid token format']);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }

    // Token lookup
    $stmt = $pdo->prepare("SELECT company_id, load_number, status FROM track_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $tokenRow = $stmt->fetch();

    if (!$tokenRow || $tokenRow['status'] !== 'active') {
        Logger::write('app.log', 'WARN', 'load-info validation [failed]', ['reason' => 'token not found or expired']);
        echo json_encode(['success' => false, 'message' => 'This tracking link has expired.']);
        exit;
    }

    $company_id = $tokenRow['company_id'];
    $load_number = $tokenRow['load_number'];

    // Stop existence check
    $stmt = $pdo->prepare("SELECT location_name, city, state FROM track_load_stops 
                           WHERE company_id = ? AND load_number = ? AND stop_sequence = ? AND milestone_type = ?");
    $stmt->execute([$company_id, $load_number, $stopSeq, $milestone]);
    $stopRow = $stmt->fetch();

    if (!$stopRow) {
        Logger::write('app.log', 'WARN', 'load-info validation [failed]', ['reason' => 'invalid stop']);
        echo json_encode(['success' => false, 'message' => 'Invalid stop requested']);
        exit;
    }

    // [2] Token / stop validation result
    Logger::write('app.log', 'INFO', 'load-info validation [passed]', [
        'token_prefix' => substr($token, 0, 8),
        'load' => $load_number,
        'stop' => $stopSeq,
        'milestone' => $milestone
    ]);

    // [3] Response dispatched
    Logger::write('app.log', 'INFO', 'load-info response sent', [
        'token_prefix' => substr($token, 0, 8),
        'milestone_label' => $milestone
    ]);

    echo json_encode([
        'success' => true,
        'carrier_name' => 'Exspeedite', // placeholder - pull from snapshot if needed
        'location_name' => $stopRow['location_name'],
        'city' => $stopRow['city'],
        'state' => $stopRow['state'],
        'milestone_label' => ucfirst($milestone),
        'stop_number' => $stopSeq
    ]);

} catch (Exception $e) {
    Logger::write('error.log', 'ERROR', 'load-info failed', ['msg' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
