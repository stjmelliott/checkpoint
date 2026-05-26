<?php
if (!isset($checkpointHeaderLinks)) {
    $checkpointHeaderLinks = [
        ['label' => 'Home', 'href' => 'index.php'],
        ['label' => 'Login', 'href' => 'login.php'],
        ['label' => 'Live Map', 'href' => 'dashboard.php'],
        ['label' => 'Test Dashboard', 'href' => 'test-dashboard.php'],
    ];
}
if (!isset($checkpointHeaderCurrent)) $checkpointHeaderCurrent = '';
?>
<header class="checkpoint-header checkpoint-header-shell">
  <div class="checkpoint-header-inner">
    <div class="checkpoint-brand-wrap">
      <img src="images/Exspeedite_logo.png" alt="Exspeedite Logo" class="checkpoint-logo" loading="lazy">
      <div class="checkpoint-brand-text"><span class="logo-check">CHECK</span><span class="logo-point">POINT</span><small class="checkpoint-tagline">Dispatch Intelligence Console</small></div>
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
<style>
.checkpoint-header{position:sticky;top:0;z-index:999;overflow:hidden}
.checkpoint-header::after{content:"";position:absolute;top:-40%;left:-35%;width:20%;height:180%;transform:skewX(-24deg);background:linear-gradient(90deg,rgba(255,255,255,0),rgba(255,255,255,.72),rgba(255,255,255,0));animation:checkpointSweep 6s linear infinite;pointer-events:none}
@keyframes checkpointSweep{from{left:-40%}to{left:140%}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const btn=document.getElementById('gitPullBridgeBtn');
  if(btn){btn.addEventListener('click', async function(){try{const res=await fetch('v1/admin/git-pull-bridge.php');const data=await res.json();alert((data.output||'No output')+'\n\nSuccess: '+ (!!data.success));}catch(e){alert('Sync failed: '+e.message);}});} 
});
</script>
