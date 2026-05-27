<?php
$expManualDebugLog = [];

function expManualDebugLog(string $message, array $context = []): void
{
    global $expManualDebugLog;

    $timestamp = date('Y-m-d H:i:s');
    $entry = [
        'time' => $timestamp,
        'message' => $message,
        'context' => $context,
    ];

    $expManualDebugLog[] = $entry;

    $contextJson = $context !== [] ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '{}';
    error_log('[EXP_MANUAL_LOAD][' . $timestamp . '] ' . $message . ' ' . $contextJson);
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    expManualDebugLog('PHP runtime warning/error captured.', [
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
    ]);

    return false;
});

set_exception_handler(function (Throwable $exception): void {
    expManualDebugLog('Uncaught exception captured.', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ]);

    http_response_code(500);
    echo '<h1 style="color:#ef4444;font-family:sans-serif;">EXP Manual Load Debug: Unhandled Exception</h1>';
    echo '<pre style="background:#111;color:#f8fafc;padding:12px;border-radius:8px;white-space:pre-wrap;">';
    echo htmlspecialchars($exception->getMessage() . "\n\n" . $exception->getTraceAsString(), ENT_QUOTES, 'UTF-8');
    echo '</pre>';
});

register_shutdown_function(function (): void {
    $fatalError = error_get_last();

    if ($fatalError !== null) {
        expManualDebugLog('Shutdown fatal error detected.', $fatalError);
    }
});

expManualDebugLog('Page bootstrap started.', [
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
]);

