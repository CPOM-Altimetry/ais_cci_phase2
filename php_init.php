<?php
// ---------------------------------------------------------------------------
// PHP INITIALISATION & GLOBAL STATE
// ---------------------------------------------------------------------------
// • Hillshade state is persisted via POST + $_SESSION (NO GET used).
// • Default hillshade = 'show' (ON by default).
// • Active tab is persisted via POST + $_SESSION (default = 'intro').
// • Product selection (ql_param), mission selection, etc. continue via GET/POST.
// ---------------------------------------------------------------------------

date_default_timezone_set("Europe/London");
session_start();

// If session says we’re on ICESat-2 (or anything not single_mission), ignore a stale GET flag
if (!empty($_SESSION['active_tab']) && $_SESSION['active_tab'] !== 'single_mission') {
    unset($_GET['show_single_mission']);
}


// ---------------------------------------------------------------------------
// HILLSHADE TOGGLE (SESSION + POST ONLY)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hillshade'])) {
    // Enforce valid values: 'show' or 'hide'
    $_SESSION['hillshade'] = ($_POST['hillshade'] === 'hide') ? 'hide' : 'show';
}
$hillshade = $_SESSION['hillshade'] ?? 'show'; // default ON

// ---------------------------------------------------------------------------
// ACTIVE TAB MEMORY (SESSION + POST)
// Allowed tabs: intro, single_mission, is2_sec, multi_mission, annual_dh
// ---------------------------------------------------------------------------
$allowed_tabs = ['intro', 'single_mission', 'is2_sec', 'multi_mission', 'annual_dh'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['active_tab'])) {
    $posted_tab = $_POST['active_tab'];
    if (in_array($posted_tab, $allowed_tabs, true)) {
        $_SESSION['active_tab'] = $posted_tab;
    }
}
if (isset($_GET['active_tab'])) {
    $_SESSION['active_tab'] = $_GET['active_tab'];
} else {
    $active_tab = $_SESSION['active_tab'] ?? 'intro';
}

// ---------------------------------------------------------------------------
// SHOW_SINGLE_MISSION (GET/POST preserved)
// ---------------------------------------------------------------------------
if (isset($_POST['show_single_mission'])) {
    $show_single_mission = $_POST['show_single_mission'];
} else {
    if (isset($_GET['show_single_mission'])) {
        $show_single_mission = $_GET['show_single_mission'];
    } else {
        $show_single_mission = 0;
    }
}

// ---------------------------------------------------------------------------
// PARAMETER DROPDOWN: ql_param (Allowed: sec, sec_uncertainty, surface_type, basin_id)
// ---------------------------------------------------------------------------
$all_ql_params = array('sec','sec_uncertainty','surface_type','basin_id');

if (isset($_POST['ql_param'])) {
    $ql_param = $_POST['ql_param'];
} else {
    if (isset($_GET['ql_param'])) {
        $ql_param = $_GET['ql_param'];
    } else {
        $ql_param = $all_ql_params[0];
    }
}
if (!in_array($ql_param, $all_ql_params, true)) {
    $ql_param = $all_ql_params[0];
}

// ---------------------------------------------------------------------------
// SINGLE MISSION VIEW: 'all','s3a','s3b','cs2','ev','e2','e1'
// ---------------------------------------------------------------------------
$all_single_mission_view_types = array('all','s3a','s3b','cs2','ev','e2','e1');

if (isset($_POST['single_mission_view'])) {
    $single_mission_view = $_POST['single_mission_view'];
} else {
    if (isset($_GET['single_mission_view'])) {
        $single_mission_view = $_GET['single_mission_view'];
    } else {
        $single_mission_view = $all_single_mission_view_types[0];
    }
}
if (!in_array($single_mission_view, $all_single_mission_view_types, true)) {
    $single_mission_view = $all_single_mission_view_types[0];
}

// Derive mission code string used in file paths
if     ($single_mission_view == 'e1')  $mission_str = 'ER1';
elseif ($single_mission_view == 'e2')  $mission_str = 'ER2';
elseif ($single_mission_view == 'ev')  $mission_str = 'ENV';
elseif ($single_mission_view == 'cs2') $mission_str = 'CS2';
elseif ($single_mission_view == 's3a') $mission_str = 'S3A';
elseif ($single_mission_view == 's3b') $mission_str = 'S3B';
else                                   $mission_str = ''; // 'all' (or unknown) -> blank

// ---------------------------------------------------------------------------
// MISC PHP SETTINGS
// ---------------------------------------------------------------------------
ini_set('pcre.jit', 0);
