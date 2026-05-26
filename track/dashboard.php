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

<div class="modal fade" id="addLoadModal" tabindex="-1" aria-labelledby="addLoadModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header manual-modal-head">
                <h5 class="modal-title" id="addLoadModalLabel">Manual Load Wizard</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="manual-load-form" class="modal-body">
                <div class="manual-tabs wizard-progress-bar">
                    <button class="manual-tab wizard-step-node active" data-panel="1" type="button">1. Carrier Registry</button>
                    <button class="manual-tab wizard-step-node" data-panel="2" type="button">2. Driver Allocation</button>
                    <button class="manual-tab wizard-step-node" data-panel="3" type="button">3. Route Mapping</button>
                </div>
                <div class="manual-panel" data-panel="1">
                    <input type="hidden" name="carrier_id" id="carrier_id" value="0">
                    <label>Carrier Name <input class="form-control" type="text" name="carrier_name" id="carrier_name"></label>
                    <div id="carrier-results" class="carrier-results"></div>
                    <div class="manual-inline-actions">
                        <button type="button" id="fmcsa-btn" class="btn btn-outline-primary">Search FMCSA Registry</button>
                        <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('carrier_id').value='0';">Use Manual Carrier Entry</button>
                    </div>
                    <label>DOT Number <input class="form-control" type="text" name="dot_number" id="dot_number"></label>
                </div>
                <div class="manual-panel hidden d-none" data-panel="2">
                    <input type="hidden" name="driver_id" id="driver_id" value="0">
                    <label>Driver Profile<select id="driver_select" class="form-select"><option value="0">+ New Driver</option></select></label>
                    <div id="new-driver-form" class="new-driver-form">
                        <label>Driver Name <input class="form-control" type="text" name="driver_name" id="driver_name"></label>
                        <label>Driver Phone <input class="form-control" type="text" name="driver_phone" id="driver_phone" placeholder="6124446655"></label>
                        <label>Driver Email <input class="form-control" type="email" name="driver_email" id="driver_email"></label>
                    </div>
                </div>
                <div class="manual-panel hidden d-none" data-panel="3">
                    <label>Load Number <input class="form-control" type="text" name="load_number" id="load_number"></label>
                    <div id="stops-wrap"></div>
                    <button type="button" id="add-stop-btn" class="btn btn-success">+ Add Stop</button>
                </div>
                <div class="manual-error" id="manual-error"></div>
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
<script>
let map = L.map('map').setView([43.0, -93.5], 7); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
let markers = {}, currentTrailLayer = null, activeStatus='active', manualPanel=1;
function normalizePhone(v){return String(v||'').replace(/\D/g,'').slice(0,10);} function err(m){document.getElementById('manual-error').textContent=m||'';}
async function loadDashboardData(status = activeStatus) { activeStatus=status; const res = await fetch(`/v1/dashboard/data.php?status=${status}`); const data = await res.json(); if (!data.success) return; Object.values(markers).forEach(m => map.removeLayer(m)); markers = {}; document.getElementById('load-list').innerHTML = ''; data.loads.forEach(load => { const sc = getStatusClass(load.last_updated_at, load.checkin_status || (load.ping_count > 0 ? 'checked_in' : 'never')); const stopLabel = load.total_stops ? `Stop ${load.stop_sequence} of ${load.total_stops}` : `Stop ${load.stop_sequence}`; const timeLabel = load.last_updated_at ? new Intl.DateTimeFormat([], {hour:'numeric', minute:'2-digit'}).format(new Date(load.last_updated_at)) : 'Never'; const card = document.createElement('div'); card.className = `load-card ${sc}`; card.innerHTML = `<div class="card-line1"><span class="card-dot"></span><span class="card-load">${load.load_number}</span><span class="card-carrier">${load.carrier_name || 'Unknown Carrier'}</span><button class="card-trail-btn" onclick="event.stopImmediatePropagation(); showTrail('${load.load_number}')">↗</button></div><div class="card-line2">${load.driver_name || 'No driver'} • ${stopLabel} • ${timeLabel}</div>`; card.onclick = () => selectLoad(load); document.getElementById('load-list').appendChild(card); if (load.latest_lat && load.latest_lng) { const marker = L.marker([parseFloat(load.latest_lat), parseFloat(load.latest_lng)], { icon: createMarkerIcon(load.latest_milestone || 'transit') }).addTo(map).bindPopup(`<strong>${load.load_number}</strong><br>${load.carrier_name || ''}`); markers[load.load_number] = marker; }}); }
function getStatusClass(lastUpdatedAt, checkinStatus){ if (checkinStatus==='completed'||checkinStatus==='cancelled') return 'status-gray'; if(!lastUpdatedAt) return 'status-red'; const mins=(Date.now()-new Date(lastUpdatedAt).getTime())/60000; if(mins<120) return 'status-green'; if(mins<240) return 'status-amber'; return 'status-red'; }
function createMarkerIcon(m){ let html=(m==='pickup')?'🚚':(m==='transit')?'➡️':'📍'; return L.divIcon({html:`<div style="font-size:20px;">${html}</div>`,className:'',iconSize:[26,26],iconAnchor:[13,13]}); }
function selectLoad(load){ if(load.latest_lat&&load.latest_lng){ map.setView([parseFloat(load.latest_lat), parseFloat(load.latest_lng)], 10); if(markers[load.load_number]) markers[load.load_number].openPopup(); } }
async function showTrail(loadNumber){ if(currentTrailLayer){map.removeLayer(currentTrailLayer); currentTrailLayer=null;} const res=await fetch(`/v1/dashboard/data.php?load_number=${loadNumber}`); const data=await res.json(); if(!data.success||!data.pings||data.pings.length===0){alert('No ping history available for this load yet.');return;} const tg=L.featureGroup(); const latlngs=data.pings.map(p=>[parseFloat(p.lat), parseFloat(p.lng)]); tg.addLayer(L.polyline(latlngs,{color:'#22c55e',weight:3,opacity:.85,dashArray:'8, 5'})); data.pings.forEach((p,i)=>{const c=L.circleMarker([parseFloat(p.lat),parseFloat(p.lng)],{radius:5,color:'#fff',weight:1.5,fillColor:'#22c55e',fillOpacity:1}); c.bindPopup(`Ping #${i+1}<br>Time: ${p.timestamp}`); tg.addLayer(c);}); tg.addTo(map); currentTrailLayer=tg; map.fitBounds(tg.getBounds(),{padding:[40,40]}); }
function clearTrail(){ if(currentTrailLayer){map.removeLayer(currentTrailLayer); currentTrailLayer=null;}}
function filterByStatus(status,button){document.querySelectorAll('.status-tab').forEach(b=>b.classList.remove('active')); button.classList.add('active'); loadDashboardData(status);}
window.loadDashboardData = loadDashboardData;
window.clearTrail = clearTrail;
window.filterByStatus = filterByStatus;

