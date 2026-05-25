<?php

function run_initialize(array $payload, int $company_id, PDO $pdo): array
{
    $response = [
        'success' => false,
        'status' => 'failed',
        'load_number' => (string)($payload['load_number'] ?? ''),
        'tracking_url' => '',
        'sms_sent' => false,
        'email_sent' => false,
        'error_code' => null,
        'message' => null,
    ];

    $loadNumber = trim((string)($payload['load_number'] ?? ''));
    $carrierName = trim((string)($payload['carrier_name'] ?? 'Unknown Carrier'));
    $driverName = trim((string)($payload['driver_name'] ?? 'Driver'));
    $driverPhone = trim((string)($payload['driver_phone'] ?? ''));
    $driverEmail = trim((string)($payload['driver_email'] ?? ''));

    if ($company_id <= 0 || $loadNumber === '' || $driverPhone === '') {
        $response['error_code'] = 'VALIDATION_ERROR';
        $response['message'] = 'Missing required fields';
        return $response;
    }

    try {
        Logger::write('app.log', 'INFO', 'Initialize request received', ['company_id' => $company_id, 'load' => $loadNumber]);

        $stmt = $pdo->prepare("INSERT IGNORE INTO track_load_snapshots
            (company_id, load_number, carrier_name, driver_name, driver_phone, driver_email, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$company_id, $loadNumber, $carrierName, $driverName, $driverPhone, $driverEmail]);

        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("INSERT INTO track_tokens
            (company_id, token, load_number, expires_at, status)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), 'active')");
        $stmt->execute([$company_id, $token, $loadNumber]);

        $trackingUrl = "https://exspeeditecheckpoint.com/tracking/start.php?token={$token}&milestone=pickup&stop=1";

        $response['tracking_url'] = $trackingUrl;

        $stmt = $pdo->prepare("SELECT credential_key, credential_value FROM system_credentials WHERE service_name = 'twilio'");
        $stmt->execute();
        $twilio = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $twilio[$row['credential_key']] = trim((string)$row['credential_value']);
        }

        $fromNumber = $twilio['phone_number'] ?? $twilio['from_number'] ?? '';
        if ($fromNumber !== '' && !empty($twilio['account_sid']) && !empty($twilio['api_key_sid']) && !empty($twilio['api_key_secret'])) {
            $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/{$twilio['account_sid']}/Messages.json");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'From' => $fromNumber,
                'To' => $driverPhone,
                'Body' => "Exspeedite Checkpoint: Load {$loadNumber} for {$driverName} is ready.\nTrack: {$trackingUrl}",
            ]));
            curl_setopt($ch, CURLOPT_USERPWD, "{$twilio['api_key_sid']}:{$twilio['api_key_secret']}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            $smsCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $response['sms_sent'] = ($smsCode >= 200 && $smsCode < 300);
            Logger::write('sms.log', 'INFO', 'Twilio SMS attempt', ['load' => $loadNumber, 'http_code' => $smsCode]);
        }

        if ($driverEmail !== '') {
            $stmt = $pdo->prepare("SELECT credential_value FROM system_credentials WHERE service_name = 'sendgrid' AND credential_key = 'api_key' LIMIT 1");
            $stmt->execute();
            $sendgridKey = (string)$stmt->fetchColumn();

            if ($sendgridKey !== '') {
                $emailBody = "Your load <strong>{$loadNumber}</strong> is ready.<br><br>Track here: <a href='{$trackingUrl}'>{$trackingUrl}</a>";
                $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer {$sendgridKey}",
                    'Content-Type: application/json',
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'personalizations' => [['to' => [['email' => $driverEmail]]]],
                    'from' => ['email' => 'selliott@strongtco.com'],
                    'subject' => "Load {$loadNumber} - Tracking Link",
                    'content' => [['type' => 'text/html', 'value' => $emailBody]],
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                $emailCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $response['email_sent'] = ($emailCode >= 200 && $emailCode < 300);
                Logger::write('sms.log', 'INFO', 'SendGrid email attempt', ['load' => $loadNumber, 'http_code' => $emailCode]);
            }
        }

        $response['success'] = true;
        $response['status'] = 'initialized';
        $response['load_number'] = $loadNumber;
        Logger::write('app.log', 'INFO', 'Initialize complete', ['company_id' => $company_id, 'load' => $loadNumber]);
        return $response;
    } catch (Throwable $e) {
        Logger::write('error.log', 'ERROR', 'Initialize failed', ['company_id' => $company_id, 'load' => $loadNumber, 'msg' => $e->getMessage()]);
        $response['error_code'] = 'SERVER_ERROR';
        $response['message'] = 'Server error';
        return $response;
    }
}
