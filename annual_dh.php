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

// ---- View selection: 'ais' (default) or 'ase'
$adh_view = isset($_POST['adh_view']) ? (($_POST['adh_view']==='ase') ? 'ase' : 'ais')
         : (isset($_GET['adh_view'])  ? (($_GET['adh_view']==='ase')  ? 'ase' : 'ais') : 'ais');
$view_suffix = ($adh_view === 'ase') ? '-ase' : '';

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
  :root { --adhv-rail:#d7dbe0; --adhv-rail-fill:#2e7bd1; }
  .adhv-wrap-annual-dh{ margin:10px auto; max-width:var(--adhv-max,1200px); border:1px solid #ddd; border-radius:10px; overflow:hidden; background:#fff; }
  .adhv-media{ background:#000; }
  .adhv-media video{ display:block; width:100%; height:auto; aspect-ratio:900/750; background:#000; }

  .adhv-controls-annual-dh{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px; background:#f7f9fc; border-top:1px solid #e6ebf0; }
  .adhv-left,.adhv-right{ display:flex; align-items:center; gap:10px }
  .adhv-center{ flex:1 1 auto; display:flex; align-items:center; gap:10px }

  .adhv-btn{ display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border:1px solid #d9dee5; border-radius:8px; background:#fff; cursor:pointer; transition:background .15s,border-color .15s; }
  .adhv-btn:hover{ background:#eef5ff; border-color:#c9d7ee }
  .adhv-btn .material-icons{ font-size:20px; line-height:1 }

  .adhv-speed{ border:1px solid #d9dee5; border-radius:8px; background:#fff; height:36px; padding:0 8px; }
  .adhv-bound{ font:13px/1.1 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Open Sans',sans-serif; color:#333; white-space:nowrap; }

  .adhv-scrub-wrap{ position:relative; flex:1 1 auto; display:flex; align-items:center; height:22px; min-width:160px }
  .adhv-range{ appearance:none; background:transparent; width:100%; height:22px; cursor:pointer; z-index:2000; }
  .adhv-range:focus{ outline:none }
  .adhv-range::-webkit-slider-runnable-track{ height:6px; border-radius:999px; background:linear-gradient(to right,var(--adhv-rail-fill) var(--adhv-fill,0%),var(--adhv-rail) var(--adhv-fill,0%)); }
  .adhv-range::-webkit-slider-thumb{ appearance:none; width:0; height:0; border:0; background:transparent; margin-top:0 }
  .adhv-range::-moz-range-track{ height:6px; background:var(--adhv-rail); border-radius:999px }
  .adhv-range::-moz-range-thumb{ width:0; height:0; border:0; background:transparent }

  .adhv-window{
    position:absolute; top:50%; transform:translateY(-50%);
    height:18px; border-radius:9px; border:1px solid #cbd5e1; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.06);
    pointer-events:none; width:64px;
  }
  /* 1) Don't clip the dropdown with the outer card */
  .adhv-wrap-annual-dh { overflow: visible; }

  /* Keep the rounded-corner clipping only on the media area */
  .adhv-media {
    overflow: hidden;
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
  }

  /* 2) Make sure the select sits above nearby controls */
  .adhv-controls-annual-dh { position: relative; overflow: visible; }
  .param-select, .adhv-speed, select#mmp-param {
    position: relative;
    z-index: 1000; /* above scrub UI */
  }

  /* Lower the scrubber's stacking level */
  .adhv-scrub-wrap, .adhv-scrub-wrap * {
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
.adhv-wrap-annual-dh { position: relative; z-index: 0; }
.adhv-controls-annual-dh { position: relative; z-index: 1; }

/* (optional) ensure individual slider bits don't float above the dropdown */
.adhv-scrub-wrap, .adhv-range, .adhv-window {
  position: relative;
  z-index: 1;
}

.adhv-view-select {
  background:white; color:#000; border-radius:6px;padding: 6px;
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
      <form class="adhv-view" id="adhv-view-form" method="POST" action="">
        <label for="adhv-view-select">View:</label>
        <select id="adhv-view-select" class="adhv-view-select" name="adh_view" aria-label="Select view" onchange="this.form.submit()">
          <option value="ais" <?php echo $adh_view==='ais'?'selected':''; ?>>Antarctica Ice Sheet</option>
          <option value="ase" <?php echo $adh_view==='ase'?'selected':''; ?>>ASE: PIG, Thwaites Glaciers</option>
        </select>
        <input type="hidden" name="active_tab" value="annual_dh">
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
<div class="adhv-wrap-annual-dh" id="annual-dh-player"
     data-start="1993-01"
     data-end="2025-08"
     data-start-label="Jan 1993"
     data-end-label="Aug 2025">
  <div class="adhv-controls-annual-dh" role="group" aria-label="Video controls">
    <div class="adhv-left">
      <button class="adhv-btn" data-role="play" aria-label="Play/Pause">
        <span class="material-icons">play_arrow</span>
      </button>
    </div>

    <div class="adhv-center">
      <span class="adhv-bound adhv-start" data-role="label-start">Jan 1993</span>
      <div class="adhv-scrub-wrap">
        <div class="adhv-window" aria-hidden="true"></div>
        <input class="adhv-range" data-role="seek" type="range" min="0" max="32" value="0" step="1" aria-label="Seek">
      </div>
      <span class="adhv-bound adhv-end" data-role="label-end">Aug 2025</span>
    </div>

    <div class="adhv-right">
      <select class="adhv-speed" data-role="speed" aria-label="Playback speed">
        <option value="0.5">0.5×</option>
        <option value="0.75">0.75×</option>
        <option value="1" selected>1×</option>
        <option value="1.25">1.25×</option>
        <option value="1.5">1.5×</option>
        <option value="2">2×</option>
      </select>
    </div>
  </div>

  <div class="adhv-media">
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

  // --- Elements ---
  var v       = root.querySelector('video');
  if (!v) return;
  var bPlay   = root.querySelector('[data-role="play"]');
  var seek    = root.querySelector('[data-role="seek"]');
  var cur     = root.querySelector('[data-role="cur"]');
  var dur     = root.querySelector('[data-role="dur"]');
  var speed   = root.querySelector('[data-role="speed"]');
  var bPip    = root.querySelector('[data-role="pip"]');
  var bFs     = root.querySelector('[data-role="fs"]');

  // Oblong window bits (only present for multi-mission)
  var scrubWrap = root.querySelector('.adhv-scrub-wrap');
  var winEl     = root.querySelector('.adhv-window');

  // --- Slider range: now generic (works for annual & 5-year) ---
  var seekMin   = seek ? parseFloat(seek.min || 0)    : 0;
  var seekMax   = seek ? parseFloat(seek.max || 1000) : 1000;
  var seekRange = seekMax - seekMin;
  if (!isFinite(seekRange) || seekRange <= 0) seekRange = 1000;

  function sliderFrac(){         // 0..1 from slider
    if (!seek) return 0;
    var val = parseFloat(seek.value || 0);
    return Math.max(0, Math.min(1, (val - seekMin)/seekRange));
  }
  function setSliderFromFrac(frac){ // set slider & track fill from 0..1
    if (!seek) return;
    frac = Math.max(0, Math.min(1, frac || 0));
    seek.value = seekMin + frac * seekRange;
    root.style.setProperty('--adhv-fill', (frac * 100) + '%');
    positionWindow(); // keep 5-year window (if present) in sync
  }
  function timeFromFrac(frac){
    frac = Math.max(0, Math.min(1, frac || 0));
    return (v.duration || 0) * frac;
  }
  function fracFromTime(t){
    if (!isFinite(v.duration) || v.duration <= 0) return 0;
    t = Math.max(0, Math.min(v.duration, t || 0));
    return t / v.duration;
  }

  // --- Source selection helper (unchanged) ---
  function chooseSrc(v){
    try{
      if (v.canPlayType('video/webm; codecs="av01.0.05M.08"')) return v.dataset.srcAv1 || '';
      if (v.canPlayType('video/webm; codecs="vp9"'))          return v.dataset.srcVp9 || '';
      return v.dataset.srcH264 || '';
    }catch(e){ return v.dataset.srcH264 || ''; }
  }

  // If using data-* only, set src for Safari
  if (!v.currentSrc || v.duration === 0) {
    if (!v.src && (v.dataset.srcAv1 || v.dataset.srcVp9 || v.dataset.srcH264)) {
      var best = chooseSrc(v);
      if (best) { v.src = best; v.load(); }
    }
  }

  // --- 5-year window geometry (no effect on annual player) ---
  var WINDOW_FRAC = 5 / (2025 - 1991); // only meaningful where winEl exists

  function sizeWindow(){
    if (!scrubWrap || !winEl) return;
    var w  = scrubWrap.clientWidth;
    var px = Math.max(16, Math.round(w * WINDOW_FRAC)); // sensible minimum
    winEl.style.width = px + 'px';
    positionWindow();
  }
  function positionWindow(){
    if (!scrubWrap || !winEl || !seek) return;
    var trackW = scrubWrap.clientWidth;
    var thumbW = winEl.offsetWidth || 0;
    var f      = sliderFrac();
    var x      = Math.round(f * (trackW - thumbW));
    winEl.style.left = x + 'px';
  }

  // --- UI helpers ---
  function fmt(t){
    if(!isFinite(t)||t<0) return '0:00';
    var m=Math.floor(t/60), s=Math.floor(t%60);
    return m + ':' + (s<10?'0':'') + s;
  }
  function syncPlayIcon(){
    if (!bPlay) return;
    bPlay.querySelector('.material-icons').textContent = v.paused ? 'play_arrow' : 'pause';
  }
  function onMeta(){
    if (dur) dur.textContent = fmt(v.duration);
    // put slider at start unless you later add “start at end” logic
    setSliderFromFrac(fracFromTime(v.currentTime || 0));
  }

  // --- Play / pause ---
  if (bPlay) bPlay.addEventListener('click', function(){ v.paused ? v.play() : v.pause(); });
  v.addEventListener('play',  syncPlayIcon);
  v.addEventListener('pause', syncPlayIcon);

  v.addEventListener('loadedmetadata', onMeta);

  // --- Progress & scrubbing (now using fractions) ---
  var seeking = false;
  var wasPlaying = false;
  var seekRAF = null;

  function updateProgress(){
    if (seeking) return;
    if (!isFinite(v.duration) || v.duration <= 0) return;
    var f = fracFromTime(v.currentTime);
    setSliderFromFrac(f);
    if (cur) cur.textContent = fmt(v.currentTime);
  }
  v.addEventListener('timeupdate', updateProgress);
  v.addEventListener('progress',   updateProgress);

  if (seek){
    function beginSeek(){
      seeking = true;
      wasPlaying = !v.paused;
      if (wasPlaying) v.pause();
    }
    seek.addEventListener('pointerdown', beginSeek);
    seek.addEventListener('mousedown',   beginSeek);
    seek.addEventListener('touchstart',  beginSeek, {passive:true});

    seek.addEventListener('input', function(){
      var f  = sliderFrac();
      var nt = timeFromFrac(f);
      if (cur) cur.textContent = fmt(nt);

      if (seekRAF) cancelAnimationFrame(seekRAF);
      seekRAF = requestAnimationFrame(function(){
        v.currentTime = nt;
        setSliderFromFrac(f); // keep gradient + window in sync
      });
    });

    function finishSeek(){
      if (!seeking) return;
      seeking = false;
      var f  = sliderFrac();
      v.currentTime = timeFromFrac(f);
      setSliderFromFrac(f);
      if (wasPlaying) v.play();
    }
    document.addEventListener('pointerup',   finishSeek);
    document.addEventListener('mouseup',     finishSeek);
    document.addEventListener('touchend',    finishSeek);
    seek.addEventListener('pointercancel',   finishSeek);
    seek.addEventListener('change',          finishSeek);
  }

  // --- Speed ---
  if (speed) speed.addEventListener('change', function(){ v.playbackRate = parseFloat(this.value); });

  // --- PiP (if ever used) ---
  if (bPip && 'pictureInPictureEnabled' in document) {
    bPip.style.display = '';
    bPip.addEventListener('click', async function(){
      try{
        if (document.pictureInPictureElement) await document.exitPictureInPicture();
        else await v.requestPictureInPicture();
      }catch(e){ console.warn('PiP error', e); }
    });
  }

  // --- Fullscreen ---
  function fsActive(){ return document.fullscreenElement === root; }
  function syncFsIcon(){ if(bFs) bFs.querySelector('.material-icons').textContent = fsActive() ? 'fullscreen_exit' : 'fullscreen'; }
  if (bFs){
    bFs.addEventListener('click', function(){
      if (!fsActive()){ if (root.requestFullscreen) root.requestFullscreen(); }
      else{ if (document.exitFullscreen) document.exitFullscreen(); }
    });
    document.addEventListener('fullscreenchange', syncFsIcon);
  }

  // --- Keyboard shortcuts ---
  root.tabIndex = 0;
  root.addEventListener('keydown', function(e){
    switch(e.key){
      case ' ': case 'k': e.preventDefault(); v.paused ? v.play() : v.pause(); break;
      case 'ArrowLeft':  v.currentTime = Math.max(0, v.currentTime - 5); break;
      case 'ArrowRight': v.currentTime = Math.min(v.duration||0, v.currentTime + 5); break;
      case 'f': fsActive()?document.exitFullscreen():root.requestFullscreen?.(); break;
    }
  });

  // --- Fit wrapper to natural width (minimise black bars) ---
  var wrap = root.closest('.mmv-wrap') || root;
  function fitToNaturalWidth(){ if (v.videoWidth > 0) wrap.style.maxWidth = v.videoWidth + 'px'; }
  if (v.readyState >= 1) { fitToNaturalWidth(); onMeta(); }
  v.addEventListener('loadedmetadata', fitToNaturalWidth);

  // 5-year window sizing (no effect where winEl doesn’t exist)
  sizeWindow();
  window.addEventListener('resize', sizeWindow, { passive:true });

  syncPlayIcon();
  root.dataset.bound = '1';
}

  /* --------------- boot / rebind --------------- */
  function initAll(){ document.querySelectorAll('.adhv-wrap-annual-dh').forEach(initOnePlayer); }
  document.addEventListener('DOMContentLoaded', initAll);
  // for lazy-loaded tabs
  window.rebindMultiMissionHandlers = initAll;
})();
</script>
