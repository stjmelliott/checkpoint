<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../lib/initialize_logic.php';

header('Content-Type: application/json');

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$bearerToken = '';
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $bearerToken = trim($matches[1]);
}

$stmt = $pdo->prepare("SELECT credential_value FROM app_credentials WHERE service_name='checkpoint' AND credential_key='webhook_bearer_token' LIMIT 1");
$stmt->execute();
$expectedToken = trim((string)$stmt->fetchColumn());

if ($expectedToken === '' || $bearerToken === '' || !hash_equals($expectedToken, $bearerToken)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'status' => 'unauthorized',
        'load_number' => '',
        'tracking_url' => '',
        'sms_sent' => false,
        'email_sent' => false,
        'error_code' => 'UNAUTHORIZED',
        'message' => 'Invalid bearer token',
    ]);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$companyId = (int)($payload['company_id'] ?? 0);
if ($companyId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'status' => 'failed',
        'load_number' => (string)($payload['load_number'] ?? ''),
        'tracking_url' => '',
        'sms_sent' => false,
        'email_sent' => false,
        'error_code' => 'VALIDATION_ERROR',
        'message' => 'company_id is required',
    ]);
    exit;
}

$result = run_initialize($payload, $companyId, $pdo);
http_response_code($result['success'] ? 200 : 400);
echo json_encode($result);
