<?php
// multi_mission.php — custom-controls video player with server-side timeline labels (no audio controls)

// Optional: poster (use .webp/.jpg/.avif; omit if you prefer)
$poster   = 'multi_mission_quicklooks/last_frame.webp';
$src_av1  = 'multi_mission_quicklooks/multi_mission_av1.webm';
$src_vp9  = 'multi_mission_quicklooks/multi_mission_vp9.webm';
$src_h264 = 'multi_mission_quicklooks/multi_mission_h264.mp4';

// Timeline JSON (same folder as videos)
$timeline_json = __DIR__ . '/multi_mission_quicklooks/timeline.json';

// Read timeline server-side so labels show immediately
$startISO   = '1991-01';
$endISO     = '2025-12';
$startLabel = 'Jan 1991';
$endLabel   = 'Dec 2025';

if (is_file($timeline_json) && is_readable($timeline_json)) {
    $raw = file_get_contents($timeline_json);
    $cfg = json_decode($raw, true);
    if (is_array($cfg)) {
        if (!empty($cfg['start']))       $startISO   = $cfg['start'];
        if (!empty($cfg['end']))         $endISO     = $cfg['end'];
        if (!empty($cfg['start_label'])) $startLabel = $cfg['start_label'];
        if (!empty($cfg['end_label']))   $endLabel   = $cfg['end_label'];
    }
}

// Unique id so we can safely init even if injected later
$PLAYER_ID = 'mmv-player';
?>
<h3>Multi-mission SEC (1991–2025) — time series</h3>
<p>This video shows the Antarctic ice sheet surface elevation change time series. Use the controls to play, scrub, change speed, or go fullscreen.</p>

