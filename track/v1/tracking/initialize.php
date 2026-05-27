<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../lib/initialize_logic.php';

header('Content-Type: application/json');

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$bearerToken = '';
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $bearerToken = trim($matches[1]);
}

$stmt = $pdo->prepare("SELECT company_id, credential_value FROM app_credentials WHERE service_name='checkpoint' AND credential_key='webhook_bearer_token'");
$stmt->execute();
$companyId = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($bearerToken !== '' && hash_equals(trim((string)$row['credential_value']), $bearerToken)) {
        $companyId = (int)($row['company_id'] ?? 0);
        break;
    }
}

if ($companyId <= 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error_code' => 'INVALID_BEARER_TOKEN',
        'message' => 'The API authorization token is invalid or has expired.',
    ]);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$result = run_initialize($payload, $companyId, $pdo);
if ($result['success']) {
    http_response_code(200);
} elseif (($result['error_code'] ?? '') === 'LOAD_ALREADY_ACTIVE') {
    http_response_code(409);
} else {
    http_response_code(400);
}

echo json_encode($result);
