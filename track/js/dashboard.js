let map;
let markers = {};
let currentTrailLayer = null;
let activeFilter = 'active';
let dashboardLoads = [];
const locallyTransitioned = new Map();

function initMap() {
    map = L.map('map').setView([43.0, -93.5], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
}

function getEffectiveStatus(load) {
    return locallyTransitioned.get(load.load_number) || load.checkin_status || 'active';
}

function getStatusClass(lastUpdatedAt, effectiveStatus) {
    if (effectiveStatus === 'completed' || effectiveStatus === 'cancelled') return 'status-gray';
    if (!lastUpdatedAt) return 'status-red';
    const mins = (Date.now() - new Date(lastUpdatedAt).getTime()) / 60000;
    if (mins < 120) return 'status-green';
    if (mins < 240) return 'status-amber';
    return 'status-red';
}

function shouldRenderLoad(load) {
    const effectiveStatus = getEffectiveStatus(load);
    if (activeFilter === 'all') return true;
    if (activeFilter === 'completed' || activeFilter === 'cancelled') return effectiveStatus === activeFilter;
    return effectiveStatus !== 'completed' && effectiveStatus !== 'cancelled';
}

function formatTime(ts) {
    if (!ts) return 'Never';
    return new Intl.DateTimeFormat([], { hour: 'numeric', minute: '2-digit' }).format(new Date(ts));
}

function gpsAccuracyQuality(val) {
    const acc = Number(val);
    if (!Number.isFinite(acc)) return 'Unknown';
    if (acc <= 25) return 'Good';
    if (acc <= 75) return 'Fair';
    return 'Poor';
}

function escapeHtml(v) {
    return String(v ?? '').replace(/[&<>'"]/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[s]));
}

function createMarkerIcon(statusClass) {
    const color = statusClass === 'status-green' ? '#00C853' : statusClass === 'status-amber' ? '#FF6D00' : statusClass === 'status-gray' ? '#666666' : '#D50000';
    return L.divIcon({
        className: 'load-pin-wrap',
        html: `<svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="5" fill="${color}" stroke="#0b130b" stroke-width="2"/></svg>`,
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    });
}

function buildPopupHtml(load, statusClass) {
    const stopText = load.total_stops ? `Stop ${load.stop_sequence || 1} of ${load.total_stops}` : `Stop ${load.stop_sequence || 1}`;
    const milestone = load.latest_milestone || 'Transit';
    const accuracy = gpsAccuracyQuality(load.gps_accuracy || load.accuracy_meters);
    const address = load.street_address || load.address || 'Address unavailable';
    return `
        <div class="popup-shell ${statusClass}" data-load-number="${escapeHtml(load.load_number)}">
            <div class="popup-title">${escapeHtml(load.load_number)}</div>
            <div class="popup-row"><strong>Carrier:</strong> ${escapeHtml(load.carrier_name || 'Unknown Carrier')}</div>
            <div class="popup-row"><strong>Driver:</strong> ${escapeHtml(load.driver_name || 'No driver')}</div>
            <div class="popup-row"><strong>Status:</strong> ${escapeHtml(milestone)} — ${escapeHtml(stopText)}</div>
            <div class="popup-row"><strong>GPS:</strong> ${escapeHtml(accuracy)}</div>
            <div class="popup-row"><strong>Address:</strong> ${escapeHtml(address)}</div>
            <div class="popup-actions">
                <button class="popup-action" data-action="remind" data-load="${escapeHtml(load.load_number)}">Send Reminder</button>
                <button class="popup-action" data-action="close" data-load="${escapeHtml(load.load_number)}">Close Load</button>
                <button class="popup-action" data-action="cancel" data-load="${escapeHtml(load.load_number)}">Cancel Load</button>
                <button class="popup-action" data-action="trail" data-load="${escapeHtml(load.load_number)}">Trail / History</button>
            </div>
            <div class="popup-feedback" id="popup-feedback-${escapeHtml(load.load_number)}"></div>
        </div>`;
}

function renderDashboard() {
    Object.values(markers).forEach(m => map.removeLayer(m));
    markers = {};
    const list = document.getElementById('load-list');
    list.innerHTML = '';

    dashboardLoads.filter(shouldRenderLoad).forEach(load => {
        const effectiveStatus = getEffectiveStatus(load);
        const statusClass = getStatusClass(load.last_updated_at, effectiveStatus);
        const stopText = load.total_stops ? `Stop ${load.stop_sequence || 1} of ${load.total_stops}` : `Stop ${load.stop_sequence || 1}`;
        const line2 = `${load.driver_name || 'No driver'} • ${load.latest_milestone || 'Transit'} • ${stopText} • ${formatTime(load.last_updated_at)}`;

        const card = document.createElement('div');
        card.className = `load-card ${statusClass}`;
        card.innerHTML = `<div class="card-line1"><span class="card-dot"></span><span class="card-load">${escapeHtml(load.load_number)}</span><span class="card-carrier">${escapeHtml(load.carrier_name || 'Unknown Carrier')}</span><button class="card-trail-btn" aria-label="Show trail" title="Show trail">↗</button></div><div class="card-line2">${escapeHtml(line2)}</div>`;
        card.querySelector('.card-trail-btn').addEventListener('click', (event) => {
            event.stopPropagation();
            showTrail(load.load_number);
        });
        card.addEventListener('click', () => selectLoad(load));
        list.appendChild(card);

        if (load.latest_lat && load.latest_lng) {
            const marker = L.marker([parseFloat(load.latest_lat), parseFloat(load.latest_lng)], { icon: createMarkerIcon(statusClass) })
                .addTo(map)
                .bindPopup(buildPopupHtml(load, statusClass));
            markers[load.load_number] = marker;
        }
    });
}

async function loadDashboardData() {
    const res = await fetch('/v1/admin/map-data.php');
    const data = await res.json();
    if (!data.success) return;
    dashboardLoads = data.loads || [];
    renderDashboard();
}

function selectLoad(load) {
    if (load.latest_lat && load.latest_lng) {
        map.setView([parseFloat(load.latest_lat), parseFloat(load.latest_lng)], 10);
        markers[load.load_number]?.openPopup();
    }
}

async function postLoadAction(url, loadNumber) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ load_number: loadNumber })
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok || payload.success === false) throw new Error(payload.message || 'Request failed');
    return payload;
}