<style>
  :root { --mmv-blue:#21578b; --mmv-bg:#0f1a26; --mmv-rail:#d7dbe0; --mmv-rail-fill:#2e7bd1; --mmv-text:#111; }
  .mmv-wrap{
  margin: 10px auto;                 /* <— centers the player */
  max-width: var(--mmv-max, 1200px); /* <— default cap, can be overridden by JS */
  border: 1px solid #ddd;
  border-radius: 10px;
  overflow: hidden;
  background: #fff;
}

  .mmv-controls{position:relative; z-index:2; display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:10px;background:#f7f9fc;border-top:1px solid #e6ebf0;}
  .mmv-media{position:relative; z-index:1; background:#000;}
  .mmv-media video{display:block;width:100%;height:auto;aspect-ratio:780/780;background:#000;}

  .mmv-left,.mmv-right{display:flex;align-items:center;gap:10px}
  .mmv-center{flex:1 1 auto;display:flex;align-items:center;gap:10px}
  .mmv-btn{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border:1px solid #d9dee5;border-radius:8px;background:#fff;cursor:pointer;transition:background .15s,border-color .15s;}
  .mmv-btn:hover{background:#eef5ff;border-color:#c9d7ee}
  .mmv-btn .material-icons{font-size:20px;line-height:1}
  .mmv-speed{border:1px solid #d9dee5;border-radius:8px;background:#fff;height:36px;padding:0 8px;}
  .mmv-bound{font:13px/1.1 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Open Sans',sans-serif;color:#333;white-space:nowrap;}

  /* Scrub */
  .mmv-scrub-wrap{position:relative;flex:1 1 auto;display:flex;align-items:center;height:22px;min-width:160px}
  .mmv-range{appearance:none;background:transparent;width:100%;height:22px;cursor:pointer}
  .mmv-range:focus{outline:none}
  .mmv-range::-webkit-slider-runnable-track{height:6px;border-radius:999px;background:linear-gradient(to right,var(--mmv-rail-fill) var(--mmv-fill,0%),var(--mmv-rail) var(--mmv-fill,0%));}
  .mmv-range::-webkit-slider-thumb{appearance:none;width:0;height:0;border:0;background:transparent;margin-top:0}
  .mmv-range::-moz-range-track{height:6px;background:var(--mmv-rail);border-radius:999px}
  .mmv-range::-moz-range-thumb{width:0;height:0;border:0;background:transparent}

  /* Custom 5-year window “thumb” */
  .mmv-window{
    position:absolute; top:50%; transform:translateY(-50%);
    height:18px; border-radius:9px; border:1px solid #cbd5e1; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.06);
    pointer-events:none; width:64px;
  }

  @media (max-width:720px){ .mmv-bound{font-size:12px} }
</style>

<div
  class="mmv-wrap"
  id="<?php echo $PLAYER_ID; ?>"
  data-start="<?php echo htmlspecialchars($startISO, ENT_QUOTES); ?>"
  data-end="<?php echo htmlspecialchars($endISO, ENT_QUOTES); ?>"
  data-start-label="<?php echo htmlspecialchars($startLabel, ENT_QUOTES); ?>"
  data-end-label="<?php echo htmlspecialchars($endLabel, ENT_QUOTES); ?>"
  data-timeline-json="multi_mission_quicklooks/timeline.json"
>
  <div class="mmv-controls" role="group" aria-label="Video controls">
    <div class="mmv-left">
      <button class="mmv-btn" data-role="play" aria-label="Play/Pause"><span class="material-icons">play_arrow</span></button>
    </div>

    <div class="mmv-center">
      <span class="mmv-bound mmv-start" data-role="label-start"><?php echo htmlspecialchars($startLabel, ENT_QUOTES); ?></span>
      <div class="mmv-scrub-wrap">
        <div class="mmv-window" aria-hidden="true"></div>
        <input class="mmv-range" data-role="seek" type="range" min="0" max="1000" value="0" step="1" aria-label="Seek">
      </div>
      <span class="mmv-bound mmv-end" data-role="label-end"><?php echo htmlspecialchars($endLabel, ENT_QUOTES); ?></span>
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
      <!-- PiP REMOVED -->
      <button class="mmv-btn" data-role="fs" aria-label="Fullscreen"><span class="material-icons">fullscreen</span></button>
    </div>
  </div>

  <div class="mmv-media">
    <video preload="metadata" playsinline <?php if ($poster) echo 'poster="'.htmlspecialchars($poster,ENT_QUOTES).'"'; ?>>
      <source src="<?php echo htmlspecialchars($src_av1,ENT_QUOTES);  ?>" type="video/webm; codecs=av01.0.05M.08">
      <source src="<?php echo htmlspecialchars($src_vp9,ENT_QUOTES);  ?>" type="video/webm; codecs=vp9">
      <source src="<?php echo htmlspecialchars($src_h264,ENT_QUOTES); ?>" type="video/mp4">
      Your browser doesn’t support HTML5 video.
    </video>
  </div>
</div>

<script>
(function(){
  function parseYYYYMM(s){ var m=(s||'').match(/^(\d{4})-(\d{2})$/); return m?{y:+m[1],m:+m[2]}:null; }
  function monthsBetween(a,b){ return (!a||!b)?null:((b.y-a.y)*12 + (b.m-a.m) + 1); }

  function initOnePlayer(root){
  if (!root || root.dataset.bound === '1') return;
  var v    = root.querySelector('video');
  if (!v) return;

  var bPlay = root.querySelector('[data-role="play"]');
  var seek  = root.querySelector('[data-role="seek"]');
  var speed = root.querySelector('[data-role="speed"]');
  var bFs   = root.querySelector('[data-role="fs"]');

  // --- NEW flags
  var userScrubbed = false;      // true once the user drags the slider
  var initializedAtEnd = false;  // we set the slider to the end once

  function syncPlayIcon(){
    if (bPlay) bPlay.querySelector('.material-icons').textContent = v.paused ? 'play_arrow' : 'pause';
  }

  // --- Play/Pause with “start from 0 on first play” logic
  function togglePlay(){
    if (v.paused) {
      // If we initialized the slider at the end and the user hasn't scrubbed,
      // start the video from the beginning when Play is pressed.
      if (initializedAtEnd && !userScrubbed && seek && +seek.value === 1000) {
        v.currentTime = 0;
      }
      v.play();
    } else {
      v.pause();
    }
  }
  if (bPlay) bPlay.addEventListener('click', togglePlay);
  v.addEventListener('play',  syncPlayIcon);
  v.addEventListener('pause', syncPlayIcon);

  // --- Metadata: set the slider UI to the **end** to match the poster
  function onMeta(){
    // Put the thumb at 100% (end); do NOT change currentTime here.
    if (!initializedAtEnd && seek) {
      initializedAtEnd = true;
      seek.value = 1000;
      root.style.setProperty('--mmv-fill', '100%');
    }
  }
  v.addEventListener('loadedmetadata', onMeta);

  // --- Progress UI while playing (normal behaviour)
  function updateProgress(){
    if (!isFinite(v.duration)) return;
    if (seek && !v.paused) {
      var p = (v.currentTime / v.duration) * 1000 || 0;
      seek.value = Math.max(0, Math.min(1000, Math.round(p)));
      root.style.setProperty('--mmv-fill', (p/10) + '%');
    }
  }
  v.addEventListener('timeupdate', updateProgress);

  // --- Scrubbing
  var seeking = false, wasPlaying = false, seekRAF = null;
  function pctToTime(pct){ return (pct/1000) * (v.duration || 0); }

  if (seek){
    function beginSeek(){
      seeking = true; userScrubbed = true;
      wasPlaying = !v.paused; if (wasPlaying) v.pause();
    }
    seek.addEventListener('pointerdown', beginSeek);
    seek.addEventListener('mousedown',   beginSeek);
    seek.addEventListener('touchstart',  beginSeek, {passive:true});

    seek.addEventListener('input', function(){
      userScrubbed = true;
      var nt = pctToTime(seek.value);
      root.style.setProperty('--mmv-fill', (seek.value/10) + '%');
      if (seekRAF) cancelAnimationFrame(seekRAF);
      seekRAF = requestAnimationFrame(function(){ v.currentTime = nt; });
    });

    function finishSeek(){
      if (!seeking) return;
      var nt = pctToTime(seek.value);
      v.currentTime = nt;
      seeking = false;
      if (wasPlaying) v.play();
    }
    document.addEventListener('pointerup',   finishSeek);
    document.addEventListener('mouseup',     finishSeek);
    document.addEventListener('touchend',    finishSeek);
    seek.addEventListener('pointercancel',   finishSeek);
    seek.addEventListener('change',          finishSeek);
  }

  // Speed + fullscreen (unchanged)
  if (speed) speed.addEventListener('change', function(){ v.playbackRate = parseFloat(this.value); });
  function fsActive(){ return document.fullscreenElement === root; }
  function syncFsIcon(){ if(bFs) bFs.querySelector('.material-icons').textContent = fsActive() ? 'fullscreen_exit' : 'fullscreen'; }
  if (bFs){
    bFs.addEventListener('click', function(){ fsActive()?document.exitFullscreen():root.requestFullscreen?.(); });
    document.addEventListener('fullscreenchange', syncFsIcon);
  }

  // Keyboard (space/k, arrows, f)
  root.tabIndex = 0;
  root.addEventListener('keydown', function(e){
    switch(e.key){
      case ' ': case 'k': e.preventDefault(); togglePlay(); break;
      case 'ArrowLeft':  v.currentTime = Math.max(0, v.currentTime - 5); break;
      case 'ArrowRight': v.currentTime = Math.min(v.duration||0, v.currentTime + 5); break;
      case 'f': fsActive()?document.exitFullscreen():root.requestFullscreen?.(); break;
    }
  });

  // Fit wrapper to natural width (if you’re using this)
  var wrap = root.closest('.mmv-wrap') || root;
  function fitToNaturalWidth(){ if (v.videoWidth > 0) wrap.style.setProperty('--mmv-max', v.videoWidth + 'px'); }
  if (v.readyState >= 1) onMeta(), fitToNaturalWidth();
  v.addEventListener('loadedmetadata', fitToNaturalWidth);

  syncPlayIcon();
  root.dataset.bound = '1';
}


  function initAllPlayers(){ document.querySelectorAll('.mmv-wrap').forEach(initOnePlayer); }
  document.addEventListener('DOMContentLoaded', initAllPlayers);
  window.rebindMultiMissionHandlers = initAllPlayers;
})();

// Fit wrapper to natural width (avoid black gutters on wide screens)
var wrap = root.closest('.mmv-wrap') || root;
function fitToNaturalWidth(){
  if (v.videoWidth > 0) {
    // Set CSS variable the CSS rule reads
    wrap.style.setProperty('--mmv-max', v.videoWidth + 'px');
  }
}
if (v.readyState >= 1) fitToNaturalWidth();
v.addEventListener('loadedmetadata', fitToNaturalWidth);

</script>
