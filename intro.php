<div style="float:right; margin:0 0 12px 16px; background:#f7fbff; border:1px solid #cfe; padding:10px 14px; border-radius:6px; font-weight:600; box-shadow:0 1px 3px rgba(0,0,0,0.08); max-width:260px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="material-icons md-dark" style="color:#033867;">
                    info
                </span>
                <div style="font-weight:600;">Click on the Tabs above to visualize each product type.</div>
            </div>
            <p style="margin:8px 0 0; font-weight:normal;">To download products click on the download link above</p>
        </div>
        <h3>Antarctic CCI+ Phase-2 SEC Product Types</h3>
        <p>The latest surface elevation change (SEC) product types available from phase-2 of the Antarctic CCI+ project
            are shown below. 
        </p>
        <ul>
            <li>SEC from the full time period of <b>individual satellite radar altimetry 
                missions</b> (ERS-1, ERS-2, ENVISAT, CryoSat-2, Sentinel-3A, Sentinel-3B)</li>
            <li>SEC from the full period of the <b>laser altimetry mission</b> ICESat-2</li>
            <li>5-year SEC from <b>multi-mission</b> cross-calibrated radar altimetry from 
            1991 onwards, in monthly steps</li>
            <li>Annual dH from <b>multi-mission</b> cross-calibrated radar altimetry since 1991.</li>
        </ul>
        <p>Parameters for each product are provided on a <b>5km south polar stereographic 
            grid</b> (EPSG:3031) in NetCDF4 format.</p>

        <h3>Input Data Sources</h3>
        <p>The primary input to the Antarctic CCI SEC processing chains are L2 files. 
            These contain along-track geophysical variables such as elevation and backscatter 
            which have been corrected for instrument, geophysical and atmospheric effects. We use the latest
            available thematic land ice L2 processing baseline products from each mission as shown below:
            </p>
        <table class="product-table" style="width:100%; border-collapse:collapse; margin-top:1em;">
            <thead>
                <tr>
                    <th style="text-align:left; padding:8px; border-bottom:2px solid #ccc;">Mission</th>
                    <th style="text-align:left; padding:8px; border-bottom:2px solid #ccc;">Instrument Type</th>
                    <th style="text-align:left; padding:8px; border-bottom:2px solid #ccc;">L2 Baseline</th>
                    <th style="text-align:left; padding:8px; border-bottom:2px solid #ccc;">Start Time</th>
                    <th style="text-align:left; padding:8px; border-bottom:2px solid #ccc;">End Time</th>
                </tr>
            </thead>
            <tbody>
                <tr><td style="padding:8px; border-bottom:1px solid #eee;">ERS-1</td><td style="padding:8px; border-bottom:1px solid #eee;">Radar altimeter</td><td style="padding:8px; border-bottom:1px solid #eee;">ESA FDR4ALT v1</td><td style="padding:8px; border-bottom:1px solid #eee;">1991</td><td style="padding:8px; border-bottom:1px solid #eee;">2000</td></tr>
                <tr><td style="padding:8px; border-bottom:1px solid #eee;">ERS-2</td><td style="padding:8px; border-bottom:1px solid #eee;">Radar altimeter</td><td style="padding:8px; border-bottom:1px solid #eee;">ESA FDR4ALT v1</td><td style="padding:8px; border-bottom:1px solid #eee;">1995</td><td style="padding:8px; border-bottom:1px solid #eee;">2011</td></tr>
                <tr><td style="padding:8px; border-bottom:1px solid #eee;">ENVISAT</td><td style="padding:8px; border-bottom:1px solid #eee;">Radar altimeter</td><td style="padding:8px; border-bottom:1px solid #eee;">ESA FDR4ALT v1</td><td style="padding:8px; border-bottom:1px solid #eee;">2002</td><td style="padding:8px; border-bottom:1px solid #eee;">2012</td></tr>
                <tr><td style="padding:8px; border-bottom:1px solid #eee;">CryoSat-2</td><td style="padding:8px; border-bottom:1px solid #eee;">SAR / Interferometric radar altimeter</td><td style="padding:8px; border-bottom:1px solid #eee;">ESA CryoTEMPO L2 Land Ice Baseline-D</td><td style="padding:8px; border-bottom:1px solid #eee;">2010</td><td style="padding:8px; border-bottom:1px solid #eee;">Present-2 mths</td></tr>
                <tr><td style="padding:8px; border-bottom:1px solid #eee;">Sentinel-3A</td><td style="padding:8px; border-bottom:1px solid #eee;">SRAL radar altimeter</td><td style="padding:8px; border-bottom:1px solid #eee;">ESA L2 BC005</td><td style="padding:8px; border-bottom:1px solid #eee;">2016</td><td style="padding:8px; border-bottom:1px solid #eee;">Present-2 mths</td></tr>
                <tr><td style="padding:8px; border-bottom:1px solid #eee;">Sentinel-3B</td><td style="padding:8px; border-bottom:1px solid #eee;">SRAL radar altimeter</td><td style="padding:8px; border-bottom:1px solid #eee;">ESA L2 BC005</td><td style="padding:8px; border-bottom:1px solid #eee;">2018</td><td style="padding:8px; border-bottom:1px solid #eee;">Present-2 mths</td></tr>
                <tr><td style="padding:8px;">ICESat-2</td><td style="padding:8px;">ATLAS laser altimeter</td><td style="padding:8px;">ATL-06 v006</td><td style="padding:8px;">2018</td><td style="padding:8px;">Present-4 mths</td></tr>
            </tbody>
        </table>