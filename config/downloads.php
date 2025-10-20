<?php
// Absolute directory that holds the files (outside web root)
return [
  'base_dir' => '/cpnet/altimetry/landice/ais_cci_plus_phase2/products/single_mission',
  'products' => [
    // short, stable id -> metadata
    'cs2_fv2' => [
      'file'  => 'ESACCI-AIS-L3C-SEC-CS2-5KM-20100927-20241203-fv2.nc',
      'label' => 'CryoSat-2 SEC 5km (2010–2024) fv2 — NetCDF',
    ],
  ],
  // used to hash IPs for privacy (change this to a long random string)
  'ip_salt' => 'change-me-to-a-long-random-secret',
];
