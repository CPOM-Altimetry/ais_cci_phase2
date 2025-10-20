<?php
$dbPath = __DIR__ . '/data/downloads.sqlite';
if (!is_file($dbPath)) { echo "No downloads yet."; exit; }

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$byProd = $pdo->query("
  SELECT product_id, COUNT(*) AS cnt, SUM(bytes) AS total_bytes
  FROM downloads
  GROUP BY product_id
  ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

$recent = $pdo->query("
  SELECT ts, product_id, filename, bytes
  FROM downloads
  ORDER BY id DESC
  LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);
?>
<h3>Download statistics</h3>

<h4>Totals by product</h4>
<table border="1" cellpadding="6" cellspacing="0">
<tr><th>Product</th><th>Downloads</th><th>Total GB</th></tr>
<?php foreach ($byProd as $r): ?>
<tr>
  <td><?= htmlspecialchars($r['product_id']) ?></td>
  <td><?= (int)$r['cnt'] ?></td>
  <td><?= number_format(($r['total_bytes']??0)/(1024*1024*1024), 3) ?></td>
</tr>
<?php endforeach; ?>
</table>

<h4>Most recent 50</h4>
<table border="1" cellpadding="6" cellspacing="0">
<tr><th>Time (UTC)</th><th>Product</th><th>File</th><th>MB</th></tr>
<?php foreach ($recent as $r): ?>
<tr>
  <td><?= htmlspecialchars($r['ts']) ?></td>
  <td><?= htmlspecialchars($r['product_id']) ?></td>
  <td><?= htmlspecialchars($r['filename']) ?></td>
  <td><?= number_format(($r['bytes']??0)/(1024*1024), 1) ?></td>
</tr>
<?php endforeach; ?>
</table>
