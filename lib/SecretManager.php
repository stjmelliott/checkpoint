<?php
// lib/SecretManager.php — Task 5.1
class SecretManager {
    public static function getTwilioCredentials($pdo) {
        $stmt = $pdo->prepare("SELECT credential_key, credential_value FROM system_credentials WHERE service_name = 'twilio'");
        $stmt->execute();
        $creds = [];
        while ($row = $stmt->fetch()) {
            $creds[$row['credential_key']] = trim($row['credential_value']);
        }
        return $creds;
    }

    public static function getSendGridKey($pdo) {
        $stmt = $pdo->prepare("SELECT credential_value FROM system_credentials WHERE service_name = 'sendgrid' AND credential_key = 'api_key'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
?>
