cat > /var/www/checkpoint/track/exp_file_browser.php << 'EOF'
<?php
$root = '/var/www/checkpoint';
$path = isset($_GET['path']) ? realpath($root . '/' . $_GET['path']) : $root;
if (strpos($path, $root) !== 0) $path = $root;

function scanAll($dir, &$results = []) {
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . '/' . $item;
        $rel = str_replace($root . '/', '', $full);

        if (is_dir($full)) {
            $results[] = ['path' => $rel, 'type' => 'folder', 'size' => '', 'ext' => ''];
            scanAll($full, $results);
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            $results[] = [
                'path' => $rel,
                'type' => 'file',
                'size' => filesize($full),
                'ext' => $ext
            ];
        }
    }
    return $results;
}

if (isset($_GET['csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="checkpoint_full_inventory.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Full Path', 'Type', 'Size (bytes)', 'Extension']);
    $data = [];
    scanAll($root, $data);
    foreach ($data as $row) {
        fputcsv($out, [$row['path'], $row['type'], $row['size'], $row['ext']]);
    }
    exit;
}

$data = scanAll($path);
$folders = array_filter($data, fn($x) => $x['type'] === 'folder');
$files = array_filter($data, fn($x) => $x['type'] === 'file');
?>
<!DOCTYPE html>
<html><head><title>Checkpoint Full Explorer</title>
<style>body{font-family:monospace; margin:20px;} table{border-collapse:collapse;} td,th{padding:4px 8px; border:1px solid #ccc;}</style>
</head><body>
<h2>Checkpoint Full File Explorer + CSV Export</h2>
<p>Current: <strong><?= htmlspecialchars(str_replace($root, '', $path) ?: '/') ?></strong></p>
<p><a href="?csv=1">📥 Download Full CSV (Everything)</a></p>

<h3>Folders (<?=count($folders)?>)</h3>
<ul>
<?php foreach($folders as $f): ?>
    <li><?=htmlspecialchars($f['path'])?></li>
<?php endforeach; ?>
</ul>

<h3>Files (<?=count($files)?>)</h3>
<table>
<tr><th>Path</th><th>Size</th><th>Ext</th></tr>
<?php foreach($files as $f): ?>
<tr><td><?=htmlspecialchars($f['path'])?></td><td><?=number_format($f['size'])?></td><td>.<?=htmlspecialchars($f['ext'])?></td></tr>
<?php endforeach; ?>
</table>

<a href="?path=">← Back to Root</a>
</body></html>
EOF