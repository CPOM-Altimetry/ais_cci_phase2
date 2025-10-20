<?php
return [
  'base_dir' => '/cpnet/altimetry/landice/ais_cci_plus_phase2/products/single_mission',
  'ip_salt'  => 'REPLACE_WITH_A_LONG_RANDOM_STRING',  // rotate yearly

  'products' => [
    'cs2_fv2' => [
      'file'  => 'ESACCI-AIS-L3C-SEC-CS2-5KM-20100927-20241203-fv2.nc',
      'label' => 'CryoSat-2 SEC fv2 (5km)'
    ],
    // add more ids here...
  ],

  'stats' => [
    'enable_db'   => true,
    'sqlite_path' => __DIR__ . '/../data/downloads.sqlite',

    'enable_geoip' => true,
    // Put the mmdb OUTSIDE web root if you can (or deny with .htaccess)
    'geoip_mmdb'   => '/raid6/cpdata/www/cpom/ais_cci_phase2/data/GeoLite2-Country.mmdb',

    // If you're behind a reverse proxy, list its IPs here to read X-Forwarded-For safely
    'trusted_proxies' => ['127.0.0.1', '::1'],
  ],
];
