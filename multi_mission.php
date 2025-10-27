<?php
// multi_mission.php — custom-controls video player (no audio controls)

// Optional: poster (use .webp/.jpg/.avif; omit if you prefer)
$poster   = 'multi_mission_quicklooks/last_frame.webp';
$src_av1  = 'multi_mission_quicklooks/multi_mission_av1.webm';
$src_vp9  = 'multi_mission_quicklooks/multi_mission_vp9.webm';
$src_h264 = 'multi_mission_quicklooks/multi_mission_h264.mp4';

// Unique id so we can safely init even if injected later
$PLAYER_ID = 'mmv-player';
?>
<h3>Multi-mission SEC (1991–2025) — time series</h3>
<p>This video shows the Antarctic ice sheet surface elevation change time series. Use the controls to play, scrub, change speed, enter picture-in-picture, or go fullscreen.</p>

<style>
  /* ---- Player layout */
  :root { --mmv-blue:#21578b; --mmv-bg:#0f1a26; --mmv-rail:#d7dbe0; --mmv-rail-fill:#2e7bd1; --mmv-text:#111; }
  .mmv-wrap{max-width:1200px;margin:10px 0;border:1px solid #ddd;border-radius:10px;overflow:hidden;background:#fff;}
  .mmv-media{background:#000;}
  .mmv-media video{display:block;width:100%;height:auto;aspect-ratio:720/720;background:#000;} /* update AR if needed */

  /* ---- Controls bar */
  .mmv-controls{
    display:flex;align-items:center;gap:10px;flex-wrap:wrap;
    padding:10px;background:#f7f9fc;border-top:1px solid #e6ebf0;
  }
  .mmv-left,.mmv-right{display:flex;align-items:center;gap:10px}
  .mmv-left{flex:0 0 auto}
  .mmv-center{flex:1 1 auto;display:flex;align-items:center;gap:10px}
  .mmv-right{flex:0 0 auto}

  /* Buttons */
  .mmv-btn{
    display:inline-flex;align-items:center;justify-content:center;
    width:36px;height:36px;border:1px solid #d9dee5;border-radius:8px;
    background:#fff;cursor:pointer;transition:background .15s,border-color .15s;
  }
  .mmv-btn:hover{background:#eef5ff;border-color:#c9d7ee}
  .mmv-btn .material-icons{font-size:20px;line-height:1}

  /* Time text */
  .mmv-time{
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Open Sans',sans-serif;
    font-size:13px;color:#333;min-width:120px
  }

  /* Progress (range) */
  .mmv-range{appearance:none;background:transparent;width:100%;height:22px;cursor:pointer}
  .mmv-range:focus{outline:none}
  .mmv-range::-webkit-slider-runnable-track{
    height:6px;background:linear-gradient(to right,var(--mmv-rail-fill) var(--mmv-fill,0%),var(--mmv-rail) var(--mmv-fill,0%));border-radius:999px
  }
  .mmv-range::-moz-range-track{height:6px;background:var(--mmv-rail);border-radius:999px}
  .mmv-range::-webkit-slider-thumb{appearance:none;margin-top:-6px;width:18px;height:18px;background:#fff;border:1px solid #cbd5e1;border-radius:50%}
  .mmv-range::-moz-range-thumb{width:16px;height:16px;background:#fff;border:1px solid #cbd5e1;border-radius:50%}

  /* Speed select */
  .mmv-speed{
    border:1px solid #d9dee5;border-radius:8px;background:#fff;height:36px;padding:0 8px;
  }

  @media (max-width:720px){
    .mmv-time{min-width:auto}
  }
</style>

<div class="mmv-wrap" id="<?php echo $PLAYER_ID; ?>">
  <div class="mmv-media">
    <video
      id="<?php echo $PLAYER_ID; ?>-video"
      preload="metadata"
      playsinline
      <?php if ($poster) echo 'poster="'.htmlspecialchars($poster,ENT_QUOTES).'"'; ?>
      data-src-av1="<?php echo htmlspecialchars($src_av1,ENT_QUOTES); ?>"
      data-src-vp9="<?php echo htmlspecialchars($src_vp9,ENT_QUOTES); ?>"
      data-src-h264="<?php echo htmlspecialchars($src_h264,ENT_QUOTES); ?>"
    >
      <!-- Keep sources as a fallback; JS will override .src explicitly -->
      <source src="<?php echo htmlspecialchars($src_av1,ENT_QUOTES);  ?>" type="video/webm; codecs=av01.0.05M.08">
      <source src="<?php echo htmlspecialchars($src_vp9,ENT_QUOTES);  ?>" type="video/webm; codecs=vp9">
      <source src="<?php echo htmlspecialchars($src_h264,ENT_QUOTES); ?>" type="video/mp4">
      Your browser doesn’t support HTML5 video.
    </video>
  </div>

  <div class="mmv-controls" role="group" aria-label="Video controls">
    <div class="mmv-left">
      <button class="mmv-btn" data-role="play" aria-label="Play/Pause"><span class="material-icons">play_arrow</span></button>
    </div>

    <div class="mmv-center">
      <input class="mmv-range" data-role="seek" type="range" min="0" max="1000" value="0" step="1" aria-label="Seek">
      <div class="mmv-time"><span data-role="cur">0:00</span> / <span data-role="dur">0:00</span></div>
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
      <button class="mmv-btn" data-role="pip" aria-label="Picture in picture" style="display:none"><span class="material-icons">picture_in_picture_alt</span></button>
      <button class="mmv-btn" data-role="fs" aria-label="Fullscreen"><span class="material-icons">fullscreen</span></button>
    </div>
  </div>
</div>

<script>
(function(){
  function fmt(t){
    if (!isFinite(t) || t<0) return '0:00';
    var m = Math.floor(t/60), s = Math.floor(t%60);
    return m + ':' + (s<10?'0':'') + s;
  }

  function initPlayer(rootId){
    var root = document.getElementById(rootId);
    if (!root || root.dataset.bound === '1') return; // avoid double-binding

    var v     = root.querySelector('video');
    var bPlay = root.querySelector('[data-role="play"]');
    var seek  = root.querySelector('[data-role="seek"]');
    var cur   = root.querySelector('[data-role="cur"]');
    var dur   = root.querySelector('[data-role="dur"]');
    var speed = root.querySelector('[data-role="speed"]');
    var bPip  = root.querySelector('[data-role="pip"]');
    var bFs   = root.querySelector('[data-role="fs"]');

    // Play/Pause
    function syncPlayIcon(){
      bPlay.querySelector('.material-icons').textContent = v.paused ? 'play_arrow' : 'pause';
    }
    bPlay.addEventListener('click', function(){ v.paused ? v.play() : v.pause(); });
    v.addEventListener('play',  syncPlayIcon);
    v.addEventListener('pause', syncPlayIcon);

    // Metadata / duration
    function onMeta(){
      dur.textContent = fmt(v.duration);
      updateProgress();
    }
    v.addEventListener('loadedmetadata', onMeta);

    // Seek
    var seeking = false;
    function updateProgress(){
      if (seeking || !isFinite(v.duration)) return;
      var p = (v.currentTime / v.duration) * 1000 || 0;
      seek.value = Math.max(0, Math.min(1000, Math.round(p)));
      root.style.setProperty('--mmv-fill', (p/10) + '%'); // webkit track fill
      cur.textContent = fmt(v.currentTime);
    }
    v.addEventListener('timeupdate', updateProgress);
    v.addEventListener('progress',   updateProgress);

    seek.addEventListener('input', function(){
      seeking = true;
      var nt = (seek.value/1000) * (v.duration || 0);
      cur.textContent = fmt(nt);
      root.style.setProperty('--mmv-fill', (seek.value/10) + '%');
    });
    seek.addEventListener('change', function(){
      var nt = (seek.value/1000) * (v.duration || 0);
      v.currentTime = nt;
      seeking = false;
    });

    // Speed
    speed.addEventListener('change', function(){
      v.playbackRate = parseFloat(this.value);
    });

    // PiP (if supported)
    if ('pictureInPictureEnabled' in document) {
      bPip.style.display = '';
      bPip.addEventListener('click', async function(){
        try{
          if (document.pictureInPictureElement) { await document.exitPictureInPicture(); }
          else { await v.requestPictureInPicture(); }
        }catch(e){ console.warn('PiP error', e); }
      });
    }

    // Fullscreen
    function fsActive(){ return document.fullscreenElement === root; }
    function syncFsIcon(){
      bFs.querySelector('.material-icons').textContent = fsActive() ? 'fullscreen_exit' : 'fullscreen';
    }
    bFs.addEventListener('click', function(){
      if (!fsActive()){
        if (root.requestFullscreen) root.requestFullscreen();
      } else {
        if (document.exitFullscreen) document.exitFullscreen();
      }
    });
    document.addEventListener('fullscreenchange', syncFsIcon);

    // Keyboard (when player focused) — no audio shortcuts now
    root.tabIndex = 0; // make focusable
    root.addEventListener('keydown', function(e){
      switch(e.key){
        case ' ': case 'k': e.preventDefault(); v.paused ? v.play() : v.pause(); break;
        case 'ArrowLeft':  v.currentTime = Math.max(0, v.currentTime - 5); break;
        case 'ArrowRight': v.currentTime = Math.min(v.duration||0, v.currentTime + 5); break;
        case 'f': fsActive()?document.exitFullscreen():root.requestFullscreen?.(); break;
      }
    });

    // Initial UI sync
    syncPlayIcon();
    if (v.readyState >= 1) onMeta();

    root.dataset.bound = '1';
  }

  // Init now if present in DOM
  document.addEventListener('DOMContentLoaded', function(){
    initPlayer('<?php echo $PLAYER_ID; ?>');
  });

  // Expose for lazy-loaded tabs: call after injecting this HTML
  window.rebindMultiMissionHandlers = function(){
    initPlayer('<?php echo $PLAYER_ID; ?>');
  };
})();
</script>
