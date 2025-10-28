<?php
// multi_mission.php — custom-controls video player with hillshade toggle (markup+styles only)

// ---- Hillshade default (comes from php_init.php as 'show' | 'hide')
$use_hs = (isset($hillshade) ? $hillshade === 'show' : true);

// ---- Non-HS assets
$poster_no   = 'multi_mission_quicklooks/last_frame.webp';
$src_av1_no  = 'multi_mission_quicklooks/multi_mission_av1.webm';
$src_vp9_no  = 'multi_mission_quicklooks/multi_mission_vp9.webm';
$src_h264_no = 'multi_mission_quicklooks/multi_mission_h264.mp4';

// ---- HS assets
$poster_hs   = 'multi_mission_quicklooks/last_frame_hs.webp';
$src_av1_hs  = 'multi_mission_quicklooks/multi_mission_av1_hs.webm';
$src_vp9_hs  = 'multi_mission_quicklooks/multi_mission_vp9_hs.webm';
$src_h264_hs = 'multi_mission_quicklooks/multi_mission_h264_hs.mp4';

// ---- Choose initial set based on $use_hs
$poster   = $use_hs ? $poster_hs   : $poster_no;
$src_av1  = $use_hs ? $src_av1_hs  : $src_av1_no;
$src_vp9  = $use_hs ? $src_vp9_hs  : $src_vp9_no;
$src_h264 = $use_hs ? $src_h264_hs : $src_h264_no;

// ---- Optional timeline labels (read server-side so they appear immediately)
$timeline_json = __DIR__ . '/multi_mission_quicklooks/timeline.json';
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

// Unique id if you ever need one (JS binds by class, so not required)
$PLAYER_ID = 'mmv-player';
?>
<h3>Multi-mission SEC (1991–2025) — time series</h3>
<p>This video shows the Antarctic ice sheet surface elevation change time series. Use the controls to play, scrub, change speed, or go fullscreen.</p>

