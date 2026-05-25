<?php
// /var/www/checkpoint/cron/send_transit_reminders.php

require_once __DIR__ . '/../config/bootstrap.php';

Logger::write('cron.log', 'INFO', 'Reminder cron started');

try {
    $company_id = 1;

    // Load Twilio credentials from database
    $stmt = $pdo->prepare("SELECT credential_key, credential_value FROM system_credentials WHERE service_name = 'twilio'");
    $stmt->execute();
    $creds = [];
    while ($row = $stmt->fetch()) {
        $creds[$row['credential_key']] = trim($row['credential_value']);
    }

    $accountSid   = $creds['account_sid'] ?? null;
    $apiKeySid    = $creds['api_key_sid'] ?? null;
    $apiKeySecret = $creds['api_key_secret'] ?? null;
    $fromNumber   = $creds['phone_number'] ?? $creds['from_number'] ?? null;

    if (!$accountSid || !$apiKeySid || !$apiKeySecret || !$fromNumber) {
        throw new Exception('Twilio credentials incomplete in database');
    }

    // Find loads that need a reminder
    $sql = "
        SELECT s.load_number, s.driver_phone, s.driver_name, t.token
        FROM track_load_snapshots s
        JOIN track_tokens t ON t.load_number = s.load_number 
            AND t.company_id = s.company_id 
            AND t.status = 'active'
        WHERE s.company_id = ?
          AND s.status = 'active'
          AND (s.last_reminder_sent_at IS NULL 
               OR TIMESTAMPDIFF(HOUR, s.last_reminder_sent_at, NOW()) >= 4)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$company_id]);
    $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Logger::write('cron.log', 'INFO', 'Loads selected for reminder', ['count' => count($loads)]);

    $sent = 0;

    foreach ($loads as $load) {
        $message = "Exspeedite Checkpoint: Please update your location for load {$load['load_number']}.";

        // Send SMS via Twilio
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        $payload = [
            'From' => $fromNumber,
            'To'   => $load['driver_phone'],
            'Body' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_USERPWD, "$apiKeySid:$apiKeySecret");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            Logger::write('cron.log', 'INFO', 'Cron reminder SMS sent', [
                'load' => $load['load_number']
            ]);
            $sent++;
        } else {
            Logger::write('cron.log', 'ERROR', 'Cron reminder SMS failed', [
                'load' => $load['load_number'],
                'http_code' => $httpCode
            ]);
        }

        // Update last reminder time
        $pdo->prepare("UPDATE track_load_snapshots SET last_reminder_sent_at = NOW() WHERE load_number = ? AND company_id = ?")
            ->execute([$load['load_number'], $company_id]);
    }

    Logger::write('cron.log', 'INFO', 'Reminder cron complete', ['sent' => $sent]);

} catch (Exception $e) {
    Logger::write('cron.log', 'ERROR', 'Reminder cron failed', ['error' => $e->getMessage()]);
}