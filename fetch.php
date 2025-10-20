<?php
// Robust, single-file streaming + logging to SQLite (creates DB/table on first run)
declare(strict_types=1);
mb_internal_encoding('UTF-8');

$config = require __DIR__ . '/config/downloads.php';
$baseDir = rtrim($config['base_dir'] ?? '', '/');
$products = $config['products'] ?? [];
$ipSalt = $config['ip_salt'] ?? 'fallback-salt';

// ---- 1) Validate request (id -> known product)
$id = $_GET['id'] ?? '';
if (!isset($products[$id])) {
    http_response_code(404);
    echo "Unknown product.";
    exit;
}
$relFile  = $products[$id]['file'];
$label    = $products[$id]['label'] ?? basename($relFile);
$absPath  = $baseDir . '/' . $relFile;

// ---- 2) Check file
if (!is_file($absPath) || !is_readable($absPath)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

// ---- 3) Open DB + log
try {
    $dbPath = __DIR__ . '/data/downloads.sqlite';
    if (!is_dir(dirname($dbPath))) {
        @mkdir(dirname($dbPath), 0775, true);
    }
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS downloads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ts TEXT NOT NULL,
            product_id TEXT NOT NULL,
            filename TEXT NOT NULL,
            bytes INTEGER NOT NULL,
            ip_hash TEXT,
            user_agent TEXT,
            referer TEXT
        )
    ");

    $bytes = filesize($absPath);
    $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // Hash IP for privacy
    $ipHash = hash_hmac('sha256', $ip, $ipSalt);
    $ua     = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 400);
    $ref    = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 400);

    $stmt = $pdo->prepare("
        INSERT INTO downloads (ts, product_id, filename, bytes, ip_hash, user_agent, referer)
        VALUES (datetime('now'), :pid, :fn, :bytes, :ip, :ua, :ref)
    ");
    $stmt->execute([
        ':pid'   => $id,
        ':fn'    => basename($absPath),
        ':bytes' => $bytes,
        ':ip'    => $ipHash,
        ':ua'    => $ua,
        ':ref'   => $ref,
    ]);
} catch (Throwable $e) {
    // If logging fails, we still serve the file.
    // error_log("Download logging failed: " . $e->getMessage());
}

// ---- 4) Stream file
$mime = 'application/x-netcdf'; // good default for NetCDF
if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) {
        $det = finfo_file($fi, $absPath);
        if ($det) $mime = $det;
        finfo_close($fi);
    }
}
$filename = basename($absPath);
$size     = filesize($absPath);

// Clean output buffers to avoid memory spikes
while (ob_get_level()) { ob_end_clean(); }

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $size);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Accept-Ranges: none'); // simple approach (no range support)

$fp = fopen($absPath, 'rb');
if ($fp === false) {
    http_response_code(500);
    echo "Could not open file.";
    exit;
}
fpassthru($fp);
fclose($fp);
exit;
