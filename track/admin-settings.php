<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['company_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    http_response_code(403);
    exit('Forbidden');
}

$companyId = (int)$_SESSION['company_id'];
$stmt = $pdo->prepare("SELECT credential_value FROM app_credentials WHERE company_id = ? AND service_name = 'checkpoint' AND credential_key = 'fmcsa_api_key' LIMIT 1");
$stmt->execute([$companyId]);
$fmcsaApiKey = (string)($stmt->fetchColumn() ?: '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkpoint Admin Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; min-height: 100vh; background: radial-gradient(circle at 15% 20%, #10253f 0%, #070d17 42%, #050910 100%); color: #e2e8f0; font-family: 'Segoe UI', Roboto, sans-serif; }
        .settings-wrap { max-width: 760px; margin: 72px auto; padding: 24px; }
        .settings-card { background: rgba(11, 20, 33, 0.85); border: 1px solid rgba(0, 255, 204, 0.35); border-radius: 14px; padding: 28px; box-shadow: 0 0 35px rgba(0, 255, 204, 0.14); backdrop-filter: blur(10px); }
        h1 { font-family: 'Orbitron', sans-serif; letter-spacing: 1.8px; text-transform: uppercase; color: #00ffcc; margin-top: 0; }
        .subtle { color: #9fb4ca; margin-bottom: 24px; }
        label { display: block; margin-bottom: 8px; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; color: #9be7ff; }
        input[type='text'] { width: 100%; box-sizing: border-box; padding: 12px 14px; border-radius: 8px; border: 1px solid rgba(148, 163, 184, 0.35); background: rgba(255,255,255,0.04); color: #fff; }
        input[type='text']:focus { outline: none; border-color: #00ffcc; box-shadow: 0 0 14px rgba(0,255,204,0.35); }
        .btn { margin-top: 16px; border: 1px solid #00ffcc; background: transparent; color: #00ffcc; padding: 10px 18px; border-radius: 8px; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; }
        .btn:hover { background: #00ffcc; color: #071420; box-shadow: 0 0 16px rgba(0, 255, 204, 0.6); }
        .toast { position: fixed; right: 24px; bottom: 24px; background: rgba(0,255,204,0.2); border: 1px solid #00ffcc; color: #d1fae5; padding: 12px 16px; border-radius: 10px; opacity: 0; transform: translateY(10px); transition: all .25s ease; box-shadow: 0 0 18px rgba(0,255,204,.45); }
        .toast.show { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>
<div class="settings-wrap">
    <div class="settings-card">
        <h1>Admin Settings</h1>
        <p class="subtle">Configure system credentials for tenant #<?= htmlspecialchars((string)$companyId) ?>.</p>
        <div class="card">
  <div class="card-header"><h5><i class="bi bi-key"></i> Edit FMCSA API Credential</h5></div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="id" value="<?php echo $id ?? ''; ?>">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label">Service Name <span class="text-danger">*</span></label>
          <select name="service_name" class="form-select" required>
            <option value="fmcsa" selected>FMCSA (Federal Motor Carrier Safety Administration)</option>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">Credential Key <span class="text-danger">*</span></label>
          <input type="text" name="credential_key" class="form-control" value="FMCSA_API_KEY" readonly>
        </div>
        <div class="col-12">
          <label class="form-label">API Key Value <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="password" name="credential_value" id="api-key-input" class="form-control" value="<?php echo htmlspecialchars($credential_value ?? ''); ?>" required>
            <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKeyVisibility()">👁️</button>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Description / Comment</label>
          <textarea name="comment" class="form-control" rows="2"><?php echo htmlspecialchars($comment ?? ''); ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Last Updated</label>
          <input type="text" class="form-control" value="<?php echo $updated_at ?? 'Now'; ?>" readonly>
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" name="save_credential" class="btn btn-success btn-lg px-5">💾 Save to system_credentials</button>
        <a href="admin-settings.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
</div>
    </div>
</div>
<div id="settings-toast" class="toast">System Credentials Updated Successfully</div>
<script>
function toggleApiKeyVisibility() {
  const input = document.getElementById('api-key-input');
  if (input) input.type = input.type === 'password' ? 'text' : 'password';
}
function testFmcsaConnection() {
  alert('✅ FMCSA connection test coming soon (will call /v1/admin/fmcsa-search.php)');
  // Future: real test endpoint
}
</script>
</body>
</html>
