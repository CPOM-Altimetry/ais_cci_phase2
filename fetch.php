<?php
declare(strict_types=1);
mb_internal_encoding('UTF-8');

/* ================== CONFIG ================== */
$config   = require __DIR__ . '/config/downloads.php';
$baseDir  = rtrim($config['base_dir'] ?? '', '/');
$products = $config['products'] ?? [];
$ipSalt   = $config['ip_salt'] ?? 'fallback-salt';

/* ================== DEBUG + LOGGING ================== */
/* Write PHP errors here so they never vanish into Apache logs */
@mkdir(__DIR__ . '/data', 0775, true);
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/data/php_error.log');
error_reporting(E_ALL);

/* Collect debug info (printed only with ?debug=1) */
$DEBUG = [
  'ok' => false,
  'step' => 'start',
  'id' => $_GET['id'] ?? null,
  // don’t expose absolute paths unless debug=2
  'file_exists' => null,
  'file_readable' => null,
  'bytes' => null,
  'headers_sent' => null,
  'ready_to_stream' => false,
];

/* Helper: bail with HTTP code and log */
function bail(int $code, string $msg, array $debug = []): void {
    http_response_code($code);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $msg;
    @file_put_contents(__DIR__ . '/data/errors.log',
        '['.date('c')."] $msg :: " . json_encode($debug) . "\n",
        FILE_APPEND
    );
    exit;
}

/* ================== RESOLVE PRODUCT ================== */
$DEBUG['step'] = 'resolve_id';
$id = $_GET['id'] ?? '';
if (!isset($products[$id])) {
    $DEBUG['ok'] = false;
    bail(404, 'Unknown product', ['id' => $id]);
}
$rel   = $products[$id]['file'] ?? '';
$label = $products[$id]['label'] ?? basename($rel);
$path  = $baseDir . '/' . $rel;

$DEBUG['step'] = 'realpath';
$realBase = realpath($baseDir) ?: '';
$realFile = realpath($path) ?: '';
$DEBUG['file_exists']   = is_file($realFile);
$DEBUG['file_readable'] = is_readable($realFile);

if (!$DEBUG['file_exists'] || !$DEBUG['file_readable']) {
    bail(404, 'File not found', ['realFile' => $realFile, 'exists' => $DEBUG['file_exists'], 'readable' => $DEBUG['file_readable']]);
}
if ($realBase === '' || strpos($realFile, $realBase) !== 0) {
    bail(403, 'Access denied (outside base)', ['realBase' => $realBase, 'realFile' => $realFile]);
}

/* ================== GATHER META ================== */
$DEBUG['step']  = 'stat';
clearstatcache(true, $realFile);
$bytes          = filesize($realFile);
$DEBUG['bytes'] = $bytes;

$ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 400);
$ref = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 400);
$ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ipHash = hash_hmac('sha256', $ip, $ipSalt);

/* ================== LIGHTWEIGHT LOG (CSV) ================== */
$DEBUG['step'] = 'csv_log';
try {
    $csv = __DIR__ . '/data/downloads.csv';
    $fh = fopen($csv, 'ab');
    if ($fh) {
        if (flock($fh, LOCK_EX)) {
            fputcsv($fh, [date('c'), $id, basename($realFile), $bytes, $ipHash, $ua, $ref]);
            flock($fh, LOCK_UN);
        }
        fclose($fh);
    } else {
        @file_put_contents(__DIR__ . '/data/errors.log',
            '['.date('c')."] csv open fail: $csv\n", FILE_APPEND);
    }
} catch (Throwable $e) {
    @file_put_contents(__DIR__ . '/data/errors.log',
        '['.date('c')."] csv log fail: ".$e->getMessage()."\n", FILE_APPEND);
    // carry on
}

/* ================== OPTIONAL DEBUG OUTPUT ================== */
if (isset($_GET['debug'])) {
    header('Content-Type: application/json; charset=UTF-8');
    if ($_GET['debug'] === '2') {
        // show absolute paths only if debug=2
        $DEBUG['realBase'] = $realBase;
        $DEBUG['realFile'] = $realFile;
    }
    $DEBUG['ok'] = true;
    echo json_encode($DEBUG, JSON_PRETTY_PRINT);
    exit;
}

/* ================== STREAM FILE ================== */
$DEBUG['step'] = 'stream_prep';
$mime = 'application/x-netcdf';
if (function_exists('finfo_open')) {
    $fi = @finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) {
        $det = @finfo_file($fi, $realFile);
        if ($det) { $mime = $det; }
        @finfo_close($fi);
    }
}

/* Prevent stray output */
while (ob_get_level()) { ob_end_clean(); }

$DEBUG['headers_sent'] = headers_sent();
if ($DEBUG['headers_sent']) {
    @file_put_contents(__DIR__ . '/data/errors.log',
        '['.date('c')."] headers already sent before streaming\n", FILE_APPEND);
}

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($realFile) . '"');
header('Content-Length: ' . $bytes);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Accept-Ranges: none');

$DEBUG['ready_to_stream'] = true;

/* Robust chunked streaming to avoid memory spikes */
$DEBUG['step'] = 'streaming';
$fp = @fopen($realFile, 'rb');
if ($fp === false) {
    bail(500, 'Unable to open file for reading', ['realFile' => $realFile]);
}

ignore_user_abort(true);
$chunk = 8192;
set_time_limit(0);
while (!feof($fp)) {
    $buf = fread($fp, $chunk);
    if ($buf === false) { break; }
    echo $buf;
    // Flush to client
    if (function_exists('fastcgi_finish_request')) {
        // not strictly necessary here, but harmless
    }
    @flush();
}
fclose($fp);
exit;
