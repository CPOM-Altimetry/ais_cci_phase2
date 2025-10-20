return [
  'base_dir' => '/cpnet/altimetry/landice/ais_cci_plus_phase2/products', // one level higher
  'ip_salt'  => '01a370b9c390885d855420c9140833cbe4d66b8e4b2c34b78b4f922a61425d99',
  'products' => [
    'cs2_fv2' => [
      'file'  => 'single_mission/ESACCI-AIS-L3C-SEC-CS2-5KM-20100927-20241203-fv2.nc',
      'label' => 'CryoSat-2 SEC fv2 (5km)',
    ],
    // 'mm_5yr_1991_1996' => ['file' => 'multi_mission/ESACCI-...nc', 'label' => '...'],
  ],
  'stats' => [
    'enable_db'       => true,
    'sqlite_path'     => __DIR__ . '/../data/downloads.sqlite',
    'enable_geoip'    => true,
    'geoip_mmdb'      => __DIR__ . '/../data/GeoLite2-Country.mmdb', // keep in /data + blocked by .htaccess
    'trusted_proxies' => ['127.0.0.1','::1'], // add your LB/CDN IPs if applicable
  ],
];
