function normalizePhone(v){
  const raw = String(v||'').trim();
  if (raw.startsWith('+')) return '+' + raw.slice(1).replace(/\D/g,'');
  return '+' + raw.replace(/\D/g,'');
}
function err(msg){document.getElementById('manual-error').textContent=msg||'';}
function success(msg){document.getElementById('manual-success').textContent=msg||'';}
function escapeHtml(v){return String(v??'').replace(/[&<>'"]/g,s=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[s]));}

const DEFAULT_FORM = {
  carrier_name: 'Exspeedite Logistics',
  dot_number: '1234567',
  driver_phone: '+17634446474',
  driver_name: 'John Doe',
  driver_email: 'selliott@strongtco.com',
  load_number: 'EXP-10001'
};

const DEFAULT_STOPS = [
  {address:'UNKNOWN - Freight Transportation Pickup', city:'Unknown', state:'TX', zip:'00000', milestone:'pickup', scheduled_at:''},
  {address:'Known Good - Freight Transportation Delivery', city:'Dallas', state:'TX', zip:'75201', milestone:'delivery', scheduled_at:''}
];

function addStopRow(stop={address:'', city:'', state:'', zip:'', milestone:'transit', scheduled_at:''}) {
  const wrap = document.getElementById('stops-wrap');
  const i = wrap.querySelectorAll('.stop-row').length;
  const r = document.createElement('div');
  r.className = 'stop-row row g-2 align-items-end border p-2 mb-2';
  r.innerHTML = `<div class="col-12"><label class="form-label">Street Address</label><input type="text" name="stop[${i}][address]" class="form-control" value="${escapeHtml(stop.address)}"></div>
  <div class="col-6"><label class="form-label">City</label><input type="text" name="stop[${i}][city]" class="form-control" value="${escapeHtml(stop.city)}"></div>
  <div class="col-3"><label class="form-label">State</label><input type="text" name="stop[${i}][state]" class="form-control" value="${escapeHtml(stop.state)}"></div>
  <div class="col-3"><label class="form-label">ZIP</label><input type="text" name="stop[${i}][zip]" class="form-control" value="${escapeHtml(stop.zip)}"></div>
  <div class="col-6"><label class="form-label">Milestone</label><select name="stop[${i}][milestone]" class="form-select"><option value="pickup">Pickup</option><option value="transit">In Transit</option><option value="delivery">Delivery</option></select></div>
  <div class="col-6"><label class="form-label">Scheduled</label><input type="datetime-local" name="stop[${i}][scheduled_at]" class="form-control" value="${escapeHtml(stop.scheduled_at)}"></div>
  <button type="button" class="btn btn-sm btn-outline-danger remove-stop col-12 mt-1">Remove Stop</button>`;
  wrap.appendChild(r);
  r.querySelector('select').value = stop.milestone || 'transit';
  r.querySelector('.remove-stop').addEventListener('click',()=>r.remove());
}

function resetForm(){
  document.getElementById('manual-load-form').reset();
  document.getElementById('carrier_id').value='0';
  document.getElementById('driver_id').value='0';
  document.getElementById('carrier-results').innerHTML='';
  document.getElementById('stops-wrap').innerHTML='';
  Object.entries(DEFAULT_FORM).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) el.value = value;
  });
  DEFAULT_STOPS.forEach(addStopRow);
  err(''); success('');
}

async function searchFmcsa(){
  const q=document.getElementById('carrier_name').value.trim();
  if(q.length<2){err('Enter at least 2 characters for FMCSA search.');return;}
  const r=await fetch('v1/admin/fmcsa-search.php?q='+encodeURIComponent(q));
  const data=await r.json();
  const box=document.getElementById('carrier-results');box.innerHTML='';
  (data||[]).forEach(item=>{const row=document.createElement('button');row.type='button';row.className='carrier-result';row.textContent=`${item.legal_name} (${item.dot_number}) ${item.city}, ${item.state}`;row.onclick=()=>{document.getElementById('carrier_name').value=item.legal_name||'';document.getElementById('dot_number').value=item.dot_number||'';box.innerHTML='';};box.appendChild(row);});
}

document.addEventListener('DOMContentLoaded',()=>{
  document.getElementById('fmcsa-btn').addEventListener('click', searchFmcsa);
  document.getElementById('add-stop-btn').addEventListener('click',()=>addStopRow());
  document.getElementById('clear-form-btn').addEventListener('click', resetForm);
  resetForm();

  document.getElementById('manual-load-form').addEventListener('submit', async (e)=>{
    e.preventDefault();
    err(''); success('');
    const carrier = document.getElementById('carrier_name').value.trim();
    const load = document.getElementById('load_number').value.trim();
    const phoneEl = document.getElementById('driver_phone');
    phoneEl.value = normalizePhone(phoneEl.value);
    if(!carrier || !load || !phoneEl.value || phoneEl.value.length < 11){
      err('Carrier, load number, and valid driver phone are required.');
      return;
    }

    const fd = new FormData(e.currentTarget);
    const res = await fetch('v1/admin/create-load.php',{method:'POST', body: fd});
    const data = await res.json().catch(()=>({}));
    if(!res.ok || data.success===false){ err(data.message||'Failed to create load'); return; }
    success(data.message || 'Load created and text sent.');
    resetForm();
  });
});
