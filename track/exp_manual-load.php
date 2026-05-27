<?php
require_once '../config/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exspeedite CheckPoint • EXP Manual Load Wizard</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <style>
        .exp-manual-screen {
            min-height: calc(100vh - 84px);
            display: flex;
            flex-direction: column;
        }

        .exp-manual-card {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .exp-manual-scroll {
            flex: 1 1 auto;
            overflow-y: auto;
        }
    </style>
</head>
<body>
<?php $checkpointHeaderCurrent = 'EXP Manual Load Wizard'; require_once __DIR__ . '/includes/header.php'; ?>

<div class="container py-4 exp-manual-screen" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0" style="color:#22c55e;">Standalone EXP Manual Load Wizard</h2>
        <a href="dashboard.php" class="btn btn-outline-light">Back to Live Map</a>
    </div>

    <div class="card bg-dark text-light border-success exp-manual-card">
        <div class="card-header manual-modal-head">
            <h5 class="mb-0">Create Load + Send Driver Text</h5>
        </div>
        <div class="card-body p-4 exp-manual-scroll">
            <form id="manual-load-form">
                <input type="hidden" name="carrier_id" id="carrier_id" value="0">
                <input type="hidden" name="driver_id" id="driver_id" value="0">

                <div class="row g-3 mb-4">
                    <div class="col-12 col-lg-7">
                        <label for="carrier_name" class="form-label">Carrier Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="carrier_name" id="carrier_name" value="Exspeedite Logistics" placeholder="Start typing legal or DBA carrier name">
                        <div id="carrier-results" class="carrier-results mt-2"></div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <label for="dot_number" class="form-label">DOT Number <span class="text-muted">(optional)</span></label>
                        <input class="form-control" type="text" name="dot_number" id="dot_number" value="1234567" placeholder="e.g. 1234567" inputmode="numeric">
                    </div>
                    <div class="col-12">
                        <button type="button" id="fmcsa-btn" class="btn btn-outline-primary w-100">🔎 Search FMCSA</button>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label for="driver_phone" class="form-label">Driver Phone <span class="text-danger">*</span></label>
                        <input class="form-control" type="tel" name="driver_phone" id="driver_phone" value="+17634446474" placeholder="+17634446474">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="driver_name" class="form-label">Driver Name</label>
                        <input class="form-control" type="text" name="driver_name" id="driver_name" value="John Doe" placeholder="John Doe">
                    </div>
                    <div class="col-12">
                        <label for="driver_email" class="form-label">Driver Email (optional)</label>
                        <input class="form-control" type="email" name="driver_email" id="driver_email" value="name@example.com" placeholder="name@example.com">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="load_number" class="form-label">Load Number <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="load_number" id="load_number" value="EXP-10001" placeholder="Internal or broker #">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Stops</label>
                        <div id="stops-wrap"></div>
                        <button type="button" id="add-stop-btn" class="btn btn-success btn-sm mt-2">+ Add Stop</button>
                    </div>
                </div>

                <div class="manual-error mt-3" id="manual-error"></div>
                <div id="manual-success" class="mt-3 text-success fw-semibold"></div>

                <div class="mt-4 d-flex gap-2">
                    <button type="button" id="clear-form-btn" class="btn btn-secondary">Clear Form</button>
                    <button type="submit" class="btn btn-success btn-lg px-4">🚚 Send Load</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/exp_manual-load.js"></script>
</body>
</html>
