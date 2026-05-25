<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
header('Content-Type: application/json');

if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$company_id = (int)$_SESSION['company_id'];

$status = $_GET['status'] ?? 'active'; // active | completed | cancelled | all

try {
    $loadNumber = trim((string)($_GET['load_number'] ?? ''));
    if ($loadNumber !== '') {
        $trail = $pdo->prepare("SELECT lat, lng, milestone_type, stop_sequence, timestamp FROM track_location_pings WHERE company_id = ? AND load_number = ? ORDER BY timestamp ASC");
        $trail->execute([$company_id, $loadNumber]);
        echo json_encode(['success' => true, 'pings' => $trail->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }
    $where = "s.company_id = ?";
    $params = [$company_id];

    if ($status !== 'all') {
        $where .= " AND s.status = ?";
        $params[] = $status;
    }

    $sql = "
        SELECT 
            s.load_number,
            s.carrier_name,
            s.driver_name,
            s.status,
            p.lat AS latest_lat,
            p.lng AS latest_lng,
            p.milestone_type AS latest_milestone,
            p.stop_sequence AS latest_stop,
            p.timestamp AS last_checkin,
            (SELECT COUNT(*) FROM track_location_pings 
             WHERE load_number = s.load_number AND company_id = ?) AS ping_count
        FROM track_load_snapshots s
        LEFT JOIN (
            SELECT p1.* FROM track_location_pings p1
            INNER JOIN (
                SELECT load_number, MAX(id) as max_id 
                FROM track_location_pings 
                WHERE company_id = ?
                GROUP BY load_number
            ) p2 ON p1.id = p2.max_id
        ) p ON p.load_number = s.load_number
        WHERE $where
        ORDER BY s.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$company_id, $company_id], $params));
    $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'loads' => $loads]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