async function handlePopupAction(action, loadNumber, feedbackEl) {
    try {
        if (action === 'trail') {
            await showTrail(loadNumber);
            return;
        }
        if (action === 'remind') {
            await postLoadAction('/v1/admin/request-manual-ping.php', loadNumber);
            feedbackEl.textContent = 'Reminder sent.';
            return;
        }
        if (action === 'close' || action === 'cancel') {
            const endpoint = action === 'close' ? '/v1/admin/close-load.php' : '/v1/admin/cancel-load.php';
            await postLoadAction(endpoint, loadNumber);
            locallyTransitioned.set(loadNumber, action === 'close' ? 'completed' : 'cancelled');
            map.closePopup();
            if (markers[loadNumber]) {
                map.removeLayer(markers[loadNumber]);
                delete markers[loadNumber];
            }
            renderDashboard();
        }
    } catch (err) {
        feedbackEl.textContent = err.message;
    }
}

document.addEventListener('click', (event) => {
    const btn = event.target.closest('.popup-action');
    if (!btn) return;
    event.preventDefault();
    event.stopPropagation();
    const loadNumber = btn.dataset.load;
    const action = btn.dataset.action;
    const feedbackEl = document.getElementById(`popup-feedback-${CSS.escape(loadNumber)}`);
    if (feedbackEl) feedbackEl.textContent = 'Working...';
    handlePopupAction(action, loadNumber, feedbackEl || { textContent: '' });
});

async function showTrail(loadNumber) {
    if (currentTrailLayer) {
        map.removeLayer(currentTrailLayer);
        currentTrailLayer = null;
    }
    const res = await fetch(`/v1/dashboard/data.php?load_number=${encodeURIComponent(loadNumber)}`);
    const data = await res.json();
    if (!data.success || !data.pings || data.pings.length === 0) return;
    const trailGroup = L.featureGroup();
    const latlngs = data.pings.map(p => [parseFloat(p.lat), parseFloat(p.lng)]);
    trailGroup.addLayer(L.polyline(latlngs, { color: '#22c55e', weight: 3, dashArray: '8, 5' }));
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
    activeFilter = status;
    document.querySelectorAll('.status-tab').forEach(b => b.classList.remove('active'));
    button.classList.add('active');
    renderDashboard();
}

document.addEventListener('DOMContentLoaded', () => {
    initMap();
    loadDashboardData();
    setInterval(loadDashboardData, 8000);
});