<style>
  :root { --mmv-blue:#21578b; --mmv-bg:#0f1a26; --mmv-rail:#d7dbe0; --mmv-rail-fill:#2e7bd1; --mmv-text:#111; }
  .mmv-wrap{ margin:10px auto; max-width:var(--mmv-max,1200px); border:1px solid #ddd; border-radius:10px; overflow:hidden; background:#fff; }
  .mmv-media{ background:#000; }
  .mmv-media video{ display:block; width:100%; height:auto; aspect-ratio:780/780; background:#000; }

  .mmv-controls{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px; background:#f7f9fc; border-top:1px solid #e6ebf0; }
  .mmv-left,.mmv-right{ display:flex; align-items:center; gap:10px }
  .mmv-center{ flex:1 1 auto; display:flex; align-items:center; gap:10px }

  .mmv-btn{ display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border:1px solid #d9dee5; border-radius:8px; background:#fff; cursor:pointer; transition:background .15s,border-color .15s; }
  .mmv-btn:hover{ background:#eef5ff; border-color:#c9d7ee }
  .mmv-btn .material-icons{ font-size:20px; line-height:1 }
  .mmv-btn--sm{ width:32px; height:32px; }
  .mmv-btn[aria-pressed="true"]{ background:#eef5ff; border-color:#c9d7ee; }

  .mmv-speed{ border:1px solid #d9dee5; border-radius:8px; background:#fff; height:36px; padding:0 8px; }
  .mmv-bound{ font:13px/1.1 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Open Sans',sans-serif; color:#333; white-space:nowrap; }

  .mmv-scrub-wrap{ position:relative; flex:1 1 auto; display:flex; align-items:center; height:22px; min-width:160px }
  .mmv-range{ appearance:none; background:transparent; width:100%; height:22px; cursor:pointer }
  .mmv-range:focus{ outline:none }
  .mmv-range::-webkit-slider-runnable-track{ height:6px; border-radius:999px; background:linear-gradient(to right,var(--mmv-rail-fill) var(--mmv-fill,0%),var(--mmv-rail) var(--mmv-fill,0%)); }
  .mmv-range::-webkit-slider-thumb{ appearance:none; width:0; height:0; border:0; background:transparent; margin-top:0 }
  .mmv-range::-moz-range-track{ height:6px; background:var(--mmv-rail); border-radius:999px }
  .mmv-range::-moz-range-thumb{ width:0; height:0; border:0; background:transparent }

  /* custom oblong “5-year window” indicator */
  .mmv-window{
    position:absolute; top:50%; transform:translateY(-50%);
    height:18px; border-radius:9px; border:1px solid #cbd5e1; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.06);
    pointer-events:none; width:64px;
  }

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
    <div class="mmv-left">
      <button class="mmv-btn" data-role="play" aria-label="Play/Pause">
        <span class="material-icons">play_arrow</span>
      </button>

      <!-- Compact hillshade toggle -->
      <button
        class="mmv-btn mmv-btn--sm"
        data-role="hs"
        aria-label="Hillshade"
        aria-pressed="<?php echo $use_hs ? 'true' : 'false'; ?>"
        title="Toggle hillshade"
      >
        <span class="material-icons">layers</span>
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

    <div class="mmv-right">
      <select class="mmv-speed" data-role="speed" aria-label="Playback speed">
        <option value="0.5">0.5×</option>
        <option value="0.75">0.75×</option>
        <option value="1" selected>1×</option>
        <option value="1.25">1.25×</option>
        <option value="1.5">1.5×</option>
        <option value="2">2×</option>
      </select>
      <button class="mmv-btn" data-role="fs" aria-label="Fullscreen">
        <span class="material-icons">fullscreen</span>
      </button>
    </div>
  </div>

  <div class="mmv-media">
    <!-- Store BOTH variants in data-* so the JS can swap instantly -->
    <video
      preload="metadata"
      playsinline
      poster="<?php echo htmlspecialchars($poster, ENT_QUOTES); ?>"
      data-poster-no="<?php echo htmlspecialchars($poster_no, ENT_QUOTES); ?>"
      data-poster-hs="<?php echo htmlspecialchars($poster_hs, ENT_QUOTES); ?>"
      data-src-av1-no="<?php echo htmlspecialchars($src_av1_no, ENT_QUOTES); ?>"
      data-src-vp9-no="<?php echo htmlspecialchars($src_vp9_no, ENT_QUOTES); ?>"
      data-src-h264-no="<?php echo htmlspecialchars($src_h264_no, ENT_QUOTES); ?>"
      data-src-av1-hs="<?php echo htmlspecialchars($src_av1_hs, ENT_QUOTES); ?>"
      data-src-vp9-hs="<?php echo htmlspecialchars($src_vp9_hs, ENT_QUOTES); ?>"
      data-src-h264-hs="<?php echo htmlspecialchars($src_h264_hs, ENT_QUOTES); ?>"
    >
      <!-- Initial active sources (browser picks best supported) -->
      <source src="<?php echo htmlspecialchars($src_av1,  ENT_QUOTES); ?>" type="video/webm; codecs=av01.0.05M.08">
      <source src="<?php echo htmlspecialchars($src_vp9,  ENT_QUOTES); ?>" type="video/webm; codecs=vp9">
      <source src="<?php echo htmlspecialchars($src_h264, ENT_QUOTES); ?>" type="video/mp4">
      Your browser doesn’t support HTML5 video.
    </video>
  </div>
</div>


<script>
(function () {
  /* ---------- utilities ---------- */
  function parseYYYYMM(s){ const m=(s||'').match(/^(\d{4})-(\d{2})$/); return m?{y:+m[1],m:+m[2]}:null; }
  function monthsBetween(a,b){ if(!a||!b) return 0; return (b.y-a.y)*12 + (b.m-a.m) + 1; }
  function clamp(n,a,b){ return Math.max(a, Math.min(b, n)); }
  function pctToTime(p,d){ return (p/1000)*(d||0); }
  function fmt(t){ if(!isFinite(t)||t<0) return '0:00'; const m=Math.floor(t/60),s=Math.floor(t%60); return m+':' +(s<10?'0':'')+s; }
  function pickBest(v, av1, vp9, h264){
    try{
      if (av1 && v.canPlayType('video/webm; codecs="av01.0.05M.08"')) return av1;
      if (vp9 && v.canPlayType('video/webm; codecs="vp9"'))          return vp9;
      return h264 || vp9 || av1 || '';
    }catch(e){ return h264 || vp9 || av1 || ''; }
  }

  /* ---------- one player ---------- */
  function initOnePlayer(root){
    if (!root || root.dataset.bound==='1') return;

    const v         = root.querySelector('video');
    const seek      = root.querySelector('[data-role="seek"]');
    const winEl     = root.querySelector('.mmv-window');
    const playBtn   = root.querySelector('[data-role="play"]');
    const speedSel  = root.querySelector('[data-role="speed"]');
    const fsBtn     = root.querySelector('[data-role="fs"]');
    const hsBtn     = root.querySelector('[data-role="hs"]');
    const scrubWrap = root.querySelector('.mmv-scrub-wrap');
    const curTxt    = root.querySelector('[data-role="cur"]'); // may not exist
    const durTxt    = root.querySelector('[data-role="dur"]'); // may not exist

    if (!v || !seek || !scrubWrap) return;

    /* timeline sizing for 5y window */
    const startObj = parseYYYYMM(root.dataset.start);
    const endObj   = parseYYYYMM(root.dataset.end);
    const totalMonths = Math.max(1, monthsBetween(startObj, endObj));
    const windowMonths = 60;

    let seeking=false, wasPlaying=false, seekRAF=null, initializedAtEnd=false;

    function syncPlayIcon(){ if (playBtn) playBtn.querySelector('.material-icons').textContent = v.paused ? 'play_arrow' : 'pause'; }
    function setFill(){ root.style.setProperty('--mmv-fill', (seek.value/10) + '%'); }
    function layoutWindow(){
      if (!winEl) return;
      const trackW = scrubWrap.getBoundingClientRect().width;
      const pxW = clamp(trackW * (windowMonths/totalMonths), 8, trackW);
      const pct = seek.value/1000;
      let left = pct*trackW - pxW/2;
      left = clamp(left, 0, trackW - pxW);
      winEl.style.width = pxW + 'px';
      winEl.style.left  = left + 'px';
    }

    function onTimeUpdate(){
      if (!isFinite(v.duration)) return;
      if (!seeking){
        const p = (v.currentTime / v.duration) * 1000 || 0;
        seek.value = clamp(Math.round(p), 0, 1000);
        setFill(); layoutWindow();
      }
      if (curTxt) curTxt.textContent = fmt(v.currentTime);
    }

    function setSliderToEnd(){
      seek.value = 1000; setFill(); layoutWindow();
    }

    function onMeta(){
      if (durTxt) durTxt.textContent = fmt(v.duration);
      if (!initializedAtEnd){
        initializedAtEnd = true;
        requestAnimationFrame(() => requestAnimationFrame(setSliderToEnd));
      } else {
        layoutWindow();
      }
      if (v.videoWidth > 0) root.style.setProperty('--mmv-max', v.videoWidth + 'px');
    }

    /* seeking UX */
    function beginSeek(){ seeking=true; wasPlaying=!v.paused; if (wasPlaying) v.pause(); }
    function liveSeek(){
      setFill(); layoutWindow();
      const nt = pctToTime(seek.value, v.duration);
      if (seekRAF) cancelAnimationFrame(seekRAF);
      seekRAF = requestAnimationFrame(() => { v.currentTime = nt; });
    }
    function finishSeek(){
      if (!seeking) return;
      seeking=false;
      const nt = pctToTime(seek.value, v.duration);
      v.currentTime = nt;
      if (wasPlaying) v.play();
    }

    /* play / speed / fullscreen */
    function togglePlay(){ v.paused ? v.play() : v.pause(); }
    function fsActive(){ return document.fullscreenElement === root; }
    function syncFsIcon(){ if (fsBtn) fsBtn.querySelector('.material-icons').textContent = fsActive() ? 'fullscreen_exit' : 'fullscreen'; }

    if (playBtn) playBtn.addEventListener('click', togglePlay);
    v.addEventListener('play',  syncPlayIcon);
    v.addEventListener('pause', syncPlayIcon);

    v.addEventListener('loadedmetadata', onMeta);
    v.addEventListener('timeupdate', onTimeUpdate);
    v.addEventListener('progress', onTimeUpdate);

    seek.addEventListener('pointerdown', beginSeek);
    seek.addEventListener('mousedown',   beginSeek);
    seek.addEventListener('touchstart',  beginSeek, {passive:true});
    seek.addEventListener('input',  liveSeek);
    seek.addEventListener('change', finishSeek);
    document.addEventListener('pointerup', finishSeek);
    document.addEventListener('mouseup',   finishSeek);
    document.addEventListener('touchend',  finishSeek);

    if (speedSel) speedSel.addEventListener('change', function(){ v.playbackRate = parseFloat(this.value || '1'); });

    if (fsBtn){
      fsBtn.addEventListener('click', () => { fsActive()?document.exitFullscreen?.():root.requestFullscreen?.(); });
      document.addEventListener('fullscreenchange', syncFsIcon);
    }

    /* ------- HILLSHADE TOGGLE ------- */
    function applyVariant(hsOn){
      // remember current position fraction + playing state
      const wasPlayingNow = !v.paused;
      const frac = isFinite(v.duration) && v.duration>0 ? (v.currentTime / v.duration) : (seek.value/1000);

      // pick sources for target variant
      const av1  = hsOn ? v.dataset.srcAv1Hs  : v.dataset.srcAv1No;
      const vp9  = hsOn ? v.dataset.srcVp9Hs  : v.dataset.srcVp9No;
      const h264 = hsOn ? v.dataset.srcH264Hs : v.dataset.srcH264No;
      const next = pickBest(v, av1, vp9, h264);

      // swap poster now for immediate perception
      v.poster = hsOn ? v.dataset.posterHs : v.dataset.posterNo;

      // if next equals currentSrc, just repaint poster/window and return
      if (v.currentSrc && next && v.currentSrc.indexOf(next) !== -1){
        if (wasPlayingNow) v.play();
        return;
      }

      // swap source by setting src directly (simpler than juggling <source> list)
      v.pause();
      v.removeAttribute('src');
      while (v.firstChild) v.removeChild(v.firstChild); // clear <source> tags
      const s = document.createElement('source');
      s.src = next || h264 || vp9 || av1 || '';
      s.type = s.src.endsWith('.mp4') ? 'video/mp4' : (s.src.indexOf('.webm')>-1 ? 'video/webm' : '');
      v.appendChild(s);
      v.load();

      // after metadata, restore position + play state
      const restore = () => {
        v.currentTime = clamp(frac * (v.duration || 0), 0, v.duration || 0);
        if (wasPlayingNow) v.play();
        v.removeEventListener('loadedmetadata', restore);
      };
      v.addEventListener('loadedmetadata', restore);
    }

    if (hsBtn){
      hsBtn.addEventListener('click', function(){
        const on = this.getAttribute('aria-pressed') !== 'true';
        this.setAttribute('aria-pressed', on ? 'true' : 'false');

        // swap immediately
        applyVariant(on);

        // persist session in the background (so other tabs see same setting)
        try{
          const fd = new FormData();
          fd.append('hillshade', on ? 'show' : 'hide');
          fd.append('active_tab', 'multi_mission');
          fetch('index.php', { method:'POST', body: fd, credentials:'same-origin', keepalive:true });
        }catch(e){}
      });
    }

    // keyboard shortcuts
    root.tabIndex = 0;
    root.addEventListener('keydown', function(e){
      switch(e.key){
        case ' ': case 'k': e.preventDefault(); togglePlay(); break;
        case 'ArrowLeft':  v.currentTime = Math.max(0, (v.currentTime||0) - 5); break;
        case 'ArrowRight': v.currentTime = Math.min(v.duration||0, (v.currentTime||0) + 5); break;
        case 'f': fsActive()?document.exitFullscreen?.():root.requestFullscreen?.(); break;
        case 'h': // optional: 'h' toggles hillshade too
          if (hsBtn) hsBtn.click();
          break;
      }
    });

    // window relayout on resize
    window.addEventListener('resize', layoutWindow, { passive:true });

    // if metadata is already there
    if (v.readyState >= 1) onMeta();

    syncPlayIcon();
    root.dataset.bound = '1';
  }

  function initAll(){ document.querySelectorAll('.mmv-wrap').forEach(initOnePlayer); }
  document.addEventListener('DOMContentLoaded', initAll);
  window.rebindMultiMissionHandlers = initAll;
})();
</script>



