<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../lib/initialize_logic.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$token = trim($input['token'] ?? '');
$milestone = trim($input['milestone_type'] ?? '');
$stopSeq = (int)($input['stop_sequence'] ?? 0);
$lat = isset($input['lat']) ? (float)$input['lat'] : 999;
$lng = isset($input['lng']) ? (float)$input['lng'] : 999;
$accuracy = (float)($input['accuracy'] ?? 0);

if (!preg_match('/^[a-f0-9]{64}$/', $token) || !in_array($milestone, ['pickup','transit','delivery'], true) || $stopSeq < 1 || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request payload']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, company_id, load_number, status, expires_at FROM track_tokens WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tokenRow) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error_code' => 'TOKEN_EXPIRED', 'message' => 'This tracking link is no longer active.']);
    exit;
}

if ($tokenRow['status'] !== 'active' || strtotime((string)$tokenRow['expires_at']) <= time()) {
    $stmt = $pdo->prepare("SELECT status FROM track_load_snapshots WHERE company_id=? AND load_number=? LIMIT 1");
    $stmt->execute([(int)$tokenRow['company_id'], (string)$tokenRow['load_number']]);
    $loadStatus = (string)$stmt->fetchColumn();
    if ($tokenRow['status'] === 'expired' && $loadStatus === 'completed' && $milestone === 'delivery') {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Location recorded.']);
        exit;
    }
    http_response_code(401);
    echo json_encode(['success' => false, 'error_code' => 'TOKEN_EXPIRED', 'message' => 'This tracking link is no longer active.']);
    exit;
}

$companyId = (int)$tokenRow['company_id'];
$loadNumber = (string)$tokenRow['load_number'];
$stmt = $pdo->prepare("SELECT location_name, city, state FROM track_load_stops WHERE company_id=? AND load_number=? AND stop_sequence=? AND milestone_type=? LIMIT 1");
$stmt->execute([$companyId, $loadNumber, $stopSeq, $milestone]);
$currentStop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$currentStop) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error_code' => 'INVALID_STOP', 'message' => 'Invalid stop']);
    exit;
}

$nextStop = null; $mutexWon = false; $isFinal = false; $driverName=''; $driverPhone=''; $driverEmail='';
try {
    $pdo->beginTransaction();
    $accuracyQuality = ($accuracy <= 100) ? 'good' : (($accuracy <= 1000) ? 'fair' : 'poor');
    $stmt = $pdo->prepare("INSERT INTO track_location_pings (token_id, load_number, company_id, stop_sequence, milestone_type, lat, lng, accuracy, accuracy_quality, ip_address, user_agent, timestamp, raw_payload)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?)");
    $stmt->execute([$tokenRow['id'], $loadNumber, $companyId, $stopSeq, $milestone, $lat, $lng, $accuracy, $accuracyQuality, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode($input)]);

    $pdo->prepare("INSERT INTO track_event_log (load_number, company_id, event_type, actor, message) VALUES (?, ?, 'ping_received', 'driver', ?)")
        ->execute([$loadNumber, $companyId, "Stop {$stopSeq} {$milestone} ping received"]);

    $stmt = $pdo->prepare("SELECT driver_name, driver_phone, driver_email, status FROM track_load_snapshots WHERE company_id=? AND load_number=? LIMIT 1");
    $stmt->execute([$companyId, $loadNumber]);
    $snap = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $driverName = (string)($snap['driver_name'] ?? 'Driver');
    $driverPhone = (string)($snap['driver_phone'] ?? '');
    $driverEmail = (string)($snap['driver_email'] ?? '');

    $stmt = $pdo->prepare("SELECT MAX(stop_sequence) FROM track_load_stops WHERE company_id=? AND load_number=?");
    $stmt->execute([$companyId, $loadNumber]);
    $isFinal = ($stopSeq >= (int)$stmt->fetchColumn());

    if ($isFinal) {
        $pdo->prepare("UPDATE track_tokens SET status='expired' WHERE id=?")->execute([$tokenRow['id']]);
        $pdo->prepare("UPDATE track_load_snapshots SET status='completed' WHERE company_id=? AND load_number=?")->execute([$companyId, $loadNumber]);
        $pdo->prepare("INSERT INTO track_event_log (load_number, company_id, event_type, actor, message) VALUES (?, ?, 'load_completed', 'system', 'Final stop confirmed')")
            ->execute([$loadNumber, $companyId]);
    } else {
        $stmt = $pdo->prepare("UPDATE track_load_stops SET sms_sent_at = UTC_TIMESTAMP() WHERE company_id=? AND load_number=? AND stop_sequence=? AND sms_sent_at IS NULL");
        $stmt->execute([$companyId, $loadNumber, $stopSeq + 1]);
        $mutexWon = $stmt->rowCount() === 1;
        if ($mutexWon) {
            $stmt = $pdo->prepare("SELECT stop_sequence, milestone_type, location_name, city, state FROM track_load_stops WHERE company_id=? AND load_number=? AND stop_sequence=? LIMIT 1");
            $stmt->execute([$companyId, $loadNumber, $stopSeq + 1]);
            $nextStop = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}

if ($isFinal) {
    $body = "All done! Thanks for the update, {$driverName}. Have a safe trip home.";
    $sms = send_twilio_sms($pdo, $driverPhone, $body, $loadNumber);
    if (!$sms) {
        $emailSent = send_sendgrid_email($pdo, $driverEmail, "Your tracking link for Load #{$loadNumber}", $body, $loadNumber);
        if ($emailSent) {
            $pdo->prepare("UPDATE track_load_snapshots SET sms_fallback_used=1 WHERE company_id=? AND load_number=?")->execute([$companyId, $loadNumber]);
        }
    }
    $pdo->prepare("INSERT INTO track_event_log (load_number, company_id, event_type, actor, message) VALUES (?, ?, 'sms_sent', 'system', ?)")
        ->execute([$loadNumber, $companyId, 'Farewell SMS sent']);
} elseif ($mutexWon && $nextStop) {
    $url = "https://track.exspeeditecheckpoint.com/start?token={$token}&milestone={$nextStop['milestone_type']}&stop={$nextStop['stop_sequence']}";
    $body = build_tracking_sms_body($driverName, (string)$nextStop['milestone_type'], (string)$nextStop['location_name'], (string)$nextStop['city'], (string)$nextStop['state'], $url);
    $sms = send_twilio_sms($pdo, $driverPhone, $body, $loadNumber);
    if (!$sms) {
        $emailSent = send_sendgrid_email($pdo, $driverEmail, "Your tracking link for Load #{$loadNumber}", $body, $loadNumber);
        if ($emailSent) {
            $pdo->prepare("UPDATE track_load_snapshots SET sms_fallback_used=1 WHERE company_id=? AND load_number=?")->execute([$companyId, $loadNumber]);
        }
    }
    $pdo->prepare("INSERT INTO track_event_log (load_number, company_id, event_type, actor, message) VALUES (?, ?, 'sms_sent', 'system', ?)")
        ->execute([$loadNumber, $companyId, "Stop {$nextStop['stop_sequence']} SMS sent"]);
}

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Location recorded.']);
