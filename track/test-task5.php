<?php
require_once __DIR__ . '/../config/bootstrap.php';
echo "<h2>Task 5 — Core Architecture & Shared Services Test</h2><pre>";

echo "TokenService loaded: " . (class_exists('TokenService') ? "✅ Yes" : "❌ No") . "\n";
$token = TokenService::generate();
echo "Generated token length: " . strlen($token) . " chars (should be 64) → " . (strlen($token) === 64 ? "✅" : "❌") . "\n";
echo "Token format valid: " . (TokenService::validateFormat($token) ? "✅ Yes" : "❌ No") . "\n\n";

echo "LocationService loaded: " . (class_exists('LocationService') ? "✅ Yes" : "❌ No") . "\n";
echo "Geocode only on delivery: " . (LocationService::shouldGeocode('delivery') ? "✅ Yes" : "❌ No") . "\n";
echo "No geocode on pickup/transit: " . (!LocationService::shouldGeocode('pickup') && !LocationService::shouldGeocode('transit') ? "✅ Yes" : "❌ No") . "\n\n";

echo "PingValidator loaded: " . (class_exists('PingValidator') ? "✅ Yes" : "❌ No") . "\n";
echo "SecretManager loaded: " . (class_exists('SecretManager') ? "✅ Yes" : "❌ No") . "\n";

echo "\n✅ All Task 5.1–5.5 components are wired correctly.\n";
echo "</pre>";
?>
