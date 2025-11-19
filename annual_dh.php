<?php
// annual_dh.php — Annual dH video with parameter selector (full-width bar) + hillshade toggle (right-aligned)

// From php_init.php
$hillshade   = isset($hillshade) ? $hillshade : 'hide'; // 'show' | 'hide'
$active_tab  = isset($active_tab) ? $active_tab : 'annual_dh';

// Parameter selector values
$param = isset($_GET['ql_param']) ? (string)$_GET['ql_param'] : 'dh';
$PARAMS = [
  'dh'            => 'dH',
  'uncertainty'   => 'Uncertainty of dH',
  'surface_type'  => 'Surface Type',
  'basin_id'      => 'Glaciological Basin ID',
];
if (!isset($PARAMS[$param])) $param = 'dh';

// Optional view (ais default / ase)
$annual_view = isset($_GET['annual_view']) ? (string)$_GET['annual_view'] : 'ais';
$view_suffix = ($annual_view === 'ase') ? '-ase' : '';

// Construct asset names
$dir  = 'annual_dh_quicklooks';
$base = 'annual_dh';

$poster_no = "{$dir}/last_frame.{$param}{$view_suffix}.webp";
$poster_hs = "{$dir}/last_frame_hs.{$param}{$view_suffix}.webp";

$src_av1_no  = "{$dir}/{$base}_av1.{$param}{$view_suffix}.webm";
$src_vp9_no  = "{$dir}/{$base}_vp9.{$param}{$view_suffix}.webm";
$src_h264_no = "{$dir}/{$base}_h264.{$param}{$view_suffix}.mp4";

$src_av1_hs  = "{$dir}/{$base}_av1_hs.{$param}{$view_suffix}.webm";
$src_vp9_hs  = "{$dir}/{$base}_vp9_hs.{$param}{$view_suffix}.webm";
$src_h264_hs = "{$dir}/{$base}_h264_hs.{$param}{$view_suffix}.mp4";

// Initial set based on hillshade
$use_hs  = ($hillshade === 'show');
$poster  = $use_hs ? $poster_hs : $poster_no;
$src_av1 = $use_hs ? $src_av1_hs : $src_av1_no;
$src_vp9 = $use_hs ? $src_vp9_hs : $src_vp9_no;
$src_h264= $use_hs ? $src_h264_hs: $src_h264_no;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

<!-- ===== Intro ===== -->
<h3>Cumulative Annual dH (1993–2025)</h3>

<p>These products provide the cumulative surface height 
    change in each 5km grid cell since the start of the 
    radar altimetry record, derived from cross-calibrated 
multi-mission radar altimetry measurements from ERS-1, ERS-2, ENVISAT,
CryoSat-2, Sentinel-3A, and Sentinel-3B. Products are stepped by 
one year to provide the change of height up until the end of that year.
Each frame of the visualization below contains a plot the Cumulative Annual dH from a single product.
    Use the controls to view the full time range of surface elevation change or
      to switch between <em>dH</em>, its <em>uncertainty</em>, <em>surface type</em>, and
  <em>glaciological basin ID</em>. You can also toggle a hill-shaded backdrop for additional context.</p>

