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
        <form id="settings-form">
            <label for="fmcsa_api_key">FMCSA API Key</label>
            <input type="text" id="fmcsa_api_key" name="fmcsa_api_key" value="<?= htmlspecialchars($fmcsaApiKey) ?>" autocomplete="off" required>
            <button type="submit" class="btn">Save Credentials</button>
        </form>
    </div>
</div>
<div id="settings-toast" class="toast">System Credentials Updated Successfully</div>
<script>
document.getElementById('settings-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    const response = await fetch('/v1/admin/save-settings.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    const data = await response.json().catch(() => ({}));
    if (response.ok && data.success) {
        const toast = document.getElementById('settings-toast');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2200);
    }
});
</script>
</body>
</html>
