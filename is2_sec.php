<?php
// is2_sec.php — ICESat-2 tab (single large image)
$mission_str = 'IS2';
$pattern = ($hillshade === 'show')
    ? "quicklooks/ESACCI-AIS-L3C-SEC-{$mission_str}-5KM-*fv2-{$ql_param}-hs.avif"
    : "quicklooks/ESACCI-AIS-L3C-SEC-{$mission_str}-5KM-*fv2-{$ql_param}.avif";
$files = glob($pattern);
$imagefile = $files[0] ?? '';

$date_txt = '';
if ($imagefile && preg_match('/(\d{8})-(\d{8})/', $imagefile, $m)) {
    $sy = substr($m[1], 0, 4); $sm = substr($m[1], 4, 2);
    $ey = substr($m[2], 0, 4); $em = substr($m[2], 4, 2);
    $date_txt = "({$sm}-{$sy} to {$em}-{$ey})";
}
?>
<h3>ICESat-2 SEC</h3>

<style>
  .float-right-img{ float:right; position:relative; top:-60px; max-width:100px; width:100%; height:auto; }
  @media (max-width:600px){ .float-right-img{ float:none; margin:10px 0; } }
</style>

<img id="single_mission_logo" src="images/is2_mission_logo.png" alt="ICESat-2 logo" class="float-right-img">

<p>
  This section shows the surface elevation change (SEC, dh/dt) from the ICESat-2 mission.
  The change is calculated within each 5&nbsp;km grid cell.
</p>

<!-- ===================== Toolbar ===================== -->
<div class="image_section">
  <!-- Right: Hill Shade toggle (POSTs & stays on this tab) -->
  <div class="toggle-container-left">
    <div class="toggle-label">Hill Shade</div>
    <div class="toggle-switch<?php echo $hillshade === 'show' ? ' on' : ''; ?>">
      <span class="toggle-option">hide</span>

      <form id="is2-hillshade-form" method="POST" style="display:none;">
        <input type="hidden" name="hillshade" id="is2-hillshade-input">
        <input type="hidden" name="active_tab" value="is2_sec">
        <input type="hidden" name="ql_param" value="<?php echo htmlspecialchars($ql_param, ENT_QUOTES); ?>">
      </form>

      <label class="switch">
        <input id="is2-toggle-hillshade" type="checkbox" <?php echo $hillshade === 'show' ? 'checked' : ''; ?>>
        <span class="slider round"></span>
      </label>
      <span class="toggle-option tog_to_hide">show</span>
    </div>
  </div>

  <!-- Left: Parameter dropdown (POST to stay on IS2 tab) -->
  <div class="w3-container">
    <div class="control-row">
        <!-- Parameter dropdown -->
                    <div id="product_dropdown" class="w3-dropdown-hover">
                        <span id="parameter_txt">Parameter:</span>
                        <button class="w3-button my-button-color">
                            <?php echo htmlspecialchars($ql_param_str, ENT_QUOTES); ?> <i class="fa fa-caret-down"></i>
                        </button>
                        <div class="w3-dropdown-content w3-bar-block w3-card-4">
                            <?php $base = "index.php?show_single_mission=0&active_tab=is2_sec;" ?>
                            <a href="<?php echo $base; ?>&ql_param=sec#tab_row"              class="w3-bar-item w3-button">Surface Elevation Change (SEC)</a>
                            <a href="<?php echo $base; ?>&ql_param=sec_uncertainty#tab_row" class="w3-bar-item w3-button">Uncertainty of SEC</a>
                            <a href="<?php echo $base; ?>&ql_param=surface_type#tab_row"    class="w3-bar-item w3-button">Surface Type</a>
                            <a href="<?php echo $base; ?>&ql_param=basin_id#tab_row"        class="w3-bar-item w3-button">Glaciological Basin ID</a>
                        </div>
                    </div>
      
    </div>
  </div>
</div>
<!-- ===================== /Toolbar ===================== -->

<!-- ===================== Large image ===================== -->
<div class="images-wrap2">
  <div class="w3-container">
    <div class="w3-card">
      <?php if ($imagefile): ?>
        <?php $webpfile = preg_replace('/\.avif$/i', '.webp', $imagefile);?>    
        <picture style="width:100%;">
            <source
                srcset="<?php echo htmlspecialchars($imagefile, ENT_QUOTES); ?>"
                type="image/avif">
            <source
                srcset="<?php echo htmlspecialchars($webpfile, ENT_QUOTES); ?>"
                type="image/webp">
            <img
                src="<?php echo htmlspecialchars($webpfile, ENT_QUOTES); ?>"
                alt="ICESat-2"
                style="width:100%;">
        </picture> 
        <div class="w3-container">
          <div class="all-text image_card_text">ICESat-2 <span class="date"><?php echo htmlspecialchars($date_txt, ENT_QUOTES); ?></span></div>
        </div>
      <?php else: ?>
        <div style="padding:16px;">
          <p style="margin:0; color:#b00;"><strong>No quicklook image found</strong> for parameter <code><?php echo htmlspecialchars($ql_param, ENT_QUOTES); ?></code> (hillshade: <code><?php echo htmlspecialchars($hillshade, ENT_QUOTES); ?></code>).</p>
          <p style="margin:.5em 0 0; color:#555; font-size:.95em;">Looked for: <code><?php echo htmlspecialchars($pattern, ENT_QUOTES); ?></code></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<!-- ===================== /Large image ===================== -->
