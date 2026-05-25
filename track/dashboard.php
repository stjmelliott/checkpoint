<?php require_once '../config/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exspeedite CheckPoint • Live Map</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/dashboard.css?v=20260522c">
    <style>
        /* Compact Sidebar - Section 6.6 */
        .load-card {
            padding: 6px 8px 6px 12px;
            border-left: 4px solid var(--status-color, #666);
            background: #1a2a1a;
            border-bottom: 1px solid #2a3a2a;
            cursor: pointer;
            margin-bottom: 4px;
        }
        .load-card:hover { background: #223322; }
        .card-line1 { display: flex; align-items: center; gap: 6px; }
        .card-dot { width:8px; height:8px; border-radius:50%; background: var(--status-color); flex-shrink: 0; }
        .card-load { font-size:13px; font-weight:600; color:#e8e8e8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .card-carrier { font-size:12px; color:#8BC34A; flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .card-trail-btn { background:none; border:none; color:#4CAF50; font-size:14px; cursor:pointer; padding:0; flex-shrink:0; }
        .card-line2 { font-size:11px; color:#aaaaaa; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .status-green { --status-color: #00C853; }
        .status-amber { --status-color: #FF6D00; }
        .status-red   { --status-color: #D50000; }
        .status-gray  { --status-color: #666666; }
    </style>
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
<script>
let map = L.map('map').setView([43.0, -93.5], 7);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

let markers = {};
let currentTrailLayer = null;

async function loadDashboardData(status = 'active') {
    const res = await fetch(`/v1/dashboard/data.php?status=${status}`);
    const data = await res.json();
    if (!data.success) return;

    Object.values(markers).forEach(m => map.removeLayer(m));
    markers = {};
    document.getElementById('load-list').innerHTML = '';

    data.loads.forEach(load => {
        const sc = getStatusClass(load.last_updated_at, load.checkin_status || (load.ping_count > 0 ? 'checked_in' : 'never'));
        const stopLabel = load.total_stops ? `Stop ${load.stop_sequence} of ${load.total_stops}` : `Stop ${load.stop_sequence}`;
        const timeLabel = load.last_updated_at 
            ? new Intl.DateTimeFormat([], {hour:'numeric', minute:'2-digit'}).format(new Date(load.last_updated_at))
            : 'Never';

        const card = document.createElement('div');
        card.className = `load-card ${sc}`;
        card.innerHTML = `
            <div class="card-line1">
                <span class="card-dot"></span>
                <span class="card-load">${load.load_number}</span>
                <span class="card-carrier">${load.carrier_name || 'Unknown Carrier'}</span>
                <button class="card-trail-btn" onclick="event.stopImmediatePropagation(); showTrail('${load.load_number}')">↗</button>
            </div>
            <div class="card-line2">${load.driver_name || 'No driver'} • ${stopLabel} • ${timeLabel}</div>
        `;
        card.onclick = () => selectLoad(load);
        document.getElementById('load-list').appendChild(card);

        if (load.latest_lat && load.latest_lng) {
            const icon = createMarkerIcon(load.latest_milestone || 'transit');
            const marker = L.marker([parseFloat(load.latest_lat), parseFloat(load.latest_lng)], { icon })
                .addTo(map)
                .bindPopup(`<strong>${load.load_number}</strong><br>${load.carrier_name || ''}`);
            markers[load.load_number] = marker;
        }
    });
}

function getStatusClass(lastUpdatedAt, checkinStatus) {
    if (checkinStatus === 'completed' || checkinStatus === 'cancelled') return 'status-gray';
    if (!lastUpdatedAt) return 'status-red';           // Never checked in = red
    const mins = (Date.now() - new Date(lastUpdatedAt).getTime()) / 60000;
    if (mins < 120) return 'status-green';            // < 2 hours = green
    if (mins < 240) return 'status-amber';            // 2-4 hours = amber
    return 'status-red';                              // > 4 hours = red (late)
}

function createMarkerIcon(milestone) {
    let html = (milestone === 'pickup') ? '🚚' : (milestone === 'transit') ? '➡️' : '📍';
    return L.divIcon({
        html: `<div style="font-size:20px;">${html}</div>`,
        className: '',
        iconSize: [26, 26],
        iconAnchor: [13, 13]
    });
}

function selectLoad(load) {
    if (load.latest_lat && load.latest_lng) {
        map.setView([parseFloat(load.latest_lat), parseFloat(load.latest_lng)], 10);
        if (markers[load.load_number]) markers[load.load_number].openPopup();
    }
}

async function showTrail(loadNumber) {
    if (currentTrailLayer) {
        map.removeLayer(currentTrailLayer);
        currentTrailLayer = null;
    }

    const res = await fetch(`/v1/dashboard/data.php?load_number=${loadNumber}`);
    const data = await res.json();

    if (!data.success || !data.pings || data.pings.length === 0) {
        alert("No ping history available for this load yet.");
        return;
    }

    const trailGroup = L.featureGroup();
    const latlngs = data.pings.map(p => [parseFloat(p.lat), parseFloat(p.lng)]);

    const polyline = L.polyline(latlngs, {
        color: '#22c55e',
        weight: 3,
        opacity: 0.85,
        dashArray: '8, 5'
    });
    trailGroup.addLayer(polyline);

    data.pings.forEach((p, index) => {
        const circle = L.circleMarker([parseFloat(p.lat), parseFloat(p.lng)], {
            radius: 5,
            color: '#ffffff',
            weight: 1.5,
            fillColor: '#22c55e',
            fillOpacity: 1
        });
        circle.bindPopup(`Ping #${index + 1}<br>Time: ${p.timestamp}`);
        trailGroup.addLayer(circle);
    });

    for (let i = 0; i < latlngs.length - 1; i++) {
        const start = latlngs[i];
        const end = latlngs[i + 1];
        const angle = Math.atan2(end[0] - start[0], end[1] - start[1]) * 180 / Math.PI;
        const midLat = (start[0] + end[0]) / 2;
        const midLng = (start[1] + end[1]) / 2;

        const arrowIcon = L.divIcon({
            html: `<div style="transform: rotate(${angle}deg); font-size:14px; color:#22c55e;">▶</div>`,
            className: '',
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });

        const arrow = L.marker([midLat, midLng], { icon: arrowIcon });
        trailGroup.addLayer(arrow);
    }

    trailGroup.addTo(map);
    currentTrailLayer = trailGroup;
    map.fitBounds(trailGroup.getBounds(), { padding: [40, 40] });
}

function clearTrail() {
    if (currentTrailLayer) {
        map.removeLayer(currentTrailLayer);
        currentTrailLayer = null;
    }
}

function filterByStatus(status, button) {
    document.querySelectorAll('.status-tab').forEach(b => b.classList.remove('active'));
    button.classList.add('active');
    loadDashboardData(status);
}

// Boot
loadDashboardData('active');
setInterval(() => loadDashboardData('active'), 15000);
</script>

</body>
</html>