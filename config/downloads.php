<?php

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
  http_response_code(403);
  exit('Forbidden');
}

return [
  // one level above subfolders like single_mission/, multi_mission/, etc.
  'base_dir' => '/cpnet/altimetry/landice/ais_cci_plus_phase2/products',

  // rotate this to a long random string (hex recommended)
  'ip_salt'  => '01a370b9c390885d855420c9140833cbe4d66b8e4b2c34b78b4f922a61425d99',

  'products' => [
    
    'mm_latest_fv2' => [
      'file'  => 'multi_mission/ESACCI-AIS-L3C-SEC-MULTIMISSION-5KM-5YEAR-MEANS-202008-202508-fv2.nc',
      'label' => 'Multi-Mission SEC, latest 5-years',
      'mission' => 'All RA',
      'grid_size' => '5km',
      'instrument' => 'RA',
    ],
    'mm_fv2' => [
      'file'  => 'multi_mission/ESACCI-AIS-L3C-SEC-MULTMISSION-5KM-5YEAR-MEANS-199107-202508-fv2.zip',
      'label' => 'Multi-Mission 5-year SEC, whole archive',
      'mission' => 'All RA',
      'grid_size' => '5km',
      'instrument' => 'RA',
    ],
    's3b_fv2' => [
      'file'  => 'single_mission/ESACCI-AIS-L3C-SEC-S3B-5KM-20181220-20250914-fv2.nc',
      'label' => 'Surface Elevation Change',
      'mission' => 'Sentinel-3B',
      'grid_size' => '5km',
      'instrument' => 'RA',
    ],
    's3a_fv2' => [
      'file'  => 'single_mission/ESACCI-AIS-L3C-SEC-S3A-5KM-20161115-20250909-fv2.nc',
      'label' => 'Surface Elevation Change',
      'mission' => 'Sentinel-3A',
      'grid_size' => '5km',
      'instrument' => 'RA',
    ],
    'cs2_fv2' => [
      'file'  => 'single_mission/ESACCI-AIS-L3C-SEC-CS2-5KM-20100927-20241203-fv2.nc',
      'label' => 'Surface Elevation Change',
      'mission' => 'CryoSat-2',
      'grid_size' => '5km',
      'instrument' => 'RA',
    ],
    'ev_fv2' => [
      'file'  => 'single_mission/ESACCI-AIS-L3C-SEC-ENV-5KM-20020909-20120409-fv2.nc',
      'label' => 'Surface Elevation Change',
      'mission' => 'ENVISAT',
      'grid_size' => '5km',
      'instrument' => 'RA',
    ],
    'e2_fv2' => [
      'file'  => 'single_mission/ESACCI-AIS-L3C-SEC-ER2-5KM-19950529-20030616-fv2.nc',
      'label' => 'Surface Elevation Change',
      'mission' => 'ERS-2',
      'grid_size' => '5km',
      'instrument' => 'RA',
    ],
    'e1_fv2' => [
      'file'  => 'single_mission/ESACCI-AIS-L3C-SEC-ER1-5KM-19910730-19960723-fv2.nc',
      'label' => 'Surface Elevation Change',
      'mission' => 'ERS-1',
      'grid_size' => '5km',
      'instrument' => 'RA',
    ],
    'is2_fv2' => [
      'file'  => 'single_mission/ESACCI-AIS-L3C-SEC-IS2-5KM-20180928-20250421-fv2.nc',
      'label' => 'Surface Elevation Change',
      'mission' => 'ICESat-2',
      'grid_size' => '5km',
      'instrument' => 'LA',
    ],
    // add more ids here...
  ],

  'stats' => [
    'enable_db'       => true,
    'sqlite_path'     => __DIR__ . '/../data/downloads.sqlite',

    'enable_geoip'    => true,
    'geoip_mmdb'      => __DIR__ . '/../data/GeoLite2-Country.mmdb',

    // if you’re behind a proxy/LB, list its IPs here so X-Forwarded-For is trusted
    'trusted_proxies' => ['127.0.0.1', '::1'],
  ],
];
