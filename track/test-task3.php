<?php
require_once __DIR__ . '/../config/bootstrap.php';
echo "<h2>Task 3 — One-Time Admin Setup Test</h2><pre>";
$stmt = $pdo->query("SELECT company_name, created_at FROM track_users LIMIT 1");
$user = $stmt->fetch();
if ($user) {
    echo "Admin account found: ✅ " . htmlspecialchars($user['company_name']) . "\n";
    echo "Created: " . $user['created_at'] . "\n";
} else {
    echo "❌ No admin account found in track_users table\n";
}
echo "\nAPI client token count: " . $pdo->query("SELECT COUNT(*) FROM track_api_clients")->fetchColumn() . "\n";
echo "</pre>";
?>
