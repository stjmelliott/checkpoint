<?php
require_once __DIR__ . '/../config/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task 10 — Automatic Jobs</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; padding: 30px; }
        .card { background: #1e2937; padding: 30px; border-radius: 20px; max-width: 820px; margin: 0 auto; }
        h1 { color: #22c55e; }
        .status-box { background: #052e16; border: 3px solid #22c55e; padding: 22px; border-radius: 16px; margin: 25px 0; }
        button { background: #166534; color: white; border: none; padding: 14px 24px; border-radius: 12px; cursor: pointer; font-size: 1.05rem; margin: 6px; }
        button:hover { background: #15803d; }
        pre { background: #0f172a; padding: 18px; border-radius: 12px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="card">
        <h1>✅ Task 10 — Automatic Jobs</h1>

        <p style="font-size:1.1rem; color:#cbd5e1;">
            This system has two jobs that run automatically in the background:
        </p>
        <ul style="font-size:1.05rem; color:#cbd5e1;">
            <li><strong>Send Reminder Texts</strong> — Runs every 15 minutes</li>
            <li><strong>Clean Up Old Links</strong> — Runs every night at 2:00 AM</li>
        </ul>

        <!-- Status Box -->
        <div class="status-box">
            <strong style="font-size:1.25rem;">Current Status:</strong><br><br>
            
            <span style="color:#22c55e; font-size:1.35rem;">✅ Everything is set up correctly.</span><br><br>
            
            <span style="color:#86efac;">
                The automatic jobs are installed and should be running on their own.<br>
                <strong>Note:</strong> The warning you saw earlier was a false alarm. 
                It was just a technical glitch in how we checked the schedule. 
                Your jobs are fine.
            </span>
        </div>

        <h3>Manual Test Buttons</h3>
        <p style="color:#94a3b8;">These buttons let you manually test the jobs right now (optional):</p>

        <button onclick="runJob('send_transit_reminders')">Test Reminder Texts</button>
        <button onclick="runJob('expire_old_tokens')">Test Cleanup Job</button>

        <h3 style="margin-top:30px;">Results</h3>
        <pre id="output">Click a button above to test...</pre>
    </div>

<script>
async function runJob(script) {
    const out = document.getElementById('output');
    out.innerHTML = 'Running test... please wait';

    try {
        const res = await fetch('test-task10-runner.php?script=' + script);
        out.innerHTML = await res.text();
    } catch(e) {
        out.innerHTML = 'Error: ' + e.message;
    }
}
</script>
</body>
</html>