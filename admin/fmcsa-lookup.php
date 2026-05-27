<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$companyId = (int)$_SESSION['company_id'];
$results = [];
$error = '';
$q = trim((string)($_POST['CARRIER_NAME'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Logger::write('app.log', 'INFO', 'FMCSA lookup page search requested', ['company_id' => $companyId, 'q' => mb_substr($q, 0, 80)]);
    if ($q === '') {
        $error = 'Carrier name is required.';
    } else {
        if (!class_exists('sts_sw_carrier')) {
            $error = 'FMCSA lookup class is unavailable.';
            Logger::write('error.log', 'ERROR', 'FMCSA lookup failed missing class', ['company_id' => $companyId]);
        } else {
            $sw = sts_sw_carrier::getInstance($pdo, $companyId);
            $results = $sw->fmcsa_lookup_carrier($q);
            if (!is_array($results)) { $results = []; }
            Logger::write('app.log', 'INFO', 'FMCSA lookup page search response received', ['company_id' => $companyId, 'count' => count($results)]);
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FMCSA Lookup</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#020617;color:#e2e8f0}.card{background:#0f172a;border:1px solid #22c55e}.form-control{background:#0b1220;color:#e2e8f0;border-color:#14532d}.btn-neon{background:#166534;border-color:#22c55e;color:#dcfce7}.accent{color:#22c55e}</style></head><body><div class="container py-4"><div class="card p-4"><h1 class="h3 accent">FMCSA Carrier Lookup</h1><?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?><form method="post" class="row g-2 mb-3"><div class="col-md-9"><input class="form-control" name="CARRIER_NAME" value="<?=htmlspecialchars($q)?>" placeholder="Carrier legal or DBA name"></div><div class="col-md-3"><button class="btn btn-neon w-100" type="submit">Search</button></div></form><table class="table table-dark table-striped"><thead><tr><th>DOT#</th><th>Legal Name</th><th>DBA Name</th><th>City</th><th>State</th></tr></thead><tbody><?php foreach($results as $r): ?><tr><td><?=htmlspecialchars((string)($r['dot_number'] ?? $r['dotNumber'] ?? ''))?></td><td><?=htmlspecialchars((string)($r['legal_name'] ?? $r['legalName'] ?? ''))?></td><td><?=htmlspecialchars((string)($r['dba_name'] ?? $r['dbaName'] ?? ''))?></td><td><?=htmlspecialchars((string)($r['city'] ?? $r['phyCity'] ?? ''))?></td><td><?=htmlspecialchars((string)($r['state'] ?? $r['phyState'] ?? ''))?></td></tr><?php endforeach; ?></tbody></table><a class="btn btn-outline-success" href="/admin/manual-load.php">Back to Manual Load Form</a></div></div></body></html>
