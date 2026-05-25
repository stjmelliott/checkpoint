<?php
if (!isset($checkpointHeaderLinks)) {
    $checkpointHeaderLinks = [
        ['label' => 'Home', 'href' => 'index.php'],
        ['label' => 'Login', 'href' => 'login.php'],
        ['label' => 'Live Map', 'href' => 'dashboard.php'],
        ['label' => 'Test Dashboard', 'href' => 'test-dashboard.php'],
    ];
}

if (!isset($checkpointHeaderCurrent)) {
    $checkpointHeaderCurrent = '';
}
?>
<header class="checkpoint-header-shell">
    <div class="checkpoint-header-inner">
        <div class="checkpoint-brand-wrap">
            <img src="images/Exspeedite_logo.png" alt="Exspeedite Logo" class="checkpoint-logo" loading="lazy">
            <div class="checkpoint-brand-text">
                <span class="logo-check">CHECK</span><span class="logo-point">POINT</span>
                <small class="checkpoint-tagline">Dispatch Intelligence Console</small>
            </div>
        </div>

        <nav class="checkpoint-nav" aria-label="Primary">
            <?php foreach ($checkpointHeaderLinks as $link):
                $isActive = ($checkpointHeaderCurrent === $link['label']); ?>
                <a href="<?= htmlspecialchars($link['href']) ?>" class="checkpoint-nav-link<?= $isActive ? ' is-active' : '' ?>">
                    <?= htmlspecialchars($link['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="checkpoint-admin-actions">
        <button class="btn btn-outline-warning btn-sm mx-2" id="gitPullBridgeBtn"><span class="glyphicon glyphicon-refresh"></span> Sync Git Live</button>
        <button class="btn btn-outline-info btn-sm mx-2" id="consoleHubBtn">Console System Hub</button>
        </div>
    </div>
</header>


<div id="consoleHubModal" class="manual-modal hidden" aria-hidden="true">
  <div class="manual-modal-card console-hub-modal">
    <div class="manual-modal-head"><h4>Console System Hub</h4><button id="consoleHubClose" class="manual-close-btn">×</button></div>
    <form id="consoleHubSettingsForm" class="console-hub-settings">
      <label>FMCSA API Key <input type="text" name="fmcsa_api_key" placeholder="Enter FMCSA key"></label>
      <button type="submit" class="btn btn-success btn-sm">Save Registry Key</button>
      <div id="consoleHubStatus" class="manual-error"></div>
    </form>
    <div class="console-hub-grid">
      <a href="dashboard.php">Dashboard Panel</a>
      <a href="admin-settings.php">Admin Settings Panel</a>
      <a href="v1/admin/fmcsa-search.php?q=ab" target="_blank">FMCSA Proxy Lookup</a>
      <a href="v1/admin/carrier-drivers.php?carrier_id=1" target="_blank">Carrier Drivers Dropdown List</a>
      <a href="v1/admin/create-load.php" target="_blank">Manual Load Submission Handler</a>
      <a href="v1/admin/git-pull-bridge.php" target="_blank">Git Deployment Synchronizer</a>
    </div>
  </div>
</div>

<style>
:root {
    --cp-bg-main: #071420;
    --cp-bg-sub: #10273a;
    --cp-neon: #22c55e;
    --cp-text: #e2e8f0;
    --cp-muted: #93a7ba;
}

.checkpoint-header-shell {
    position: sticky;
    top: 0;
    z-index: 999;
    overflow: hidden;
    background: linear-gradient(120deg, var(--cp-bg-main), var(--cp-bg-sub));
    border-bottom: 1px solid rgba(34, 197, 94, 0.5);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45), inset 0 -1px 0 rgba(34, 197, 94, 0.2);
}


.checkpoint-header-shell::after {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(105deg, transparent 35%, rgba(92, 255, 148, 0.38) 50%, transparent 65%);
    transform: translateX(-130%);
    animation: checkpointSweep 3.2s ease-in-out infinite;
}

@keyframes checkpointSweep {
    0% { transform: translateX(-130%); opacity: 0; }
    18% { opacity: 0.9; }
    50% { transform: translateX(0%); opacity: 0.8; }
    82% { opacity: 0.5; }
    100% { transform: translateX(130%); opacity: 0; }
}

.checkpoint-header-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 12px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
}

.checkpoint-brand-wrap { display: flex; align-items: center; gap: 12px; }
.checkpoint-logo { width: 42px; height: 42px; object-fit: contain; filter: drop-shadow(0 0 6px rgba(34, 197, 94, 0.4)); }
.logo-check, .logo-point { font-weight: 900; font-size: 30px; letter-spacing: 3px; line-height: 1; font-family: 'Orbitron', 'Segoe UI', sans-serif; }
.logo-check { color: #f8fafc; }
.logo-point { color: var(--cp-neon); }
.checkpoint-brand-text { display: flex; flex-direction: column; }
.checkpoint-tagline { color: var(--cp-muted); font-size: 11px; letter-spacing: 1.6px; text-transform: uppercase; margin-top: 2px; }

.checkpoint-nav {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.checkpoint-nav-link {
    text-decoration: none;
    color: var(--cp-text);
    background: rgba(15, 23, 42, 0.65);
    border: 1px solid rgba(34, 197, 94, 0.25);
    border-radius: 999px;
    padding: 8px 14px;
    font-size: 13px;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    transition: all 0.2s ease;
}

.checkpoint-nav-link:hover,
.checkpoint-nav-link.is-active {
    border-color: rgba(34, 197, 94, 0.95);
    background: rgba(34, 197, 94, 0.15);
    box-shadow: 0 0 14px rgba(34, 197, 94, 0.35);
    color: #ffffff;
}

@media (max-width: 820px) {
    .checkpoint-header-inner { justify-content: center; }
    .checkpoint-brand-wrap { width: 100%; justify-content: center; }
    .checkpoint-nav { justify-content: center; }
    .logo-check, .logo-point { font-size: 24px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var btn=document.getElementById('gitPullBridgeBtn');
  if(!btn) return;
  btn.addEventListener('click', async function(){
    if(!window.confirm('Sync live code from Git now?')) return;
    try{
      const res=await fetch('v1/admin/git-pull-bridge.php');
      const data=await res.json();
      alert((data.output||'No output') + '\n\nSuccess: ' + (!!data.success));
      window.location.reload(true);
    }catch(e){
      alert('Sync failed: '+e.message);
    }
  });

  const hubBtn=document.getElementById('consoleHubBtn');
  if(hubBtn){
    hubBtn.addEventListener('click',()=>{
      const modal=document.getElementById('consoleHubModal');
      if(modal){ modal.classList.remove('hidden'); }
    });
  }
  const hubClose=document.getElementById('consoleHubClose');
  if(hubClose){hubClose.addEventListener('click',()=>document.getElementById('consoleHubModal')?.classList.add('hidden'));}
  const saveForm=document.getElementById('consoleHubSettingsForm');
  if(saveForm){
    saveForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const fd=new FormData(saveForm);
      const status=document.getElementById('consoleHubStatus');
      try{
        const res=await fetch('v1/admin/save-settings.php',{method:'POST',body:fd});
        const data=await res.json();
        status.textContent=data.success ? 'FMCSA API key saved.' : (data.message||'Save failed');
      }catch(err){status.textContent='Save failed: '+err.message;}
    });
  }
});
</script>
