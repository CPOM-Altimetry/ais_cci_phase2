<?php
// stats.php — downloads + sizes (PHP 7.4, SQLite)
// Filters: ?days=7|30|90|365|all (default 30), ?product=<id>

if (!defined('AIS_CCI_APP')) define('AIS_CCI_APP', true);

// Diagnostics toggle
$DEBUG = isset($_GET['debug']) && $_GET['debug'] === '1';
@ini_set('log_errors', '1');
@ini_set('error_log', __DIR__ . '/data/php_error.log');
if ($DEBUG) { error_reporting(E_ALL); @ini_set('display_errors', '1'); } else { error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); }

// Load config
$cfg = require __DIR__ . '/config/downloads.php';
$stats = $cfg['stats'] ?? [];
$products = $cfg['products'] ?? [];

// SQLite availability
$enabled = !empty($stats['enable_db']);
$sqlite  = $stats['sqlite_path'] ?? '';
$dbOk    = $enabled && $sqlite && is_file($sqlite) && is_readable($sqlite);

// Filters
$validDays = ['7','30','90','365','all'];
$daysParam = isset($_GET['days']) ? $_GET['days'] : '30';
if (!in_array($daysParam, $validDays, true)) $daysParam = '30';

$sinceYmd = null;
if ($daysParam !== 'all') {
    $dt = new DateTime('now', new DateTimeZone('UTC'));
    $dt->modify('-'.((int)$daysParam).' days');
    $sinceYmd = $dt->format('Y-m-d');
}

$prodParam = isset($_GET['product']) ? $_GET['product'] : '';
if ($prodParam && !isset($products[$prodParam])) $prodParam = '';

// Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function pdo_or_error($path){
    try { $pdo = new PDO('sqlite:'.$path); $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); return $pdo; }
    catch (Throwable $e) { return $e->getMessage(); }
}
function table_exists(PDO $pdo, $name){
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
    $stmt->execute([$name]);
    return (bool)$stmt->fetchColumn();
}
function format_bytes($bytes){
    $bytes = (float)$bytes;
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units)-1){ $bytes /= 1024; $i++; }
    return sprintf(($i===0?'%d %s':'%.2f %s'), $bytes, $units[$i]);
}

