<header class="checkpoint-header">
    <div class="checkpoint-header-inner">
        <div class="checkpoint-brand-wrap">
            <img src="/images/Exspeedite_logo.png" alt="Exspeedite Logo" class="checkpoint-logo" loading="lazy">
            <div class="checkpoint-brand-text">
                <span class="logo-check" style="color:#ffffff;">CHECK</span><span class="logo-point" style="color:#00ffcc;">POINT</span>
                <small class="checkpoint-tagline" style="display:block; font-size:10px; color:#888;">Dispatch Intelligence Console</small>
            </div>
        </div>

        <nav class="checkpoint-nav" aria-label="Primary">
            <a href="/index.php" class="checkpoint-nav-link text-decoration-none">Home</a>
            <a href="/login.php" class="checkpoint-nav-link text-decoration-none">Login</a>
            <a href="/track/dashboard.php" class="checkpoint-nav-link text-decoration-none is-active">Live Map</a>
            <a href="/test-dashboard.php" class="checkpoint-nav-link text-decoration-none">Test Dashboard</a>
        </nav>

        <div class="checkpoint-admin-actions">
            <button class="btn btn-outline-warning btn-sm" id="gitPullBridgeBtn" type="button">Sync Git Live</button>
            <button class="btn btn-outline-info btn-sm" id="consoleHubBtn" type="button" data-bs-toggle="modal" data-bs-target="#settingsHubModal">Console System Hub</button>
        </div>
    </div>
</header>

<style>
/* Header Structural Enforcement */
.checkpoint-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e2937 100%);
    border-bottom: 2px solid #00ffcc;
    position: relative;
    overflow: hidden;
    padding: 10px 20px;
}
.checkpoint-header-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 100%;
    margin: 0 auto;
}
/* Logo Restriction */
.checkpoint-logo {
    height: 40px;
    width: auto;
    margin-right: 12px;
}
.checkpoint-brand-wrap {
    display: flex;
    align-items: center;
}
.checkpoint-brand-text {
    font-size: 22px;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: 1px;
}
/* Navigation Grid */
.checkpoint-nav {
    display: flex;
    gap: 15px;
    align-items: center;
}
.checkpoint-nav-link {
    color: #cbd5e1;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    transition: color 0.2s ease;
}
.checkpoint-nav-link:hover, .checkpoint-nav-link.is-active {
    color: #00ffcc;
}
.checkpoint-admin-actions {
    display: flex;
    gap: 10px;
}
/* White Light Sweep Animation */
.checkpoint-header::after {
    content: ''; position: absolute; top: 0; left: -150%; width: 50%; height: 100%;
    background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.4) 50%, rgba(255,255,255,0) 100%);
    transform: skewX(-25deg); animation: lightSweep 6s infinite linear;
}
@keyframes lightSweep { 0% { left: -150%; } 15% { left: 150%; } 100% { left: 150%; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('gitPullBridgeBtn');
  if(btn){
      btn.addEventListener('click', async function(){
          try {
              const res = await fetch('v1/admin/git-pull-bridge.php');
              const data = await res.json();
              alert((data.output || 'No output') + '\n\nSuccess: ' + (!!data.success));
          } catch(e) {
              alert('Sync failed: ' + e.message);
          }
      });
  }
});
</script>
