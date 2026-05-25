<?php
// /var/www/checkpoint/track/help-dashboard-logic.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>How the Dashboard Works</title>
    <style>
        body { font-family: system-ui, sans-serif; background:#0f172a; color:#e2e8f0; padding:50px; line-height:1.7; }
        .container { max-width: 820px; margin: auto; }
        h1 { color: #22c55e; }
        .section { background:#1e2937; padding:28px; border-radius:16px; margin-bottom:30px; border:1px solid #334155; }
        ul { padding-left:22px; }
    </style>
</head>
<body>
<div class="container">
    <h1>How the Exspeedite CheckPoint Dashboard Works</h1>

    <div class="section">
        <h3>1. What Shows on the Map?</h3>
        <p>The map only shows loads that have <strong>checked in at least once</strong> (they have GPS pings).</p>
        <ul>
            <li>Each active load gets <strong>one clean marker</strong> showing its latest known position.</li>
            <li>Loads that have never checked in appear only in the sidebar as <strong>“Never Checked In”</strong>.</li>
        </ul>
    </div>

    <div class="section">
        <h3>2. Trail / History</h3>
        <p>By default, the map is clean with no trails.</p>
        <p>When you click <strong>“Show Trail”</strong>, it displays the movement history for that specific load only.</p>
        <p><strong>Note:</strong> Some loads currently show “Checked In” but may not have enough historical pings yet to display a full trail.</p>
    </div>

    <div class="section">
        <h3>3. Why Some Loads Don’t Show a Trail</h3>
        <p>Even if a load says “Checked In”, it needs multiple pings over time to generate a visible trail. Newer loads often only have their latest position.</p>
    </div>
</div>
</body>
</html>