<?php
// download.php — list all configured downloads

$dl = require __DIR__ . '/config/downloads.php';
$baseDir  = rtrim($dl['base_dir'] ?? '', '/');
$products = $dl['products'] ?? [];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function format_bytes($bytes){
    $bytes = (float)$bytes;
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units)-1){ $bytes /= 1024; $i++; }
    return sprintf(($i===0?'%d %s':'%.2f %s'), $bytes, $units[$i]);
}

// Build rows with computed metadata
$rows = [];
foreach ($products as $id => $meta) {
    $rel   = $meta['file']  ?? '';
    $label = $meta['label'] ?? basename($rel);
    $abs   = $baseDir . '/' . $rel;

    $exists = is_file($abs) && is_readable($abs);
    $sizeText = $exists ? format_bytes(filesize($abs)) : '—';
    $mtime    = $exists ? date('Y-m-d', filemtime($abs)) : '—';

    $rows[] = [
        'id'     => $id,
        'label'  => $label,
        'exists' => $exists,
        'size'   => $sizeText,
        'mtime'  => $mtime,
    ];
}

// Optional: sort alphabetically by label
usort($rows, function($a,$b){ return strcasecmp($a['label'], $b['label']); });

?>
<h3>Downloads</h3>
<p>You can download the available SEC products below.</p>

<style>
  /* Light table cosmetics (can move to main.css if you prefer) */
  table.downloads{ width:100%; border-collapse:collapse; margin:10px 0 18px; }
  table.downloads th, table.downloads td{ padding:10px; border-bottom:1px solid #eee; text-align:left; }
  table.downloads th{ background:#fafafa; }
  .download-btn{
    display:inline-flex; align-items:center; gap:8px;
    background:#21578b; color:#fff; padding:8px 12px;
    border-radius:6px; text-decoration:none;
  }
  .download-btn .material-icons{ font-size:20px; line-height:1; }
  .muted{ color:#777; }
  .unavail{ color:#a00; font-weight:600; }
</style>

<table class="downloads">
  <thead>
    <tr>
      <th>Product</th>
      <th style="width:140px">Size</th>
      <th style="width:140px">Updated</th>
      <th style="width:160px">Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo h($r['label']); ?></td>
        <td><?php echo h($r['size']); ?></td>
        <td class="muted"><?php echo h($r['mtime']); ?></td>
        <td>
          <?php if ($r['exists']): ?>
            <a class="download-btn" href="fetch.php?id=<?php echo urlencode($r['id']); ?>">
              <span class="material-icons" aria-hidden="true">download</span>
              Download
            </a>
          <?php else: ?>
            <span class="unavail">Unavailable</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php if (empty($rows)): ?>
  <p class="muted">No products are configured yet.</p>
<?php endif; ?>
