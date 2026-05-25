<?php
require_once '../config/bootstrap.php';

echo "<h3>Environment Test:</h3>";
if (isset($pdo)) {
    echo "✅ Database: CONNECTED<br>";
}
if (isset($GLOBALS['creds']['twilio']['from_number'])) {
    echo "✅ Credentials Loaded. Twilio Number: " . $GLOBALS['creds']['twilio']['from_number'] . "<br>";
}

Logger::log('app.log', 'Smoke test executed successfully.');
echo "✅ Logger: Log line written to app.log<br>";
