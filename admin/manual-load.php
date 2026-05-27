<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../lib/initialize_logic.php';

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$companyId = (int)$_SESSION['company_id'];
Logger::write('app.log', 'INFO', 'Manual load form opened', ['company_id' => $companyId]);

$modeStmt = $pdo->prepare("SELECT credential_value FROM app_credentials WHERE company_id = ? AND service_name='checkpoint' AND credential_key='load_entry_mode' LIMIT 1");
$modeStmt->execute([$companyId]);
$loadEntryMode = (string)($modeStmt->fetchColumn() ?: 'webhook');

$errors = [];
$success = '';
$form = [
    'carrier_name' => '', 'driver_name' => '', 'driver_phone' => '', 'driver_email' => '', 'load_number' => '',
];
$stops = [
    ['stop_number' => 1, 'stop_type' => 'pickup', 'stop_name' => '', 'address' => ''],
    ['stop_number' => 2, 'stop_type' => 'delivery', 'stop_name' => '', 'address' => ''],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Logger::write('app.log', 'INFO', 'Manual load submit requested', ['company_id' => $companyId]);

    $form['carrier_name'] = trim((string)($_POST['carrier_name'] ?? ''));
    $form['driver_name'] = trim((string)($_POST['driver_name'] ?? ''));
    $form['driver_phone'] = trim((string)($_POST['driver_phone'] ?? ''));
    $form['driver_email'] = trim((string)($_POST['driver_email'] ?? ''));
    $form['load_number'] = trim((string)($_POST['load_number'] ?? ''));

    $postedStops = $_POST['stops'] ?? [];
    $stops = [];
    if (is_array($postedStops)) {
        foreach ($postedStops as $row) {
            if (!is_array($row)) { continue; }
            $stops[] = [
                'stop_number' => (int)($row['stop_number'] ?? 0),
                'stop_type' => trim((string)($row['stop_type'] ?? 'pickup')),
                'stop_name' => trim((string)($row['stop_name'] ?? '')),
                'address' => trim((string)($row['address'] ?? '')),
            ];
        }
    }

    if (!in_array($loadEntryMode, ['manual', 'both'], true)) {
        $errors[] = 'Manual load entry is disabled for this tenant.';
    }
    if ($form['carrier_name'] === '' || $form['driver_name'] === '' || $form['driver_phone'] === '' || $form['load_number'] === '') {
        $errors[] = 'Carrier, driver, phone, and load number are required.';
    }
    if (!preg_match('/^\+[1-9]\d{1,14}$/', $form['driver_phone'])) {
        $errors[] = 'Driver phone must be valid E.164 format (example: +15551234567).';
    }
    if ($form['driver_email'] !== '' && !filter_var($form['driver_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Driver email is invalid.';
    }
    if (count($stops) < 2) { $errors[] = 'At least 2 stops are required.'; }

    $deliveryCount = 0;
    $expected = 1;
    foreach ($stops as $i => $s) {
        if ($s['stop_number'] !== $expected) {
            $errors[] = 'Stop numbers must be sequential starting at 1.';
            break;
        }
        if ($s['stop_type'] === 'delivery') { $deliveryCount++; }
        if ($s['stop_name'] === '' || $s['address'] === '') {
            $errors[] = 'Each stop requires stop name and address.';
            break;
        }
        $expected++;
    }
    if ($deliveryCount !== 1) { $errors[] = 'Exactly one stop must be marked as delivery.'; }

    if (!$errors) {
        $payload = [
            'company_id' => $companyId,
            'carrier_name' => $form['carrier_name'],
            'driver_name' => $form['driver_name'],
            'driver_phone' => $form['driver_phone'],
            'driver_email' => $form['driver_email'],
            'load_number' => $form['load_number'],
            'stops' => $stops,
        ];
        Logger::write('app.log', 'INFO', 'Manual load initialize invoked', ['company_id' => $companyId, 'load' => $form['load_number']]);
        $result = run_initialize($payload, $companyId, $pdo);
        if (!empty($result['success'])) {
            Logger::write('app.log', 'INFO', 'Manual load initialize succeeded', ['company_id' => $companyId, 'load' => $form['load_number']]);
            header('Location: /dashboard.php');
            exit;
        }
        $errors[] = (string)($result['message'] ?? 'Load creation failed.');
        Logger::write('error.log', 'ERROR', 'Manual load initialize failed', ['company_id' => $companyId, 'load' => $form['load_number'], 'result' => $result]);
    } else {
        Logger::write('app.log', 'WARN', 'Manual load validation failed', ['company_id' => $companyId, 'errors' => $errors]);
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Manual Load Entry</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#020617;color:#e2e8f0}.card{background:#0f172a;border:1px solid #22c55e}.form-control,.form-select{background:#0b1220;color:#e2e8f0;border-color:#14532d}.btn-neon{background:#166534;border-color:#22c55e;color:#dcfce7}.btn-neon:hover{background:#15803d;color:#fff}.accent{color:#22c55e}</style></head><body><div class="container py-4"><div class="card p-4"><h1 class="h3 accent">Manual Load Entry</h1><p>Mode: <strong><?=htmlspecialchars($loadEntryMode)?></strong></p><?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?=htmlspecialchars($e)?></div><?php endforeach; ?></div><?php endif; ?><form method="post"><div class="row g-3"><div class="col-md-6"><label class="form-label">Carrier Name</label><input class="form-control" name="carrier_name" value="<?=htmlspecialchars($form['carrier_name'])?>" required></div><div class="col-md-6 d-flex align-items-end"><a class="btn btn-neon w-100" href="/admin/fmcsa-lookup.php" target="_blank" rel="noopener">Search FMCSA</a></div><div class="col-md-6"><label class="form-label">Driver Name</label><input class="form-control" name="driver_name" value="<?=htmlspecialchars($form['driver_name'])?>" required></div><div class="col-md-6"><label class="form-label">Driver Phone (E.164)</label><input class="form-control" name="driver_phone" placeholder="+15551234567" value="<?=htmlspecialchars($form['driver_phone'])?>" required></div><div class="col-md-6"><label class="form-label">Driver Email (optional)</label><input class="form-control" name="driver_email" value="<?=htmlspecialchars($form['driver_email'])?>"></div><div class="col-md-6"><label class="form-label">Load Number</label><input class="form-control" name="load_number" value="<?=htmlspecialchars($form['load_number'])?>" required></div></div><hr><h2 class="h5 accent">Stops (minimum 2, one delivery)</h2><?php foreach ($stops as $i => $s): ?><div class="row g-2 mb-2"><div class="col-md-1"><input class="form-control" name="stops[<?=$i?>][stop_number]" type="number" value="<?= (int)$s['stop_number']?>" min="1" required></div><div class="col-md-2"><select class="form-select" name="stops[<?=$i?>][stop_type]"><option value="pickup"<?=$s['stop_type']==='pickup'?' selected':''?>>Pickup</option><option value="delivery"<?=$s['stop_type']==='delivery'?' selected':''?>>Delivery</option></select></div><div class="col-md-3"><input class="form-control" name="stops[<?=$i?>][stop_name]" placeholder="Stop Name" value="<?=htmlspecialchars($s['stop_name'])?>" required></div><div class="col-md-6"><input class="form-control" name="stops[<?=$i?>][address]" placeholder="Address" value="<?=htmlspecialchars($s['address'])?>" required></div></div><?php endforeach; ?><button class="btn btn-neon mt-3" type="submit">Create Load</button></form></div></div></body></html>
