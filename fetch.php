<?php
// fetch.php — PHP 7.4 compatible download endpoint with optional SQLite logging
// Works with your existing config/downloads.php structure

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
@ini_set('display_errors', '0');

// --------- Load config ----------
$cfg      = require __DIR__ . '/config/downloads.php';
$baseDir  = rtrim((string)($cfg['base_dir'] ?? ''), '/');
$products = (array)($cfg['products'] ?? []);
$statsCfg = (array)($cfg['stats'] ?? []);
$logEnabled = !empty($statsCfg['enable_db']);
$sqlitePath = (string)($statsCfg['sqlite_path'] ?? '');

// --------- Helpers ----------
function hfn($s) { // sanitize name for Content-Disposition
    $s = preg_replace('/[^\w.\-()+\[\] ]+/u', '_', (string)$s);
    return $s !== '' ? $s : 'download.bin';
}
function realp($p) { $r = realpath($p); return $r !== false ? $r : ''; }
function http_error($code, $msg) {
    http_response_code($code);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $msg;
    exit;
}
function client_ip() {
    // Keep it simple; don’t trust forwarded headers blindly in generic code
    return $_SERVER['REMOTE_ADDR'] ?? '';
}
function cf_country() {
    // If behind Cloudflare, this header is present (2-letter ISO)
    $iso = strtoupper(trim($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''));
    return preg_match('/^[A-Z]{2}$/', $iso) ? $iso : '';
}

// --------- Validate request ----------
$id = isset($_GET['id']) ? (string)$_GET['id'] : '';
if ($id === '' || !isset($products[$id])) {
    http_error(404, 'Unknown download id.');
}

$meta = (array)$products[$id];
$rel  = (string)($meta['file'] ?? '');
if ($rel === '') {
    http_error(404, 'Product file not configured.');
}

$absRequested = $baseDir . '/' . $rel;
$abs          = realp($absRequested);
$baseReal     = realp($baseDir);

// Containment check: downloaded file must live under base_dir
if ($abs === '' || $baseReal === '' || strpos($abs, $baseReal) !== 0) {
    http_error(404, 'File not accessible.');
}
if (!is_file($abs) || !is_readable($abs)) {
    http_error(404, 'File unavailable.');
}

// Nice filename in header
$downloadName = hfn(basename($rel));

// Guess MIME (fallback)
$mime = 'application/octet-stream';
if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) {
        $m = finfo_file($fi, $abs);
        if (is_string($m) && $m !== '') $mime = $m;
        finfo_close($fi);
    }
}

// --------- Stats logging (optional) ----------
if ($logEnabled && $sqlitePath !== '') {
    try {
        if (is_file($sqlitePath) || is_dir(dirname($sqlitePath))) {
            $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Create table if missing — matches what stats.php expects
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS downloads (
                  id INTEGER PRIMARY KEY AUTOINCREMENT,
                  ts TEXT NOT NULL,
                  product_id TEXT,
                  file_name TEXT,
                  bytes INTEGER,
                  ip TEXT,
                  country_iso TEXT,
                  country_name TEXT,
                  referer TEXT
                );
            ");

            $stmt = $pdo->prepare("
                INSERT INTO downloads (ts, product_id, file_name, bytes, ip, country_iso, country_name, referer)
                VALUES (:ts, :pid, :file, :bytes, :ip, :iso, :name, :ref)
            ");

            $iso = cf_country();            // e.g., "US" if behind Cloudflare; else ''
            $name = null;                   // optional: leave NULL; stats.php COALESCEs to 'Unknown'
            $ref = isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : null;

            $stmt->execute([
                ':ts'    => gmdate('Y-m-d H:i:s'),      // UTC
                ':pid'   => $id,
                ':file'  => basename($rel),
                ':bytes' => (int)@filesize($abs),
                ':ip'    => client_ip(),
                ':iso'   => $iso ?: null,
                ':name'  => $name,
                ':ref'   => $ref,
            ]);
        }
    } catch (Throwable $e) {
        // Fail silently: downloads must still work
        // If you want to debug, write to a log file here.
        // @error_log('fetch.php log error: ' . $e->getMessage());
    }
}

// --------- Send file ----------
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', 'Off');
// Remove any accidental output buffers to avoid corrupting the file
while (ob_get_level() > 0) { @ob_end_clean(); }

$size = @filesize($abs);

header('Content-Type: ' . $mime);
if ($size !== false) header('Content-Length: ' . (string)$size);
header('Content-Disposition: attachment; filename="' . $downloadName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));

header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600, must-revalidate');
header('Accept-Ranges: none');

// If you have X-Sendfile enabled in your web server, you can use it instead:
// header('X-Sendfile: ' . $abs);
// exit;

$fp = fopen($abs, 'rb');
if (!$fp) http_error(404, 'Could not open file.');
$chunk = 1048576; // 1MB
while (!feof($fp)) {
    $buf = fread($fp, $chunk);
    if ($buf === false) break;
    echo $buf;
    @flush();
}
fclose($fp);
exit;
