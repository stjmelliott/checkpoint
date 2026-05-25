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
<header class="checkpoint-header checkpoint-header-shell">
    <div class="checkpoint-header-inner">
        <div class="checkpoint-brand-wrap">
            <img src="images/Exspeedite_logo.png" alt="Exspeedite Logo" class="checkpoint-logo" loading="lazy">
            <div class="checkpoint-brand-text">
                <span class="logo-check">CHECK</span><span class="logo-point">POINT</span>
                <small class="checkpoint-tagline">Dispatch Intelligence Console</small>
            </div>
        </div>
        <nav class="checkpoint-nav" aria-label="Primary">
            <?php foreach ($checkpointHeaderLinks as $link): $isActive = ($checkpointHeaderCurrent === $link['label']); ?>
                <a href="<?= htmlspecialchars($link['href']) ?>" class="checkpoint-nav-link<?= $isActive ? ' is-active' : '' ?>"><?= htmlspecialchars($link['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="checkpoint-admin-actions">
            <button class="btn btn-outline-warning btn-sm" id="gitPullBridgeBtn" type="button">Sync Git Live</button>
            <button class="btn btn-outline-info btn-sm" id="consoleHubBtn" type="button" data-bs-toggle="modal" data-bs-target="#settingsHubModal">Console System Hub</button>
        </div>
    </div>
</header>

<div class="modal fade" id="settingsHubModal" tabindex="-1" aria-labelledby="settingsHubModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content console-hub-modal-card" role="dialog">
      <div class="modal-header console-hub-modal-head">
        <h5 class="modal-title" id="settingsHubModalLabel">Console System Hub</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="consoleHubSettingsForm" class="console-hub-settings mb-4">
          <label class="form-label" for="console_fmcsa_api_key">FMCSA API Key</label>
          <input id="console_fmcsa_api_key" class="form-control" type="text" name="fmcsa_api_key" placeholder="Enter FMCSA API key" autocomplete="off">
          <button type="submit" class="btn btn-success btn-sm mt-2">Save Registry Key</button>
          <div id="consoleHubStatus" class="manual-error mt-2"></div>
        </form>
        <div class="console-hub-grid">
          <a href="dashboard.php">Dispatcher Dashboard</a>
          <a href="admin-settings.php">Admin Settings Panel</a>
          <a href="v1/admin/fmcsa-search.php?q=ab" target="_blank" rel="noopener">FMCSA Search Proxy</a>
          <a href="v1/admin/carrier-drivers.php?carrier_id=1" target="_blank" rel="noopener">Carrier Drivers Dropdown</a>
          <a href="#addLoadModal" data-bs-toggle="modal" data-bs-target="#addLoadModal" data-bs-dismiss="modal">Manual Load Submission</a>
          <a href="v1/admin/git-pull-bridge.php" target="_blank" rel="noopener">Git Sync</a>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.checkpoint-header{position:sticky;top:0;z-index:999;overflow:hidden}
.checkpoint-header::after{content:"";position:absolute;top:-40%;left:-35%;width:20%;height:180%;transform:skewX(-24deg);background:linear-gradient(90deg,rgba(255,255,255,0),rgba(255,255,255,.72),rgba(255,255,255,0));animation:checkpointSweep 6s linear infinite;pointer-events:none}
@keyframes checkpointSweep{from{left:-40%}to{left:140%}}
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const btn=document.getElementById('gitPullBridgeBtn');
  if(btn){btn.addEventListener('click', async function(){
    try{const res=await fetch('v1/admin/git-pull-bridge.php');const data=await res.json();alert((data.output||'No output')+'\n\nSuccess: '+ (!!data.success));}
    catch(e){alert('Sync failed: '+e.message);} 
  });}

  const saveForm=document.getElementById('consoleHubSettingsForm');
  if(saveForm){saveForm.addEventListener('submit', async (e)=>{e.preventDefault();const fd=new FormData(saveForm);const status=document.getElementById('consoleHubStatus');try{const res=await fetch('v1/admin/save-settings.php',{method:'POST',body:fd});const data=await res.json();status.textContent=data.success?'FMCSA API key saved.':(data.message||'Save failed');}catch(err){status.textContent='Save failed: '+err.message;}});}
});
</script>
