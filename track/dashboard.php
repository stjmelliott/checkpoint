<?php require_once '../config/bootstrap.php'; ?>
<?php
$load_entry_mode = 'webhook';
try {
    $stmt = $pdo->prepare("SELECT credential_value FROM app_credentials WHERE service_name = 'checkpoint' AND credential_key = 'load_entry_mode' LIMIT 1");
    $stmt->execute();
    $mode = strtolower((string)$stmt->fetchColumn());
    if (in_array($mode, ['webhook', 'manual', 'both'], true)) {
        $load_entry_mode = $mode;
    }
} catch (Throwable $e) {
    Logger::write('error.log', 'WARN', 'Failed to resolve load_entry_mode', ['msg' => $e->getMessage()]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exspeedite CheckPoint • Live Map</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/dashboard.css?v=20260522c">
    
</head>
<body>

<?php
$checkpointHeaderCurrent = 'Live Map';
require_once __DIR__ . '/includes/header.php';
?>

<div id="map"></div>

<div class="legend">
    <strong style="color:#22c55e; display:block; margin-bottom:8px;">Legend</strong>
    <div>🚚 Pickup &nbsp;&nbsp; ➡️ In Transit &nbsp;&nbsp; 📍 Delivery</div>
</div>

<div class="sidebar">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3 style="margin:0; color:#22c55e;">Loads</h3>
        <div style="display:flex; gap:8px;">
            <?php if (in_array($load_entry_mode, ['manual', 'both'], true)): ?>
            <button class="btn btn-success btn-sm" id="ADD_LOAD_BTN" data-bs-toggle="modal" data-bs-target="#addLoadModal"><span class="glyphicon glyphicon-plus"></span> Add Load</button>
            <?php endif; ?>
            <a href="help-dashboard-logic.php" target="_blank">
                <button style="background:#334155; padding:8px 16px;">Help</button>
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="status-tabs" style="margin-bottom:15px; display:flex; gap:6px; flex-wrap:wrap;">
        <button class="status-tab active" onclick="filterByStatus('active', this)">Active</button>
        <button class="status-tab" onclick="filterByStatus('completed', this)">Completed</button>
        <button class="status-tab" onclick="filterByStatus('cancelled', this)">Canceled</button>
        <button class="status-tab" onclick="filterByStatus('all', this)">All</button>
    </div>

    <div id="load-list"></div>
    <button onclick="clearTrail()" style="margin-top:15px; width:100%;">Clear Trail</button>
</div>

<div id="addLoadModal" class="manual-modal hidden" aria-hidden="true">
    <div class="manual-modal-card modal-content" role="dialog" aria-modal="true" aria-labelledby="addLoadTitle">
        <div class="manual-modal-head modal-header">
            <h4 id="addLoadTitle" class="modal-title">Add Load</h4>
            <button id="manual-close-btn" class="manual-close-btn" type="button" aria-label="Close">×</button>
        </div>
        <div class="manual-tabs wizard-progress-bar" aria-label="Load creation steps">
            <button class="manual-tab wizard-step-node active" data-panel="1" type="button">1. Carrier</button>
            <button class="manual-tab wizard-step-node" data-panel="2" type="button">2. Driver</button>
            <button class="manual-tab wizard-step-node" data-panel="3" type="button">3. Load</button>
        </div>
        <form id="manual-load-form" class="modal-body">
            <div class="manual-panel" data-panel="1">
                <input type="hidden" name="carrier_id" id="carrier_id" value="0">
                <label>Carrier Name <input class="form-control" type="text" name="carrier_name" id="carrier_name" required autocomplete="off"></label>
                <div id="carrier-results" class="carrier-results"></div>
                <div class="manual-inline-actions">
                    <button type="button" id="fmcsa-btn" class="btn btn-outline-primary">Search FMCSA Registry</button>
                    <button type="button" id="manualCarrierEntry" class="btn btn-outline-primary">Use Manual Carrier Entry</button>
                </div>
                <label>DOT Number <input class="form-control" type="text" name="dot_number" id="dot_number"></label>
            </div>
            <div class="manual-panel hidden" data-panel="2">
                <input type="hidden" name="driver_id" id="driver_id" value="0">
                <label>Active Driver Profile
                    <select id="driver_select" class="form-select"><option value="0">+ New Driver</option></select>
                </label>
                <div id="new-driver-form" class="new-driver-form">
                    <label>Driver Name <input class="form-control" type="text" name="driver_name" id="driver_name"></label>
                    <label>Driver Phone <input class="form-control" type="text" name="driver_phone" id="driver_phone" placeholder="6124446655" required></label>
                    <label>Driver Email <input class="form-control" type="email" name="driver_email" id="driver_email"></label>
                </div>
            </div>
            <div class="manual-panel hidden" data-panel="3">
                <label>Load Number <input class="form-control" type="text" name="load_number" id="load_number" required></label>
                <div id="stops-wrap"></div>
                <button type="button" id="add-stop-btn" class="btn btn-success">+ Add Stop</button>
            </div>
            <div class="manual-error" id="manual-error"></div>
            <div class="manual-actions">
                <button type="button" id="manual-clear-btn" class="btn-clear-form">Clear Form / Start Over</button>
                <button type="button" id="manual-back-btn" class="btn btn-outline-primary">Back</button>
                <button type="button" id="manual-next-btn" class="btn btn-success">Next</button>
                <button type="submit" id="manual-submit-btn" class="btn btn-success hidden">Create Load</button>
            </div>
        </form>
    </div>
</div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/dashboard.js?v=20260525a"></script>

</body>
</html>
