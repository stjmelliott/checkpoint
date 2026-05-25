<?php
// /var/www/checkpoint/track/v1/dashboard/data.php
require_once __DIR__ . '/../../../config/bootstrap.php';

header('Content-Type: application/json');

$company_id = 1; // TODO: Replace with real session later

try {
    $load_number = $_GET['load_number'] ?? null;

    // If load_number is passed, return trail pings for that load only
    if ($load_number) {
        $stmt = $pdo->prepare("
            SELECT lat, lng, milestone_type, stop_sequence, timestamp, accuracy_quality
            FROM track_location_pings
            WHERE load_number = ? AND company_id = ?
            ORDER BY timestamp ASC
        ");
        $stmt->execute([$load_number, $company_id]);
        $pings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'pings' => $pings]);
        exit;
    }

    // Default: Return active loads with latest position only
    $sql = "
        SELECT 
            s.load_number,
            s.carrier_name,
            s.driver_name,
            s.status,
            t.token,
            p.lat AS latest_lat,
            p.lng AS latest_lng,
            p.milestone_type AS latest_milestone,
            p.stop_sequence AS latest_stop,
            p.timestamp AS last_checkin,
            p.accuracy_quality,
            (SELECT COUNT(*) FROM track_location_pings 
             WHERE load_number = s.load_number AND company_id = ?) AS ping_count
        FROM track_load_snapshots s
        LEFT JOIN track_tokens t 
            ON t.load_number = s.load_number 
           AND t.company_id = s.company_id 
           AND t.status = 'active'
        LEFT JOIN (
            SELECT p1.* 
            FROM track_location_pings p1
            INNER JOIN (
                SELECT load_number, MAX(id) as max_id 
                FROM track_location_pings 
                WHERE company_id = ?
                GROUP BY load_number
            ) p2 ON p1.id = p2.max_id
        ) p ON p.load_number = s.load_number
        WHERE s.company_id = ? 
          AND s.status = 'active'
        ORDER BY s.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$company_id, $company_id, $company_id]);
    $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'loads' => $loads
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}