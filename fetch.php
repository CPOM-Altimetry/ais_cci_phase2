<?php
/* Robust downloader: plaintext debug, CSV logging, guarded env/mbstring calls */

if (function_exists('mb_internal_encoding')) {
  mb_internal_encoding('UTF-8');
}

/* ---------- config ---------- */
$config   = require __DIR__ . '/config/downloads.php';
$baseDir  = rtrim(isset($config['base_dir']) ? $config['base_dir'] : '', '/');
$products = isset($config['products']) ? $config['products'] : array();
$ipSalt   = isset($config['ip_salt']) ? $config['ip_salt'] : 'fallback-salt';

/* ---------- error logging ---------- */
$DATA_DIR = __DIR__ . '/data';
if (!is_dir($DATA_DIR)) { @mkdir($DATA_DIR, 0775, true); }
@ini_set('log_errors', '1');
@ini_set('error_log', $DATA_DIR . '/php_error.log');
error_reporting(E_ALL);

/* ---------- runtime safety ---------- */
if (function_exists('apache_setenv')) {
  @apache_setenv('no-gzip', '1'); // only if Apache module
}
@ini_set('zlib.output_compression', '0');
while (ob_get_level()) { ob_end_clean(); }

/* toggle Content-Length with ?len=1 */
$SEND_LENGTH = (isset($_GET['len']) && $_GET['len'] === '1');

/* helpers */
function log_line($msg) {
  @file_put_contents(__DIR__ . '/data/errors.log', '['.date('c')."] $msg\n", FILE_APPEND);
}
function bail($code, $msg, $extra = array()) {
  http_response_code($code);
  header('Content-Type: text/plain; charset=UTF-8');
  echo $msg;
  if (!empty($extra)) {
    $dump = var_export($extra, true);
    log_line("$msg :: $dump");
  } else {
    log_line($msg);
  }
  exit;
}

/* ---------- resolve product ---------- */
$id = isset($_GET['id']) ? $_GET['id'] : '';
if (!isset($products[$id])) bail(404, 'Unknown product', array('id'=>$id));

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

/* ---------- meta ---------- */
clearstatcache(true, $realFile);
$bytes = filesize($realFile);
$ua    = substr(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '', 0, 400);
$ref   = substr(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', 0, 400);
$ip    = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

/* ---------- plaintext debug BEFORE hashing/logging ---------- */
if (isset($_GET['debug'])) {
  header('Content-Type: text/plain; charset=UTF-8');
  echo "ok: yes\n";
  echo "id: $id\n";
  echo "file_exists: " . (is_file($realFile) ? "true" : "false") . "\n";
  echo "file_readable: " . (is_readable($realFile) ? "true" : "false") . "\n";
  echo "bytes: $bytes\n";
  echo "send_length: " . ($SEND_LENGTH ? "true" : "false") . "\n";
  if ($_GET['debug'] === '2') {
    echo "realBase: $realBase\n";
    echo "realFile: $realFile\n";
  }
  exit;
}

/* ---------- privacy-safe IP hash with fallback ---------- */
if (function_exists('hash_hmac')) {
  $ipHash = hash_hmac('sha256', $ip, $ipSalt);
} elseif (function_exists('sha1')) {
  $ipHash = sha1($ipSalt . '|' . $ip);
} else {
  $ipHash = $ip; // last resort
}

/* ---------- CSV logging (non-blocking) ---------- */
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
    log_line("csv open fail: $csv");
  }
} catch (Exception $e) {
  log_line("csv log fail: " . $e->getMessage());
}

/* ---------- headers ---------- */
$mime = 'application/x-netcdf';  // conservative to avoid WAF quirks
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($realFile) . '"');
if ($SEND_LENGTH) header('Content-Length: ' . $bytes);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Accept-Ranges: none');

/* ---------- stream ---------- */
$fp = @fopen($realFile, 'rb');
if (!$fp) bail(500, 'Unable to open file for reading', array('file'=>$realFile));
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
