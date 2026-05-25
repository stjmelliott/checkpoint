<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['company_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
Logger::write('app.log', 'INFO', 'FMCSA search requested', ['company_id' => (int)$_SESSION['company_id'], 'q' => mb_substr($q, 0, 80)]);

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT credential_value FROM app_credentials WHERE credential_key='fmcsa_api_key' LIMIT 1");
$stmt->execute();
$key = trim((string)$stmt->fetchColumn());
if ($key === '') {
    echo json_encode(['error' => 'FMCSA key not configured']);
    exit;
}

$url = 'https://mobile.fmcsa.dot.gov/qc/services/carriers/name/' . rawurlencode($q) . '?webKey=' . rawurlencode($key);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$body = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$decoded = json_decode((string)$body, true);
$results = $decoded['content'] ?? $decoded['results'] ?? [];
$out = [];
if (is_array($results)) {
    foreach ($results as $item) {
        if (!is_array($item)) { continue; }
        $out[] = [
            'dot_number' => (string)($item['dotNumber'] ?? $item['dot_number'] ?? ''),
            'legal_name' => (string)($item['legalName'] ?? $item['legal_name'] ?? ''),
            'dba_name' => (string)($item['dbaName'] ?? $item['dba_name'] ?? ''),
            'city' => (string)($item['phyCity'] ?? $item['city'] ?? ''),
            'state' => (string)($item['phyState'] ?? $item['state'] ?? ''),
        ];
    }
}
Logger::write('app.log', 'INFO', 'FMCSA search response received', ['company_id' => (int)$_SESSION['company_id'], 'count' => count($out), 'http_status' => $httpCode]);
echo json_encode($out);
