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
        <button class="tablinks tab-download" onclick="openTab(event, 'multi_mission')">Multi-Mission 5-year SEC</button>
        <button class="tablinks tab-download" onclick="openTab(event, 'annual_dh')">Annual dH</button>
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
    var v     = root.querySelector('video');
    if (!v) return;

    // If <source> tags are present, fine. If using data-* only, set src (Safari).
    if (!v.currentSrc || v.duration === 0) {
      if (!v.src && (v.dataset.srcAv1 || v.dataset.srcVp9 || v.dataset.srcH264)) {
        var best = chooseSrc(v);
        if (best) { v.src = best; v.load(); }
      }
    }

    var bPlay = root.querySelector('[data-role="play"]');
    var seek  = root.querySelector('[data-role="seek"]');
    var cur   = root.querySelector('[data-role="cur"]');
    var dur   = root.querySelector('[data-role="dur"]');
    var speed = root.querySelector('[data-role="speed"]');
    var bPip  = root.querySelector('[data-role="pip"]');
    var bFs   = root.querySelector('[data-role="fs"]');

    function syncPlayIcon(){ if(bPlay) bPlay.querySelector('.material-icons').textContent = v.paused ? 'play_arrow' : 'pause'; }
    if (bPlay) bPlay.addEventListener('click', function(){ v.paused ? v.play() : v.pause(); });
    v.addEventListener('play',  syncPlayIcon);
    v.addEventListener('pause', syncPlayIcon);

    function onMeta(){ if (dur) dur.textContent = fmt(v.duration); updateProgress(); }
    v.addEventListener('loadedmetadata', onMeta);

    // --- drop-in replacement for the seek logic ---
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
  }
  if (cur) cur.textContent = fmt(v.currentTime);
}
v.addEventListener('timeupdate', updateProgress);
v.addEventListener('progress',   updateProgress);

if (seek){
  // Begin drag (support mouse/touch)
  function beginSeek(){
    seeking = true;
    wasPlaying = !v.paused;
    if (wasPlaying) v.pause();
  }
  seek.addEventListener('pointerdown', beginSeek);
  seek.addEventListener('mousedown',   beginSeek);
  seek.addEventListener('touchstart',  beginSeek, {passive:true});

  // Live scrub: update currentTime on every input (throttled by rAF)
  seek.addEventListener('input', function(){
    var nt = pctToTime(seek.value);
    if (cur) cur.textContent = fmt(nt);
    root.style.setProperty('--mmv-fill', (seek.value/10) + '%');

    if (seekRAF) cancelAnimationFrame(seekRAF);
    seekRAF = requestAnimationFrame(function(){
      v.currentTime = nt; // Chrome renders preview while paused
    });
  });

  // Finish drag (catch mouseup/touchend anywhere on the page)
  function finishSeek(){
    if (!seeking) return;
    var nt = pctToTime(seek.value);
    v.currentTime = nt;   // ensure final position applied
    seeking = false;
    if (wasPlaying) v.play();
  }
  document.addEventListener('pointerup',   finishSeek);
  document.addEventListener('mouseup',     finishSeek);
  document.addEventListener('touchend',    finishSeek);
  seek.addEventListener('pointercancel',   finishSeek);
  seek.addEventListener('change',          finishSeek); // keyboard/fallback

  // Keep UI in sync around seek events
  v.addEventListener('seeking',  updateProgress);
  v.addEventListener('seeked',   updateProgress);
}


    if (speed) speed.addEventListener('change', function(){ v.playbackRate = parseFloat(this.value); });

    if (bPip && 'pictureInPictureEnabled' in document) {
      bPip.style.display = '';
      bPip.addEventListener('click', async function(){
        try{ if (document.pictureInPictureElement) await document.exitPictureInPicture(); else await v.requestPictureInPicture(); }
        catch(e){ console.warn('PiP error', e); }
      });
    }

    function fsActive(){ return document.fullscreenElement === root; }
    function syncFsIcon(){ if(bFs) bFs.querySelector('.material-icons').textContent = fsActive() ? 'fullscreen_exit' : 'fullscreen'; }
    if (bFs){
      bFs.addEventListener('click', function(){
        if (!fsActive()){ if (root.requestFullscreen) root.requestFullscreen(); }
        else{ if (document.exitFullscreen) document.exitFullscreen(); }
      });
      document.addEventListener('fullscreenchange', syncFsIcon);
    }

    // Keyboard
    root.tabIndex = 0;
    root.addEventListener('keydown', function(e){
      switch(e.key){
        case ' ': case 'k': e.preventDefault(); v.paused ? v.play() : v.pause(); break;
        case 'ArrowLeft':  v.currentTime = Math.max(0, v.currentTime - 5); break;
        case 'ArrowRight': v.currentTime = Math.min(v.duration||0, v.currentTime + 5); break;
        case 'f': fsActive()?document.exitFullscreen():root.requestFullscreen?.(); break;
      }
    });

    // Fit wrapper to natural width (avoid black gutters)
    var wrap = root.closest('.mmv-wrap') || root;
    function fitToNaturalWidth(){ if (v.videoWidth > 0) wrap.style.maxWidth = v.videoWidth + 'px'; }
    if (v.readyState >= 1) fitToNaturalWidth();
    v.addEventListener('loadedmetadata', fitToNaturalWidth);

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




</html>
