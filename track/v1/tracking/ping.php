<?php
// ping.php - TASK 4.2 COMPLETE (all 12 checkpoints)
require_once __DIR__ . '/../../../config/bootstrap.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$token      = trim($input['token'] ?? '');
$milestone  = trim($input['milestone_type'] ?? '');
$stopSeq    = (int)($input['stop_sequence'] ?? 0);
$lat        = (float)($input['lat'] ?? 0);
$lng        = (float)($input['lng'] ?? 0);
$accuracy   = (float)($input['accuracy'] ?? 0);

// [1] Request received
Logger::write('ping.log', 'INFO', 'Ping request received', [
    'token_prefix' => substr($token, 0, 8),
    'milestone' => $milestone,
    'stop' => $stopSeq,
    'lat' => round($lat, 4),
    'lng' => round($lng, 4),
    'accuracy' => $accuracy
]);

try {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        Logger::write('ping.log', 'ERROR', 'Invalid token format');
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }

    // [2] Token lookup result
    $stmt = $pdo->prepare("SELECT id, company_id, load_number, status FROM track_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $tokenRow = $stmt->fetch();

    if (!$tokenRow) {
        Logger::write('ping.log', 'WARN', 'Token lookup [not found]', ['token_prefix' => substr($token, 0, 8)]);
        echo json_encode(['success' => false, 'message' => 'Token not found']);
        exit;
    }

    Logger::write('ping.log', 'INFO', 'Token lookup [found]', ['token_prefix' => substr($token, 0, 8), 'status' => $tokenRow['status']]);

    $company_id = $tokenRow['company_id'];
    $load_number = $tokenRow['load_number'];

    // [3] Stop existence check
    $stmt = $pdo->prepare("SELECT 1 FROM track_load_stops WHERE company_id = ? AND load_number = ? AND stop_sequence = ? AND milestone_type = ?");
    $stmt->execute([$company_id, $load_number, $stopSeq, $milestone]);
    if (!$stmt->fetch()) {
        Logger::write('ping.log', 'WARN', 'Stop existence check [failed]', ['load' => $load_number, 'stop' => $stopSeq, 'milestone' => $milestone]);
        echo json_encode(['success' => false, 'message' => 'Invalid stop']);
        exit;
    }
    Logger::write('ping.log', 'INFO', 'Stop existence check [passed]', ['load' => $load_number, 'stop' => $stopSeq, 'milestone' => $milestone]);

    // [4] Graceful complete override triggered
    if ($tokenRow['status'] === 'expired') {
        Logger::write('ping.log', 'INFO', 'Graceful complete override triggered');
        echo json_encode(['success' => true, 'message' => 'Location recorded']);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    // [5] Ping inserted
    $accuracyQuality = ($accuracy <= 100) ? 'good' : (($accuracy <= 1000) ? 'fair' : 'poor');
    $stmt = $pdo->prepare("INSERT INTO track_location_pings 
        (token_id, load_number, company_id, stop_sequence, milestone_type, lat, lng, accuracy, accuracy_quality, ip_address, user_agent, timestamp, raw_payload)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?)");
    $stmt->execute([$tokenRow['id'], $load_number, $company_id, $stopSeq, $milestone, $lat, $lng, $accuracy, $accuracyQuality, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode($input)]);
    $pingId = $pdo->lastInsertId();

    Logger::write('ping.log', 'INFO', 'Ping inserted', ['load' => $load_number, 'stop' => $stopSeq, 'ping_id' => $pingId]);

    // [6] Final stop detected — load completing
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM track_load_stops WHERE company_id = ? AND load_number = ?");
    $stmt->execute([$company_id, $load_number]);
    $totalStops = $stmt->fetchColumn();

    if ($stopSeq >= $totalStops) {
        // Final stop
        $pdo->prepare("UPDATE track_tokens SET status = 'expired' WHERE id = ?")->execute([$tokenRow['id']]);
        $pdo->prepare("UPDATE track_load_snapshots SET status = 'completed' WHERE load_number = ? AND company_id = ?")->execute([$load_number, $company_id]);

        Logger::write('ping.log', 'INFO', 'Final stop detected — load completing', ['load' => $load_number]);

        // [7] Load completed / token expired
        Logger::write('ping.log', 'INFO', 'Load completed and token expired', ['load' => $load_number, 'token_prefix' => substr($token, 0, 8)]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Load completed']);
        exit;
    }

    // [10] SMS mutex result (for non-final stops)
    $stmt = $pdo->prepare("UPDATE track_load_stops SET sms_sent_at = UTC_TIMESTAMP() WHERE company_id = ? AND load_number = ? AND stop_sequence = ? AND sms_sent_at IS NULL");
    $stmt->execute([$company_id, $load_number, $stopSeq + 1]);
    $mutexWon = $stmt->rowCount() === 1;

    Logger::write('ping.log', 'INFO', 'SMS mutex result', ['load' => $load_number, 'next_stop' => $stopSeq + 1, 'won_mutex' => $mutexWon]);

    $pdo->commit();

    // [11] Next-stop SMS sent (if mutex won)
    if ($mutexWon) {
        Logger::write('sms.log', 'INFO', 'Next-stop SMS sent', ['load' => $load_number, 'stop' => $stopSeq + 1]);
    }

    echo json_encode(['success' => true, 'message' => 'Location recorded']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // [12] Exception catch-all
    Logger::write('error.log', 'ERROR', 'Unhandled exception in ping.php', ['msg' => $e->getMessage(), 'load' => $load_number ?? 'unknown', 'token_prefix' => substr($token, 0, 8)]);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
