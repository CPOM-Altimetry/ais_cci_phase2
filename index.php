<!DOCTYPE html>
<!-- 
  Run locally with:
  /usr/local/bin/php -S localhost:8000
-->
<html lang="en">
<head>
    <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

    <title>AIS CCI+ Phase-2: SEC Products</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="./css/main.css" type="text/css" media="all">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=background_grid_small" />
    <?php include 'php_init.php';?>
</head>
<body>
    <div class="blue-banner">
        <div class="header-txt-large">Surface Elevation Change of the Antarctic Ice Sheet</div>
        <div class="header-txt-small">Surface Elevation Change of the Antarctic Ice Sheet</div>
    </div>

    <div class="light-grey-row">
        <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
            <div style="flex:1; min-width:220px;">
                Antarctic Ice Sheet Surface Elevation Change (SEC) products processed 
                by <a href="https://cpom.org.uk/">CPOM</a> from available Radar 
                and Laser satellite Altimetry missions since 1991 for the 
                <a href="https://climate.esa.int/en/projects/ice-sheets-antarctic/">Antarctic CCI+ Phase-2 project</a> for 
                <a href="https://climate.esa.int/">ESA</a>. Products are available from this CPOM hosted portal or through the 
                <a href="http://cci.esa.int/data">CCI Open Data Portal</a>
            </div>
            <div id="latest_release">
                Latest release: <br>Nov 2025
            </div>
        </div>
    </div>

    <div  class="tab">
        <button id="tab_row" class="tablinks" <?php if (!$show_single_mission) echo 'id="defaultOpen"'; ?> onclick="openTab(event, 'intro')">Product Types</button>
        <button class="tablinks tab-download" onclick="openTab(event, 'multi_mission')">Multi-Mission 5-year dH/dt</button>
        <button class="tablinks tab-download" onclick="openTab(event, 'annual_dh')">Cumulative Annual dH</button>
        <button class="tablinks tab-download" <?php if ($show_single_mission) echo 'id="defaultOpen"'; ?> onclick="openTab(event, 'single_mission')">Single RA Mission SEC</button>
        <button class="tablinks tab-download" onclick="openTab(event, 'is2_sec')">ICESat-2 SEC</button>
        <button class="tablinks tab-download tab-right" onclick="openTab(event, 'download')">
            <span class="material-icons" aria-hidden="true">download</span>
            <span class="download_txt">Download<br>Products</span>
        </button>
    </div>

    <!-- Tab panes -->
    <div id="intro" class="tabcontent tabcontent-intro">
    <?php if ($active_tab === 'intro') include __DIR__.'/intro.php'; ?>
    </div>

    <div id="single_mission" class="tabcontent">
    <?php if ($active_tab === 'single_mission') include __DIR__.'/single_mission.php'; ?>
    </div>

    <div id="is2_sec" class="tabcontent">
    <?php if ($active_tab === 'is2_sec') include __DIR__.'/is2_sec.php'; ?>
    </div>

    <div id="multi_mission" class="tabcontent">
    <?php if ($active_tab === 'multi_mission') include __DIR__.'/multi_mission.php'; ?>
    </div>

    <div id="annual_dh" class="tabcontent">
    <?php if ($active_tab === 'annual_dh') include __DIR__.'/annual_dh.php'; ?>
    </div>

    <div id="download" class="tabcontent">
    <?php if ($active_tab === 'download') include __DIR__.'/download.php'; ?>
    </div>
    
    <!-- Page content goes here -->
</body>

<!-- Javascript -->
<script>
/* ===== Config from PHP (for initial fetch params) ===== */
const TAB_DEFAULTS = {
  active:  "<?php echo $active_tab; ?>",
  qlParam: "<?php echo htmlspecialchars($ql_param, ENT_QUOTES); ?>",
  shade:   "<?php echo htmlspecialchars($hillshade, ENT_QUOTES); ?>",
  view:    "<?php echo htmlspecialchars($single_mission_view, ENT_QUOTES); ?>"
};

