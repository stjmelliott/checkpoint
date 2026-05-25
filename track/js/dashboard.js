let map;
let markers = {};
let currentTrailLayer = null;

function initMap() {
    map = L.map('map').setView([43.0, -93.5], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
}

async function loadDashboardData(status = 'active') {
    const res = await fetch(`/v1/dashboard/data.php?status=${status}`);
    const data = await res.json();
    if (!data.success) return;

    Object.values(markers).forEach(m => map.removeLayer(m));
    markers = {};
    document.getElementById('load-list').innerHTML = '';

    data.loads.forEach(load => {
        const card = document.createElement('div');
        card.className = 'load-card';
        card.innerHTML = `
            <div><strong>${load.load_number}</strong></div>
            <div style="font-size:0.9rem; color:#94a3b8;">${load.driver_name || 'No driver'}</div>
            <div style="margin-top:6px;">
                <span class="status ${load.ping_count > 0 ? 'active' : 'never'}">
                    ${load.ping_count > 0 ? 'Checked In' : 'Never Checked In'}
                </span>
            </div>
            <div style="margin-top:8px;">
                <button onclick="event.stopImmediatePropagation(); showTrail('${load.load_number}')">Show Trail</button>
            </div>
        `;
        card.onclick = () => selectLoad(load);
        document.getElementById('load-list').appendChild(card);

        if (load.latest_lat && load.latest_lng) {
            const icon = createMarkerIcon(load.latest_milestone || 'transit');
            const marker = L.marker([parseFloat(load.latest_lat), parseFloat(load.latest_lng)], { icon })
                .addTo(map)
                .bindPopup(`<strong>${load.load_number}</strong><br>${load.driver_name || ''}`);
            markers[load.load_number] = marker;
        }
    });
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
        dashArray: '8, 5'
    });
    trailGroup.addLayer(polyline);

    data.pings.forEach((p, index) => {
        const circle = L.circleMarker([parseFloat(p.lat), parseFloat(p.lng)], {
            radius: 5,
            color: '#ffffff',
            fillColor: '#22c55e',
            fillOpacity: 1
        });
        trailGroup.addLayer(circle);
    });

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

// Boot everything when page loads
document.addEventListener('DOMContentLoaded', () => {
    initMap();
    loadDashboardData('active');
    setInterval(() => loadDashboardData('active'), 15000);
});