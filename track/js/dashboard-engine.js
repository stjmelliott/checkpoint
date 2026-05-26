let map = L.map('map').setView([43.0, -93.5], 7); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
let markers = {}, currentTrailLayer = null, activeStatus='active', manualPanel=1;
function normalizePhone(v){return String(v||'').replace(/\D/g,'').slice(0,10);} function err(m){document.getElementById('manual-error').textContent=m||'';}
async function loadDashboardData(status = activeStatus) {
  activeStatus = status || activeStatus;
  const res = await fetch(`/v1/dashboard/data.php?status=${encodeURIComponent(activeStatus)}`);
  const data = await res.json();
  if (!data.success) return;

  Object.values(markers).forEach(m => map.removeLayer(m));
  markers = {};

  const loadList = document.getElementById('load-list');
  loadList.innerHTML = '';

  const loads = Array.isArray(data.loads) ? data.loads : [];
  if (loads.length === 0) {
    loadList.innerHTML = '<div class="load-empty">No loads found for this status</div>';
    return;
  }

  loads.forEach(load => {
    const sc = getStatusClass(load.last_updated_at, load.checkin_status || (load.ping_count > 0 ? 'checked_in' : 'never'));
    const stopLabel = load.total_stops ? `Stop ${load.stop_sequence} of ${load.total_stops}` : `Stop ${load.stop_sequence}`;
    const timeLabel = load.last_updated_at ? new Intl.DateTimeFormat([], {hour:'numeric', minute:'2-digit'}).format(new Date(load.last_updated_at)) : 'Never';
    const card = document.createElement('div');
    card.className = `load-card ${sc}`;
    card.innerHTML = `<div class="card-line1"><span class="card-dot"></span><span class="card-load">${load.load_number}</span><span class="card-carrier">${load.carrier_name || 'Unknown Carrier'}</span><button class="card-trail-btn" onclick="event.stopImmediatePropagation(); showTrail('${load.load_number}')">↗</button></div><div class="card-line2">${load.driver_name || 'No driver'} • ${stopLabel} • ${timeLabel}</div>`;
    card.onclick = () => selectLoad(load);
    loadList.appendChild(card);

    if (load.latest_lat && load.latest_lng) {
      const marker = L.marker([parseFloat(load.latest_lat), parseFloat(load.latest_lng)], { icon: createMarkerIcon(load.latest_milestone || 'transit') }).addTo(map).bindPopup(`<strong>${load.load_number}</strong><br>${load.carrier_name || ''}`);
      markers[load.load_number] = marker;
    }
  });
}
function getStatusClass(lastUpdatedAt, checkinStatus){ if (checkinStatus==='completed'||checkinStatus==='cancelled') return 'status-gray'; if(!lastUpdatedAt) return 'status-red'; const mins=(Date.now()-new Date(lastUpdatedAt).getTime())/60000; if(mins<120) return 'status-green'; if(mins<240) return 'status-amber'; return 'status-red'; }
function createMarkerIcon(m){ let html=(m==='pickup')?'🚚':(m==='transit')?'➡️':'📍'; return L.divIcon({html:`<div style="font-size:20px;">${html}</div>`,className:'',iconSize:[26,26],iconAnchor:[13,13]}); }
function selectLoad(load){ if(load.latest_lat&&load.latest_lng){ map.setView([parseFloat(load.latest_lat), parseFloat(load.latest_lng)], 10); if(markers[load.load_number]) markers[load.load_number].openPopup(); } }
async function showTrail(loadNumber){ if(currentTrailLayer){map.removeLayer(currentTrailLayer); currentTrailLayer=null;} const res=await fetch(`/v1/dashboard/data.php?load_number=${loadNumber}`); const data=await res.json(); if(!data.success||!data.pings||data.pings.length===0){alert('No ping history available for this load yet.');return;} const tg=L.featureGroup(); const latlngs=data.pings.map(p=>[parseFloat(p.lat), parseFloat(p.lng)]); tg.addLayer(L.polyline(latlngs,{color:'#22c55e',weight:3,opacity:.85,dashArray:'8, 5'})); data.pings.forEach((p,i)=>{const c=L.circleMarker([parseFloat(p.lat),parseFloat(p.lng)],{radius:5,color:'#fff',weight:1.5,fillColor:'#22c55e',fillOpacity:1}); c.bindPopup(`Ping #${i+1}<br>Time: ${p.timestamp}`); tg.addLayer(c);}); tg.addTo(map); currentTrailLayer=tg; map.fitBounds(tg.getBounds(),{padding:[40,40]}); }
function clearTrail(){ if(currentTrailLayer){map.removeLayer(currentTrailLayer); currentTrailLayer=null;}}
function filterByStatus(status,button){activeStatus=status;document.querySelectorAll('.status-tab').forEach(b=>b.classList.remove('active'));if(button) button.classList.add('active');loadDashboardData(activeStatus);}
window.loadDashboardData = loadDashboardData;
window.clearTrail = clearTrail;
window.filterByStatus = filterByStatus;

