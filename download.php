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

// Build rows with computed metadata for RA (Radar Altimetry)
$rows = [];
foreach ($products as $id => $meta) {
    if ($meta['instrument'] != 'RA') continue;
    $rel   = $meta['file']  ?? '';
    $label = $meta['label'] ?? basename($rel);
    $abs   = $baseDir . '/' . $rel;
    $mission = $meta['mission'] ?? '';
    $grid_size = $meta['grid_size'] ?? '';
    $exists = is_file($abs) && is_readable($abs);
    $sizeText = $exists ? format_bytes(filesize($abs)) : '—';
    $mtime    = $exists ? date('Y-m-d', filemtime($abs)) : '—';

    $rows[] = [
        'file'   => $rel,
        'id'     => $id,
        'label'  => $label,
        'exists' => $exists,
        'size'   => $sizeText,
        'mtime'  => $mtime,
        'mission' => $mission,
        'grid_size' => $grid_size,
    ];
}

// Optional: sort alphabetically by label
usort($rows, function($a,$b){ return strcasecmp($a['label'], $b['label']); });

?>
<style>
  /* NEW: Two-column layout for the metadata sections */
  .citation {
    background-color: #D4E4F1;
    border-radius: 5px;
    padding: 10px;
    padding-top: 0px;
  }
  .version {
    background-color: #fafafa;
    border-radius: 5px;
    padding: 10px;
        padding-top: 0px;
  }
  .two-column-section {
    display: flex;
    gap: 30px; /* Space between the two columns */
    margin-bottom: 25px; /* Space before the download table sections */
  }
  .column-item {
    flex: 1; /* Ensures both items take up equal width */
  }
  /* Stack the columns vertically on smaller screens (less than 768px wide) */
  @media (max-width: 768px) {
    .two-column-section {
      flex-direction: column;
      gap: 0;
    }
  }

  /* Light table cosmetics (can move to main.css if you prefer) */
  table.downloads{ width:100%; border-collapse:collapse; margin:10px 0 18px; }
  table.downloads th, table.downloads td{ padding:10px; border-bottom:1px solid #eee; text-align:left; }
  table.downloads th{ background:#fafafa; }
  .download-btn{
    display:inline-flex; align-items:center; gap:8px;
    background:#21578b; color:#fff; padding:8px 12px;
    border-radius:6px; text-decoration:none;
    /* Ensuring the button is always visible */
    white-space: nowrap;
  }
  .download-btn .material-icons{ font-size:20px; line-height:1; }
  .muted{ color:#777; }
  .unavail{ color:#a00; font-weight:600; }

  /* NEW: Style for the small version details table */
  table.version-details {
    width: auto; /* Keeps the table compact */
    border-collapse: collapse;
    margin-top: 5px;
  }
  table.version-details td {
    padding: 5px 10px 5px 0; /* Increased vertical padding */
    border-bottom: 1px solid #eee; /* Added light grey bottom border */
    text-align: left;
  }
  /* Remove border on the last row for cleanliness */
  table.version-details tr:last-child td {
    border-bottom: none;
  }
</style>

<!-- Start of the new two-column section -->
<div class="two-column-section">
  <div class="column-item citation">
    <h4>Dataset Citation</h4>
    <p>This dataset is citable as:  
        Shepherd, A., Gilbert, L., Muir, A. S., Konrad, H., McMillan, M., Slater, T ., et al. (2019). Trends in Antarctic Ice
        Sheet elevation and mass. Geophysical Research Letters, 46, 8174–8183.
        https://doi.org/10.1029/2019GL082182."</p>
  </div>
  <div class="column-item version">
    <h4>Version Details</h4>
    <table class="version-details">
      <tr>
        <td style="font-weight: 600;">Project:</td>
        <td>Antarctic CCI+ Phase-2</td>
      </tr>
      <tr>
        <td style="font-weight: 600;">Release:</td>
        <td>1 (Nov 2025)</td>
      </tr>
      
      <tr>
        <td style="font-weight: 600;">Update Frequency:</td>
        <td>Monthly (note that single mission products from missions that have ended do not change between releases)</td>

      </tr>
    </table>
  </div>
</div>
<!-- End of the new two-column section -->

<h3>Radar Altimetry Mission SEC Downloads (Single Mission)</h3>

<table class="downloads">
  <thead>
    <tr>
      <th style="width:10%">Mission</th>
      <th style="width:10%">Grid Size</th>
      <th style="width:10%">Size</th>
      <th style="width: 50%">File</th>
      <th style="width:15%">Updated</th>
      <th style="width:5%">Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
         <td><?php echo h($r['mission']); ?></td>
         <td><?php echo h($r['grid_size']); ?></td>
        <td><?php echo h($r['size']); ?></td>
        <td><?php echo h(basename($r['file'])); ?></td>
        <td class="muted"><?php echo h($r['mtime']); ?></td>
        <td>
          <?php if ($r['exists']): ?>
            <a class="download-btn" href="fetch.php?id=<?php echo urlencode($r['id']); ?>">
              <span class="material-icons" aria-hidden="true">download</span>
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

<?php
// Build rows with computed metadata for LA (Laser Altimetry)
$rows = [];
foreach ($products as $id => $meta) {
    if ($meta['instrument'] != 'LA') continue;
    $rel   = $meta['file']  ?? '';
    $label = $meta['label'] ?? basename($rel);
    $abs   = $baseDir . '/' . $rel;
    $mission = $meta['mission'] ?? '';
    $grid_size = $meta['grid_size'] ?? '';
    $exists = is_file($abs) && is_readable($abs);
    $sizeText = $exists ? format_bytes(filesize($abs)) : '—';
    $mtime    = $exists ? date('Y-m-d', filemtime($abs)) : '—';

    $rows[] = [
        'file'   => $rel,
        'id'     => $id,
        'label'  => $label,
        'exists' => $exists,
        'size'   => $sizeText,
        'mtime'  => $mtime,
        'mission' => $mission,
        'grid_size' => $grid_size,
    ];
}

// Optional: sort alphabetically by label
usort($rows, function($a,$b){ return strcasecmp($a['label'], $b['label']); });
?>

<h3>Laser Altimetry Mission SEC Downloads</h3>

<table class="downloads">
  <thead>
    <tr>
      <th style="width:10%">Mission</th>
      <th style="width:10%">Grid Size</th>
      <th style="width:10%">Size</th>
      <th style="width: 50%">File</th>
      <th style="width:15%">Updated</th>
      <th style="width:5%">Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
         <td><?php echo h($r['mission']); ?></td>
         <td><?php echo h($r['grid_size']); ?></td>
        <td><?php echo h($r['size']); ?></td>
        <td><?php echo h(basename($r['file'])); ?></td>
        <td class="muted"><?php echo h($r['mtime']); ?></td>
        <td>
          <?php if ($r['exists']): ?>
            <a class="download-btn" href="fetch.php?id=<?php echo urlencode($r['id']); ?>">
              <span class="material-icons" aria-hidden="true">download</span>
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
