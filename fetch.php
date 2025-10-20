<?php
/**
 * fetch.php — Secure file streamer with CSV/SQLite logging + GeoIP (PHP 7.4)
 *
 * GET params:
 *   id       = product key from config/downloads.php
 *   len=1    = (optional) send Content-Length header
 *   debug=1  = quick health info (no download)
 *   debug=2  = extended debug (paths, client_ip, geoip class, etc.)
 */

if (function_exists('mb_internal_encoding')) { mb_internal_encoding('UTF-8'); }

// --------------------------- Config & paths ---------------------------
$config    = require __DIR__ . '/config/downloads.php';
$baseDir   = rtrim($config['base_dir'] ?? '', '/');
$products  = $config['products'] ?? [];
$ipSalt    = $config['ip_salt'] ?? 'fallback-salt';
$statsCfg  = $config['stats'] ?? [];

$DATA_DIR = __DIR__ . '/data';
if (!is_dir($DATA_DIR)) { @mkdir($DATA_DIR, 0775, true); }
@touch($DATA_DIR . '/php_error.log');
@touch($DATA_DIR . '/errors.log');
@ini_set('log_errors', '1');
@ini_set('error_log', $DATA_DIR . '/php_error.log');
error_reporting(E_ALL);

// Avoid output buffering & compression (streaming)
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
@ini_set('zlib.output_compression', '0');
while (ob_get_level()) { @ob_end_clean(); }

// Toggle Content-Length with ?len=1
$SEND_LENGTH = (isset($_GET['len']) && $_GET['len'] === '1');

// --------------------------- Helpers ---------------------------
function log_line($m){
  @file_put_contents(__DIR__.'/data/errors.log','['.date('c')."] $m\n",FILE_APPEND);
}

function bail($code,$msg,$extra=[]){
  http_response_code($code);
  header('Content-Type: text/plain; charset=UTF-8');
  echo $msg;
  if ($extra) log_line($msg.' :: '.var_export($extra,true));
  exit;
}

/** Return client IP; if REMOTE_ADDR is a trusted proxy, honour first XFF entry */
function client_ip(array $trusted): string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && in_array($ip, $trusted, true)) {
    $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $cand  = trim($parts[0] ?? '');
    if (filter_var($cand, FILTER_VALIDATE_IP)) return $cand;
  }
  return $ip;
}

// --------------------------- Resolve product ---------------------------
$id = $_GET['id'] ?? '';
if (!isset($products[$id])) bail(404,'Unknown product',['id'=>$id]);

$rel   = $products[$id]['file']  ?? '';
$label = $products[$id]['label'] ?? basename($rel);
$req   = $baseDir . '/' . $rel;

$realBase = realpath($baseDir);
$realFile = realpath($req);

if ($realFile === false || !is_file($realFile) || !is_readable($realFile)) {
  bail(404,'File not found',['req'=>$req,'real'=>$realFile]);
}
if ($realBase === false || strpos($realFile, $realBase) !== 0) {
  bail(403,'Access denied (outside base_dir)',['base'=>$realBase,'file'=>$realFile]);
}

// --------------------------- Meta ---------------------------
clearstatcache(true,$realFile);
$bytes = filesize($realFile);
$ua    = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 400);
$ref   = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 400);

$trusted = $statsCfg['trusted_proxies'] ?? [];
$ip      = client_ip($trusted);

// --------------------------- Debug (no download) ---------------------------
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
    echo "client_ip: $ip\n";
    echo "sqlite_enabled: " . (!empty($statsCfg['enable_db']) ? 'true' : 'false') . "\n";
    echo "sqlite_path: " . ($statsCfg['sqlite_path'] ?? '(none)') . "\n";
    echo "geoip_enabled: " . (!empty($statsCfg['enable_geoip']) ? 'true' : 'false') . "\n";
    echo "geoip_mmdb: " . ($statsCfg['geoip_mmdb'] ?? '(none)') . "\n";
    // Which GeoIP class would be used?
    $geoipClass = 'none';
    if (file_exists(__DIR__.'/vendor/autoload.php')) require_once __DIR__.'/vendor/autoload.php';
    elseif (file_exists(__DIR__.'/../vendor/autoload.php')) require_once __DIR__.'/../vendor/autoload.php';
    if (class_exists(\GeoIp2\Database\Reader::class))      $geoipClass = 'GeoIp2\Database\Reader';
    elseif (class_exists(\MaxMind\Db\Reader::class))       $geoipClass = 'MaxMind\Db\Reader';
    echo "geoip_class: $geoipClass\n";
  }
  exit;
}