function addStopRow(stop={milestone:'transit',city:'',state:'',scheduled_at:''}){const w=document.getElementById('stops-wrap');const i=w.children.length;const r=document.createElement('div');r.className='stop-row';r.innerHTML=`<label>Milestone <select name='stop[${i}][milestone]'><option value='pickup'>pickup</option><option value='transit'>transit</option><option value='delivery'>delivery</option></select></label><label>City <input type='text' name='stop[${i}][city]' value='${stop.city||''}'></label><label>State <input type='text' name='stop[${i}][state]' value='${stop.state||''}'></label><label>Scheduled <input type='datetime-local' name='stop[${i}][scheduled_at]' value='${stop.scheduled_at||''}'></label><button type='button' class='remove-stop'>Remove</button>`;w.appendChild(r);r.querySelector('select').value=stop.milestone;r.querySelector('.remove-stop').onclick=()=>r.remove();}
function showPanel(n){manualPanel=n;document.querySelectorAll('.manual-panel').forEach(el=>el.classList.toggle('hidden',+el.dataset.panel!==n));document.querySelectorAll('.manual-tab').forEach(el=>el.classList.toggle('active',+el.dataset.panel===n));document.getElementById('manual-back-btn').classList.toggle('hidden',n===1);document.getElementById('manual-next-btn').classList.toggle('hidden',n===3);document.getElementById('manual-submit-btn').classList.toggle('hidden',n!==3);} 
function panelValid(p){if(p===1)return !!document.getElementById('carrier_name').value.trim();if(p===2)return normalizePhone(document.getElementById('driver_phone').value).length===10;if(p===3)return !!document.getElementById('load_number').value.trim();return true;}
function resetManualLoadForm(){const form=document.getElementById('manual-load-form');if(!form)return;form.reset();document.getElementById('carrier_id').value='0';document.getElementById('driver_id').value='0';const w=document.getElementById('stops-wrap');w.innerHTML='';addStopRow({milestone:'pickup'});addStopRow({milestone:'delivery'});document.getElementById('carrier-results').innerHTML='';document.getElementById('driver_select').innerHTML='<option value="0">+ New Driver</option>';document.getElementById('new-driver-form').style.display='block';err('');showPanel(1);} 
async function searchFmcsa(){const q=document.getElementById('carrier_name').value.trim();if(q.length<2){err('Enter at least 2 characters.');return;}const r=await fetch('/track/v1/admin/fmcsa-search.php?q='+encodeURIComponent(q));const data=await r.json();const box=document.getElementById('carrier-results');box.innerHTML='';(data||[]).forEach(item=>{const b=document.createElement('button');b.type='button';b.className='carrier-result';b.textContent=`${item.legal_name} (${item.dot_number})`;b.onclick=()=>{carrier_id.value='0';carrier_name.value=item.legal_name||'';dot_number.value=item.dot_number||'';box.innerHTML='';};box.appendChild(b);});}
async function loadDrivers(){const carrierId=+document.getElementById('carrier_id').value||0;if(carrierId<=0)return;const r=await fetch('/track/v1/admin/carrier-drivers.php?carrier_id='+carrierId);const data=await r.json();const sel=document.getElementById('driver_select');sel.innerHTML='<option value="0">+ New Driver</option>';(data||[]).forEach(d=>{const op=document.createElement('option');op.value=String(d.id);op.textContent=`${d.driver_name} (${d.driver_phone})`;op.dataset.name=d.driver_name||'';op.dataset.phone=d.driver_phone||'';op.dataset.email=d.driver_email||'';sel.appendChild(op);});}
document.addEventListener('DOMContentLoaded',()=>{
  loadDashboardData(activeStatus);
  setInterval(() => loadDashboardData(activeStatus), 15000);

  const modal=document.getElementById('addLoadModal'); if(!modal) return; showPanel(1); if(document.querySelectorAll('.stop-row').length===0){addStopRow({milestone:'pickup'});addStopRow({milestone:'delivery'});} document.getElementById('fmcsa-btn').onclick=searchFmcsa; document.getElementById('manual-next-btn').onclick=()=>{if(!panelValid(manualPanel)){err('Carrier name, load number, and a 10-digit driver phone are required.');return;} err(''); if(manualPanel===1)loadDrivers(); showPanel(Math.min(3,manualPanel+1));}; document.getElementById('manual-back-btn').onclick=()=>showPanel(Math.max(1,manualPanel-1)); document.getElementById('add-stop-btn').onclick=()=>addStopRow(); document.getElementById('modalClearFormBtn').addEventListener('click',resetManualLoadForm); modal.addEventListener('hidden.bs.modal',resetManualLoadForm); document.getElementById('driver_select').onchange=(e)=>{const isNew=e.target.value==='0';document.getElementById('driver_id').value=e.target.value;document.getElementById('new-driver-form').style.display=isNew?'block':'none';if(!isNew){const op=e.target.selectedOptions[0];driver_name.value=op.dataset.name||'';driver_phone.value=op.dataset.phone||'';driver_email.value=op.dataset.email||'';}}; document.getElementById('manual-load-form').addEventListener('submit',async e=>{e.preventDefault();if(!panelValid(1)||!panelValid(2)||!panelValid(3)){err('Carrier name, load number, and a 10-digit driver phone are required.');return;}document.querySelectorAll('.stop-row').forEach((row)=>{const c=row.querySelector("input[name*='[city]']");const s=row.querySelector("input[name*='[state]']");if(c&&!c.value.trim())c.value='Unknown City';if(s&&!s.value.trim())s.value='US';});driver_phone.value=normalizePhone(driver_phone.value);const fd=new FormData(e.currentTarget);const res=await fetch('/track/v1/admin/create-load.php',{method:'POST',body:fd});const data=await res.json().catch(()=>({}));if(!res.ok||data.success===false){err(data.message||'Failed to create load');return;}bootstrap.Modal.getOrCreateInstance(modal).hide();resetManualLoadForm();loadDashboardData(activeStatus);});});
