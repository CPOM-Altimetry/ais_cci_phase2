<?php
// multi_mission.php — custom-controls video player (two-row controls, in-row hillshade toggle + view switch)

// ---- Hillshade default from php_init.php ('show' | 'hide')
$use_hs = (isset($hillshade) ? $hillshade === 'show' : true);

// ---- View selection: 'ais' (default) or 'ase'
$mm_view = isset($_POST['mm_view']) ? (($_POST['mm_view']==='ase') ? 'ase' : 'ais')
         : (isset($_GET['mm_view'])  ? (($_GET['mm_view']==='ase')  ? 'ase' : 'ais') : 'ais');

// ---- Pick asset suffix based on view
$suffix = ($mm_view === 'ase') ? '.sec-ase' : '.sec';

// ---- Non-HS assets (per view)
$poster_no   = 'annual_dh_quicklooks/last_frame'                  . $suffix . '.webp';
$src_av1_no  = 'annual_dh_quicklooks/multi_mission_av1'          . $suffix . '.webm';
$src_vp9_no  = 'annual_dh_quicklooks/multi_mission_vp9'          . $suffix . '.webm';
$src_h264_no = 'annual_dh_quicklooks/multi_mission_h264'         . $suffix . '.mp4';

// ---- HS assets (per view)
$poster_hs   = 'annual_dh_quicklooks/last_frame_hs'              . $suffix . '.webp';
$src_av1_hs  = 'annual_dh_quicklooks/multi_mission_av1_hs'      . $suffix . '.webm';
$src_vp9_hs  = 'annual_dh_quicklooks/multi_mission_vp9_hs'      . $suffix . '.webm';
$src_h264_hs = 'annual_dh_quicklooks/multi_mission_h264_hs'     . $suffix . '.mp4';

// ---- Choose initial set based on hillshade
$poster   = $use_hs ? $poster_hs   : $poster_no;
$src_av1  = $use_hs ? $src_av1_hs  : $src_av1_no;
$src_vp9  = $use_hs ? $src_vp9_hs  : $src_vp9_no;
$src_h264 = $use_hs ? $src_h264_hs : $src_h264_no;

// ---- Timeline labels (server-side)
$timeline_json = __DIR__ . '/annual_dh_quicklooks/timeline.json';
$startISO='1991-01'; $endISO='2025-12'; $startLabel='Jan 1991'; $endLabel='Dec 2025';
if (is_file($timeline_json) && is_readable($timeline_json)) {
  $cfg = json_decode(file_get_contents($timeline_json), true);
  if (is_array($cfg)) {
    $startISO   = $cfg['start']       ?? $startISO;
    $endISO     = $cfg['end']         ?? $endISO;
    $startLabel = $cfg['start_label'] ?? $startLabel;
    $endLabel   = $cfg['end_label']   ?? $endLabel;
  }
}

$PLAYER_ID = 'mmv-player';
?>
<h3>Cumulative Annual dH</h3>
<img id="multi_mission_logo" src="images/multi_mission_logo.webp" alt="Single mission logo" class="float-right-img">

<p>These products provide the cumulative surface height 
    change in each 5km grid cell since the start of the 
    radar altimetry record, derived from cross-calibrated 
multi-mission radar altimetry measurements from ERS-1, ERS-2, ENVISAT,
CryoSat-2, Sentinel-3A, and Sentinel-3B. Products are stepped by 
one year to provide the change of height up until the end of that year.</p>

