<h3>Single RA Mission SEC</h3>

        <style>
        .float-right-img{
            float:right; position:relative; top:-60px;
            max-width:100px; width:100%; height:auto;
        }
        @media (max-width:600px){ .float-right-img{ float:none; margin:10px 0; } }
        </style>

        <img id="single_mission_logo" src="images/single_mission_logo.png" alt="Single mission logo" class="float-right-img">

        <p>These products show the ice sheet surface elevation change (SEC, also known as dh/dt) measured over the full period
            of individual satellite radar altimetry missions. The change is calculated within each 5km grid cell.
        </p>

        <!-- ===================== Toolbar (parameter + mission radios + hillshade) ===================== -->
        <div class="image_section">
            <!-- Right: Hill Shade toggle (POSTs hillshade + active_tab) -->
            <div class="toggle-container-left">
                <div class="toggle-label">Hill Shade</div>
                <div class="toggle-switch<?php echo $hillshade === 'show' ? ' on' : ''; ?>">
                    <span class="toggle-option">hide</span>

                    <form id="hillshade-form" method="POST" style="display:none;">
                        <input type="hidden" name="hillshade" id="hillshade-input">
                        <input type="hidden" name="active_tab" id="active_tab_input">
                        <input type="hidden" name="show_single_mission" value="1">
                        <input type="hidden" name="single_mission_view" value="<?php echo htmlspecialchars($single_mission_view, ENT_QUOTES); ?>">
                        <input type="hidden" name="ql_param" value="<?php echo htmlspecialchars($ql_param, ENT_QUOTES); ?>">
                    </form>

                    <label class="switch">
                        <input id="toggle-hillshade" type="checkbox" <?php echo $hillshade === 'show' ? 'checked' : ''; ?>>
                        <span class="slider round"></span>
                    </label>
                    <span class="toggle-option tog_to_hide">show</span>
                </div>
            </div>

            <!-- Left: Parameter dropdown + (conditional) Mission radios -->
            <div class="w3-container">
                <div class="control-row">
                    <!-- Parameter dropdown -->
                    <div id="product_dropdown" class="w3-dropdown-hover">
                        <span id="parameter_txt">Parameter:</span>
                        <button class="w3-button my-button-color">
                            <?php echo htmlspecialchars($ql_param_str, ENT_QUOTES); ?> <i class="fa fa-caret-down"></i>
                        </button>
                        <div class="w3-dropdown-content w3-bar-block w3-card-4">
                            <?php $base = "index.php?show_single_mission=1&active_tab=single_mission&single_mission_view=" . urlencode($single_mission_view); ?>
                            <a href="<?php echo $base; ?>&ql_param=sec"              class="w3-bar-item w3-button">Surface Elevation Change (SEC)</a>
                            <a href="<?php echo $base; ?>&ql_param=sec_uncertainty" class="w3-bar-item w3-button">Uncertainty of SEC</a>
                            <a href="<?php echo $base; ?>&ql_param=surface_type"    class="w3-bar-item w3-button">Surface Type</a>
                            <a href="<?php echo $base; ?>&ql_param=basin_id"        class="w3-bar-item w3-button">Glaciological Basin ID</a>
                        </div>
                    </div>

                    <!-- Mission radios (only in single-image view) -->
                    <?php if ($single_mission_view != 'all') { ?>
  <form id="mission-select-form" method="POST" class="mission-select-form">
    <input type="hidden" name="active_tab" value="single_mission">
    <input type="hidden" name="ql_param" value="<?php echo htmlspecialchars($ql_param, ENT_QUOTES); ?>">
    <input type="hidden" name="show_single_mission" value="1">

    <label for="mission-select" class="mission-select-label">Mission:</label>
    <select id="mission-select" name="single_mission_view" class="mission-select">
      <option value="all"  <?php echo $single_mission_view==='all' ? 'selected' : ''; ?>>All</option>
      <option value="s3b"  <?php echo $single_mission_view==='s3b' ? 'selected' : ''; ?>>S3B</option>
      <option value="s3a"  <?php echo $single_mission_view==='s3a' ? 'selected' : ''; ?>>S3A</option>
      <option value="cs2"  <?php echo $single_mission_view==='cs2' ? 'selected' : ''; ?>>CS2</option>
      <option value="ev"   <?php echo $single_mission_view==='ev'  ? 'selected' : ''; ?>>ENV</option>
      <option value="e2"   <?php echo $single_mission_view==='e2'  ? 'selected' : ''; ?>>ERS-2</option>
      <option value="e1"   <?php echo $single_mission_view==='e1'  ? 'selected' : ''; ?>>ERS-1</option>
    </select>
  </form>
<?php } ?>

                </div>
            </div>
        </div>
        <!-- ===================== /Toolbar ===================== -->

        <!-- ===================== Images (placed OUTSIDE toolbar) ===================== -->
        <?php if ($single_mission_view == 'all') { ?>
            <div class="images-wrap">
                <div class="w3-container w3-margin-top" style="clear:both; margin-left:0; margin-right:0;">
                    <div class="w3-row-padding ">

                        <!-- S3-B -->
                        <div class="w3-third">
                            <div class="w3-card" style="padding-bottom:5px; margin-bottom:10px;">
                                <?php 
                                if ($hillshade === 'show') {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-S3B-5KM-*fv2-{$ql_param}-hs.png")[0] ?? '';
                                } else {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-S3B-5KM-*fv2-{$ql_param}.png")[0] ?? '';
                                }
                                if ($imagefile && preg_match('/(\d{8})-(\d{8})/', $imagefile, $matches)) {
                                    $start_year = substr($matches[1], 0, 4);
                                    $start_month = substr($matches[1], 4, 2);
                                    $end_year = substr($matches[2], 0, 4);
                                    $end_month = substr($matches[2], 4, 2);
                                }
                                ?>
                                <div class="w3-container">
                                    <div class="date1">Sentinel-3B <span class="date2">(<?php echo "{$start_month}-{$start_year} to {$end_month}-{$end_year}"; ?>)</span></div>
                                </div>
                                <a href="index.php?show_single_mission=1&ql_param=<?php echo urlencode($ql_param); ?>&sec_type=single_mission&single_mission_view=s3b&active_tab=single_mission">
                                    <img style="width:100%" src="<?php echo htmlspecialchars($imagefile, ENT_QUOTES); ?>" alt="S3B">
                                </a>
                            </div>
                        </div>

                        <!-- S3-A -->
                        <div class="w3-third">
                            <div class="w3-card" style="padding-bottom:5px; margin-bottom:10px;">
                                <?php 
                                if ($hillshade === 'show') {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-S3A-5KM-*fv2-{$ql_param}-hs.png")[0] ?? '';
                                } else {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-S3A-5KM-*fv2-{$ql_param}.png")[0] ?? '';
                                }
                                if ($imagefile && preg_match('/(\d{8})-(\d{8})/', $imagefile, $matches)) {
                                    $start_year = substr($matches[1], 0, 4);
                                    $start_month = substr($matches[1], 4, 2);
                                    $end_year = substr($matches[2], 0, 4);
                                    $end_month = substr($matches[2], 4, 2);
                                }
                                ?>
                                <div class="w3-container">
                                    <div class="date1">Sentinel-3A <span class="date2">(<?php echo "{$start_month}-{$start_year} to {$end_month}-{$end_year}"; ?>)</span></div>
                                </div>
                                <a href="index.php?show_single_mission=1&ql_param=<?php echo urlencode($ql_param); ?>&sec_type=single_mission&single_mission_view=s3a&active_tab=single_mission">
                                    <img style="width:100%" src="<?php echo htmlspecialchars($imagefile, ENT_QUOTES); ?>" alt="S3A">
                                </a>
                            </div>
                        </div>

                        <!-- CS2 -->
                        <div class="w3-third">
                            <div class="w3-card" style="padding-bottom:5px; margin-bottom:10px;">
                                <?php 
                                if ($hillshade === 'show') {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-CS2-5KM-*fv2-{$ql_param}-hs.png")[0] ?? '';
                                } else {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-CS2-5KM-*fv2-{$ql_param}.png")[0] ?? '';
                                }
                                if ($imagefile && preg_match('/(\d{8})-(\d{8})/', $imagefile, $matches)) {
                                    $start_year = substr($matches[1], 0, 4);
                                    $start_month = substr($matches[1], 4, 2);
                                    $end_year = substr($matches[2], 0, 4);
                                    $end_month = substr($matches[2], 4, 2);
                                }
                                ?>
                                <div class="w3-container">
                                    <div class="date1">CryoSat-2 <span class="date2">(<?php echo "{$start_month}-{$start_year} to {$end_month}-{$end_year}"; ?>)</span></div>
                                </div>
                                <a href="index.php?show_single_mission=1&ql_param=<?php echo urlencode($ql_param); ?>&sec_type=single_mission&single_mission_view=cs2&active_tab=single_mission">
                                    <img style="width:100%" src="<?php echo htmlspecialchars($imagefile, ENT_QUOTES); ?>" alt="CS2">
                                </a>
                            </div>
                        </div>

                        <!-- ENV -->
                        <div class="w3-third">
                            <div class="w3-card" style="padding-bottom:5px; margin-bottom:10px;">
                                <?php 
                                if ($hillshade === 'show') {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-ENV-5KM-*fv2-{$ql_param}-hs.png")[0] ?? '';
                                } else {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-ENV-5KM-*fv2-{$ql_param}.png")[0] ?? '';
                                }
                                if ($imagefile && preg_match('/(\d{8})-(\d{8})/', $imagefile, $matches)) {
                                    $start_year = substr($matches[1], 0, 4);
                                    $start_month = substr($matches[1], 4, 2);
                                    $end_year = substr($matches[2], 0, 4);
                                    $end_month = substr($matches[2], 4, 2);
                                }
                                ?>
                                <div class="w3-container">
                                    <div class="date1">ENVISAT <span class="date2">(<?php echo "{$start_month}-{$start_year} to {$end_month}-{$end_year}"; ?>)</span></div>
                                </div>
                                <a href="index.php?show_single_mission=1&ql_param=<?php echo urlencode($ql_param); ?>&sec_type=single_mission&single_mission_view=ev&active_tab=single_mission">
                                    <img style="width:100%" src="<?php echo htmlspecialchars($imagefile, ENT_QUOTES); ?>" alt="ENVISAT">
                                </a>
                            </div>
                        </div>

                        <!-- ERS-2 -->
                        <div class="w3-third">
                            <div class="w3-card" style="padding-bottom:5px; margin-bottom:10px;">
                                <?php 
                                if ($hillshade === 'show') {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-ER2-5KM-*fv2-{$ql_param}-hs.png")[0] ?? '';
                                } else {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-ER2-5KM-*fv2-{$ql_param}.png")[0] ?? '';
                                }
                                if ($imagefile && preg_match('/(\d{8})-(\d{8})/', $imagefile, $matches)) {
                                    $start_year = substr($matches[1], 0, 4);
                                    $start_month = substr($matches[1], 4, 2);
                                    $end_year = substr($matches[2], 0, 4);
                                    $end_month = substr($matches[2], 4, 2);
                                }
                                ?>
                                <div class="w3-container">
                                    <div class="date1">ERS-2 <span class="date2">(<?php echo "{$start_month}-{$start_year} to {$end_month}-{$end_year}"; ?>)</span></div>
                                </div>
                                <a href="index.php?show_single_mission=1&ql_param=<?php echo urlencode($ql_param); ?>&sec_type=single_mission&single_mission_view=e2&active_tab=single_mission">
                                    <img style="width:100%" src="<?php echo htmlspecialchars($imagefile, ENT_QUOTES); ?>" alt="ERS-2">
                                </a>
                            </div>
                        </div>

                        <!-- ERS-1 -->
                        <div class="w3-third">
                            <div class="w3-card" style="padding-bottom:5px; margin-bottom:10px;">
                                <?php 
                                if ($hillshade === 'show') {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-ER1-5KM-*fv2-{$ql_param}-hs.png")[0] ?? '';
                                } else {
                                    $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-ER1-5KM-*fv2-{$ql_param}.png")[0] ?? '';
                                }
                                if ($imagefile && preg_match('/(\d{8})-(\d{8})/', $imagefile, $matches)) {
                                    $start_year = substr($matches[1], 0, 4);
                                    $start_month = substr($matches[1], 4, 2);
                                    $end_year = substr($matches[2], 0, 4);
                                    $end_month = substr($matches[2], 4, 2);
                                }
                                ?>
                                <div class="w3-container">
                                    <div class="date1">ERS-1 <span class="date2">(<?php echo "{$start_month}-{$start_year} to {$end_month}-{$end_year}"; ?>)</span></div>
                                </div>
                                <a href="index.php?show_single_mission=1&ql_param=<?php echo urlencode($ql_param); ?>&sec_type=single_mission&single_mission_view=e1&active_tab=single_mission">
                                    <img style="width:100%" src="<?php echo htmlspecialchars($imagefile, ENT_QUOTES); ?>" alt="ERS-1">
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        <?php } else { ?>
            <!-- Large single image -->
            <div class="images-wrap2">
                <div class="w3-container">
                    <div class="w3-card">
                        <?php 
                        if ($hillshade === 'show') {
                            $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-{$mission_str}-5KM-*fv2-{$ql_param}-hs.png")[0] ?? '';
                        } else {
                            $imagefile = glob("quicklooks/ESACCI-AIS-L3C-SEC-{$mission_str}-5KM-*fv2-{$ql_param}.png")[0] ?? '';
                        }
                        ?>
                        <a href="index.php?show_single_mission=1&ql_param=<?php echo urlencode($ql_param); ?>&sec_type=single_mission&single_mission_view=all&active_tab=single_mission">
                            <img style="width:100%" src="<?php echo htmlspecialchars($imagefile, ENT_QUOTES); ?>" alt="Large mission quicklook">
                        </a>
                        <div class="w3-container">   
                            <div class="all-text image_card_text"><?php echo htmlspecialchars($mission_str ?: 'Mission', ENT_QUOTES); ?> <span class="date">(Dec-2018 to Apr-2021)</span></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        <!-- ===================== /Images ===================== -->