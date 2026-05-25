<?php
require_once '../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['company_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$companyId = (int)$_SESSION['company_id'];
$carrierId = (int)($_GET['carrier_id'] ?? 0);
if ($carrierId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, driver_name, driver_phone, driver_email FROM track_carrier_drivers WHERE carrier_id = ? AND company_id = ? AND is_active = 1 ORDER BY driver_name");
$stmt->execute([$carrierId, $companyId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
