<?php
// annual_dh.php — custom-controls video player (two-row controls, in-row hillshade toggle + view switch)

// ---- Hillshade default from php_init.php ('show' | 'hide')
$use_hs = (isset($hillshade) ? $hillshade === 'show' : true);

// ---- View selection: 'ais' (default) or 'ase'
$mm_view = isset($_POST['mm_view']) ? (($_POST['mm_view']==='ase') ? 'ase' : 'ais')
         : (isset($_GET['mm_view'])  ? (($_GET['mm_view']==='ase')  ? 'ase' : 'ais') : 'ais');

// ---- Pick asset suffix based on view
$suffix = ($mm_view === 'ase') ? '.dh-ase' : '.dh';

// ---- Non-HS assets (per view)
$poster_no   = 'annual_dh_quicklooks/last_frame'                  . $suffix . '.webp';
$src_av1_no  = 'annual_dh_quicklooks/annual_dh_av1'          . $suffix . '.webm';
$src_vp9_no  = 'annual_dh_quicklooks/annual_dh_vp9'          . $suffix . '.webm';
$src_h264_no = 'annual_dh_quicklooks/annual_dh_h264'         . $suffix . '.mp4';

// ---- HS assets (per view)
$poster_hs   = 'annual_dh_quicklooks/last_frame_hs'              . $suffix . '.webp';
$src_av1_hs  = 'annual_dh_quicklooks/annual_dh_av1_hs'      . $suffix . '.webm';
$src_vp9_hs  = 'annual_dh_quicklooks/annual_dh_vp9_hs'      . $suffix . '.webm';
$src_h264_hs = 'annual_dh_quicklooks/annual_dh_h264_hs'     . $suffix . '.mp4';

// ---- Choose initial set based on hillshade
$poster   = $use_hs ? $poster_hs   : $poster_no;
$src_av1  = $use_hs ? $src_av1_hs  : $src_av1_no;
$src_vp9  = $use_hs ? $src_vp9_hs  : $src_vp9_no;
$src_h264 = $use_hs ? $src_h264_hs : $src_h264_no;

// ---- Timeline labels (server-side)

// timeline.json file format
// {
//     "start": "1991-01",
//     "end": "2025-12",
//     "start_label": "Jan 1991",
//     "end_label": "Dec 2025"
// }

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

<p>Each frame of the visualization below contains a plot of the Cumulative Annual dH from a single product.
    Use the controls to view the full time range of surface elevation change.</p>
