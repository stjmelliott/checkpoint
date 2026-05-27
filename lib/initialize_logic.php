<?php

function build_tracking_sms_body(string $driverName, string $milestone, string $locationName, string $city, string $state, string $url): string
{
    if ($milestone === 'pickup') {
        return "Hi {$driverName}, tap to confirm your pickup at {$locationName}, {$city} {$state}: {$url}";
    }
    if ($milestone === 'transit') {
        return "Hi {$driverName}, tap to confirm arrival at {$locationName}, {$city} {$state}: {$url}";
    }
    return "Hi {$driverName}, tap to confirm delivery at {$locationName}, {$city} {$state}: {$url}";
}

function send_twilio_sms(PDO $pdo, string $to, string $body, string $loadNumber): bool
{
    $stmt = $pdo->prepare("SELECT credential_key, credential_value FROM system_credentials WHERE service_name = 'twilio'");
    $stmt->execute();
    $twilio = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $twilio[$row['credential_key']] = trim((string)$row['credential_value']);
    }

    $fromNumber = $twilio['phone_number'] ?? $twilio['from_number'] ?? '';
    if ($fromNumber === '' || empty($twilio['account_sid']) || empty($twilio['api_key_sid']) || empty($twilio['api_key_secret'])) {
        return false;
    }

    $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/{$twilio['account_sid']}/Messages.json");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['From' => $fromNumber, 'To' => $to, 'Body' => $body]));
    curl_setopt($ch, CURLOPT_USERPWD, "{$twilio['api_key_sid']}:{$twilio['api_key_secret']}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    Logger::write('sms.log', 'INFO', 'Twilio SMS attempt', ['load' => $loadNumber, 'http_code' => $code, 'phone_last4' => substr($to, -4)]);
    return ($code >= 200 && $code < 300);
}

function send_sendgrid_email(PDO $pdo, string $to, string $subject, string $bodyText, string $loadNumber): bool
{
    if ($to === '') return false;
    $stmt = $pdo->prepare("SELECT credential_value FROM system_credentials WHERE service_name='sendgrid' AND credential_key='api_key' LIMIT 1");
    $stmt->execute();
    $sendgridKey = (string)$stmt->fetchColumn();
    if ($sendgridKey === '') return false;

    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$sendgridKey}", 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'personalizations' => [['to' => [['email' => $to]]]],
        'from' => ['email' => 'tracking@exspeeditecheckpoint.com'],
        'subject' => $subject,
        'content' => [['type' => 'text/plain', 'value' => $bodyText]],
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    Logger::write('sms.log', 'INFO', 'SendGrid email attempt', ['load' => $loadNumber, 'http_code' => $code]);
    return ($code >= 200 && $code < 300);
}

