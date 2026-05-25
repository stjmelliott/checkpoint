<?php
require_once __DIR__ . '/../config/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EXSPEEDITE CHECKPOINT — Master Test Dashboard</title>
    <style>
        body {font-family: monospace; background:#0f172a; color:#e2e8f0; padding:20px; margin:0;}
        h1 {color:#60a5fa; text-align:center;}
        .container {max-width:1200px; margin:auto;}
        button {background:#3b82f6; color:white; border:none; padding:16px 32px; font-size:1.1em; border-radius:12px; cursor:pointer; margin:5px;}
        button:hover {background:#2563eb;}
        .task {background:#1e2937; margin:20px 0; padding:20px; border-radius:12px;}
        .task-header {display:flex; justify-content:space-between; align-items:center; font-size:1.2em;}
        .green {color:#22c55e; font-weight:bold;}
        pre {background:#0f172a; padding:15px; border-radius:8px; max-height:400px; overflow:auto; font-size:0.95em;}
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 EXSPEEDITE CHECKPOINT — Master Test Dashboard</h1>
    <p style="text-align:center; color:#94a3b8;">Tasks 1–9 are now fully wired.</p>

    <!-- Tasks 1-5 -->
    <div class="task"><div class="task-header"><strong>Task 1</strong><span class="green">✅ COMPLETE</span></div><button onclick="runTask1()">Run Task 1</button><div id="task1-result"></div></div>
    <div class="task"><div class="task-header"><strong>Task 2</strong><span class="green">✅ COMPLETE</span></div><button onclick="runTask2()">Run Task 2</button><div id="task2-result"></div></div>
    <div class="task"><div class="task-header"><strong>Task 3</strong><span class="green">✅ COMPLETE</span></div><button onclick="runTask3()">Run Task 3</button><div id="task3-result"></div></div>
    <div class="task"><div class="task-header"><strong>Task 4</strong><span class="green">✅ COMPLETE</span></div><button onclick="runTask4()">Run Task 4</button><div id="task4-result"></div></div>
    <div class="task"><div class="task-header"><strong>Task 5</strong><span class="green">✅ COMPLETE</span></div><button onclick="runTask5()">Run Task 5</button><div id="task5-result"></div></div>

    <!-- Tasks 6-8 -->
    <div class="task"><div class="task-header"><strong>Task 6</strong><span class="green">✅ COMPLETE</span></div><button onclick="runTask6()">Run Task 6</button><div id="task6-result"></div></div>
    <div class="task"><div class="task-header"><strong>Task 7</strong><span class="green">✅ COMPLETE</span></div><button onclick="runTask7()">Run Task 7</button><div id="task7-result"></div></div>
    <div class="task"><div class="task-header"><strong>Task 8</strong><span class="green">✅ COMPLETE</span></div><button onclick="runTask8()">Run Task 8</button><div id="task8-result"></div></div>

    <!-- TASK 9 (now added) -->
    <div class="task">
        <div class="task-header">
            <strong>Task 9: Dispatcher Admin Endpoints (map-data, close-load, cancel-load, request-manual-ping)</strong>
            <span class="green">✅ WIRED</span>
        </div>
        <button onclick="runTask9()">Run Task 9 Tests</button>
        <div id="task9-result"></div>
    </div>

<!-- TASK 10 -->
<div class="task">
    <div class="task-header">
        <strong>Task 10 — Cron Jobs</strong>
        <span class="green">✅ COMPLETE</span>
    </div>
    <button onclick="window.location.href='test-task10.php'">Open Task 10 Test Page</button>
</div>

    <button onclick="runAllTasks()" style="background:#22c55e; font-size:1.3em; padding:22px 44px; display:block; margin:30px auto;">
        🚀 RUN ALL TASKS 1–9 AT ONCE
    </button>

    <div id="all-results"></div>
</div>

<script>
async function runTask1(){const r=await fetch('/test-task1.php');const h=await r.text();document.getElementById('task1-result').innerHTML=`<h3>Task 1</h3>${h}`;}
async function runTask2(){const r=await fetch('/test-task2.php');const h=await r.text();document.getElementById('task2-result').innerHTML=`<h3>Task 2</h3>${h}`;}
async function runTask3(){const r=await fetch('/test-task3.php');const h=await r.text();document.getElementById('task3-result').innerHTML=`<h3>Task 3</h3>${h}`;}
async function runTask4(){const r=await fetch('/test-logging.php');const h=await r.text();document.getElementById('task4-result').innerHTML=`<h3>Task 4</h3>${h}`;}
async function runTask5(){const r=await fetch('/test-task5.php');const h=await r.text();document.getElementById('task5-result').innerHTML=`<h3>Task 5</h3>${h}`;}
async function runTask6(){const r=await fetch('/test-task6.php');const h=await r.text();document.getElementById('task6-result').innerHTML=`<h3>Task 6</h3>${h}`;}
async function runTask7(){const r=await fetch('/test-task7.php');const h=await r.text();document.getElementById('task7-result').innerHTML=`<h3>Task 7</h3>${h}`;}
async function runTask8(){const r=await fetch('/test-task8.php');const h=await r.text();document.getElementById('task8-result').innerHTML=`<h3>Task 8</h3>${h}`;}
async function runTask9(){const r=await fetch('/test-task9.php');const h=await r.text();document.getElementById('task9-result').innerHTML=`<h3>Task 9</h3>${h}`;}
async function runAllTasks(){
    document.getElementById('all-results').innerHTML='<h2>Running All Tests...</h2>';
    await runTask1();await runTask2();await runTask3();await runTask4();await runTask5();
    await runTask6();await runTask7();await runTask8();await runTask9();
}
</script>
</body>
</html>
