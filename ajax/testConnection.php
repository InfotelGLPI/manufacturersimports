<?php
/*
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2009-2022 by the Manufacturersimports Development Team.
 -------------------------------------------------------------------------
 */

if (strpos($_SERVER['PHP_SELF'], "testConnection.php")) {
    header("Content-Type: application/json; charset=UTF-8");
    Html::header_nocache();
}
if (!defined('GLPI_ROOT')) {
    die("Can not access directly to this file");
}

Session::checkLoginUser();

$token_url = trim($_POST['token_url'] ?? '');

if (empty($token_url)) {
    echo json_encode([
        'success' => false,
        'message' => __('Token URL is empty', 'manufacturersimports'),
    ]);
    exit;
}

if (!function_exists('curl_init')) {
    echo json_encode([
        'success' => false,
        'message' => __('Curl PHP package not installed', 'manufacturersimports'),
    ]);
    exit;
}

global $CFG_GLPI;

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

if (!empty($CFG_GLPI['proxy_name'])) {
    curl_setopt($ch, CURLOPT_PROXY, $CFG_GLPI['proxy_name'] . ':' . $CFG_GLPI['proxy_port']);
    if (!empty($CFG_GLPI['proxy_user'])) {
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $CFG_GLPI['proxy_user'] . ':' . (new GLPIKey())->decrypt($CFG_GLPI['proxy_passwd']));
    }
}

curl_exec($ch);
$http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error !== '') {
    echo json_encode([
        'success' => false,
        'message' => $curl_error,
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => sprintf(__('Server reachable (HTTP %d)', 'manufacturersimports'), $http_code),
]);
