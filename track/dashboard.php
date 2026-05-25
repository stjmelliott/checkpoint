<?php require_once '../config/bootstrap.php'; ?>
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

<div class="header">
    <img src="/images/Exspeedite_logo.png" alt="Exspeedite Logo">
    <div class="logo-text">
        <span class="logo-check">CHECK</span><span class="logo-point">POINT</span>
    </div>
</div>

<div id="map"></div>

<div class="legend">
    <strong style="color:#22c55e; display:block; margin-bottom:8px;">Legend</strong>
    <div>🚚 Pickup &nbsp;&nbsp; ➡️ In Transit &nbsp;&nbsp; 📍 Delivery</div>
</div>

<div class="sidebar">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3 style="margin:0; color:#22c55e;">Loads</h3>
        <a href="help-dashboard-logic.php" target="_blank">
            <button style="background:#334155; padding:8px 16px;">Help</button>
        </a>
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

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/dashboard.js?v=20260525a"></script>

</body>
</html>