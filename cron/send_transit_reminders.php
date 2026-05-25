<?php
require_once __DIR__ . '/../config/bootstrap.php';

Logger::write('cron.log', 'INFO', 'Reminder cron started');

try {
    $twilioStmt = $pdo->prepare("SELECT credential_key, credential_value FROM system_credentials WHERE service_name='twilio'");
    $twilioStmt->execute();
    $twilio = [];
    while ($row = $twilioStmt->fetch(PDO::FETCH_ASSOC)) {
        $twilio[$row['credential_key']] = trim((string)$row['credential_value']);
    }

    $sendgridStmt = $pdo->prepare("SELECT credential_value FROM system_credentials WHERE service_name='sendgrid' AND credential_key='api_key' LIMIT 1");
    $sendgridStmt->execute();
    $sendgridApiKey = trim((string)$sendgridStmt->fetchColumn());

    $sql = "SELECT s.company_id, s.load_number, s.driver_name, s.driver_phone, s.driver_email, s.last_reminder_sent_at, s.last_updated_at AS last_ping_utc,
                   u.timezone AS company_timezone, u.contact_email
            FROM track_load_snapshots s
            LEFT JOIN track_users u ON u.company_id = s.company_id AND u.role = 'admin'
            WHERE s.status='active'
              AND s.driver_phone <> ''
              AND (s.last_reminder_sent_at IS NULL OR TIMESTAMPDIFF(HOUR, s.last_reminder_sent_at, UTC_TIMESTAMP()) >= 4)";
    $loads = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($loads as $row) {
        if (!empty($row['last_reminder_sent_at'])) {
            $last = strtotime((string)$row['last_reminder_sent_at']);
            if ($last !== false && (time() - $last) < 4 * 3600) {
                Logger::write('cron.log', 'INFO', 'Skipping duplicate reminder window', ['load' => $row['load_number'], 'company_id' => (int)$row['company_id']]);
                continue;
            }
        }

        $smsBody = "Exspeedite Checkpoint: Please update your location for load {$row['load_number']}.";
        $smsCode = 0;
        if (!empty($twilio['account_sid']) && !empty($twilio['api_key_sid']) && !empty($twilio['api_key_secret']) && !empty($twilio['phone_number'])) {
            $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/{$twilio['account_sid']}/Messages.json");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['From' => $twilio['phone_number'], 'To' => $row['driver_phone'], 'Body' => $smsBody]));
            curl_setopt($ch, CURLOPT_USERPWD, "{$twilio['api_key_sid']}:{$twilio['api_key_secret']}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            $smsCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        $companyTimezone = (string)($row['company_timezone'] ?: 'America/Chicago');
        if (!in_array($companyTimezone, timezone_identifiers_list(), true)) {
            $companyTimezone = 'America/Chicago';
        }
        $utcTime = new DateTime((string)$row['last_ping_utc'], new DateTimeZone('UTC'));
        $localTz = new DateTimeZone($companyTimezone);
        $displayTime = $utcTime->setTimezone($localTz)->format('M j, Y g:i A T');

        if (!empty($row['contact_email']) && filter_var($row['contact_email'], FILTER_VALIDATE_EMAIL) && $sendgridApiKey !== '') {
            $emailBody = "Driver silence alert for load {$row['load_number']}. Last ping: {$displayTime}.";
            $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$sendgridApiKey}", 'Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'personalizations' => [['to' => [['email' => $row['contact_email']]]]],
                'from' => ['email' => 'selliott@strongtco.com'],
                'subject' => "Dispatcher Alert: {$row['load_number']}",
                'content' => [['type' => 'text/plain', 'value' => $emailBody]],
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            $emailCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            Logger::write('cron.log', 'INFO', 'Dispatcher alert email attempted', ['load' => $row['load_number'], 'company_id' => (int)$row['company_id'], 'http_code' => $emailCode]);
        }

        $msgStmt = $pdo->prepare("INSERT INTO track_messages (company_id, load_number, recipient, message_type, message_body, provider_status, created_at) VALUES (?, ?, ?, 'dispatcher_alert', ?, ?, NOW())");
        $msgStmt->execute([(int)$row['company_id'], $row['load_number'], $row['driver_phone'], $smsBody, (string)$smsCode]);

        $eventStmt = $pdo->prepare("INSERT INTO track_event_log (company_id, load_number, event_type, event_payload, created_at) VALUES (?, ?, 'dispatcher_alert_sent', ?, NOW())");
        $eventStmt->execute([(int)$row['company_id'], $row['load_number'], json_encode(['sms_code' => $smsCode, 'display_time' => $displayTime])]);

        $updateStmt = $pdo->prepare("UPDATE track_load_snapshots SET last_reminder_sent_at = UTC_TIMESTAMP() WHERE company_id = ? AND load_number = ?");
        $updateStmt->execute([(int)$row['company_id'], $row['load_number']]);

        Logger::write('cron.log', 'INFO', 'Reminder iteration complete', ['load' => $row['load_number'], 'company_id' => (int)$row['company_id'], 'sms_http' => $smsCode]);
    }
} catch (Throwable $e) {
    Logger::write('cron.log', 'ERROR', 'Reminder cron failed', ['error' => $e->getMessage()]);
}
