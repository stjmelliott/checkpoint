<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['company_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'output' => 'Forbidden']);
    exit;
}

$output = [];
$code = 1;
exec('cd /var/www/checkpoint && git pull origin main 2>&1', $output, $code);

echo json_encode([
    'success' => ($code === 0),
    'output' => implode("\n", $output),
]);