/* ===== Helpers ===== */
function showTabUI(evt, tabId) {
  var i, tabcontent = document.getElementsByClassName("tabcontent"),
         tablinks   = document.getElementsByClassName("tablinks");
  for (i = 0; i < tabcontent.length; i++) tabcontent[i].style.display = "none";
  for (i = 0; i < tablinks.length;   i++) tablinks[i].className = tablinks[i].className.replace(" active", "");
  var pane = document.getElementById(tabId);
  if (pane) pane.style.display = "block";
  if (evt && evt.currentTarget) {
    evt.currentTarget.className += " active";
  } else {
    var btn = document.querySelector('.tab button[onclick*="' + tabId + '"]');
    if (btn) btn.className += " active";
  }
}

function paneIsLoaded(pane){
  if (!pane) return false;
  if (pane.dataset && pane.dataset.loaded === "1") return true;
  return (pane.innerHTML && pane.innerHTML.trim().length > 10);
}

function markPaneLoaded(pane){
  if (pane && pane.dataset) pane.dataset.loaded = "1";
}

/* Bind listeners that the tab’s HTML expects (works for both SSR and lazy) */
function initTabBehaviors(tabId){
  const pane = document.getElementById(tabId);
  if (!pane) return;

  // Hillshade (supports both generic ids and IS2-specific ids)
  const toggle = pane.querySelector('#is2-toggle-hillshade, #toggle-hillshade');
  if (toggle && !toggle.dataset.bound) {
    toggle.addEventListener('change', function(){
      const form   = pane.querySelector('#is2-hillshade-form, #hillshade-form');
      const hidden = pane.querySelector('#is2-hillshade-input, #hillshade-input');
      if (!form || !hidden) return;
      hidden.value = this.checked ? 'show' : 'hide';
      form.submit();
    });
    toggle.dataset.bound = "1";
  }

  // Mission dropdown (if present)
  const missionSel  = pane.querySelector('#mission-select');
  const missionForm = pane.querySelector('#mission-select-form, #mission-radio-form');
  if (missionSel && missionForm && !missionSel.dataset.bound) {
    missionSel.addEventListener('change', function(){ missionForm.submit(); });
    missionSel.dataset.bound = "1";
  }

  // If this tab contains a video player, (re)bind it
  if (pane.querySelector('.mmv-wrap')) {
    if (window.rebindMultiMissionHandlers) window.rebindMultiMissionHandlers();
  }
}

/* ===== Lazy loader ===== */
const loadedTabs = new Set();

function fetchTabHtml(tabId){
  const pane = document.getElementById(tabId);
  if (!pane) return Promise.resolve();

  pane.innerHTML = '<div style="padding:16px;color:#666;">Loading…</div>';

  const params = new URLSearchParams({
    active_tab: tabId,
    ql_param:   TAB_DEFAULTS.qlParam,
    hillshade:  TAB_DEFAULTS.shade,
    single_mission_view: TAB_DEFAULTS.view
  });

  return fetch('tab_router.php?' + params.toString(), { credentials: 'same-origin' })
    .then(r => r.text())
    .then(html => {
      pane.innerHTML = html;
      markPaneLoaded(pane);
      loadedTabs.add(tabId);
      initTabBehaviors(tabId);
    })
    .catch(err => {
      console.error(err);
      pane.innerHTML = '<div style="padding:16px;color:#b00;">Failed to load tab.</div>';
    });
}

/* ===== Public: openTab (click handler on buttons) ===== */
function openTab(evt, tabId) {
  showTabUI(evt, tabId);
  const pane = document.getElementById(tabId);
  if (paneIsLoaded(pane)) {
    initTabBehaviors(tabId);
    return;
  }
  fetchTabHtml(tabId);
}

