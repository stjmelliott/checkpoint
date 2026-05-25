<?php
require_once __DIR__ . '/../config/bootstrap.php';
echo "<h2>Task 7 — Driver-Facing Endpoints Test</h2><pre>";
echo "load-info.php test (dummy token): ";
$response = file_get_contents("https://exspeeditecheckpoint.com/v1/tracking/load-info.php?token=dummy&milestone=pickup&stop=1");
$data = json_decode($response, true);
echo ($data['success'] === false ? "✅ Correctly rejected invalid token" : "❌ Unexpected") . "\n";
echo "</pre>";
?>