function run_initialize(array $payload, int $company_id, PDO $pdo): array
{
    $loadNumber = trim((string)($payload['load_number'] ?? ''));
    $carrierName = trim((string)($payload['carrier_name'] ?? ''));
    $driverName = trim((string)($payload['driver_name'] ?? ''));
    $driverPhone = trim((string)($payload['driver_phone'] ?? ''));
    $driverEmail = trim((string)($payload['driver_email'] ?? ''));
    $stops = $payload['stops'] ?? [];

    $missing = [];
    foreach (['load_number','carrier_name','driver_name','driver_phone','driver_email','stops'] as $field) {
        if (!isset($payload[$field]) || $payload[$field] === '' || $payload[$field] === []) $missing[] = $field;
    }
    if ($missing) {
        return ['success'=>false,'error_code'=>'MISSING_MANDATORY_FIELDS','message'=>'Required fields missing: ['.implode(', ', $missing).'].'];
    }
    if (!preg_match('/^\+[1-9]\d{1,14}$/', $driverPhone) || !is_array($stops) || count($stops) < 1) {
        return ['success'=>false,'error_code'=>'MISSING_MANDATORY_FIELDS','message'=>'Required fields missing or invalid.'];
    }

    foreach ($stops as $s) {
        $seq = (int)($s['stop_sequence'] ?? 0);
        $milestone = (string)($s['milestone_type'] ?? '');
        $locationName = trim((string)($s['location_name'] ?? ''));
        $city = trim((string)($s['city'] ?? ''));
        $state = trim((string)($s['state'] ?? ''));
        if ($seq < 1 || !in_array($milestone, ['pickup','transit','delivery'], true) || $locationName === '' || $city === '' || $state === '') {
            return ['success'=>false,'error_code'=>'MISSING_MANDATORY_FIELDS','message'=>'Invalid stops payload.'];
        }
    }

    $stmt = $pdo->prepare("SELECT status FROM track_load_snapshots WHERE company_id = ? AND load_number = ? LIMIT 1");
    $stmt->execute([$company_id, $loadNumber]);
    $existingStatus = $stmt->fetchColumn();
    if ($existingStatus === 'active') {
        return ['success'=>false,'error_code'=>'LOAD_ALREADY_ACTIVE','message'=>'Load already active. Close existing load before re-initializing.'];
    }

    if ($existingStatus) {
        $pdo->prepare("UPDATE track_load_snapshots SET status='cancelled' WHERE company_id=? AND load_number=? AND status='active'")->execute([$company_id, $loadNumber]);
    }

    $pdo->prepare("INSERT INTO track_load_snapshots (company_id, load_number, carrier_name, driver_name, driver_phone, driver_email, status, sms_fallback_used)
        VALUES (?, ?, ?, ?, ?, ?, 'active', 0)
        ON DUPLICATE KEY UPDATE carrier_name=VALUES(carrier_name),driver_name=VALUES(driver_name),driver_phone=VALUES(driver_phone),driver_email=VALUES(driver_email),status='active',sms_fallback_used=0")
        ->execute([$company_id, $loadNumber, $carrierName, $driverName, $driverPhone, $driverEmail]);

    $pdo->prepare("DELETE FROM track_load_stops WHERE company_id=? AND load_number=?")->execute([$company_id, $loadNumber]);
    $insStop = $pdo->prepare("INSERT INTO track_load_stops (company_id, load_number, stop_sequence, milestone_type, location_name, city, state, sms_sent_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NULL)");
    foreach ($stops as $s) {
        $insStop->execute([$company_id, $loadNumber, (int)$s['stop_sequence'], $s['milestone_type'], $s['location_name'], $s['city'], $s['state']]);
    }

    $token = TokenService::generate();
    $expiryDays = TokenService::getExpiryDays();
    $pdo->prepare("INSERT INTO track_tokens (company_id, token, load_number, expires_at, status)
        VALUES (?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL {$expiryDays} DAY), 'active')")
        ->execute([$company_id, $token, $loadNumber]);

    $first = $stops[0];
    $trackingUrl = "https://track.exspeeditecheckpoint.com/start?token={$token}&milestone={$first['milestone_type']}&stop=1";
    $smsBody = build_tracking_sms_body($driverName, $first['milestone_type'], (string)$first['location_name'], (string)$first['city'], (string)$first['state'], $trackingUrl);

    $smsSent = send_twilio_sms($pdo, $driverPhone, $smsBody, $loadNumber);
    $emailSent = false;
    if (!$smsSent) {
        $emailSent = send_sendgrid_email($pdo, $driverEmail, "Your tracking link for Load #{$loadNumber}", $smsBody, $loadNumber);
        if ($emailSent) {
            $pdo->prepare("UPDATE track_load_snapshots SET sms_fallback_used=1 WHERE company_id=? AND load_number=?")->execute([$company_id, $loadNumber]);
        }
    }

    if ($smsSent || $emailSent) {
        $pdo->prepare("UPDATE track_load_stops SET sms_sent_at = UTC_TIMESTAMP() WHERE company_id=? AND load_number=? AND stop_sequence=1")->execute([$company_id, $loadNumber]);
        $pdo->prepare("INSERT INTO track_event_log (load_number, company_id, event_type, actor, message) VALUES (?, ?, 'sms_sent', 'system', ?)")
            ->execute([$loadNumber, $company_id, 'Stop 1 notification sent']);
    }

    return [
        'success' => true,
        'status' => 'initialized',
        'load_number' => $loadNumber,
        'tracking_url' => $trackingUrl,
        'sms_sent' => $smsSent,
        'email_sent' => $emailSent,
    ];
}
