<!doctype html>
<html>
<head>

    <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
    
    <!-- Google Lato font-->
    <link href="https://fonts.googleapis.com/css?family=Lato:100,100italic,300,300italic,regular,italic,700,700italic,900,900italic" rel="stylesheet" type="text/css">
    
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/main.css">
    
    <?php include 'php_init.php';?>
    
<title>Antarctic SEC</title>
</head>

<body>
    
    <nav class="navigation">
        
        <a href="http://www.esa.int"><img src="images/ESA_logo_white_transparent.png"  id="esa_image"></a>
        <a href="http://www.cpom.org.uk"><img src="images/CPOM-white.png"  id="cpom_image"></a>
        
        <ul class="nav-menu">
				<li style="padding-right: 10px;"><a href="http://www.cpom.ucl.ac.uk/csopr">Home</a></li>
                <li class="li_selected"><a  href="">Ice Sheets</a></li>
				<li><a href="http://www.cpom.ucl.ac.uk/csopr/seaice.php">Sea Ice</a></li>
				<li><a href="http://www.cpom.ucl.ac.uk/csopr/iv/index.php">Ice Velocity</a></li>
				<li><a href="http://www.cpom.ucl.ac.uk/csopr/brunt/">Ice Shelves</a></li>
                <li style="padding-left: 10px;"><a href="http://www.cpom.ucl.ac.uk/csopr/about.html">About</a></li>
        </ul>
        
         <p id="dataportal_text">CPOM Data Portal</p>
    </nav>
    
    <!----------------------------------------------------------------------------------------------------------------
    Tab selecting Antarctica or Greenland, and Product: Surface Elevation Change \/
    ------------------------------------------------------------------------------------------------------------------ -->
    
    <div id="tab_row">
        <ul class="nav nav-tabs" id="myTab">
            <?php if ($icesheet_zone == 'antarctica') { ?>
            <li class="nav-item"><a class="nav-link active" href="?icesheet_zone=antarctica">Antarctic Ice Sheet</a></li>
            <li class="nav-item my-nav-item"><a class="nav-link mynavlink" href="?icesheet_zone=greenland">Greenland Ice Sheet</a></li>
            <?php } else { ?>
    
            <li class="nav-item"><a class="nav-link active" href="?icesheet_zone=greenland">Greenland Ice Sheet</a></li>
            <li class="nav-item my-nav-item"><a class="nav-link mynavlink" href="?icesheet_zone=antarctica">Antarctic Ice Sheet</a></li>
            <?php } ?>

        
        </ul>
        
        <p id="product_type_txt"> Product:</p>
        
        <div class="w3-container">
            <div id="product_dropdown" class="w3-dropdown-hover">
                <button class="w3-button my-button-color">Surface Elevation Change <i class="fa fa-caret-down"></i></button>
                <div class="w3-dropdown-content w3-bar-block w3-card-4">
                  <a href="mass.php" class="w3-bar-item w3-button">Mass Change</a>
                  <a href="#" class="w3-bar-item w3-button">DEMs</a>
                </div>
           </div>
        </div>
    </div> <!-- end of tab_row -->
    
    <!----------------------------------------------------------------------------------------------------------------
    Main Section
    ------------------------------------------------------------------------------------------------------------------ -->
    
    <div class="main_section">
        <div class="w3-container">
            <div <?php if ($icesheet_zone == 'greenland') {
                        echo 'style="width: 100%"';
                        } else {
                        echo 'style="width: 80%"';
                        }
                 ?>
                 style="width: 80%"; class="w3-container w3-left">
                <h3>Surface Elevation Change (SEC) of the <?php echo $icesheet_name ; ?> Ice Sheet</h3>
                <p> The change in surface elevation of the <?php echo $icesheet_name ; ?> grounded ice sheet is measured from all available ESA Radar Altimetry missions (ERS-1, ERS-2, ENVISAT, CryoSat-2, Sentinel-3A, and Sentinel-3B) from 1991 to 2021.  We provide netCDF products of gridded surface elevation change at 5km resolution for every 5-year period between 1991 and 2021 (stepped by 1-year), and also for the full period of each altimetry mission.
                </p>
            </div>
            <div <?php if ($icesheet_zone == 'greenland') echo 'style="display:none;"' ?>
                 class="w3-container w3-display-topright">
                <img width="160px" src="images/ais_cci_logo.png" class="w3-round "></image>
            </div>
        </div> <!-- end of w3_container-->
    
        <?php if ($sec_type == 'download' ) { 
       //---------------------------------------------------------------------------------------
       //--      SEC Downloads
       //---------------------------------------------------------------------------------------
        ?>
    
    <div class="image_section">
        
        <?php
        // -------------------------------------------------------------------------------------
        // --     Registration for downloads (low security method!)
        // -------------------------------------------------------------------------------------
        
    
       if ($registered == 0 || $name == '' || $institute == '') { ?>
        <div class="w3-container">
        <form class="w3-container w3-half"  method="post" action="index.php" >
          <h3>Registration</h3> 
          <p>Before downloading SEC products we request that you fill in a few quick details. We only use these for download statistics and you will not be contacted.</p>
            
          <?php if ( $name != '' && $institute == '') echo '<p class="red">Please enter both Firstname.Lastname and Institute</p>';?>
          <?php if ( $name == '' && $institute != '') echo '<p class="red">Please enter both Firstname.Lastname and Institute</p>';
                if ($name_format_error) echo '<p class="red">Please use Firstname.Lastname format</p>';
            ?>

          <input class="w3-input" name="name" type="text" placeholder="Firstname.Lastname"><br>
          <input class="w3-input" name="institute" type="text" placeholder="Institute"><br>
          
         <button class="w3-button w3-black">Register</button>
          <input type="hidden" name="registered" value=1 >
          <input type="hidden" name="sec_type" value='download' >
            <!--<input type="submit" value="Submit"/> -->
        </form>
        
        <br class="reset">   
        <br>
            
    </div>
    
      <?php  } else { 
        
        // -------------------------------------------------------------------------------------
        // --     Downloads Page (after registration) (Antarctica)
        // -------------------------------------------------------------------------------------
        ?>
        <div class="w3-container">
        <div class="w3-container">
            <br>
            <p>Thanks <?php echo "$firstname";?> for registering for SEC data downloads. Click <a class="blue_link" href="index.php?icesheet_zone=antarctica">here</a> to exit the download page.</p>
            <hr>
            <h3>Product Downloads </h3>
            
            <!-- Multi-mission 5-year Product -->
            
            <h4>Multi-Mission Products</h4>
            
            <table class="w3-table w3-striped w3-bordered w3-border w3-rest">
                <tr>
                    <td>Product Description</td><td>Surface Elevation Change of the Antarctic Ice Sheet from Multi-Mission Altimetry (1991-2021), 5-year Gridded Means stepped by 1-year, 5km Resolution. Contains Netcdf data file and Quicklook images of main parameters for each 5-year period.</td>

                </tr>
                <tr>
                    <td>Product File</td><td><a class="blue_link" 
                        href="log.php?<?php print "seed=${rand_seed}&name=${name}&institute=${institute}";?>&file=ESACCI-AIS-L3C-SEC-MULTIMISSION-5KM-5YEAR-MEANS-1991-2021-fv1.zip">ESACCI-AIS-L3C-SEC-MULTIMISSION-5KM-5YEAR-MEANS-1991-2021-fv1.zip</a></td>

                </tr>
                <tr>
                    <td>Release date</td><td>1-Jun-2021</td>

                </tr>
                <tr>
                    <td>File size</td><td>110MB (compressed zip), 610MB (uncompressed data files within zip file)</td>
                </tr>
                <tr>
                    <td>Quicklook images</td><td>Quicklook images are included in the zip file, however you can <a class="blue_link" href="index.php?icesheet_zone=antarctica&sec_type=multi_mission">view them here</a></td>
                </tr>
            </table>
            
            <h4>Single Mission Products</h4>
            
            <!-- Single-mission Product -->
            
            <!-- S3-B -->
            <table class="w3-table w3-striped w3-bordered w3-border w3-rest">
                <tr>
                    <td>Product Description</td><td><b>Sentinel-3B</b> Single Mission Surface Elevation Change of the Antarctic Ice Sheet (2018-..), 5km Resolution. Contains Netcdf data file and Quicklook images of main parameters.</td>

                </tr>
                <tr>
                    <td>Product File</td><td><a class="blue_link" href="log.php?<?php print "seed=${rand_seed}name=${name}&institute=${institute}";?>&file=ESACCI-AIS-L3C-SEC-S3B-5KM-20181220-20210408-fv1.zip">ESACCI-AIS-L3C-SEC-S3B-5KM-20181220-20210408-fv1.zip</a></td>

                </tr>
                <tr>
                    <td>Release date</td><td>1-Jun-2021</td>

                </tr>
                <tr>
                    <td>File size</td><td>16MB (compressed zip), 41MB (uncompressed data files within zip file)</td>
                </tr>
                <tr>
                    <td>Quicklook images</td><td>Quicklook images are included in the zip file, however you can <a class="blue_link" href="index.php?icesheet_zone=antarctica&sec_type=single_mission&single_mission_view=s3b">view them here</a></td>
                </tr>
            </table>
            
            <br>
            
            <!-- S3-A -->
            <table class="w3-table w3-striped w3-bordered w3-border w3-rest">
                <tr>
                    <td>Product Description</td><td><b>Sentinel-3A</b> Single Mission Surface Elevation Change of the Antarctic Ice Sheet (2016-..), 5km Resolution. Contains Netcdf data file and Quicklook images of main parameters.</td>

                </tr>
                <tr>
                    <td>Product File</td><td><a class="blue_link" href="log.php?<?php print "seed=${rand_seed}&name=${name}&institute=${institute}";?>&file=ESACCI-AIS-L3C-SEC-S3A-5KM-20161115-20210202-fv1.zip">ESACCI-AIS-L3C-SEC-S3A-5KM-20161115-20210202-fv1.zip</a></td>

                </tr>
                <tr>
                    <td>Release date</td><td>1-Jun-2021</td>

                </tr>
                <tr>
                    <td>File size</td><td>16MB (compressed zip), 41MB (uncompressed data files within zip file)</td>
                </tr>
                <tr>
                    <td>Quicklook images</td><td>Quicklook images are included in the zip file, however you can <a class="blue_link" href="index.php?icesheet_zone=antarctica&sec_type=single_mission&single_mission_view=s3a">view them here</a></td>
                </tr>
            </table>
            
            <br>
            
            <!-- CS2 -->
            <table class="w3-table w3-striped w3-bordered w3-border w3-rest">
                <tr>
                    <td>Product Description</td><td><b>CryoSat-2</b> Single Mission Surface Elevation Change of the Antarctic Ice Sheet (2010-..), 5km Resolution. Contains Netcdf data file and Quicklook images of main parameters.</td>

                </tr>
                <tr>
                    <td>Product File</td><td><a class="blue_link" href="log.php?<?php print "seed=${rand_seed}&name=${name}&institute=${institute}";?>&file=ESACCI-AIS-L3C-SEC-CS2-5KM-20100927-20210202-fv1.zip">ESACCI-AIS-L3C-SEC-CS2-5KM-20100927-20210202-fv1.zip</a></td>

                </tr>
                <tr>
                    <td>Release date</td><td>1-Jun-2021</td>
                </tr>
                <tr>
                    <td>File size</td><td>18MB (compressed zip), 41MB (uncompressed data files within zip file)</td>
                </tr>
                <tr>
                    <td>Quicklook images</td><td>Quicklook images are included in the zip file, however you can <a class="blue_link" href="index.php?icesheet_zone=antarctica&sec_type=single_mission&single_mission_view=cs2">view them here</a></td>
                </tr>
            </table>
            
            <br>
            
            <!-- ENVISAT -->
            <table class="w3-table w3-striped w3-bordered w3-border w3-rest">
                <tr>
                    <td>Product Description</td><td><b>ENVISAT</b> Single Mission Surface Elevation Change of the Antarctic Ice Sheet (2002-2012), 5km Resolution. Contains Netcdf data file and Quicklook images of main parameters.</td>

                </tr>
                <tr>
                    <td>Product File</td><td><a class="blue_link" href="log.php?<?php print "seed=${rand_seed}&name=${name}&institute=${institute}";?>&file=ESACCI-AIS-L3C-SEC-ENV-5KM-20020909-20120409-fv1.zip">ESACCI-AIS-L3C-SEC-ENV-5KM-20020909-20120409-fv1.zip</a></td>

                </tr>
                <tr>
                    <td>Release date</td><td>1-Jun-2021</td>
                </tr>
                <tr>
                    <td>File size</td><td>16MB (compressed zip), 41MB (uncompressed data files within zip file)</td>
                </tr>
                <tr>
                    <td>Quicklook images</td><td>Quicklook images are included in the zip file, however you can <a class="blue_link" href="index.php?icesheet_zone=antarctica&sec_type=single_mission&single_mission_view=ev">view them here</a></td>
                </tr>
            </table>
            
            <br>
            
             <!-- ERS-2 -->
            <table class="w3-table w3-striped w3-bordered w3-border w3-rest">
                <tr>
                    <td>Product Description</td><td><b>ERS-2</b> Single Mission Surface Elevation Change of the Antarctic Ice Sheet (1995-2003), 5km Resolution. Contains Netcdf data file and Quicklook images of main parameters.</td>

                </tr>
                <tr>
                    <td>Product File</td><td><a class="blue_link" href="log.php?<?php print "seed=${rand_seed}&name=${name}&institute=${institute}";?>&file=ESACCI-AIS-L3C-SEC-ER2-5KM-19950529-20030616-fv1.zip">ESACCI-AIS-L3C-SEC-ER2-5KM-19950529-20030616-fv1.zip</a></td>

                </tr>
                <tr>
                    <td>Release date</td><td>1-Jun-2021</td>

                </tr>
                <tr>
                    <td>File size</td><td>16MB (compressed zip), 41MB (uncompressed data files within zip file)</td>
                </tr>
                <tr>
                    <td>Quicklook images</td><td>Quicklook images are included in the zip file, however you can <a class="blue_link" href="index.php?icesheet_zone=antarctica&sec_type=single_mission&single_mission_view=e2">view them here</a></td>
                </tr>
            </table>
            
            <br>
            
            <!-- ERS-1 -->
            <table class="w3-table w3-striped w3-bordered w3-border w3-rest">
                <tr>
                    <td>Product Description</td><td><b>ERS-1</b> Single Mission Surface Elevation Change of the Antarctic Ice Sheet (1991-1996), 5km Resolution. Contains Netcdf data file and Quicklook images of main parameters.</td>
                </tr>
                <tr>
                    <td>Product File</td><td><a class="blue_link" href="log.php?<?php print "seed=${rand_seed}&name=${name}&institute=${institute}";?>&file=ESACCI-AIS-L3C-SEC-ER1-5KM-19910813-19960519-fv1.zip">ESACCI-AIS-L3C-SEC-ER1-5KM-19910813-19960519-fv1.zip</a></td>
                </tr>
                <tr>
                    <td>Release date</td><td>1-Jun-2021</td>
                </tr>
                <tr>
                    <td>File size</td><td>16MB (compressed zip), 41MB (uncompressed data files within zip file)</td>
                </tr>
                <tr>
                    <td>Quicklook images</td><td>Quicklook images are included in the zip file, however you can <a class="blue_link" href="index.php?icesheet_zone=antarctica&sec_type=single_mission&single_mission_view=e1">view them here</a></td>
                </tr>
            </table>
            
        </div>
        </div>
        <br>
    <?php } ?>
    
    
    </div> <!-- image_section -->
    
       <?php  } // end of if (sec_type == 'download'
        else {   ?> 
    
        <!-- --------------------------------------------------------------------------------------------------------------
        --   Select via radio button either "o 5-Year Mean SEC, 10-Year Steps   o Full Mission SEC,     +  Download link"
        -------------------------------------------------------------------------------------------------------------- -->
    
        <div class="w3-container">
        
            <div class="w3-container sec_types">
            <div id="sec_type_controls">
                <?php if ( $icesheet_zone == 'antarctica') { ?>
                 <form style="display: inline;"  id='sec_controls_form' method ="post" action="index.php" >
                     
                    <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" onClick="this.form.submit();" name="sec_type" id="mapRadio1" value="multi_mission" <?php if($sec_type == 'multi_mission') print 'checked';?>>
                          <label class="form-check-label" for="mapRadio1">5-Year Mean SEC, 1-Year Steps</label>
                    </div>
                     
                    <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" onClick="this.form.submit();" name="sec_type" id="inlineRadio2" value="single_mission" <?php if($sec_type == 'single_mission') print 'checked';?>>
                          <label class="form-check-label" for="inlineRadio2">Full Mission SEC</label>
                    </div>
                     
                    <input type="hidden" name="icesheet_zone" value="<?php print "$icesheet_zone";?>" >
                    
                </form>
                
            
            </div>
                <a href="index.php?sec_type=download"><button class="btn download_button"><i class="fa fa-download"></i> Download NetCDF Products</button></a>
            </div>
            <?php } ?>
        </div> <!-- end of w3_container-->
 
        <?php if ( $icesheet_zone == 'antarctica') { ?>
    <!-- --------------------------------------------------------------------------------------------------------------
        --   Show Quicklook images (either as 3x3 small images or single)
        -------------------------------------------------------------------------------------------------------------- -->
    
     <div class="image_section">
         
         <!-- --------------------------------------------------------------------------------------------------------------
        --   Select Quicklook Parameter to show in dropdown selector
        -------------------------------------------------------------------------------------------------------------- -->
                  
             <div class="w3-container ">
              <br>
                 <?php if ($sec_type == 'single_mission') { ?>
             <p class="single_map_heading">Surface Elevation Change Product Maps from Single Radar Altimetry Missions</p>
                 <?php  } else { ?>
             <p class="single_map_heading">Surface Elevation Change Product Maps from Multi-mission Radar Altimetry </p>
                 <?php } ?>
                 <div class="w3-container">
                     
                    <div id="product_dropdown" class="w3-dropdown-hover">
                        <span id="parameter_txt">Parameter:</span>
                        <button class="w3-button my-button-color"><?php echo "$ql_param";?> <i class="fa fa-caret-down"></i></button>
                        <div class="w3-dropdown-content w3-bar-block w3-card-4">
                          <a href="index.php?ql_param=sec&sec_type=<?php echo "${sec_type}&single_mission_view=${single_mission_view}";?>&icesheet_zone=<?php echo "$icesheet_zone";?> " class="w3-bar-item w3-button">Surface Elevation Change (SEC)</a>
                          <a href="index.php?ql_param=sec_uncertainty&sec_type=<?php echo "${sec_type}&single_mission_view=${single_mission_view}";?>&icesheet_zone=<?php echo "$icesheet_zone";?> " class="w3-bar-item w3-button">Uncertainty of SEC</a>
                          <a href="index.php?ql_param=surface_type&sec_type=<?php echo "${sec_type}&single_mission_view=${single_mission_view}";?>&icesheet_zone=<?php echo "$icesheet_zone";?> " class="w3-bar-item w3-button">Surface Type (BedMachine v2) </a>
                          <a href="index.php?ql_param=basin_id&sec_type=<?php echo "${sec_type}&single_mission_view=${single_mission_view}";?>&icesheet_zone=<?php echo "$icesheet_zone";?> " class="w3-bar-item w3-button">Glacialogical Basin ID (IMBIE)</a>
                        </div>
                   </div>
                </div>
                 
             </div> <!-- w3-container -->
         
         <?php if ($sec_type == 'single_mission') { ?>
         
             <!-- --------------------------------------------------------------------------------------------------------------
            --   Show 3x3 array of Quicklook images of current parameter (Single Mission)
            -------------------------------------------------------------------------------------------------------------- -->
    
             <?php if ($single_mission_view == 'all') { ?>
         
             <div class="w3-row-padding w3-margin-top">

                 <!-- S3-B -->
                 <div class="w3-third">
                     <div class="w3-card">
                         <!-- the image -->
                         <!-- <image style=\"width:100%\"  srcset=\"$image_file_492 492w,\" src=\"$image_file_492\"  sizes=\"(min-width: 768px) 80vw, 100vw\"></a>"); 
                         -->
                         <?php 
                                $imagefile=glob("quicklooks/ESACCI-AIS-L3C-SEC-S3B-5KM-*_${ql_param}.png")[0];
                         ?>
                         <a href="index.php?ql_param=<?php echo "$ql_param";?>&sec_type=single_mission&single_mission_view=s3b"><image style="width:100%" srcset="" src="<?php echo $imagefile;?>" sizes=""></a>
                        <div class="w3-container">   
                            <div class="all-text image_card_text">Sentinel-3B <span class="date">(Dec-2018 to Apr-2021)</span></div>
                        </div>
                     </div>
                 </div>

                  <!-- S3-A -->
                  <div class="w3-third">
                     <div class="w3-card">
                         <!-- the image -->
                        <?php 
                                $imagefile=glob("quicklooks/ESACCI-AIS-L3C-SEC-S3A-5KM-*_${ql_param}.png")[0];
                         ?>
                         <a href="index.php?ql_param=<?php echo "$ql_param";?>&sec_type=single_mission&single_mission_view=s3a"><image style="width:100%" srcset="" src="<?php echo $imagefile;?>" sizes=""></a>
                        <div class="w3-container">   
                            <div class="all-text image_card_text">Sentinel-3A <span class="date">(Nov-2016 to Feb-2021)</span></div>
                        </div>
                     </div>
                 </div>

                   <!-- CS2 -->
                  <div class="w3-third">
                     <div class="w3-card">
                         <!-- the image -->
                         <?php 
                                $imagefile=glob("quicklooks/ESACCI-AIS-L3C-SEC-CS2-5KM-*_${ql_param}.png")[0];
                         ?>
                         <a href="index.php?ql_param=<?php echo "$ql_param";?>&sec_type=single_mission&single_mission_view=cs2"><image style="width:100%" srcset="" src="<?php echo $imagefile;?>" sizes=""></a>
                        <div class="w3-container">   
                            <div class="all-text image_card_text">CryoSat-2 <span class="date">(Sep-2010 to Feb-2021)</span></div>
                        </div>
                     </div>
                 </div>

             </div>

            <div class="w3-row-padding w3-margin-top">

                 <!-- ENVISAT -->
                 <div class="w3-third">
                     <div class="w3-card">
                         <!-- the image -->
                         <!-- <image style=\"width:100%\"  srcset=\"$image_file_492 492w,\" src=\"$image_file_492\"  sizes=\"(min-width: 768px) 80vw, 100vw\"></a>"); 
                         -->
                         <?php 
                                $imagefile=glob("quicklooks/ESACCI-AIS-L3C-SEC-ENV-5KM-*_${ql_param}.png")[0];
                         ?>
                         <a href="index.php?ql_param=<?php echo "$ql_param";?>&sec_type=single_mission&single_mission_view=ev"><image style="width:100%" srcset="" src="<?php echo $imagefile;?>" sizes=""></a>
                        <div class="w3-container">   
                            <div class="all-text image_card_text">ENVISAT <span class="date">(Sep-2002 to Apr-2012)</span></div>
                        </div>
                     </div>
                 </div>

                  <!-- ERS-2 -->
                  <div class="w3-third">
                     <div class="w3-card">
                         <!-- the image -->
                        <?php 
                                $imagefile=glob("quicklooks/ESACCI-AIS-L3C-SEC-ER2-5KM-*_${ql_param}.png")[0];
                         ?>
                         <a href="index.php?ql_param=<?php echo "$ql_param";?>&sec_type=single_mission&single_mission_view=e2"><image style="width:100%" srcset="" src="<?php echo $imagefile;?>" sizes=""></a>
                        <div class="w3-container">   
                            <div class="all-text image_card_text">ERS-2 <span class="date">(May-1995 to Jun-2003)</span></div>
                        </div>
                     </div>
                 </div>

                   <!-- ERS-1 -->
                  <div class="w3-third">
                     <div class="w3-card">
                         <!-- the image -->
                         <?php 
                                $imagefile=glob("quicklooks/ESACCI-AIS-L3C-SEC-ER1-5KM-*_${ql_param}.png")[0];
                         ?>
                         <a href="index.php?ql_param=<?php echo "$ql_param";?>&sec_type=single_mission&single_mission_view=e1"><image style="width:100%" srcset="" src="<?php echo $imagefile;?>" sizes=""></a>
                        <div class="w3-container">   
                            <div class="all-text image_card_text">ERS-1 <span class="date">(Aug-1991 to May-1996)</span></div>
                        </div>
                     </div>
                 </div>

             </div> <!-- end of image row -->
            <?php 
            } else {  
                // -------------------------------------------------------------------------------------
                // --     Show single large quicklook (single mission) 
                // -------------------------------------------------------------------------------------
                      ?>
                
                    <div class="w3-container mission_radios_div">
                 <?php   
                // -------------------------------------------------------------------------------------
                // --     Show single large quicklook (single mission) 
                // -------------------------------------------------------------------------------------?>
                        
                 <form style="display: inline;"  id='single_view_form' method ="post" action="index.php" >
                     
                    <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" onClick="this.form.submit();" name="single_mission_view" id="viewRadio1" value="all" <?php if($single_mission_view == 'all') print 'checked';?>>
                          <label class="form-check-label" for="viewRadio1">Show All</label>
                    </div>
                     
                     
                    <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" onClick="this.form.submit();" name="single_mission_view" id="viewRadio2" value="s3b" <?php if($single_mission_view  == 's3b') print 'checked';?>>
                          <label class="form-check-label" for="viewRadio2">Sentinel-3B</label>
                    </div>
                     
                     <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" onClick="this.form.submit();" name="single_mission_view" id="viewRadio3" value="s3a" <?php if($single_mission_view  == 's3a') print 'checked';?>>
                          <label class="form-check-label" for="viewRadio3">Sentinel-3A</label>
                    </div>
                     
                    <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" onClick="this.form.submit();" name="single_mission_view" id="viewRadio4" value="cs2" <?php if($single_mission_view  == 'cs2') print 'checked';?>>
                          <label class="form-check-label" for="viewRadio4">CryoSat-2</label>
                    </div>
                     
                    <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" onClick="this.form.submit();" name="single_mission_view" id="viewRadio5" value="ev" <?php if($single_mission_view  == 'ev') print 'checked';?>>
                          <label class="form-check-label" for="viewRadio5">ENVISAT</label>
                    </div>
                     
                    <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" onClick="this.form.submit();" name="single_mission_view" id="viewRadio6" value="e2" <?php if($single_mission_view  == 'e2') print 'checked';?>>
                          <label class="form-check-label" for="viewRadio6">ERS-2</label>
                    </div> 
                     
                    <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" onClick="this.form.submit();" name="single_mission_view" id="viewRadio7" value="e1" <?php if($single_mission_view  == 'e1') print 'checked';?>>
                          <label class="form-check-label" for="viewRadio7">ERS-1</label>
                    </div> 
                    
                     
                    <input type="hidden" name="icesheet_zone" value="<?php print "$icesheet_zone";?>" >
                    <input type="hidden" name="sec_type" value="<?php print "$sec_type";?>" >
                  
                </form>
            
            </div>  <!-- end of div surrounding mission radio buttons -->
                    
                
                <div class="w3-container">
                     <div class="w3-card">
                         <!-- the image -->
                         <!-- <image style=\"width:100%\"  srcset=\"$image_file_492 492w,\" src=\"$image_file_492\"  sizes=\"(min-width: 768px) 80vw, 100vw\"></a>"); 
                         -->
                         <?php 
                                $search_str="quicklooks/ESACCI-AIS-L3C-SEC-${mission_str}-5KM-*_${ql_param}.png";
                                $imagefile=glob($search_str)[0];
                         ?>
                         <a href="index.php?ql_param=<?php echo "$ql_param";?>&sec_type=single_mission&single_mission_view=all"><image style="width:100%" srcset="" src="<?php echo $imagefile;?>" sizes=""></a>
                        <div class="w3-container">   
                            <div class="all-text"><?php echo ${mission_str}; ?></div>
                        </div>
                     </div>
                 </div>
            <?php }
        } else { ?>
                      
            <div class="w3-container">           
                
                <div class="w3-content w3-display-container">
                    <div class="w3-center">
                      <div class="w3-section">
                            <button class="w3-button w3-light-grey" onclick="plusDivs(-1)">❮ Prev 5-year Period</button>
                            <p style="display: inline;" id="image_period">2017-2021</p>
                            <button class="w3-button w3-light-grey" onclick="plusDivs(1)">Next 5-year Period ❯</button>
                          </div>
                        <div style="display: inline;">1991-1995</div>
                        <?php 
                        
                        for ($i = 1; $i <= 27; $i++) {
                            echo "<button class=\" demo timeline\" onclick=\"currentDiv(${i})\">.</button>";
                        }
                        ?>
                        <div style="display: inline;">2017-2021</div>
                          
                    </div>
                    <?php 
                        $start_year=1991;
                        $end_year=2021-4;
                        for ($year = $start_year; $year <= $end_year; $year++) {
                            $eyear=$year+4;
                            if ($ql_param == 'sec' || $ql_param == 'sec_uncertainty') {
                                $imgfile="quicklooks/ESACCI-AIS-L3C-SEC-MULTIMISSION-5KM-5YEAR-MEANS-1991-2021-fv1-${year}-${eyear}_${ql_param}.png";
                            } else {
                                $imgfile="quicklooks/ESACCI-AIS-L3C-SEC-MULTIMISSION-5KM-5YEAR-MEANS-1991-2021-fv1_${ql_param}.png";
                            }
                            echo "<img class=\"slide\" src=\"${imgfile}\">\n";
                        }
                    ?>
                  
                    
                </div>
                    
            </div>         
                    
                    
        <?php }  // end of multi-mission image section ?>

    </div> <!-- end of image_section -->
                      <?php } // if antarctica
                         ?>
                      
                      <?php }  ?>
    </div> <!-- end of main_section-->
        
                <?php if ($icesheet_zone == 'greenland') {?>
                     <h3>Notice</h3>
                    <p>Greenland Ice Sheet surface elevation change products will be available shortly from CPOM. Until then, please visit the <a class="blue_link" href="http://esa-icesheets-greenland-cci.org/">Greenland CCI portal</a> for Greenland SEC products.</p>
                <?php }  ?>

<script>
var slideIndex = 27;
showDivs(slideIndex);

function plusDivs(n) {
  showDivs(slideIndex += n);
}

function currentDiv(n) {
  showDivs(slideIndex = n);
}

const parameterizedString = (...args) => {
  const str = args[0];
  const params = args.filter((arg, index) => index !== 0);
  if (!str) return "";
  return str.replace(/%s[0-9]+/g, matchedStr => {
    const variableIndex = matchedStr.replace("%s", "") - 1;
    return params[variableIndex];
  });
}

function showDivs(n) {
  var i;
  var x = document.getElementsByClassName("slide");
  var dots = document.getElementsByClassName("demo");
  var txt = document.getElementById("image_period");
  
  if (n > x.length) {slideIndex = 1;
                    n=1;}    
  if (n < 1) {slideIndex = x.length}
  for (i = 0; i < x.length; i++) {
    x[i].style.display = "none";  
  }
  for (i = 0; i < dots.length; i++) {
    dots[i].className = dots[i].className.replace(" bg_blue", "");
  }
  x[slideIndex-1].style.display = "block";  
  dots[slideIndex-1].className += " bg_blue";
    
  txt.innerHTML = (1990+n) + "-" + (1990+n+4);
}
</script>
</body>
</html>
