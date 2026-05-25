<?php
require_once __DIR__ . '/../config/bootstrap.php';
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = trim($_POST['company_name'] ?? '');
    $password    = $_POST['password'] ?? '';

    if ($companyName === 'Exspeedite' && $password === 'Trucking1') {
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['company_id']          = 1;
        $_SESSION['company_name']        = 'Exspeedite';

        Logger::write('auth.log', 'INFO', 'Admin login successful (plain text match)', ['company_id' => 1]);
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Incorrect company name or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exspeedite Checkpoint - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #e2e8f0; font-family: system-ui; }
        .login-card { max-width: 420px; margin: 56px auto; background: rgba(15,23,42,0.95); border: 1px solid #00c853; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
    </style>
</head>
<body>
<?php
$checkpointHeaderCurrent = 'Login';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="login-card card p-5">
        <h2 class="text-center mb-4" style="color:#00c853">EXSPEEDITE CHECKPOINT</h2>
        <h4 class="text-center mb-4">Dispatcher Login</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Company Name</label>
                <input type="text" name="company_name" value="Exspeedite" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" value="Trucking1" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success w-100 py-3">LOGIN</button>
        </form>
    </div>
</div>
</body>
</html>