try {
    require_once '../config/bootstrap.php';
    expManualDebugLog('Bootstrap loaded successfully.', ['file' => '../config/bootstrap.php']);
} catch (Throwable $exception) {
    expManualDebugLog('Bootstrap load failed.', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);
    throw $exception;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exspeedite CheckPoint • EXP Manual Load Wizard</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --exp-accent: #22c55e;
            --exp-accent-soft: rgba(34, 197, 94, 0.2);
            --exp-bg-1: #030712;
            --exp-bg-2: #0a1427;
            --exp-text: #dbeafe;
            --exp-border: rgba(148, 163, 184, 0.28);
        }

        html,
        body {
            height: 100%;
            overflow-y: auto;
        }

        body {
            background:
                radial-gradient(1200px circle at 5% -5%, rgba(34, 197, 94, 0.22), transparent 48%),
                radial-gradient(1000px circle at 95% 0%, rgba(6, 182, 212, 0.18), transparent 40%),
                linear-gradient(135deg, var(--exp-bg-1), var(--exp-bg-2));
            color: var(--exp-text);
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
        }

        body::-webkit-scrollbar,
        .exp-manual-scroll::-webkit-scrollbar,
        .carrier-results::-webkit-scrollbar,
        pre::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        body::-webkit-scrollbar-thumb,
        .exp-manual-scroll::-webkit-scrollbar-thumb,
        .carrier-results::-webkit-scrollbar-thumb,
        pre::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #22c55e, #06b6d4);
            border: 2px solid rgba(15, 23, 42, 0.9);
            border-radius: 999px;
        }

        body::-webkit-scrollbar-track,
        .exp-manual-scroll::-webkit-scrollbar-track,
        .carrier-results::-webkit-scrollbar-track,
        pre::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.85);
            border-radius: 999px;
        }

        .exp-manual-screen {
            min-height: calc(100vh - 84px);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .exp-manual-title {
            font-family: "Orbitron", sans-serif;
            letter-spacing: 0.02em;
            color: #86efac;
            text-shadow: 0 0 14px rgba(34, 197, 94, 0.45);
        }

        .exp-manual-card {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
            border: 1px solid var(--exp-border) !important;
            border-radius: 18px;
            backdrop-filter: blur(12px);
            background: linear-gradient(150deg, rgba(2, 6, 23, 0.88), rgba(15, 23, 42, 0.86));
            box-shadow: 0 16px 45px rgba(2, 6, 23, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        .manual-modal-head {
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.18), rgba(14, 116, 144, 0.18));
            border-bottom: 1px solid var(--exp-border);
        }

        .exp-manual-scroll {
            flex: 1 1 auto;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .form-control,
        .form-select {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(100, 116, 139, 0.55);
            color: #f8fafc;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 0.25rem rgba(34, 197, 94, 0.18);
            background: rgba(15, 23, 42, 0.95);
            color: #fff;
        }

        .form-label {
            color: #bfdbfe;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .btn-success {
            background: linear-gradient(120deg, #16a34a, #0891b2);
            border: 0;
            box-shadow: 0 10px 20px rgba(8, 145, 178, 0.25);
        }

        .btn-success:hover {
            filter: brightness(1.08);
            transform: translateY(-1px);
        }

        .alert-secondary {
            border-radius: 12px;
        }
    </style>
</head>
<body>
<?php
$checkpointHeaderCurrent = 'EXP Manual Load Wizard';

try {
    require_once __DIR__ . '/includes/header.php';
    expManualDebugLog('Header include loaded successfully.', ['file' => __DIR__ . '/includes/header.php']);
} catch (Throwable $exception) {
    expManualDebugLog('Header include failed.', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);
    throw $exception;
}
?>

<div class="container py-4 exp-manual-screen" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0 exp-manual-title">Standalone EXP Manual Load Wizard</h2>
        <a href="dashboard.php" class="btn btn-outline-light">Back to Live Map</a>
    </div>

    <div class="card bg-dark text-light border-success exp-manual-card">
        <div class="card-header manual-modal-head">
            <h5 class="mb-0">Create Load + Send Driver Text</h5>
        </div>
        <div class="card-body p-4 exp-manual-scroll">
            <div class="alert alert-secondary border border-warning-subtle text-light mb-4" style="background:#111827;">
                <h6 class="mb-2 text-warning">Debug Log (live for this request)</h6>
                <pre class="mb-0" style="max-height:260px;overflow:auto;white-space:pre-wrap;"><?php
                    foreach ($expManualDebugLog as $debugEntry) {
                        echo htmlspecialchars('[' . $debugEntry['time'] . '] ' . $debugEntry['message'], ENT_QUOTES, 'UTF-8') . "\n";

                        if (!empty($debugEntry['context'])) {
                            echo htmlspecialchars(json_encode($debugEntry['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') . "\n";
                        }

                        echo "\n";
                    }
                ?></pre>
            </div>

            <form id="manual-load-form">
                <input type="hidden" name="carrier_id" id="carrier_id" value="0">
                <input type="hidden" name="driver_id" id="driver_id" value="0">

                <div class="row g-3 mb-4">
                    <div class="col-12 col-lg-7">
                        <label for="carrier_name" class="form-label">Carrier Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="carrier_name" id="carrier_name" value="MANNING TRANSFER, INC." placeholder="Start typing legal or DBA carrier name">
                        <div id="carrier-results" class="carrier-results mt-2"></div>
                    </div>
                    <div class="col-12 col-lg-5">
                        <label for="dot_number" class="form-label">DOT Number <span class="text-muted">(optional)</span></label>
                        <input class="form-control" type="text" name="dot_number" id="dot_number" value="1234567" placeholder="e.g. 1234567" inputmode="numeric">
                    </div>
                    <div class="col-12">
                        <button type="button" id="fmcsa-btn" class="btn btn-outline-primary w-100">🔎 Search FMCSA</button>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label for="driver_phone" class="form-label">Driver Phone <span class="text-danger">*</span></label>
                        <input class="form-control" type="tel" name="driver_phone" id="driver_phone" value="+17634446474" placeholder="+17634446474">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="driver_name" class="form-label">Driver Name</label>
                        <input class="form-control" type="text" name="driver_name" id="driver_name" value="John Doe" placeholder="John Doe">
                    </div>
                    <div class="col-12">
                        <label for="driver_email" class="form-label">Driver Email (optional)</label>
                        <input class="form-control" type="email" name="driver_email" id="driver_email" value="selliott@strongtco.com" placeholder="name@example.com">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="load_number" class="form-label">Load Number <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="load_number" id="load_number" value="EXP-10001" placeholder="Internal or broker #">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Stops</label>
                        <div id="stops-wrap"></div>
                        <button type="button" id="add-stop-btn" class="btn btn-success btn-sm mt-2">+ Add Stop</button>
                    </div>
                </div>

                <div class="manual-error mt-3" id="manual-error"></div>
                <div id="manual-success" class="mt-3 text-success fw-semibold"></div>

                <div class="mt-4 d-flex gap-2">
                    <button type="button" id="clear-form-btn" class="btn btn-secondary">Clear Form</button>
                    <button type="submit" class="btn btn-success btn-lg px-4">🚚 Send Load</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/exp_manual-load.js?v=<?php echo time(); ?>"></script>
</body>
</html>
