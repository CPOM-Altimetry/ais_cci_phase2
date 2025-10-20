<?php
/* PHP 5.x–compatible download proxy with CSV logging + debug mode */
mb_internal_encoding('UTF-8');

/* ===== Config ===== */
$config   = require __DIR__ . '/config/downloads.php';
$baseDir  = rtrim(isset($config['base_dir']) ? $config['base_dir'] : '', '/');
$products = isset($config['products']) ? $config['products'] : array();
$ipSalt   = isset($config['ip_salt']) ? $config['ip_salt'] : 'fallback-salt';

/* ===== Error logging to local file (never rely on php.ini) ===== */
if (!is_dir(__DIR__ . '/data')) { @mkdir(__DIR__ . '/data', 0775, true); }
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/data/php_error.log');
error_reporting(E_ALL);

/* ===== Simple helpers ===== */
function bail($code, $msg, $detailArr) {
    http_response_code($code);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $msg;
    @file_put_contents(__DIR__ . '/data/errors.log',
        '['.date('c').'] '.$msg.' :: '.json_encode($detailArr)."\n",
        FILE_APPEND
    );
    exit;
}

/* ===== Resolve product ===== */
$id = isset($_GET['id']) ? $_GET['id'] : '';
if (!isset($products[$id])) {
    bail(404, 'Unknown product', array('id' => $id));
}
$rel   = isset($products[$id]['file'])  ? $products[$id]['file']  : '';
$label = isset($products[$id]['label']) ? $products[$id]['label'] : basename($rel);
$path  = $baseDir . '/' . $rel;

$realBase = realpath($baseDir);
$realFile = realpath($path);

if ($realFile === false || !is_file($realFile) || !is_readable($realFile)) {
    bail(404, 'File not found', array('requested' => $path, 'realFile' => $realFile));
}
if ($realBase === false || strpos($realFile, $realBase) !== 0) {
    bail(403, 'Access denied (outside base_dir)', array('realBase' => $realBase, 'realFile' => $realFile));
}

/* ===== Meta + CSV log (SQLite optional later) ===== */
clearstatcache(true, $realFile);
$bytes = filesize($realFile);
$ua    = substr(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '', 0, 400);
$ref   = substr(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', 0, 400);
$ip    = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
$ipHash = hash_hmac('sha256', $ip, $ipSalt);

/* Debug dump if requested (before headers) */
if (isset($_GET['debug'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $out = array(
        'ok'            => true,
        'id'            => $id,
        'file_exists'   => is_file($realFile),
        'file_readable' => is_readable($realFile),
        'bytes'         => $bytes,
    );
    if ($_GET['debug'] === '2') {
        $out['realBase'] = $realBase;
        $out['realFile'] = $realFile;
    }
    echo json_encode($out);
    exit;
}

/* CSV logging (never block the download on failure) */
try {
    $csv = __DIR__ . '/data/downloads.csv';
    $fh = @fopen($csv, 'ab');
    if ($fh) {
        if (flock($fh, LOCK_EX)) {
            fputcsv($fh, array(date('c'), $id, basename($realFile), $bytes, $ipHash, $ua, $ref));
            flock($fh, LOCK_UN);
        }
        fclose($fh);
    } else {
        @file_put_contents(__DIR__ . '/data/errors.log',
            '['.date('c')."] csv open fail: $csv\n", FILE_APPEND);
    }
} catch (Exception $e) {
    @file_put_contents(__DIR__ . '/data/errors.log',
        '['.date('c')."] csv log fail: ".$e->getMessage()."\n", FILE_APPEND);
}

/* ===== Stream file ===== */
$mime = 'application/x-netcdf';
if (function_exists('finfo_open')) {
    $fi = @finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) {
        $det = @finfo_file($fi, $realFile);
        if ($det) { $mime = $det; }
        @finfo_close($fi);
    }
}

/* Clear any existing buffers to avoid "headers already sent" + memory spikes */
while (ob_get_level()) { ob_end_clean(); }

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($realFile) . '"');
header('Content-Length: ' . $bytes);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Accept-Ranges: none');

$fp = @fopen($realFile, 'rb');
if ($fp === false) {
    bail(500, 'Unable to open file for reading', array('realFile' => $realFile));
}
ignore_user_abort(true);
set_time_limit(0);
$chunk = 8192;
while (!feof($fp)) {
    $buf = fread($fp, $chunk);
    if ($buf === false) { break; }
    echo $buf;
    @flush();
}
fclose($fp);
exit;