<style>
  :root { --mmv-blue:#21578b; --mmv-bg:#0f1a26; --mmv-rail:#d7dbe0; --mmv-rail-fill:#2e7bd1; --mmv-text:#111; }
  .mmv-wrap{ margin:10px auto; max-width:var(--mmv-max,1200px); border:1px solid #ddd; border-radius:10px; overflow:hidden; background:#fff; }
  .mmv-media{ background:#000; }
  .mmv-media video{ display:block; width:100%; height:auto; aspect-ratio:780/780; background:#000; }

  /* Two-row controls layout */
  .mmv-controls{
    display:flex; flex-direction:column;
    gap:8px; padding:10px; background:#f7f9fc; border-top:1px solid #e6ebf0;
  }
  .mmv-row{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
  .mmv-row-top{ justify-content:flex-start; }
  .mmv-row-bottom{ justify-content:space-between; }

  .mmv-left,.mmv-center,.mmv-right{ display:flex; align-items:center; gap:10px; }
  .mmv-center{ flex:1 1 auto; }

  .mmv-btn{
    display:inline-flex; align-items:center; justify-content:center;
    width:36px; height:36px; border:1px solid #d9dee5; border-radius:8px;
    background:#fff; cursor:pointer; transition:background .15s,border-color .15s;
  }
  .mmv-btn:hover{ background:#eef5ff; border-color:#c9d7ee }
  .mmv-btn .material-icons{ font-size:20px; line-height:1 }

  .mmv-speed-wrap{ display:flex; flex-direction:column; gap:4px; font:13px/1.1 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Open Sans',sans-serif; color:#333; }
  .mmv-speed{ border:1px solid #d9dee5; border-radius:8px; background:#fff; height:36px; padding:0 8px; }
  .mmv-bound{ font:13px/1.1 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Open Sans',sans-serif; color:#333; white-space:nowrap; }

  /* Scrub */
  .mmv-scrub-wrap{ position:relative; flex:1 1 auto; display:flex; align-items:center; height:22px; min-width:160px }
  .mmv-range{ appearance:none; background:transparent; width:100%; height:22px; cursor:pointer }
  .mmv-range:focus{ outline:none }
  .mmv-range::-webkit-slider-runnable-track{
    height:6px; border-radius:999px;
    background:linear-gradient(to right,var(--mmv-rail-fill) var(--mmv-fill,0%),var(--mmv-rail) var(--mmv-fill,0%));
  }
  .mmv-range::-webkit-slider-thumb{ appearance:none; width:0; height:0; border:0; background:transparent; margin-top:0 }
  .mmv-range::-moz-range-track{ height:6px; background:var(--mmv-rail); border-radius:999px }
  .mmv-range::-moz-range-thumb{ width:0; height:0; border:0; background:transparent }

  /* Custom 5-year window “thumb” (JS positions this with transforms) */
  .mmv-window{
    position:absolute; top:50%; transform:translateY(-50%);
    height:18px; border-radius:9px; border:1px solid #cbd5e1; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.06);
    pointer-events:none; width:32px;
  }

  /* View dropdown */
  .mmv-view{ display:flex; align-items:center; gap:6px; }
  .mmv-view label{
    font:13px/1.1 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Open Sans',sans-serif;
    color:#333; white-space:nowrap;
  }
  .mmv-view-select{
    height:36px; padding:0 8px; background:#fff;
    border:1px solid #d9dee5; border-radius:8px;
  }

  /* Compact, in-row hillshade toggle: reuses global switch styles, scaled down */
  .mmv-compact-toggle{ display:flex; align-items:center; gap:8px }
  .mmv-compact-toggle .toggle-switch{ display:flex; align-items:center; gap:6px }
  .mmv-compact-toggle .switch{ transform:scale(.9); transform-origin:center }
  .mmv-compact-toggle .toggle-option{ font-size:13px; }

  @media (max-width:720px){ .mmv-bound{ font-size:12px } }
</style>

<div
  class="mmv-wrap"
  id="<?php echo $PLAYER_ID; ?>"
  data-start="<?php echo htmlspecialchars($startISO, ENT_QUOTES); ?>"
  data-end="<?php echo htmlspecialchars($endISO, ENT_QUOTES); ?>"
  data-start-label="<?php echo htmlspecialchars($startLabel, ENT_QUOTES); ?>"
  data-end-label="<?php echo htmlspecialchars($endLabel, ENT_QUOTES); ?>"
>
  <div class="mmv-controls" role="group" aria-label="Video controls">

    <!-- Row 1: View dropdown : Hillshade toggle : Speed selector -->
    <div class="mmv-row mmv-row-top">
      <!-- View selector (wired via form submit; preserves hillshade + tab) -->
      <form class="mmv-view" id="mmv-view-form" method="POST" action="">
        <label for="mmv-view-select">View:</label>
        <select id="mmv-view-select" class="mmv-view-select" name="mm_view" aria-label="Select view" onchange="this.form.submit()">
          <option value="ais" <?php echo $mm_view==='ais'?'selected':''; ?>>Antarctica Ice Sheet</option>
          <option value="ase" <?php echo $mm_view==='ase'?'selected':''; ?>>ASE: PIG, Thwaites Glaciers</option>
        </select>
        <input type="hidden" name="active_tab" value="annual_dh">
        <input type="hidden" name="hillshade" value="<?php echo $use_hs ? 'show' : 'hide'; ?>">
      </form>

      <!-- Hill Shade toggle (inline, preserves view + tab) -->
      <div class="mmv-compact-toggle">
        <div class="toggle-switch<?php echo $use_hs ? ' on' : ''; ?>">
          <form id="hillshade-form" method="POST" style="display:none;">
            <input type="hidden" name="hillshade" id="hillshade-input" value="<?php echo $use_hs ? 'show' : 'hide'; ?>">
            <input type="hidden" name="active_tab" id="active_tab_input" value="annual_dh">
            <input type="hidden" name="mm_view" value="<?php echo htmlspecialchars($mm_view, ENT_QUOTES); ?>">
          </form>

          <label class="switch">
            <input id="toggle-hillshade" type="checkbox" <?php echo $use_hs ? 'checked' : ''; ?>>
            <span class="slider round"></span>
          </label>
          <span class="toggle-option tog_to_hide">Hillshade</span>
        </div>
      </div>

      <!-- Speed selector -->
      <div class="mmv-speed-wrap">
        <label for="mmv-speed-select">Playback speed</label>
        <select id="mmv-speed-select" class="mmv-speed" data-role="speed">
          <option value="0.5">0.5×</option>
          <option value="0.75">0.75×</option>
          <option value="1" selected>1×</option>
          <option value="1.25">1.25×</option>
          <option value="1.5">1.5×</option>
          <option value="2">2×</option>
        </select>
      </div>
    </div>

    <!-- Row 2: Play button : Scrubber -->
    <div class="mmv-row mmv-row-bottom">
      <div class="mmv-left">
        <button class="mmv-btn" data-role="play" aria-label="Play/Pause">
          <span class="material-icons">play_arrow</span>
        </button>
      </div>

      <div class="mmv-center">
        <span class="mmv-bound mmv-start" data-role="label-start"><?php echo htmlspecialchars($startLabel, ENT_QUOTES); ?></span>
        <div class="mmv-scrub-wrap">
          <div class="mmv-window" aria-hidden="true"></div>
          <input class="mmv-range" data-role="seek" type="range" min="0" max="1000" value="0" step="1" aria-label="Seek">
        </div>
        <span class="mmv-bound mmv-end" data-role="label-end"><?php echo htmlspecialchars($endLabel, ENT_QUOTES); ?></span>
      </div>
    </div>
  </div>

  <div class="mmv-media">
    <video
      preload="metadata"
      playsinline
      poster="<?php echo htmlspecialchars($poster, ENT_QUOTES); ?>"
    >
      <source src="<?php echo htmlspecialchars($src_av1,  ENT_QUOTES); ?>" type="video/webm; codecs=av01.0.05M.08">
      <source src="<?php echo htmlspecialchars($src_vp9,  ENT_QUOTES); ?>" type="video/webm; codecs=vp9">
      <source src="<?php echo htmlspecialchars($src_h264, ENT_QUOTES); ?>" type="video/mp4">
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
    var wrap = root.closest('.mmv-wrap') || root;
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
  function initAll(){ document.querySelectorAll('.mmv-wrap').forEach(initOnePlayer); }
  document.addEventListener('DOMContentLoaded', initAll);
  // for lazy-loaded tabs
  window.rebindMultiMissionHandlers = initAll;
})();
</script>
