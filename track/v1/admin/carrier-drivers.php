<?php
// /v1/admin/carrier-drivers.php
require_once '../../config/bootstrap.php';
if (!isset($_SESSION['company_id'])) { http_response_code(401); exit; }

$carrier_id = (int)($_GET['carrier_id'] ?? 0);
if ($carrier_id <= 0) exit(json_encode([]));

$stmt = $pdo->prepare("SELECT id, driver_name, driver_phone, driver_email 
    FROM track_carrier_drivers 
    WHERE carrier_id = ? AND company_id = ? AND is_active = 1 
    ORDER BY driver_name");
$stmt->execute([$carrier_id, $_SESSION['company_id']]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>