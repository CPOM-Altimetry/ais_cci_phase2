<?php
// stats.php — simple download analytics (PHP 7.4, SQLite)
//
// Filters (optional):
//   ?days=7|30|90|365|all     (default 30)
//   ?product=<product_id>     (default all)
// ------------------------------------------------------------------

if (!defined('AIS_CCI_APP')) define('AIS_CCI_APP', true); // plays nice with guarded configs
$config = require __DIR__ . '/config/downloads.php';
$stats  = $config['stats'] ?? [];

$cssPath = './css/main.css'; // adjust if needed

// -------- guard: DB available? --------
$enabled = !empty($stats['enable_db']);
$sqlite  = $stats['sqlite_path'] ?? '';
$dbOk    = $enabled && $sqlite && is_file($sqlite) && is_readable($sqlite);

// -------- filters --------
$validDays = ['7','30','90','365','all'];
$daysParam = isset($_GET['days']) ? $_GET['days'] : '30';
if (!in_array($daysParam, $validDays, true)) $daysParam = '30';

$sinceYmd = null;
if ($daysParam !== 'all') {
    $dt = new DateTime('now', new DateTimeZone('UTC'));
    $dt->modify('-'.((int)$daysParam).' days');
    $sinceYmd = $dt->format('Y-m-d');
}

$products = $config['products'] ?? [];
$prodParam = isset($_GET['product']) ? $_GET['product'] : '';
if ($prodParam && !isset($products[$prodParam])) $prodParam = ''; // unknown -> all

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>AIS CCI+ — Download Stats</title>
  <link rel="stylesheet" href="<?php echo h($cssPath); ?>">
  <style>
    .stats-wrap{max-width:1200px;margin:20px auto;padding:0 16px}
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin:12px 0 20px}
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
    .badge{display:inline-block;border:1px solid #ddd;border-radius:12px;padding:2px 8px;font-size:12px;background:#f7f7f7}
    .warn{background:#fff7f0;border:1px solid #ffc391;padding:10px;border-radius:6px}
    .ok{background:#f3fff4;border:1px solid #bfe6c2;padding:10px;border-radius:6px}
    @media (max-width:720px){ .stats-grid{grid-template-columns:1fr} }
  </style>
</head>
<body>
  <div class="stats-wrap">
    <h2>Download Statistics</h2>

    <?php if (!$dbOk): ?>
      <div class="warn">
        <strong>Statistics database not available.</strong><br>
        Please ensure <code>enable_db = true</code> and the file exists &amp; is readable:<br>
        <code><?php echo h($sqlite ?: '(not set)'); ?></code>
      </div>
    <?php else: ?>

      <?php
      // ---------- open DB ----------
      try {
          $pdo = new PDO('sqlite:'.$sqlite);
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      } catch (Throwable $e) {
          echo '<div class="warn">Unable to open SQLite: '.h($e->getMessage()).'</div>';
          exit;
      }

      // Base WHERE
      $where = [];
      $args  = [];
      if ($sinceYmd) {
          $where[] = "substr(ts,1,10) >= :since";
          $args[':since'] = $sinceYmd;
      }
      if ($prodParam) {
          $where[] = "product_id = :pid";
          $args[':pid'] = $prodParam;
      }
      $whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

      // ---------- headline metrics ----------
      $total = (int)$pdo->prepare("SELECT COUNT(*) FROM downloads $whereSql")
                        ->execute($args) ? (int)$pdo->query("SELECT COUNT(*) FROM downloads $whereSql", PDO::FETCH_COLUMN, 0) : 0;

      // Because PDO::query twice with WHERE won't keep binds, do proper prepared:
      $stmt = $pdo->prepare("SELECT COUNT(*) FROM downloads $whereSql");
      $stmt->execute($args);
      $total = (int)$stmt->fetchColumn();

      $stmt = $pdo->prepare("SELECT MIN(ts), MAX(ts) FROM downloads $whereSql");
      $stmt->execute($args);
      [$minTs,$maxTs] = $stmt->fetch(PDO::FETCH_NUM) ?: [null,null];

      // ---------- by product ----------
      $stmt = $pdo->prepare("
        SELECT product_id, file_name, COUNT(*) AS cnt
        FROM downloads
        $whereSql
        GROUP BY product_id, file_name
        ORDER BY cnt DESC, product_id ASC
      ");
      $stmt->execute($args);
      $byProduct = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // ---------- by country (top 25) ----------
      $stmt = $pdo->prepare("
        SELECT COALESCE(country_iso,'??') AS iso,
               COALESCE(country_name,'Unknown') AS name,
               COUNT(*) AS cnt
        FROM downloads
        $whereSql
        GROUP BY iso, name
        ORDER BY cnt DESC
        LIMIT 25
      ");
      $stmt->execute($args);
      $byCountry = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // ---------- daily trend (last N days) ----------
      $stmt = $pdo->prepare("
        SELECT substr(ts,1,10) AS day, COUNT(*) AS cnt
        FROM downloads
        $whereSql
        GROUP BY day
        ORDER BY day ASC
      ");
      $stmt->execute($args);
      $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // ---------- recent downloads ----------
      $stmt = $pdo->prepare("
        SELECT ts, product_id, file_name, country_iso, country_name, referer
        FROM downloads
        $whereSql
        ORDER BY ts DESC
        LIMIT 100
      ");
      $stmt->execute($args);
      $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // helper to days label
      function days_label($d){ return $d==='all' ? 'All time' : "Last $d days"; }
      ?>

      <!-- Filters -->
      <form class="filters" method="get" action="">
        <label f
