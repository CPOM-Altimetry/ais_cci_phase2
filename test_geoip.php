<?php
require __DIR__ . '/vendor/autoload.php';
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;

$DB = '/var/lib/geoip/GeoLite2-Country.mmdb'; // update if needed
$reader = new Reader($DB);

// Get client IP (dev will be ::1)
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Use a public IP for lookup if private/loopback
function is_public_ip(string $ip): bool {
  return (bool) filter_var($ip, FILTER_VALIDATE_IP,
    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}
$lookupIp = is_public_ip($ip) ? $ip : '8.8.8.8'; // or '1.1.1.1'

try {
  $rec = $reader->country($lookupIp);
  echo 'Client IP: ' . htmlspecialchars($ip) . "<br>";
  echo 'Lookup IP: ' . htmlspecialchars($lookupIp) . "<br>";
  echo 'Country: ' . $rec->country->isoCode . ' — ' . $rec->country->name;
} catch (AddressNotFoundException $e) {
  echo 'Client IP: ' . htmlspecialchars($ip) . "<br>";
  echo 'Lookup IP: ' . htmlspecialchars($lookupIp) . "<br>";
  echo 'Country: (unknown)';
}
