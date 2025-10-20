<?php
/* Robust downloader: CSV logging, safe headers, debug mode.
   Works behind Apache even with compression/WAF quirks. */

mb_internal_encoding('UTF-8');

/* ================== CONFIG ================== */
$config   = require __DIR__ . '/config/downloads.php';
$baseDir  = rtrim(isset($config['base_dir']) ? $config['base_dir'] : '', '/');
$products = isset($config['products']) ? $config['products'] : array();
$ipSalt   = isset($config['ip_salt']) ? $config['ip_salt'] : 'fallback-salt';

/* ================== ERROR LOGGING ================== */
$DATA_DIR = __DIR__ . '/data';
if (!is_dir($DATA_DIR)) { @mkdir($DATA_DIR, 0775, true); }
ini_set('log_errors', '1');
ini_set('error_log', $DATA_DIR . '/php_error.log');
error_reporting(E_ALL);

/* ================== RUNTIME SAFETY ================== */
// kill compression & buffering; avoids 500s from length mismatch
@apache_setenv('no-gzip', '1');
@ini_set('zlib.output_compression', '0');
while (ob_get_level()) { ob_end_clean(); }

/* toggle sending Content-Length: default OFF, enable with ?len=1 */
$SEND_LENGTH = (isset($_GET['len']) && $_GET['len'] === '1');

/* helper */
function bail($code, $msg, $extra = array()) {
  http_response_code($code);
  header('Content-Type: text/plain; charset=UTF-8');
  echo $msg;
  @file_put_contents(__DIR__ . '/data/errors.log',
    '['.date('c')."] $msg :: ".json_encode($extra)."\n", FILE_APPEND);
  exit;
}

/* ================== RESOLVE PRODUCT ================== */
$id = isset($_GET['id']) ? $_GET['id'] : '';
if (!isset($products[$id])) {
  bail(404, 'Unknown product', array('id'=>$id));
}
$rel   = isset($products[$id]['file'])  ? $products[$id]['file']  : '';
$label = isset($products[$id]['label']) ? $products[$id]['label'] : basename($rel);
$req   = $baseDir . '/' . $rel;

$realBase = realpath($baseDir);
$realFile = realpath($req);

if ($realFile === false || !is_file($realFile) || !is_readable($realFile)) {
  bail(404, 'File not found', array('req'=>$req, 'real'=>$realFile));
}
if ($realBase === false || strpos($realFile, $realBase) !== 0) {
  bail(403, 'Access denied (outside base_dir)', array('base'=>$realBase, 'file'=>$realFile));
}

/* ================== META + DEBUG ================== */
clearstatcache(true, $realFile);
$bytes = filesize($realFile);
$ua    = substr(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '', 0, 400);
$ref   = substr(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', 0, 400);
$ip    = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
$ipHash = hash_hmac('sha256', $ip, $ipSalt);

/* quick debug before headers */
if (isset($_GET['debug'])) {
  header('Content-Type: application/json; charset=UTF-8');
  $out = array(
    'ok' => true,
    'id' => $id,
    'file_exists' => is_file($realFile),
    'file_readable' => is_readable($realFile),
    'bytes' => $bytes,
    'send_length' => $SEND_LENGTH,
  );
  if ($_GET['debug'] === '2') { $out['realFile'] = $realFile; $out['realBase'] = $realBase; }
  echo json_encode($out, JSON_PRETTY_PRINT);
  exit;
}

/* ================== CSV LOGGING (non-blocking) ================== */
try {
  $csv = $DATA_DIR . '/downloads.csv';
  $fh  = @fopen($csv, 'ab');
  if ($fh) {
    if (flock($fh, LOCK_EX)) {
      fputcsv($fh, array(date('c'), $id, basename($realFile), $bytes, $ipHash, $ua, $ref));
      flock($fh, LOCK_UN);
    }
    fclose($fh);
  } else {
    @file_put_contents($DATA_DIR . '/errors.log',
      '['.date('c')."] csv open fail: $csv\n", FILE_APPEND);
  }
} catch (Exception $e) {
  @file_put_contents($DATA_DIR . '/errors.log',
    '['.date('c')."] csv log fail: ".$e->getMessage()."\n", FILE_APPEND);
}

/* ================== HEADERS ================== */
// conservative type (switch to finfo if you want)
$mime = 'application/x-netcdf';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($realFile) . '"');
if ($SEND_LENGTH) {
  // Only enable once you’ve verified no gzip/proxy mangling
  header('Content-Length: ' . $bytes);
}
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Accept-Ranges: none');

/* ================== STREAM ================== */
$fp = @fopen($realFile, 'rb');
if (!$fp) {
  bail(500, 'Unable to open file for reading', array('file'=>$realFile));
}
ignore_user_abort(true);
set_time_limit(0);

$chunk = 8192;
while (!feof($fp)) {
  $buf = fread($fp, $chunk);
  if ($buf === false) break;
  echo $buf;
  @flush();
}
fclose($fp);
exit;