// --------------------------- Privacy-safe IP hash ---------------------------
if (function_exists('hash_hmac'))      $ipHash = hash_hmac('sha256', $ip, $ipSalt);
elseif (function_exists('sha1'))       $ipHash = sha1($ipSalt.'|'.$ip);
else                                   $ipHash = $ip;

// --------------------------- GeoIP (optional, non-blocking) ---------------------------
$countryIso  = null;
$countryName = null;

if (!empty($statsCfg['enable_geoip']) && !empty($statsCfg['geoip_mmdb'])) {
  try {
    // Composer autoload (either local vendor/ or parent)
    if (file_exists(__DIR__.'/vendor/autoload.php')) {
      require_once __DIR__.'/vendor/autoload.php';
    } elseif (file_exists(__DIR__.'/../vendor/autoload.php')) {
      require_once __DIR__.'/../vendor/autoload.php';
    }

    $mmdb = $statsCfg['geoip_mmdb'];
    if (class_exists(\GeoIp2\Database\Reader::class) && is_readable($mmdb)) {
      // Preferred: GeoIP2 v2.x (you installed this)
      $reader = new \GeoIp2\Database\Reader($mmdb);
      $rec    = $reader->country($ip);
      $reader->close();
      $countryIso  = $rec->country->isoCode ?? null;
      $countryName = $rec->country->name    ?? null;

    } elseif (class_exists(\MaxMind\Db\Reader::class) && is_readable($mmdb)) {
      // Fallback: low-level reader (in case you ever swap packages)
      $reader = new \MaxMind\Db\Reader($mmdb);
      $arr    = $reader->get($ip);
      $reader->close();
      if (is_array($arr)) {
        $countryIso  = $arr['country']['iso_code']
                    ?? ($arr['registered_country']['iso_code'] ?? null);
        $countryName = $arr['country']['names']['en']
                    ?? ($arr['registered_country']['names']['en'] ?? null);
      }
    }
  } catch (\Throwable $e) {
    log_line('geoip fail: '.$e->getMessage());
  } catch (\Exception $e) { // PHP 7.x safety
    log_line('geoip fail: '.$e->getMessage());
  }
}

// --------------------------- CSV logging (non-blocking) ---------------------------
try {
  $csv = $DATA_DIR . '/downloads.csv';
  $fh  = @fopen($csv, 'ab');
  if ($fh) {
    if (flock($fh, LOCK_EX)) {
      fputcsv($fh, [
        date('c'),
        $id,
        basename($realFile),
        $bytes,
        $ipHash,
        $ua,
        $ref,
        $countryIso,
        $countryName
      ]);
      flock($fh, LOCK_UN);
    }
    fclose($fh);
  } else {
    log_line("csv open fail: $csv");
  }
} catch (\Throwable $e) {
  log_line("csv log fail: ".$e->getMessage());
} catch (\Exception $e) {
  log_line("csv log fail: ".$e->getMessage());
}

// --------------------------- SQLite logging (optional) ---------------------------
if (!empty($statsCfg['enable_db']) && !empty($statsCfg['sqlite_path'])) {
  try {
    $pdo = new PDO('sqlite:' . $statsCfg['sqlite_path']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table if needed (cheap if it already exists)
    $pdo->exec("CREATE TABLE IF NOT EXISTS downloads (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      ts TEXT NOT NULL,
      product_id TEXT NOT NULL,
      file_name TEXT NOT NULL,
      bytes INTEGER NOT NULL,
      ip_hash TEXT NOT NULL,
      user_agent TEXT,
      referer TEXT,
      country_iso TEXT,
      country_name TEXT
    )");

    $stmt = $pdo->prepare("INSERT INTO downloads
      (ts, product_id, file_name, bytes, ip_hash, user_agent, referer, country_iso, country_name)
      VALUES (:ts,:pid,:fn,:b,:ip,:ua,:ref,:iso,:name)");

    $stmt->execute([
      ':ts'   => date('c'),
      ':pid'  => $id,
      ':fn'   => basename($realFile),
      ':b'    => $bytes,
      ':ip'   => $ipHash,
      ':ua'   => $ua,
      ':ref'  => $ref,
      ':iso'  => $countryIso,
      ':name' => $countryName,
    ]);

  } catch (\Throwable $e) {
    log_line('sqlite log fail: '.$e->getMessage());
  } catch (\Exception $e) {
    log_line('sqlite log fail: '.$e->getMessage());
  }
}

// --------------------------- Headers ---------------------------
$mime = 'application/x-netcdf';
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($realFile) . '"');
if ($SEND_LENGTH) header('Content-Length: ' . $bytes);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Accept-Ranges: none');

// --------------------------- Stream ---------------------------
$fp = @fopen($realFile, 'rb');
if (!$fp) bail(500,'Unable to open file for reading',['file'=>$realFile]);

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
