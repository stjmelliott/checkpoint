<?php
define('SETUP_KEY', 'ChangeThisToSomethingLongAndRandom2026');

if (!isset($_GET['setup_key']) || $_GET['setup_key'] !== SETUP_KEY) {
    http_response_code(403);
    die('Access denied.');
}

require_once __DIR__ . '/../config/bootstrap.php';

$companyName   = 'Exspeedite';
$plainPassword = 'ChangeMeToAStrongPassword123!';

$hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $pdo->prepare("INSERT INTO track_users (company_name, password) 
                       VALUES (?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password)");
$stmt->execute([$companyName, $hash]);

$companyId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM track_users WHERE company_name = '$companyName'")->fetchColumn();

// Create Bearer Token
$rawToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $rawToken);

$pdo->prepare("INSERT INTO track_api_clients (company_id, client_name, bearer_token_hash) 
               VALUES (?, ?, ?)")
    ->execute([$companyId, 'Exspeedite CheckPoint — TMS Integration', $tokenHash]);

unlink(__FILE__); // Self delete

echo "<h1>✅ Setup Complete!</h1><pre>";
echo "Company: $companyName\n";
echo "Password: $plainPassword\n\n";
echo "BEARER TOKEN (copy now):\n$rawToken\n";
echo "</pre>";
?>
