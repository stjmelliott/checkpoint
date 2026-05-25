<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
header('Content-Type: application/json');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['company_id']) || (int)$_SESSION['company_id'] <= 0) {
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
        echo json_encode($trail->fetchAll(PDO::FETCH_ASSOC));
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
            s.status AS checkin_status,
            s.driver_phone AS phone,
            latest_ping.lat AS latest_lat,
            latest_ping.lng AS latest_lng,
            latest_ping.milestone_type AS latest_milestone,
            latest_ping.stop_sequence AS latest_stop,
            latest_ping.timestamp AS last_checkin,
            COALESCE(ping_totals.ping_count, 0) AS ping_count
        FROM track_load_snapshots s
        LEFT JOIN (
            SELECT p.company_id, p.load_number, p.lat, p.lng, p.milestone_type, p.stop_sequence, p.timestamp
            FROM track_location_pings p
            INNER JOIN (
                SELECT company_id, load_number, MAX(id) AS max_id
                FROM track_location_pings
                WHERE company_id = ?
                GROUP BY company_id, load_number
            ) latest ON latest.company_id = p.company_id AND latest.load_number = p.load_number AND latest.max_id = p.id
        ) latest_ping ON latest_ping.company_id = s.company_id AND latest_ping.load_number = s.load_number
        LEFT JOIN (
            SELECT company_id, load_number, COUNT(*) AS ping_count
            FROM track_location_pings
            WHERE company_id = ?
            GROUP BY company_id, load_number
        ) ping_totals ON ping_totals.company_id = s.company_id AND ping_totals.load_number = s.load_number
        WHERE $where
        ORDER BY s.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$company_id, $company_id], $params));
    $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($loads);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
