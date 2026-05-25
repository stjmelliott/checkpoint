<?php
// /v1/admin/fmcsa-search.php
require_once '../../config/bootstrap.php';

// Session + company_id check
if (!isset($_SESSION['company_id'])) {
    http_response_code(401); exit(json_encode(['error' => 'Unauthorized']));
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    exit(json_encode([]));
}

Logger::write('app.log', 'INFO', 'FMCSA search requested', ['company_id' => $_SESSION['company_id'], 'q' => substr($q,0,40)]);

require_once '../../include/sts_sw_carrier_class.php';  // your existing class
$sw = sts_sw_carrier::getInstance($exspeedite_db ?? $pdo, false);  // reuse your instance

$results = $sw->fmcsa_lookup_carrier($q);

Logger::write('app.log', 'INFO', 'FMCSA search result', ['count' => count($results ?? []), 'http_status' => 200]);

exit(json_encode($results ?? []));
?>