<?php
require __DIR__.'/php_init.php';

$tab = $_GET['active_tab'] ?? 'intro';
$allowed = ['intro','single_mission','is2_sec','multi_mission','annual_dh','download'];
if (!in_array($tab, $allowed, true)) { http_response_code(404); exit('Unknown tab'); }

// accept state so images render correctly
if (isset($_GET['ql_param'])) $ql_param = $_GET['ql_param'];
if (isset($_GET['hillshade'])) $hillshade = $_GET['hillshade'];
if (isset($_GET['single_mission_view'])) $single_mission_view = $_GET['single_mission_view'];

ob_start();
include __DIR__ . "/{$tab}.php";
$html = ob_get_clean();

header('Content-Type: text/html; charset=UTF-8');
echo $html;
?>