/* ===== Initial boot ===== */
document.addEventListener('DOMContentLoaded', function () {
  var tabId = TAB_DEFAULTS.active || 'intro';

  if (tabId !== 'single_mission') {
    try {
      var url = new URL(window.location.href);
      if (url.searchParams.has('show_single_mission')) {
        url.searchParams.delete('show_single_mission');
        window.history.replaceState({}, "", url.toString());
      }
    } catch(e) {}
  }

  showTabUI(null, tabId);
  const pane = document.getElementById(tabId);
  if (!paneIsLoaded(pane)) {
    fetchTabHtml(tabId);
  } else {
    initTabBehaviors(tabId);
  }
});

/* ===== Minimal, ID-less video player bootstrap (no PHP vars) ===== */
(function(){
  function fmt(t){ if(!isFinite(t)||t<0) return '0:00'; var m=Math.floor(t/60), s=Math.floor(t%60); return m + ':' + (s<10?'0':'') + s; }
  function chooseSrc(v){
    try{
      if (v.canPlayType('video/webm; codecs="av01.0.05M.08"')) return v.dataset.srcAv1 || '';
      if (v.canPlayType('video/webm; codecs="vp9"'))          return v.dataset.srcVp9 || '';
      return v.dataset.srcH264 || '';
    }catch(e){ return v.dataset.srcH264 || ''; }
  }

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

  // Oblong window pieces
  var scrubWrap = root.querySelector('.mmv-scrub-wrap');
  var winEl     = root.querySelector('.mmv-window');

  // --- Safari-friendly source selection (works with <source> or data-* sources) ---
  if (!v.currentSrc || v.duration === 0) {
    if (!v.src && (v.dataset.srcAv1 || v.dataset.srcVp9 || v.dataset.srcH264)) {
      var best = chooseSrc(v);
      if (best) { v.src = best; v.load(); }
    }
  }

  // --- Helpers ---
  function syncPlayIcon(){
    if (bPlay) bPlay.querySelector('.material-icons').textContent = v.paused ? 'play_arrow' : 'pause';
  }
  function onMeta(){
    if (dur) dur.textContent = fmt(v.duration);
    updateProgress();
    sizeWindow();  // ensure correct initial sizing after metadata arrives
  }

  // 5-year window fraction of total (1991–2025 -> 34 years)
  var WINDOW_FRAC = 5 / (2025 - 1991); // ≈ 0.14706

  function sizeWindow(){
    if (!scrubWrap || !winEl) return;
    var w  = scrubWrap.clientWidth;
    var px = Math.max(16, Math.round(w * WINDOW_FRAC)); // keep a sensible minimum
    winEl.style.width = px + 'px';
    positionWindow(); // reposition after sizing
  }
  function positionWindow(){
    if (!scrubWrap || !winEl || !seek) return;
    var trackW = scrubWrap.clientWidth;
    var thumbW = winEl.offsetWidth || 0;
    var pct    = (seek.value/1000);           // 0..1
    var x      = Math.round(pct * (trackW - thumbW));
    winEl.style.left = x + 'px';
  }

  // --- Wire play/pause ---
  if (bPlay) bPlay.addEventListener('click', function(){ v.paused ? v.play() : v.pause(); });
  v.addEventListener('play',  syncPlayIcon);
  v.addEventListener('pause', syncPlayIcon);

  // --- Metadata / duration ---
  v.addEventListener('loadedmetadata', onMeta);

  // --- Progress & scrubbing (Chrome-friendly live thumbnails) ---
  var seeking = false;
  var wasPlaying = false;
  var seekRAF = null;

  function pctToTime(pct){ return (pct/1000) * (v.duration || 0); }

  function updateProgress(){
    if (seeking || !isFinite(v.duration)) return;
    var p = (v.currentTime / v.duration) * 1000 || 0;
    if (seek) {
      seek.value = Math.max(0, Math.min(1000, Math.round(p)));
      root.style.setProperty('--mmv-fill', (p/10) + '%');
      positionWindow(); // keep 5-year window in sync during playback
    }
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
      var nt = pctToTime(seek.value);
      if (cur) cur.textContent = fmt(nt);
      root.style.setProperty('--mmv-fill', (seek.value/10) + '%');
      positionWindow();

      if (seekRAF) cancelAnimationFrame(seekRAF);
      seekRAF = requestAnimationFrame(function(){
        v.currentTime = nt; // Chrome shows the frame while paused
      });
    });

    function finishSeek(){
      if (!seeking) return;
      var nt = pctToTime(seek.value);
      v.currentTime = nt;   // ensure final position applied
      seeking = false;
      positionWindow();
      if (wasPlaying) v.play();
    }
    document.addEventListener('pointerup',    finishSeek);
    document.addEventListener('mouseup',      finishSeek);
    document.addEventListener('touchend',     finishSeek);
    seek.addEventListener('pointercancel',    finishSeek);
    seek.addEventListener('change',           finishSeek); // keyboard/fallback

    v.addEventListener('seeking', updateProgress);
    v.addEventListener('seeked',  updateProgress);
  }

  // --- Speed ---
  if (speed) speed.addEventListener('change', function(){ v.playbackRate = parseFloat(this.value); });

  // --- PiP (if available) ---
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

  // --- Fit wrapper to natural width (reduces black gutters) ---
  var wrap = root.closest('.mmv-wrap') || root;
  function fitToNaturalWidth(){ if (v.videoWidth > 0) wrap.style.maxWidth = v.videoWidth + 'px'; }
  if (v.readyState >= 1) fitToNaturalWidth();
  v.addEventListener('loadedmetadata', fitToNaturalWidth);

  // --- Size the 5-year window now and on resize ---
  sizeWindow();
  window.addEventListener('resize', sizeWindow, { passive:true });

  // Initial UI sync
  syncPlayIcon();
  if (v.readyState >= 1) onMeta();

  root.dataset.bound = '1';
}


  function initAllPlayers(){
    document.querySelectorAll('.mmv-wrap').forEach(initOnePlayer);
  }

  document.addEventListener('DOMContentLoaded', initAllPlayers);
  window.rebindMultiMissionHandlers = initAllPlayers;
})();
</script>

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
    var winEl  = root.querySelector('.adhv-window');
    var rail   = root.querySelector('.adhv-scrub-wrap');

    // Hillshade (same ids as other tabs)
    var hsToggle = root.querySelector('#toggle-hillshade');
    var hsForm   = root.querySelector('#hillshade-form');
    var hsInput  = root.querySelector('#hillshade-input');
    var hsTab    = root.querySelector('#active_tab_input');

    if (!v || !seek || !rail || !winEl) return;

    /* ---- timeline for window width ---- */
    var startISO = root.dataset.start || '1993-01';
    var endISO   = root.dataset.end   || '2025-08';
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
      root.style.setProperty('--adhv-fill', (seek.value/10) + '%');
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
      root.style.setProperty('--adhv-fill', (seek.value/10) + '%');
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
    var wrap = root.closest('.adhv-wrap-annual-dh') || root;
    function fitToNaturalWidth(){
      if (v.videoWidth > 0) wrap.style.setProperty('--adhv-max', v.videoWidth + 'px');
    }

    /* ---- initialize slider UI to END to match poster (UI only) ---- */
    function setToEndUI(){
      if (initialEndApplied) return;
      seek.value = 1000;
      root.style.setProperty('--adhv-fill', '100%');
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
  function initAll(){ document.querySelectorAll('.adhv-wrap-annual-dh').forEach(initOnePlayer); }
  document.addEventListener('DOMContentLoaded', initAll);
  // for lazy-loaded tabs
  window.rebindMultiMissionHandlers = initAll;
})();
</script>


</html>