<style>
  /* Full-width parameter bar to match single_mission.php */
  .adh-bar.light-grey-row{
    margin: 10px 0;
    padding: 10px 0;
  }
  .adh-bar .inner{
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 12px;

    display:flex;
    align-items:center;
    justify-content:space-between; /* <-- hillshade to the right */
    gap:16px;
    flex-wrap:wrap;
  }

  .adh-left, .adh-right{
    display:flex; align-items:center; gap:16px; flex-wrap:wrap;
  }

  /* Parameter dropdown */
  .adh-param-wrap{ display:flex; align-items:center; gap:10px; }
  .adh-param-label{
    font-weight:600;
    background: transparent;     /* <-- same as bar bg */
    color: inherit;              /* inherit text color */
    padding: 0;                  /* remove pill look */
    border-radius: 0;            /* remove pill look */
  }
  #adh-param-dropdown .w3-button{
    background:#21578b; color:#fff; border-radius:6px;
  }
  #adh-param-dropdown .w3-dropdown-content .w3-button{
    background:#fff; color:#111; text-align:left;
  }

  /* Hillshade toggle on the right; reuse your global .switch/.slider */
  .adh-hs{
    display:flex; align-items:center; gap:8px;
  }
  .adh-hs .switch{ transform:scale(.95); transform-origin:left center; }
  .adh-hs .toggle-text{ font-weight:600; }

  /* Player shell (unchanged) */
  :root { --mmv-rail:#d7dbe0; --mmv-rail-fill:#2e7bd1; }
  .mmv-wrap-annual-dh{ margin:10px auto; max-width:var(--mmv-max,1200px); border:1px solid #ddd; border-radius:10px; overflow:hidden; background:#fff; }
  .mmv-media{ background:#000; }
  .mmv-media video{ display:block; width:100%; height:auto; aspect-ratio:900/750; background:#000; }

  .mmv-controls-annual-dh{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px; background:#f7f9fc; border-top:1px solid #e6ebf0; }
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

  .mmv-window{
    position:absolute; top:50%; transform:translateY(-50%);
    height:18px; border-radius:9px; border:1px solid #cbd5e1; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.06);
    pointer-events:none; width:64px;
  }
  /* 1) Don't clip the dropdown with the outer card */
  .mmv-wrap-annual-dh { overflow: visible; }

  /* Keep the rounded-corner clipping only on the media area */
  .mmv-media {
    overflow: hidden;
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
  }

  /* 2) Make sure the select sits above nearby controls */
  .mmv-controls-annual-dh { position: relative; overflow: visible; }
  .param-select, .mmv-speed, select#mmp-param {
    position: relative;
    z-index: 1000; /* above scrub UI */
  }

  /* Lower the scrubber's stacking level */
  .mmv-scrub-wrap, .mmv-scrub-wrap * {
    position: relative;
    z-index: 1;
  }

  /* Make the parameter bar (and its <select>) sit above the video controls */
.adh-filter-bar {            /* the full-width light-grey bar */
  position: relative;
  z-index: 50;
}
.adh-filter-bar select {
  position: relative;        /* ensure it participates in stacking */
  z-index: 51;               /* slightly above the bar */
}

/* Player controls can stay lower */
.mmv-wrap-annual-dh { position: relative; z-index: 0; }
.mmv-controls-annual-dh { position: relative; z-index: 1; }

/* (optional) ensure individual slider bits don't float above the dropdown */
.mmv-scrub-wrap, .mmv-range, .mmv-window {
  position: relative;
  z-index: 1;
}


</style>

<!-- ===== Full-width parameter & hillshade bar ===== -->
<div class="adh-bar light-grey-row">
  <div class="inner">
    <div class="adh-left">
      <div class="adh-param-wrap">
        <span class="adh-param-label">Product Parameter:</span>
        <div id="adh-param-dropdown" class="w3-dropdown-hover">
          <button class="w3-button">
            <?php echo h($PARAMS[$param]); ?> <i class="fa fa-caret-down"></i>
          </button>
          <div class="w3-dropdown-content w3-bar-block w3-card-4">
            <?php
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
    </div>

    <!-- View selector (wired via form submit; preserves hillshade + tab) -->
      <form class="mmv-view" id="mmv-view-form" method="POST" action="">
        <label for="mmv-view-select">View:</label>
        <select id="mmv-view-select" class="mmv-view-select" name="mm_view" aria-label="Select view" onchange="this.form.submit()">
          <option value="ais" <?php echo $mm_view==='ais'?'selected':''; ?>>Antarctica Ice Sheet</option>
          <option value="ase" <?php echo $mm_view==='ase'?'selected':''; ?>>ASE: PIG, Thwaites Glaciers</option>
        </select>
        <input type="hidden" name="active_tab" value="multi_mission">
        <input type="hidden" name="hillshade" value="<?php echo $use_hs ? 'show' : 'hide'; ?>">
      </form>

    <div class="adh-right">
      <!-- Hillshade toggle (right aligned) -->
      <form id="hillshade-form" method="POST" style="display:none;">
        <input type="hidden" name="hillshade" id="hillshade-input" value="<?php echo $use_hs ? 'show' : 'hide'; ?>">
        <input type="hidden" name="active_tab" value="annual_dh">
        <input type="hidden" name="ql_param" value="<?php echo h($param); ?>">
        <input type="hidden" name="annual_view" value="<?php echo h($annual_view); ?>">
      </form>

      <div class="adh-hs">
        <label class="toggle-text" for="toggle-hillshade">Hillshade</label>
        <label class="switch">
          <input id="toggle-hillshade" type="checkbox" <?php echo $use_hs ? 'checked' : ''; ?>>
          <span class="slider round"></span>
        </label>
      </div>
    </div>
  </div>
</div>

<!-- ===== Video player ===== -->
<div class="mmv-wrap-annual-dh" id="annual-dh-player"
     data-start="1991-01"
     data-end="2025-12"
     data-start-label="Jan 1993"
     data-end-label="Sep 2025">
  <div class="mmv-controls-annual-dh" role="group" aria-label="Video controls">
    <div class="mmv-left">
      <button class="mmv-btn" data-role="play" aria-label="Play/Pause">
        <span class="material-icons">play_arrow</span>
      </button>
    </div>

    <div class="mmv-center">
      <span class="mmv-bound mmv-start" data-role="label-start">Jan 1993</span>
      <div class="mmv-scrub-wrap">
        <div class="mmv-window" aria-hidden="true"></div>
        <input class="mmv-range" data-role="seek" type="range" min="0" max="1000" value="0" step="1" aria-label="Seek">
      </div>
      <span class="mmv-bound mmv-end" data-role="label-end">Sep 2025</span>
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
      <source src="<?php echo h($src_av1);  ?>" type="video/webm; codecs=av01.0.05M.08">
      <source src="<?php echo h($src_vp9);  ?>" type="video/webm; codecs=vp9">
      <source src="<?php echo h($src_h264); ?>" type="video/mp4">
      Your browser doesn’t support HTML5 video.
    </video>
  </div>
</div>


<script>
(function () {
  /* ---------------- utils ---------------- */
  function parseYYYYMM(s){ const m=(s||'').match(/^(\d{4})-(\d{2})$/); return m?{y:+m[1],m:+m[2]}:null; }
  function monthsBetween(a,b){ if(!a||!b) return 0; return (b.y-a.y)*12 + (b.m-a.m) + 1; }
  function clamp(n,a,b){ return Math.max(a, Math.min(b, n)); }
  function pctToTime(p,d){ return (p/1000)*(d||0); }

  /* --------------- one player --------------- */
  function initOnePlayer(root){
    if (!root || root.dataset.bound === '1') return;

    var v      = root.querySelector('video');
    var bPlay  = root.querySelector('[data-role="play"]');
    var seek   = root.querySelector('[data-role="seek"]');
    var speed  = root.querySelector('[data-role="speed"]');
    var winEl  = root.querySelector('.mmv-window');
    var rail   = root.querySelector('.mmv-scrub-wrap');

    // Hillshade (same ids as other tabs)
    var hsToggle = root.querySelector('#toggle-hillshade');
    var hsForm   = root.querySelector('#hillshade-form');
    var hsInput  = root.querySelector('#hillshade-input');
    var hsTab    = root.querySelector('#active_tab_input');

    if (!v || !seek || !rail || !winEl) return;

    /* ---- timeline for window width ---- */
    var startISO = root.dataset.start || '1991-01';
    var endISO   = root.dataset.end   || '2025-12';
    var start    = parseYYYYMM(startISO);
    var end      = parseYYYYMM(endISO);
    var totalM   = Math.max(1, monthsBetween(start, end)); // guard
    var windowM  = 24; // 2 year

    // sizing
    var railW = 0, windowW = 0, initialEndApplied = false;

    function measureRailWidth(){
      // Prefer the actual visible rail width
      const r = (rail.getBoundingClientRect().width || 0);
      const s = (seek.getBoundingClientRect().width || 0);
      railW = Math.max(r, s, 0);
      // Compute the 5-year window width; keep it visible
      windowW = clamp(railW * (windowM / totalM), 16, railW);
      winEl.style.width = windowW + 'px';
    }

    function placeWindow(pct){
      // pct: 0..1000
      const x = clamp((pct/1000) * railW - windowW/2, 0, Math.max(0, railW - windowW));
      winEl.style.left = x + 'px';
    }

    // Keep the “window” above the track and clicks going to the range
    winEl.style.pointerEvents = 'none';
    winEl.style.zIndex = '2';
    seek.style.position = 'relative';
    seek.style.zIndex = '1';

    /* ---- UI sync (during playback) ---- */
    var seeking = false, wasPlaying = false, seekRAF = null;

    function updateProgress(){
      if (!isFinite(v.duration)) return;
      if (seeking) return;
      var p = (v.currentTime / v.duration) * 1000 || 0;
      seek.value = clamp(Math.round(p), 0, 1000);
      root.style.setProperty('--mmv-fill', (seek.value/10) + '%');
      placeWindow(seek.value);
    }

    v.addEventListener('timeupdate', updateProgress);
    v.addEventListener('progress',   updateProgress);

    /* ---- play/pause ---- */
    function syncPlayIcon(){
      if (bPlay) bPlay.querySelector('.material-icons').textContent = v.paused ? 'play_arrow' : 'pause';
    }
    if (bPlay) bPlay.addEventListener('click', function(){ v.paused ? v.play() : v.pause(); });
    v.addEventListener('play',  syncPlayIcon);
    v.addEventListener('pause', syncPlayIcon);

    /* ---- seeking (Chrome-friendly live scrub) ---- */
    function beginSeek(){ seeking = true; wasPlaying = !v.paused; if (wasPlaying) v.pause(); }
    function finishSeek(){
      if (!seeking) return;
      var nt = pctToTime(seek.value, v.duration);
      v.currentTime = nt;
      seeking = false;
      if (wasPlaying) v.play();
    }

    seek.addEventListener('pointerdown', beginSeek);
    seek.addEventListener('mousedown',   beginSeek);
    seek.addEventListener('touchstart',  beginSeek, {passive:true});

    seek.addEventListener('input', function(){
      var nt = pctToTime(seek.value, v.duration);
      root.style.setProperty('--mmv-fill', (seek.value/10) + '%');
      placeWindow(seek.value);
      if (seekRAF) cancelAnimationFrame(seekRAF);
      seekRAF = requestAnimationFrame(function(){ v.currentTime = nt; });
    });

    document.addEventListener('pointerup',   finishSeek);
    document.addEventListener('mouseup',     finishSeek);
    document.addEventListener('touchend',    finishSeek);
    seek.addEventListener('pointercancel',   finishSeek);
    seek.addEventListener('change',          finishSeek);

    /* ---- speed ---- */
    if (speed) speed.addEventListener('change', function(){ v.playbackRate = parseFloat(this.value); });

    /* ---- keyboard ---- */
    root.tabIndex = 0;
    root.addEventListener('keydown', function(e){
      switch(e.key){
        case ' ': case 'k': e.preventDefault(); v.paused ? v.play() : v.pause(); break;
        case 'ArrowLeft':  v.currentTime = Math.max(0, v.currentTime - 5); break;
        case 'ArrowRight': v.currentTime = Math.min(v.duration||0, v.currentTime + 5); break;
      }
    });

    /* ---- fit wrapper to the video’s natural width ---- */
    var wrap = root.closest('.mmv-wrap-annual-dh') || root;
    function fitToNaturalWidth(){
      if (v.videoWidth > 0) wrap.style.setProperty('--mmv-max', v.videoWidth + 'px');
    }

    /* ---- initialize slider UI to END to match poster (UI only) ---- */
    function setToEndUI(){
      if (initialEndApplied) return;
      seek.value = 1000;
      root.style.setProperty('--mmv-fill', '100%');
      placeWindow(1000);
      initialEndApplied = true;
    }

    // Measure rail + set initial UI immediately (so the window has size)
    measureRailWidth();
    setToEndUI();

    // When metadata arrives (duration known), keep sizes and UI sane
    v.addEventListener('loadedmetadata', function(){
      fitToNaturalWidth();
      // re-measure (layout may change after fonts/video aspect settle)
      requestAnimationFrame(function(){
        measureRailWidth();
        if (initialEndApplied) placeWindow(seek.value);
        else setToEndUI();
      });
    });

    if (v.readyState >= 1){
      fitToNaturalWidth();
      requestAnimationFrame(function(){
        measureRailWidth();
        if (initialEndApplied) placeWindow(seek.value);
        else setToEndUI();
      });
    }

    // React to container/viewport resizes
    if ('ResizeObserver' in window){
      const ro = new ResizeObserver(function(){ measureRailWidth(); placeWindow(seek.value || 1000); });
      ro.observe(rail);
    }
    window.addEventListener('resize', function(){
      measureRailWidth();
      placeWindow(seek.value || 1000);
    });

    /* ---- hillshade POST toggle (same flow as other tabs) ---- */
    if (hsToggle && hsForm && hsInput){
      if (hsTab) hsTab.value = 'annual_dh';
      hsToggle.addEventListener('change', function(){
        hsInput.value = this.checked ? 'show' : 'hide';
        if (hsTab) hsTab.value = 'annual_dh';
        hsForm.submit();
      });
    }

    syncPlayIcon();
    root.dataset.bound = '1';
  }

  /* --------------- boot / rebind --------------- */
  function initAll(){ document.querySelectorAll('.mmv-wrap-annual-dh').forEach(initOnePlayer); }
  document.addEventListener('DOMContentLoaded', initAll);
  // for lazy-loaded tabs
  window.rebindMultiMissionHandlers = initAll;
})();
</script>