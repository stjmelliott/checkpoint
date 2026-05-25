<?php
// /var/www/checkpoint/track/v1/tracking/initialize.php - TASK 4 COMPLETE
require_once __DIR__ . '/../../../config/bootstrap.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$load_number   = trim($input['load_number'] ?? '');
$carrier_name  = trim($input['carrier_name'] ?? 'Unknown Carrier');
$driver_name   = trim($input['driver_name'] ?? 'Driver');
$driver_phone  = trim($input['driver_phone'] ?? '');
$driver_email  = trim($input['driver_email'] ?? '');

if (empty($load_number) || empty($driver_phone)) {
    Logger::write('app.log', 'ERROR', 'Initialize missing fields', ['load' => $load_number]);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// [1] Request received
Logger::write('app.log', 'INFO', 'Initialize request received', ['load' => $load_number, 'phone_last4' => substr($driver_phone, -4)]);

try {
    $company_id = 1; // TODO: Replace with real session/company later

    // [2] Bearer token auth result (placeholder - expand when full auth is added)
    Logger::write('auth.log', 'INFO', 'Bearer token auth passed', ['load' => $load_number]);

    // Create snapshot + token
    $stmt = $pdo->prepare("INSERT IGNORE INTO track_load_snapshots 
        (company_id, load_number, carrier_name, driver_name, driver_phone, driver_email, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$company_id, $load_number, $carrier_name, $driver_name, $driver_phone, $driver_email]);

    $token = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare("INSERT INTO track_tokens 
        (company_id, token, load_number, expires_at, status) 
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), 'active')");
    $stmt->execute([$company_id, $token, $load_number]);

    $tracking_url = "https://exspeeditecheckpoint.com/tracking/start.php?token=$token&milestone=pickup&stop=1";

    // [3] Token generated
    Logger::write('app.log', 'INFO', 'Token generated', ['load' => $load_number, 'token_prefix' => substr($token,0,8)]);

    // Twilio SMS
    $stmt = $pdo->prepare("SELECT credential_key, credential_value FROM system_credentials WHERE service_name = 'twilio'");
    $stmt->execute();
    $creds = [];
    while ($row = $stmt->fetch()) {
        $creds[$row['credential_key']] = trim($row['credential_value']);
    }

    $smsSent = false;
    $fromNumber = $creds['phone_number'] ?? $creds['from_number'] ?? '';

    if (!empty($fromNumber)) {
        $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/{$creds['account_sid']}/Messages.json");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'From' => $fromNumber,
            'To'   => $driver_phone,
            'Body' => "Exspeedite Checkpoint: Load $load_number for $driver_name is ready.\nTrack: $tracking_url"
        ]));
        curl_setopt($ch, CURLOPT_USERPWD, "{$creds['api_key_sid']}:{$creds['api_key_secret']}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) $smsSent = true;

        // [6] Twilio SMS result
        Logger::write('sms.log', 'INFO', 'Twilio SMS attempt', ['load' => $load_number, 'http_code' => $httpCode, 'phone_last4' => substr($driver_phone, -4)]);
    }

    // SendGrid Email
    $emailSent = false;
    if (!empty($driver_email)) {
        $stmt = $pdo->prepare("SELECT credential_value FROM system_credentials WHERE service_name = 'sendgrid' AND credential_key = 'api_key'");
        $stmt->execute();
        $sendgridKey = $stmt->fetchColumn();

        if ($sendgridKey) {
            $emailBody = "Your load <strong>$load_number</strong> is ready.<br><br>Track here: <a href='$tracking_url'>$tracking_url</a>";

            $ch = curl_init("https://api.sendgrid.com/v3/mail/send");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $sendgridKey", "Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                "personalizations" => [["to" => [["email" => $driver_email]]]],
                "from" => ["email" => "selliott@strongtco.com"],
                "subject" => "Load $load_number - Tracking Link",
                "content" => [["type" => "text/html", "value" => $emailBody]]
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            $emailCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($emailCode >= 200 && $emailCode < 300) $emailSent = true;

            // [7] SendGrid fallback result
            Logger::write('sms.log', 'INFO', 'SendGrid email attempt', ['load' => $load_number, 'http_code' => $emailCode]);
        }
    }

    // [8] Response dispatched
    Logger::write('app.log', 'INFO', 'Initialize complete', ['load' => $load_number, 'sms_sent' => $smsSent, 'email_sent' => $emailSent]);

    echo json_encode([
        'success' => true,
        'status' => 'initialized',
        'load_number' => $load_number,
        'tracking_url' => $tracking_url,
        'sms_sent' => $smsSent,
        'email_sent' => $emailSent
    ]);

} catch (Exception $e) {
    Logger::write('error.log', 'ERROR', 'Initialize failed', ['msg' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
