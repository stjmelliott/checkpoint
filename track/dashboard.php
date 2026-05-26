<?php 
require_once '../config/bootstrap.php'; 
$stmt = $pdo->prepare("SELECT credential_value FROM app_credentials WHERE service_name='checkpoint' AND credential_key='load_entry_mode' LIMIT 1");
$stmt->execute();
$load_entry_mode = $stmt->fetchColumn() ?: 'webhook';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exspeedite CheckPoint • Live Map</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

<?php $checkpointHeaderCurrent = 'Live Map'; require_once __DIR__ . '/includes/header.php'; ?>

<div id="map"></div>

<div class="legend">
    <strong style="color:#22c55e; display:block; margin-bottom:8px;">Legend</strong>
    <div>🚚 Pickup &nbsp;&nbsp; ➡️ In Transit &nbsp;&nbsp; 📍 Delivery</div>
</div>

<div class="sidebar">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3 style="margin:0; color:#22c55e;">Loads</h3>
        <div style="display:flex; gap:8px;">
            <?php if (in_array($load_entry_mode, ['manual', 'both'])): ?>
                <button type="button" class="btn btn-success" id="ADD_LOAD_BTN" data-bs-toggle="modal" data-bs-target="#addLoadModal" style="background:#22c55e; border:none; padding:6px 12px; border-radius:4px; font-weight:600; color:#fff;">+ Add Load</button>
            <?php endif; ?>
            <a href="help-dashboard-logic.php" target="_blank"><button style="background:#334155; padding:8px 16px;">Help</button></a>
        </div>
    </div>
    
    <div class="status-tabs" style="margin-bottom:15px; display:flex; gap:6px; flex-wrap:wrap;">
        <button class="status-tab active" onclick="filterByStatus('active', this)">Active</button>
        <button class="status-tab" onclick="filterByStatus('completed', this)">Completed</button>
        <button class="status-tab" onclick="filterByStatus('cancelled', this)">Canceled</button>
        <button class="status-tab" onclick="filterByStatus('all', this)">All</button>
    </div>
    
    <div id="load-list"></div>
    <button onclick="clearTrail()" style="margin-top:15px; width:100%;">Clear Trail</button>
</div>

<div class="modal fade" id="addLoadModal" tabindex="-1" aria-labelledby="addLoadModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header manual-modal-head">
                <h5 class="modal-title" id="addLoadModalLabel">Manual Load Wizard</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="manual-load-form" class="modal-body p-4">
  <input type="hidden" name="carrier_id" id="carrier_id" value="0">
  <input type="hidden" name="driver_id" id="driver_id" value="0">
  <!-- Carrier + DOT side-by-side -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-7">
      <label for="carrier_name" class="form-label">Carrier Name <span class="text-danger">*</span></label>
      <input class="form-control" type="text" name="carrier_name" id="carrier_name" placeholder="Start typing legal or DBA carrier name">
      <div id="carrier-results" class="carrier-results mt-2"></div>
    </div>
    <div class="col-12 col-lg-5">
      <label for="dot_number" class="form-label">DOT Number <span class="text-muted">(optional)</span></label>
      <input class="form-control" type="text" name="dot_number" id="dot_number" placeholder="e.g. 1234567" inputmode="numeric">
    </div>
    <button type="button" id="fmcsa-btn" class="btn btn-outline-primary w-100" onclick="searchFmcsa()">🔎 Search FMCSA</button>
  </div>
  <!-- Driver Info -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
      <label for="driver_phone" class="form-label">Driver Phone</label>
      <input class="form-control" type="tel" name="driver_phone" id="driver_phone" value="17634446474" placeholder="17634446474 or driver's number">
    </div>
    <div class="col-12 col-md-6">
      <label for="driver_name" class="form-label">Driver Name</label>
      <input class="form-control" type="text" name="driver_name" id="driver_name" placeholder="John Doe">
    </div>
    <div class="col-12">
      <label for="driver_email" class="form-label">Driver Email (optional)</label>
      <input class="form-control" type="email" name="driver_email" id="driver_email" placeholder="name@example.com">
    </div>
  </div>
  <!-- Load Details + Stops -->
  <div class="row g-3">
    <div class="col-12 col-md-6">
      <label for="load_number" class="form-label">Load Number <span class="text-danger">*</span></label>
      <input class="form-control" type="text" name="load_number" id="load_number" placeholder="Internal or broker #">
    </div>
    <div class="col-12">
      <label class="form-label">Stops</label>
      <div id="stops-wrap"></div>
      <button type="button" id="add-stop-btn" class="btn btn-success btn-sm mt-2" onclick="addStopRow()">+ Add Stop</button>
    </div>
  </div>
  <div class="manual-error mt-3" id="manual-error"></div>
</form>
            <div class="modal-footer manual-actions">
                <button type="button" id="modalClearFormBtn" class="btn-clear-form">Clear Form / Start Over</button>
                <button type="button" id="manual-back-btn" class="btn btn-outline-primary">Back</button>
                <button type="button" id="manual-next-btn" class="btn btn-success">Next</button>
                <button type="submit" form="manual-load-form" id="manual-submit-btn" class="btn btn-success hidden d-none">Create Load</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="settingsHubModal" tabindex="-1" aria-labelledby="settingsHubModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header console-hub-modal-head">
                <h5 class="modal-title" id="settingsHubModalLabel">Console System Hub</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="console-hub-grid d-flex flex-column gap-2 mb-4">
                    <a class="btn btn-outline-light text-start" href="dashboard.php">Dispatcher Dashboard</a>
                    <a class="btn btn-outline-light text-start" href="admin-settings.php">Admin Settings Panel</a>
                    <a class="btn btn-outline-light text-start" href="/track/v1/admin/fmcsa-search.php">FMCSA Search Proxy</a>
                    <a class="btn btn-outline-light text-start" href="/track/v1/admin/carrier-drivers.php">Carrier Drivers Dropdown</a>
                    <a class="btn btn-outline-light text-start" href="/track/v1/admin/create-load.php">Manual Load Submission</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/dashboard-engine.js"></script>
</body>
</html>