// WHERE + args
$where = []; $args = [];
if ($sinceYmd){ $where[] = "substr(ts,1,10) >= :since"; $args[':since'] = $sinceYmd; }
if ($prodParam){ $where[] = "product_id = :pid"; $args[':pid'] = $prodParam; }
$whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>AIS CCI+ — Download Stats</title>
  <link rel="stylesheet" href="./css/main.css">
  <style>
    .wrap{max-width:1200px;margin:20px auto;padding:0 16px}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin:12px 0 20px}
    .card{border:1px solid #ddd;border-radius:8px;background:#fff}
    .card h4{margin:0;padding:10px 12px;border-bottom:1px solid #eee;background:#fafafa}
    .card .body{padding:10px 12px}
    table.stats{width:100%;border-collapse:collapse}
    table.stats th, table.stats td{padding:8px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}
    table.stats th{background:#fafafa}
    .filters{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:10px 0 16px}
    .filters label{font-weight:600}
    .filters select{height:34px;padding:4px 8px;border:1px solid #ccc;border-radius:4px;background:#fff}
    .muted{color:#666}
    .warn{background:#fff7f0;border:1px solid #ffc391;padding:10px;border-radius:6px}
    .ok{background:#f3fff4;border:1px solid #bfe6c2;padding:10px;border-radius:6px}
    .badge{display:inline-block;border:1px solid #ddd;border-radius:12px;padding:2px 8px;font-size:12px;background:#f7f7f7}
    /* Make the Top Countries card full width */
    .card.countries{ grid-column: 1 / -1; }
    @media (max-width:720px){ .grid{grid-template-columns:1fr} }
  </style>
</head>
<body>
<div class="wrap">
  <h2>AIS CCI+ Phase-2 Download Statistics</h2>

  <?php if (!$enabled): ?>
    <div class="warn"><strong>Statistics disabled.</strong> Set <code>enable_db = true</code> in <code>config/downloads.php</code>.</div>
  <?php elseif (!$sqlite): ?>
    <div class="warn"><strong>No SQLite path set.</strong> Provide <code>sqlite_path</code> in <code>config/downloads.php</code>.</div>
  <?php elseif (!is_file($sqlite)): ?>
    <div class="warn"><strong>Database not found.</strong> Expected at:<br><code><?php echo h($sqlite); ?></code></div>
  <?php elseif (!is_readable($sqlite)): ?>
    <div class="warn"><strong>Database not readable.</strong> Check permissions:<br><code><?php echo h($sqlite); ?></code></div>
  <?php else: ?>

    <?php
    $pdo = pdo_or_error($sqlite);
    if (!($pdo instanceof PDO)) { echo '<div class="warn"><strong>Could not open SQLite DB:</strong> '.h($pdo).'</div>'; echo '</div></body></html>'; exit; }
    if (!table_exists($pdo,'downloads')) { echo '<div class="warn"><strong>No data yet.</strong> The <code>downloads</code> table will be created by <code>fetch.php</code> after the first download.</div>'; echo '</div></body></html>'; exit; }

    // totals
    $qTotal = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(bytes),0) FROM downloads $whereSql");
    $qTotal->execute($args);
    [$totalCnt,$totalBytes] = $qTotal->fetch(PDO::FETCH_NUM);

    $qRange = $pdo->prepare("SELECT MIN(ts), MAX(ts) FROM downloads $whereSql");
    $qRange->execute($args);
    [$minTs,$maxTs] = $qRange->fetch(PDO::FETCH_NUM) ?: [null,null];

    // by product
    $qByProduct = $pdo->prepare("
      SELECT product_id, file_name,
             COUNT(*) AS cnt,
             COALESCE(SUM(bytes),0) AS sum_bytes
      FROM downloads
      $whereSql
      GROUP BY product_id, file_name
      ORDER BY cnt DESC, product_id ASC
    ");
    $qByProduct->execute($args);
    $byProduct = $qByProduct->fetchAll(PDO::FETCH_ASSOC);

    // by country
    $qByCountry = $pdo->prepare("
      SELECT COALESCE(country_iso,'??') AS iso,
             COALESCE(country_name,'Unknown') AS name,
             COUNT(*) AS cnt,
             COALESCE(SUM(bytes),0) AS sum_bytes
      FROM downloads
      $whereSql
      GROUP BY iso, name
      ORDER BY cnt DESC
      LIMIT 25
    ");
    $qByCountry->execute($args);
    $byCountry = $qByCountry->fetchAll(PDO::FETCH_ASSOC);

    // daily trend
    $qDaily = $pdo->prepare("
      SELECT substr(ts,1,10) AS day,
             COUNT(*) AS cnt,
             COALESCE(SUM(bytes),0) AS sum_bytes
      FROM downloads
      $whereSql
      GROUP BY day
      ORDER BY day ASC
    ");
    $qDaily->execute($args);
    $daily = $qDaily->fetchAll(PDO::FETCH_ASSOC);

    // recent
    $qRecent = $pdo->prepare("
      SELECT ts, product_id, file_name, country_iso, country_name, referer, bytes
      FROM downloads
      $whereSql
      ORDER BY ts DESC
      LIMIT 100
    ");
    $qRecent->execute($args);
    $recent = $qRecent->fetchAll(PDO::FETCH_ASSOC);

    $daysLabel = ($daysParam==='all') ? 'All time' : ('Last '.$daysParam.' days');
    ?>

    <!-- Filters -->
    <form class="filters" method="get" action="">
      <label for="days">Range:</label>
      <select id="days" name="days" onchange="this.form.submit()">
        <?php foreach ($validDays as $d): ?>
          <option value="<?php echo h($d); ?>" <?php echo $d===$daysParam?'selected':''; ?>>
            <?php echo h($d==='all'?'All time':"Last $d days"); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="product">Product:</label>
      <select id="product" name="product" onchange="this.form.submit()">
        <option value="">All products</option>
        <?php foreach ($products as $pid=>$meta): ?>
          <option value="<?php echo h($pid); ?>" <?php echo $pid===$prodParam?'selected':''; ?>>
            <?php echo h($pid.' — '.($meta['label'] ?? $meta['file'] ?? '')); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <?php if ($DEBUG): ?><span class="badge">DEBUG ON</span><?php endif; ?>
      <noscript><button type="submit">Apply</button></noscript>
    </form>

    <div class="grid">
      <div class="card">
        <h4>Summary</h4>
        <div class="body">
          <div><strong>Total downloads:</strong> <?php echo number_format((int)$totalCnt); ?></div>
          <div><strong>Total transferred:</strong> <?php echo h(format_bytes($totalBytes)); ?></div>
          <div class="muted">
            Range:
            <?php
              if ($sinceYmd) {
                  $end = $maxTs ? substr($maxTs,0,10) : 'today';
                  echo h($sinceYmd.' → '.$end);
              } else {
                  echo 'All time';
              }
            ?>
          </div>
        </div>
      </div>

      <div class="card countries">
        <h4>Top Countries</h4>
        <div class="body">
          <?php if (!$byCountry): ?>
            <div class="muted">No data.</div>
          <?php else: ?>
            <table class="stats">
              <thead><tr><th>Country</th><th>Downloads</th><th>Bytes</th></tr></thead>
              <tbody>
              <?php foreach ($byCountry as $r): ?>
                <tr>
                  <td><?php echo h($r['name']); ?> <span class="badge"><?php echo h($r['iso']); ?></span></td>
                  <td style="width:110px"><?php echo number_format((int)$r['cnt']); ?></td>
                  <td style="width:160px"><?php echo h(format_bytes($r['sum_bytes'])); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="grid-column:1/-1">
        <h4>Downloads by Product</h4>
        <div class="body">
          <?php if (!$byProduct): ?>
            <div class="muted">No data.</div>
          <?php else: ?>
            <table class="stats">
              <thead><tr><th>Product</th><th>Label</th><th>Downloads</th><th>Bytes</th></tr></thead>
              <tbody>
              <?php foreach ($byProduct as $r):
                $pid = $r['product_id'];
                $label = $products[$pid]['label'] ?? '';
              ?>
                <tr>
                  <td><?php echo h($pid); ?></td>
                  <td><?php echo h($label ?: $r['file_name']); ?></td>
                  <td style="width:110px"><?php echo number_format((int)$r['cnt']); ?></td>
                  <td style="width:160px"><?php echo h(format_bytes($r['sum_bytes'])); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="grid-column:1/-1">
        <h4>Daily trend (<?php echo h($daysLabel); ?>)</h4>
        <div class="body">
          <?php if (!$daily): ?>
            <div class="muted">No data.</div>
          <?php else: ?>
            <table class="stats">
              <thead><tr><th>Date</th><th>Downloads</th><th>Bytes</th></tr></thead>
              <tbody>
              <?php foreach ($daily as $r): ?>
                <tr>
                  <td><?php echo h($r['day']); ?></td>
                  <td style="width:110px"><?php echo number_format((int)$r['cnt']); ?></td>
                  <td style="width:160px"><?php echo h(format_bytes($r['sum_bytes'])); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="grid-column:1/-1">
        <h4>Most recent 100 downloads</h4>
        <div class="body">
          <?php if (!$recent): ?>
            <div class="muted">No data.</div>
          <?php else: ?>
            <table class="stats">
              <thead>
                <tr>
                  <th style="width:170px">Timestamp (UTC)</th>
                  <th>Product</th>
                  <th>Country</th>
                  <th>Size</th>
                  <th>Referrer</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($recent as $r): ?>
                <tr>
                  <td><?php echo h($r['ts']); ?></td>
                  <td><?php echo h($r['product_id']); ?></td>
                  <td><?php
                    $iso = $r['country_iso'] ?: '??';
                    $nm  = $r['country_name'] ?: 'Unknown';
                    echo h($nm).' '; ?><span class="badge"><?php echo h($iso); ?></span>
                  </td>
                  <td><?php echo h(format_bytes($r['bytes'])); ?></td>
                  <td class="muted"><?php echo h($r['referer'] ?: '—'); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- /grid -->

  <?php endif; ?>
</div>
</body>
</html>
