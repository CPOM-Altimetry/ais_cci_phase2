<?php
$dl = require __DIR__ . '/config/downloads.php';
$p  = $dl['products']['cs2_fv2'];
?>
<h3>Downloads</h3>

<p>You can download the latest CryoSat-2 SEC product below.</p>

<p>
  <a class="download-btn" href="fetch.php?id=cs2_fv2">
    <span class="material-icons" aria-hidden="true">download</span>
    <?php echo htmlspecialchars($p['label'], ENT_QUOTES); ?>
  </a>
</p>

<!-- Optional: show file size (fast check via PHP once at render time) -->
<?php
$abs = rtrim($dl['base_dir'],'/') . '/' . $p['file'];
if (is_file($abs)) {
  $mb = number_format(filesize($abs) / (1024*1024), 1);
  echo "<p style='color:#555; font-size:0.95em;'>Size: {$mb} MB</p>";
}
?>
