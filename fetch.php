<?php
declare(strict_types=1);
mb_internal_encoding('UTF-8');

$config   = require __DIR__ . '/config/downloads.php';
$baseDir  = rtrim($config['base_dir'] ?? '', '/');
$products = $config['products'] ?? [];

/* ---------- helpers ---------- */
function bail(int $code, string $msg, ?string $detail = null): void {
    http_response_code($code);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $msg;
    if ($detail) {
        @file_put_contents(__DIR__ . '/data/errors.log',
            '['.date('c')."] $msg :: $detail\n",
            FILE_APPEND);
    }
    exit;
}

/* ---------- resolve product ---------- */
$id = $_GET['id'] ?? '';
if (!isset($products[$id])) {
    bail(404, 'Unknown product', "id=$id");
}

$rel   = $products[$id]['file'] ?? '';
$label = $products[$id]['label'] ?? basename($rel);
$path  = $baseDir . '/' . $rel;

$realBase = realpath($baseDir) ?: '';
$realFile = realpath($path) ?: '';

if (!$realFile || !is_file($realFile) || !is_readable($realFile)) {
    bail(404, 'File not found', $path);
}
if ($realBase === '' || strpos($realFile, $realBase) !== 0) {
    bail(403, 'Access denied', "outside base: $realFile");
}

/* ---------- gather meta ---------- */
$bytes = filesize($realFile);
$ua    = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 400);
$ref   = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 400);

/* ---------- ensure data dir ---------- */
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) { @mkdir($dataDir, 0775, true); }

/* ---------- try SQLite logging, else CSV ---------- */
$logged = false;
try {
    if (in_array('sqlite', PDO::getAvailableDrivers(), true) || in_array('sqlite3', PDO::getAvailableDrivers(), true)) {
        $pdo = new PDO('sqlite:' . $dataDir . '/downloads.sqlite', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS downloads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ts TEXT NOT NULL,
                product_id TEXT NOT NULL,
                filename TEXT NOT NULL,
                bytes INTEGER NOT NULL,
                user_agent TEXT,
                referer TEXT
            )");
        $stmt = $pdo->prepare("
            INSERT INTO downloads (ts, product_id, filename, bytes, user_agent, referer)
            VALUES (datetime('now'), :pid, :fn, :bytes, :ua, :ref)");
        $stmt->execute([
            ':pid'   => $id,
            ':fn'    => basename($realFile),
            ':bytes' => $bytes,
            ':ua'    => $ua,
            ':ref'   => $ref,
        ]);
        $logged = true;
    }
} catch (Throwable $e) {
    @file_put_contents($dataDir . '/errors.log',
        '['.date('c')."] sqlite log fail: ".$e->getMessage()."\n", FILE_APPEND);
}

if (!$logged) {
    // Fallback CSV
    try {
        $fh = fopen($dataDir . '/downloads.csv', 'ab');
        if ($fh) {
            if (flock($fh, LOCK_EX)) {
                fputcsv($fh, [date('c'), $id, basename($realFile), $bytes, $ua, $ref]);
                flock($fh, LOCK_UN);
            }
            fclose($fh);
        }
    } catch (Throwable $e) {
        @file_put_contents($dataDir . '/errors.log',
            '['.date('c')."] csv log fail: ".$e->getMessage()."\n", FILE_APPEND);
        // continue anyway
    }
}

/* ---------- stream file ---------- */
$mime = 'application/x-netcdf';
if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) {
        $det = @finfo_file($fi, $realFile);
        if ($det) $mime = $det;
        finfo_close($fi);
    }
}

// Clear any buffers to avoid memory spikes
while (ob_get_level()) { ob_end_clean(); }

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($realFile) . '"');
header('Content-Length: ' . $bytes);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Accept-Ranges: none');

$fp = fopen($realFile, 'rb');
if ($fp === false) {
    bail(500, 'Unable to open file', $realFile);
}
fpassthru($fp);
fclose($fp);
exit;
