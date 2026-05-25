<?php
// /var/www/checkpoint/track/help-dashboard-logic.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Help: How the Dashboard Works</title>
    <style>
        body { font-family: system-ui, sans-serif; background:#0f172a; color:#e2e8f0; padding:40px; line-height:1.6; }
        .container { max-width: 800px; margin: auto; }
        h1 { color: #22c55e; }
        .section { background:#1e2937; padding:25px; border-radius:12px; margin-bottom:25px; }
        ul { padding-left:20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>How the Exspeedite CheckPoint Dashboard Works</h1>

    <div class="section">
        <h3>1. What Shows on the Map?</h3>
        <p>The map only shows loads that have <strong>checked in at least once</strong> (they have GPS pings).</p>
        <ul>
            <li>Each active load gets <strong>one marker</strong> showing its latest known position.</li>
            <li>Loads that have never checked in do <strong>not</strong> appear on the map.</li>
        </ul>
    </div>

    <div class="section">
        <h3>2. Sidebar vs Map</h3>
        <ul>
            <li><strong>Sidebar</strong> = Shows <strong>all active loads</strong> (including those that have never checked in).</li>
            <li><strong>Map</strong> = Only shows loads that have actual GPS data.</li>
        </ul>
    </div>

    <div class="section">
        <h3>3. Trail / History</h3>
        <p>By default, no breadcrumb trails are shown. This keeps the map clean.</p>
        <p>When you click <strong>"Show Trail"</strong> on a load, it will display the history of where that specific load has been.</p>
    </div>

    <div class="section">
        <h3>4. Why Some Loads Don’t Appear on the Map</h3>
        <p>If a load shows <strong>“Never Checked In”</strong> in the sidebar, it means it has not sent any GPS pings yet. It will appear on the map automatically once it checks in.</p>
    </div>
</div>
</body>
</html>