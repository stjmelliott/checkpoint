<?php
// /var/www/checkpoint/config/credentials.php

function loadCredentials(PDO $pdo): array {
    $stmt = $pdo->query(
        "SELECT service_name, credential_key, credential_value 
         FROM app_credentials 
         WHERE is_active = 1"
    );
    $rows = $stmt->fetchAll();
    $creds = [];
    foreach ($rows as $row) {
        $creds[$row['service_name']][$row['credential_key']] = $row['credential_value'];
    }
    return $creds;
}

// Load once and store globally
$GLOBALS['creds'] = loadCredentials($pdo);