function addStopRow(stop={milestone:'transit',city:'',state:'',scheduled_at:''}){const w=document.getElementById('stops-wrap');const i=w.children.length;const r=document.createElement('div');r.className='stop-row';r.innerHTML=`<label>Milestone <select name='stop[${i}][milestone]'><option value='pickup'>pickup</option><option value='transit'>transit</option><option value='delivery'>delivery</option></select></label><label>City <input type='text' name='stop[${i}][city]' value='${stop.city||''}'></label><label>State <input type='text' name='stop[${i}][state]' value='${stop.state||''}'></label><label>Scheduled <input type='datetime-local' name='stop[${i}][scheduled_at]' value='${stop.scheduled_at||''}'></label><button type='button' class='remove-stop'>Remove</button>`;w.appendChild(r);r.querySelector('select').value=stop.milestone;r.querySelector('.remove-stop').onclick=()=>r.remove();}
function showPanel(n){manualPanel=n;document.querySelectorAll('.manual-panel').forEach(el=>el.classList.toggle('hidden',+el.dataset.panel!==n));document.querySelectorAll('.manual-tab').forEach(el=>el.classList.toggle('active',+el.dataset.panel===n));document.getElementById('manual-back-btn').classList.toggle('hidden',n===1);document.getElementById('manual-next-btn').classList.toggle('hidden',n===3);document.getElementById('manual-submit-btn').classList.toggle('hidden',n!==3);} 
function panelValid(p){if(p===1)return !!document.getElementById('carrier_name').value.trim();if(p===2)return normalizePhone(document.getElementById('driver_phone').value).length===10;if(p===3)return !!document.getElementById('load_number').value.trim();return true;}
function resetManualLoadForm(){const form=document.getElementById('manual-load-form');if(!form)return;form.reset();document.getElementById('carrier_id').value='0';document.getElementById('driver_id').value='0';const w=document.getElementById('stops-wrap');w.innerHTML='';addStopRow({milestone:'pickup'});addStopRow({milestone:'delivery'});document.getElementById('carrier-results').innerHTML='';document.getElementById('driver_select').innerHTML='<option value="0">+ New Driver</option>';document.getElementById('new-driver-form').style.display='block';err('');showPanel(1);} 
async function searchFmcsa(){const q=document.getElementById('carrier_name').value.trim();if(q.length<2){err('Enter at least 2 characters.');return;}const r=await fetch('/track/v1/admin/fmcsa-search.php?q='+encodeURIComponent(q));const data=await r.json();const box=document.getElementById('carrier-results');box.innerHTML='';(data||[]).forEach(item=>{const b=document.createElement('button');b.type='button';b.className='carrier-result';b.textContent=`${item.legal_name} (${item.dot_number})`;b.onclick=()=>{carrier_id.value='0';carrier_name.value=item.legal_name||'';dot_number.value=item.dot_number||'';box.innerHTML='';};box.appendChild(b);});}
async function loadDrivers(){const carrierId=+document.getElementById('carrier_id').value||0;if(carrierId<=0)return;const r=await fetch('/track/v1/admin/carrier-drivers.php?carrier_id='+carrierId);const data=await r.json();const sel=document.getElementById('driver_select');sel.innerHTML='<option value="0">+ New Driver</option>';(data||[]).forEach(d=>{const op=document.createElement('option');op.value=String(d.id);op.textContent=`${d.driver_name} (${d.driver_phone})`;op.dataset.name=d.driver_name||'';op.dataset.phone=d.driver_phone||'';op.dataset.email=d.driver_email||'';sel.appendChild(op);});}
loadDashboardData('active'); setInterval(() => loadDashboardData(activeStatus), 15000);
document.addEventListener('DOMContentLoaded',()=>{const modal=document.getElementById('addLoadModal'); if(!modal) return; showPanel(1); if(document.querySelectorAll('.stop-row').length===0){addStopRow({milestone:'pickup'});addStopRow({milestone:'delivery'});} document.getElementById('fmcsa-btn').onclick=searchFmcsa; document.getElementById('manual-next-btn').onclick=()=>{if(!panelValid(manualPanel)){err('Carrier name, load number, and a 10-digit driver phone are required.');return;} err(''); if(manualPanel===1)loadDrivers(); showPanel(Math.min(3,manualPanel+1));}; document.getElementById('manual-back-btn').onclick=()=>showPanel(Math.max(1,manualPanel-1)); document.getElementById('add-stop-btn').onclick=()=>addStopRow(); document.getElementById('modalClearFormBtn').addEventListener('click',resetManualLoadForm); modal.addEventListener('hidden.bs.modal',resetManualLoadForm); document.getElementById('driver_select').onchange=(e)=>{const isNew=e.target.value==='0';document.getElementById('driver_id').value=e.target.value;document.getElementById('new-driver-form').style.display=isNew?'block':'none';if(!isNew){const op=e.target.selectedOptions[0];driver_name.value=op.dataset.name||'';driver_phone.value=op.dataset.phone||'';driver_email.value=op.dataset.email||'';}}; document.getElementById('manual-load-form').addEventListener('submit',async e=>{e.preventDefault();if(!panelValid(1)||!panelValid(2)||!panelValid(3)){err('Carrier name, load number, and a 10-digit driver phone are required.');return;}document.querySelectorAll('.stop-row').forEach((row)=>{const c=row.querySelector("input[name*='[city]']");const s=row.querySelector("input[name*='[state]']");if(c&&!c.value.trim())c.value='Unknown City';if(s&&!s.value.trim())s.value='US';});driver_phone.value=normalizePhone(driver_phone.value);const fd=new FormData(e.currentTarget);const res=await fetch('/track/v1/admin/create-load.php',{method:'POST',body:fd});const data=await res.json().catch(()=>({}));if(!res.ok||data.success===false){err(data.message||'Failed to create load');return;}bootstrap.Modal.getOrCreateInstance(modal).hide();resetManualLoadForm();loadDashboardData(activeStatus);});});
</script>
</body>
</html>
