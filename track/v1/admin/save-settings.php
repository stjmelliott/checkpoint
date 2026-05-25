<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
header('Content-Type: application/json');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['company_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$companyId = (int)$_SESSION['company_id'];
$apiKey = trim((string)($_POST['fmcsa_api_key'] ?? ''));

$stmt = $pdo->prepare(
    "INSERT INTO app_credentials (company_id, service_name, credential_key, credential_value)
     VALUES (:company_id, 'checkpoint', 'fmcsa_api_key', :api_key)
     ON DUPLICATE KEY UPDATE credential_value = VALUES(credential_value)"
);
$stmt->execute([
    ':company_id' => $companyId,
    ':api_key' => $apiKey,
]);

echo json_encode(['success' => true]);
