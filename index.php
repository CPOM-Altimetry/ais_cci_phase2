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
  // If server rendered content is already there, also treat as loaded:
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
      // Find matching form + hidden input inside this pane
      const form   = pane.querySelector('#is2-hillshade-form, #hillshade-form');
      const hidden = pane.querySelector('#is2-hillshade-input, #hillshade-input');
      if (!form || !hidden) return;
      hidden.value = this.checked ? 'show' : 'hide';
      form.submit(); // full POST (server keeps us on the right tab via hidden active_tab)
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
}

/* ===== Lazy loader ===== */
const loadedTabs = new Set(); // track tabs fetched via AJAX

function fetchTabHtml(tabId){
  const pane = document.getElementById(tabId);
  if (!pane) return Promise.resolve();

  // Loading placeholder
  pane.innerHTML = '<div style="padding:16px;color:#666;">Loading…</div>';

  // Pass along state; server also has session, but this helps first paint
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
  // If already loaded (SSR or previously fetched), just (re)bind and return
  if (paneIsLoaded(pane)) {
    initTabBehaviors(tabId);
    return;
  }

  // Otherwise, lazy fetch then bind
  fetchTabHtml(tabId);
}

/* ===== Initial boot ===== */
document.addEventListener('DOMContentLoaded', function () {
  // Prefer server-side remembered tab
  var tabId = TAB_DEFAULTS.active || 'intro';

  // If we’re not on single_mission, clean up stale show_single_mission=1 in URL (cosmetic)
  if (tabId !== 'single_mission') {
    try {
      var url = new URL(window.location.href);
      if (url.searchParams.has('show_single_mission')) {
        url.searchParams.delete('show_single_mission');
        window.history.replaceState({}, "", url.toString());
      }
    } catch(e) {}
  }

  // Show the tab, then lazy-load only if the pane is empty
  showTabUI(null, tabId);
  const pane = document.getElementById(tabId);
  if (!paneIsLoaded(pane)) {
    fetchTabHtml(tabId);
  } else {
    initTabBehaviors(tabId);
  }
});
</script>


</html>
