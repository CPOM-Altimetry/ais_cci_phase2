<?php
// annual_dh.php — Annual dH video with parameter selector (no extra JS required)

// Expect these to be available from php_init.php (as in your other tabs)
$hillshade = isset($hillshade) ? $hillshade : 'hide'; // 'show' | 'hide'
$active_tab = isset($active_tab) ? $active_tab : 'annual_dh';

// --- Parameter selector (like single_mission.php, but our own set) ---
$param = isset($_GET['ql_param']) ? (string)$_GET['ql_param'] : 'dh'; // default
$PARAMS = [
  'dh'            => 'dH',
  'uncertainty'   => 'Uncertainty of dH',
  'surface_type'  => 'Surface Type',
  'basin_id'      => 'Glaciological Basin ID',
];
if (!isset($PARAMS[$param])) $param = 'dh';

// --- Optional "view": Antarctica (ais, default) or ASE (ase) ---
// If you don't use a view yet, this will just choose AIS assets by default.
$annual_view = isset($_GET['annual_view']) ? (string)$_GET['annual_view'] : 'ais';
$view_suffix = ($annual_view === 'ase') ? '-ase' : ''; // e.g., ".dh-ase.webm"

// --- Build asset names based on param + view + hillshade ---
// Directory and base name are fixed per your spec:
$dir  = 'annual_dh_quicklooks';
$base = 'annual_dh';

// Poster (ok if missing; the <video> will still play)
$poster_no = "{$dir}/last_frame.{$param}{$view_suffix}.webp";
$poster_hs = "{$dir}/last_frame_hs.{$param}{$view_suffix}.webp";

// Non-HS
$src_av1_no  = "{$dir}/{$base}_av1.{$param}{$view_suffix}.webm";
$src_vp9_no  = "{$dir}/{$base}_vp9.{$param}{$view_suffix}.webm";
$src_h264_no = "{$dir}/{$base}_h264.{$param}{$view_suffix}.mp4";

// HS
$src_av1_hs  = "{$dir}/{$base}_av1_hs.{$param}{$view_suffix}.webm";
$src_vp9_hs  = "{$dir}/{$base}_vp9_hs.{$param}{$view_suffix}.webm";
$src_h264_hs = "{$dir}/{$base}_h264_hs.{$param}{$view_suffix}.mp4";

// Choose initial set based on hillshade
$use_hs  = ($hillshade === 'show');
$poster  = $use_hs ? $poster_hs : $poster_no;
$src_av1 = $use_hs ? $src_av1_hs : $src_av1_no;
$src_vp9 = $use_hs ? $src_vp9_hs : $src_vp9_no;
$src_h264= $use_hs ? $src_h264_hs: $src_h264_no;

// Small helper
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<h3>Annual dH (time series)</h3>

