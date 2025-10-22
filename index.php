<!DOCTYPE html>
<!-- 
  Run locally with:
  /usr/local/bin/php -S localhost:8000
-->
<html lang="en">
<head>
    <meta charset="UTF-8">
    
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

    <div class="tab">
        <button class="tablinks" <?php if (!$show_single_mission) echo 'id="defaultOpen"'; ?> onclick="openTab(event, 'intro')">Product Types</button>
        <button class="tablinks tab-download" <?php if ($show_single_mission) echo 'id="defaultOpen"'; ?> onclick="openTab(event, 'single_mission')">Single RA Mission SEC</button>
        <button class="tablinks tab-download" onclick="openTab(event, 'is2_sec')">ICESat-2 SEC</button>
        <button class="tablinks tab-download" onclick="openTab(event, 'multi_mission')">Multi-Mission RA SEC</button>
        <button class="tablinks tab-download" onclick="openTab(event, 'annual_dh')">Annual dH</button>
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
    function openTab(evt, cityName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) { tabcontent[i].style.display = "none"; }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) { tablinks[i].className = tablinks[i].className.replace(" active", ""); }
    document.getElementById(cityName).style.display = "block";
    if (evt && evt.currentTarget) {
      evt.currentTarget.className += " active";
    } else {
      var btn = document.querySelector('.tab button[onclick*="' + cityName + '"]');
      if (btn) btn.className += " active";
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Always prefer the server-side session memory
    var activeTabFromPHP = "<?php echo $active_tab; ?>";
    var btn = document.querySelector(".tab button[onclick*=\"'" + activeTabFromPHP + "'\"]");
    if (btn) {
      btn.click();
    } else {
      var def = document.getElementById("defaultOpen");
      if (def) def.click();
    }

    // If we're NOT on single_mission, clean up any stale show_single_mission=1 in the URL
    if (activeTabFromPHP !== 'single_mission') {
      try {
        var url = new URL(window.location.href);
        if (url.searchParams.has('show_single_mission')) {
          url.searchParams.delete('show_single_mission');
          window.history.replaceState({}, "", url.toString());
        }
      } catch(e) {}
    }
  });

    // Hillshade POST submit with tab memory
    const toggle = document.getElementById('toggle-hillshade');
    if (toggle) {
        toggle.addEventListener('change', function() {
            const inputHill = document.getElementById('hillshade-input');
            const inputTab  = document.getElementById('active_tab_input');
            const form      = document.getElementById('hillshade-form');

            let tabId = 'intro';
            const activeBtn = document.querySelector('.tablinks.active');
            if (activeBtn) {
                const m = activeBtn.getAttribute('onclick').match(/'([^']+)'/);
                if (m && m[1]) tabId = m[1];
            } else {
                const visible = Array.from(document.querySelectorAll('.tabcontent'))
                    .find(el => el.style.display === 'block');
                if (visible && visible.id) tabId = visible.id;
            }

            inputHill.value = this.checked ? 'show' : 'hide';
            inputTab.value  = tabId;
            form.submit();
        });
    }

    // Auto-submit when a mission radio is picked
    const missionForm = document.getElementById('mission-radio-form');
    if (missionForm) {
        missionForm.addEventListener('change', function () {
            this.submit(); // POSTs: single_mission_view, ql_param, show_single_mission, active_tab
        });
    }
    // Auto-submit when a mission is chosen from the dropdown
const missionSelect = document.getElementById('mission-select');
if (missionSelect) {
  missionSelect.addEventListener('change', function () {
    document.getElementById('mission-select-form').submit();
  });
}
</script>

</html>
