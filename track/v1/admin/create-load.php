<?php
// /v1/admin/create-load.php
require_once '../../config/bootstrap.php';
require_once '../../lib/initialize_logic.php';

if (!isset($_SESSION['company_id'])) { http_response_code(401); exit(json_encode(['success'=>false])); }

// load_entry_mode guard
// ... (check app_credentials)

Logger::write('app.log', 'INFO', 'Manual load creation requested', ['company_id' => $_SESSION['company_id'], 'load' => $_POST['load_number'] ?? 'missing']);

// Build $payload from form POST (carrier_id/driver_id handling, inline upsert if 0)
// Call run_initialize($payload, $_SESSION['company_id'], $pdo)

// Log 'manual_load_created' to track_event_log

echo json_encode($result);
?>