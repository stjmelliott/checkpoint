<?php
require_once __DIR__ . '/../../config/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exspeedite Checkpoint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: system-ui, -apple-system, sans-serif; }
        .container { max-width: 540px; margin-top: 40px; }
        .btn-checkin { font-size: 1.4rem; padding: 22px 20px; font-weight: 700; background:#2E7D32;border-color:#2E7D32; }
        .btn-retry{background:#E65100!important;border-color:#E65100!important;color:#fff!important;}
        .success-card { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center mb-4">
            <h4 class="text-primary">Exspeedite Checkpoint</h4>
        </div>

        <!-- Load Info Card -->
        <div id="loadInfo" class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="text-center text-muted">Loading load details...</div>
            </div>
        </div>

        <!-- Action Button -->
        <button id="checkInBtn" class="btn btn-success btn-checkin w-100 mb-3">Loading...</button>

        <!-- Status Area -->
        <div id="status" class="text-center"></div>

        <!-- Success Screen -->
        <div id="successScreen" class="success-card text-center mt-4">
            <div class="display-1 mb-3">✅</div>
            <h3 class="text-success">You're all set!</h3>
            <p class="text-muted">You can close this page.</p>
        </div>
    </div>

    <script>
        const params = new URLSearchParams(window.location.search);
        const token = params.get('token');
        const milestone = params.get('milestone');
        const stopSeq = parseInt(params.get('stop'), 10);

        const validMilestones = ['pickup', 'transit', 'delivery'];

        if (!token || !validMilestones.includes(milestone) || isNaN(stopSeq) || stopSeq < 1) {
            showExpiredMessage("This link is not valid. Please contact your dispatcher.");
        } else {
            initPage(token, milestone, stopSeq);
        }

        function initPage(token, milestone, stopSeq) {
            // Button labels per spec
            const labels = {
                pickup:   "I've Arrived at the Shipper ✓",
                transit:  "I've Arrived at My Stop ✓",
                delivery: "Delivery Complete ✓"
            };

            document.getElementById('checkInBtn').textContent = labels[milestone];

            // Load driver-safe info
            fetch(`/v1/tracking/load-info.php?token=${token}&milestone=${milestone}&stop=${stopSeq}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('loadInfo').innerHTML = `
                            <div class="card-body">
                                <h5 class="card-title">${data.carrier_name || 'Carrier'}</h5>
                                <p class="mb-1">${data.location_name || ''}, ${data.city || ''} ${data.state || ''}</p>
                                <strong>${data.milestone_label || milestone.toUpperCase()} — Stop ${data.stop_number} of ${data.total_stops}</strong>
                            </div>`;
                    }
                })
                .catch(() => {});

            // Button action
            document.getElementById('checkInBtn').onclick = () => captureMilestoneLocation(token, milestone, stopSeq);
        }

        function captureMilestoneLocation(token, milestone, stopSeq) {
            const btn = document.getElementById('checkInBtn');
            btn.disabled = true;
            btn.textContent = 'Getting your location...';

            navigator.geolocation.getCurrentPosition(
                pos => sendPingToServer(pos, token, milestone, stopSeq),
                err => handleGeoError(err, token, milestone, stopSeq),
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
            );
        }

        function sendPingToServer(pos, token, milestone, stopSeq) {
            fetch('/v1/tracking/ping.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    token: token,
                    milestone_type: milestone,
                    stop_sequence: stopSeq,
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    accuracy: pos.coords.accuracy
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    renderSuccessUI();
                } else {
                    showMessage(data.message || "Something went wrong.");
                }
            })
            .catch(() => {
                showMessage("Location saved but couldn't send. Tap Retry to upload.");
                setButtonState('retry', token, milestone, stopSeq);
            });
        }

        function handleGeoError(err, token, milestone, stopSeq) {
            if (err.code === err.PERMISSION_DENIED) {
                showExpiredMessage("Please allow location access in your browser settings, then reload this page.");
                return;
            }
            if (err.code === err.TIMEOUT) {
                navigator.geolocation.getCurrentPosition(
                    pos => sendPingToServer(pos, token, milestone, stopSeq),
                    () => { showMessage("Having trouble finding your location. Tap Retry or step outside."); setButtonState('retry', token, milestone, stopSeq); },
                    { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
                );
                return;
            }
            showMessage("Can't get your location right now. Tap Retry to try again.");
            setButtonState('retry', token, milestone, stopSeq);
        }

        function renderSuccessUI() {
            document.getElementById('checkInBtn').style.display = 'none';
            document.getElementById('successScreen').style.display = 'block';
        }

        function showMessage(msg) {
            document.getElementById('status').innerHTML = `<div class="alert alert-info">${msg}</div>`;
        }

        function showExpiredMessage(msg) {
            document.getElementById('status').innerHTML = `<div class="alert alert-warning">${msg}</div>`;
            document.getElementById('checkInBtn').style.display = 'none';
        }

        function setButtonState(state, token, milestone, stopSeq) {
            const btn = document.getElementById('checkInBtn');
            if (state === 'retry') {
                btn.textContent = 'Try Again';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-retry');
                btn.onclick = () => captureMilestoneLocation(token, milestone, stopSeq);
            }
            btn.disabled = false;
        }
    </script>
</body>
</html>