<style>
  /* Top parameter bar (like your single_mission style, compact) */
  .adh-toolbar{
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    padding:10px 0; margin-bottom:6px;
  }
  .adh-param-label{ font-weight:600; }
  .adh-param .w3-button{ background:#21578b; color:#fff; border-radius:6px; }
  .adh-param .w3-dropdown-content .w3-button{ background:#fff; color:#111; text-align:left; }

  /* Player shell to match your other tabs */
  :root { --mmv-rail:#d7dbe0; --mmv-rail-fill:#2e7bd1; }
  .mmv-wrap{ margin:10px auto; max-width:var(--mmv-max,1200px); border:1px solid #ddd; border-radius:10px; overflow:hidden; background:#fff; }
  .mmv-media{ background:#000; }
  .mmv-media video{ display:block; width:100%; height:auto; aspect-ratio:780/780; background:#000; }

  .mmv-controls{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px; background:#f7f9fc; border-top:1px solid #e6ebf0; }
  .mmv-left,.mmv-right{ display:flex; align-items:center; gap:10px }
  .mmv-center{ flex:1 1 auto; display:flex; align-items:center; gap:10px }

  .mmv-btn{ display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border:1px solid #d9dee5; border-radius:8px; background:#fff; cursor:pointer; transition:background .15s,border-color .15s; }
  .mmv-btn:hover{ background:#eef5ff; border-color:#c9d7ee }
  .mmv-btn .material-icons{ font-size:20px; line-height:1 }

  .mmv-speed{ border:1px solid #d9dee5; border-radius:8px; background:#fff; height:36px; padding:0 8px; }
  .mmv-bound{ font:13px/1.1 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Open Sans',sans-serif; color:#333; white-space:nowrap; }

  .mmv-scrub-wrap{ position:relative; flex:1 1 auto; display:flex; align-items:center; height:22px; min-width:160px }
  .mmv-range{ appearance:none; background:transparent; width:100%; height:22px; cursor:pointer }
  .mmv-range:focus{ outline:none }
  .mmv-range::-webkit-slider-runnable-track{ height:6px; border-radius:999px; background:linear-gradient(to right,var(--mmv-rail-fill) var(--mmv-fill,0%),var(--mmv-rail) var(--mmv-fill,0%)); }
  .mmv-range::-webkit-slider-thumb{ appearance:none; width:0; height:0; border:0; background:transparent; margin-top:0 }
  .mmv-range::-moz-range-track{ height:6px; background:var(--mmv-rail); border-radius:999px }
  .mmv-range::-moz-range-thumb{ width:0; height:0; border:0; background:transparent }

  /* Oblong 5-yr window indicator (same as your multi_mission) */
  .mmv-window{
    position:absolute; top:50%; transform:translateY(-50%);
    height:18px; border-radius:9px; border:1px solid #cbd5e1; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.06);
    pointer-events:none; width:64px;
  }
</style>

<!-- ===== Parameter toolbar (links like single_mission) ===== -->
<div class="adh-toolbar">
  <div id="adh-param-dropdown" class="w3-dropdown-hover adh-param">
    <span class="adh-param-label">Parameter:</span>
    <button class="w3-button">
      <?php echo h($PARAMS[$param]); ?> <i class="fa fa-caret-down"></i>
    </button>
    <div class="w3-dropdown-content w3-bar-block w3-card-4">
      <?php
        // Base URL that preserves tab, hillshade, and (optional) view
        $base = "index.php?active_tab=annual_dh"
              . "&hillshade=" . urlencode($hillshade)
              . "&annual_view=" . urlencode($annual_view);
        foreach ($PARAMS as $key => $label):
      ?>
        <a class="w3-bar-item w3-button"
           href="<?php echo $base . '&ql_param=' . urlencode($key) . '#annual_dh'; ?>">
           <?php echo h($label); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ===== Video player (same structure your JS already binds to) ===== -->
<div class="mmv-wrap" id="annual-dh-player"
     data-start="1991-01"
     data-end="2025-12"
     data-start-label="Jan 1991"
     data-end-label="Dec 2025">
  <!-- Controls (row order can follow your standard if you have it; keep minimal here) -->
  <div class="mmv-controls" role="group" aria-label="Video controls">
    <div class="mmv-left">
      <button class="mmv-btn" data-role="play" aria-label="Play/Pause">
        <span class="material-icons">play_arrow</span>
      </button>
    </div>

    <div class="mmv-center">
      <span class="mmv-bound mmv-start" data-role="label-start">Jan 1991</span>
      <div class="mmv-scrub-wrap">
        <div class="mmv-window" aria-hidden="true"></div>
        <input class="mmv-range" data-role="seek" type="range" min="0" max="1000" value="0" step="1" aria-label="Seek">
      </div>
      <span class="mmv-bound mmv-end" data-role="label-end">Dec 2025</span>
    </div>

    <div class="mmv-right">
      <select class="mmv-speed" data-role="speed" aria-label="Playback speed">
        <option value="0.5">0.5×</option>
        <option value="0.75">0.75×</option>
        <option value="1" selected>1×</option>
        <option value="1.25">1.25×</option>
        <option value="1.5">1.5×</option>
        <option value="2">2×</option>
      </select>
    </div>
  </div>

  <div class="mmv-media">
    <video
      preload="metadata"
      playsinline
      <?php if ($poster) echo 'poster="'.h($poster).'"'; ?>
      data-poster-no="<?php echo h($poster_no); ?>"
      data-poster-hs="<?php echo h($poster_hs); ?>"

      data-src-av1-no="<?php echo h($src_av1_no); ?>"
      data-src-vp9-no="<?php echo h($src_vp9_no); ?>"
      data-src-h264-no="<?php echo h($src_h264_no); ?>"

      data-src-av1-hs="<?php echo h($src_av1_hs); ?>"
      data-src-vp9-hs="<?php echo h($src_vp9_hs); ?>"
      data-src-h264-hs="<?php echo h($src_h264_hs); ?>"
    >
      <!-- Initial active sources (browser chooses) -->
      <source src="<?php echo h($src_av1);  ?>" type="video/webm; codecs=av01.0.05M.08">
      <source src="<?php echo h($src_vp9);  ?>" type="video/webm; codecs=vp9">
      <source src="<?php echo h($src_h264); ?>" type="video/mp4">
      Your browser doesn’t support HTML5 video.
    </video>
  </div>
</div>
