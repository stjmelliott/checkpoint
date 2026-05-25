<?php
require_once __DIR__ . '/../config/bootstrap.php';
echo "<h2>Task 2 — Database Schema Test (8 Tables)</h2><pre>";
$tables = ['track_users','track_api_clients','track_load_snapshots','track_load_stops','track_tokens','track_location_pings','track_messages','track_event_log'];
foreach ($tables as $t) {
    $res = $pdo->query("SHOW TABLES LIKE '$t'")->fetch();
    echo "$t → " . ($res ? "✅ Present" : "❌ Missing") . "\n";
}
echo "</pre>";
?>
