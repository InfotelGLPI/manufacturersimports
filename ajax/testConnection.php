<?php

/*
 -------------------------------------------------------------------------
 manufacturersimports plugin for GLPI
 Copyright (C) 2015-2026 by the manufacturersimports Development Team.

 https://github.com/InfotelGLPI/manufacturersimports
 -------------------------------------------------------------------------

 LICENSE

 This file is part of manufacturersimports.

 manufacturersimports is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 manufacturersimports is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with manufacturersimports. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (strpos($_SERVER['PHP_SELF'], "testConnection.php")) {
    header("Content-Type: application/json; charset=UTF-8");
    Html::header_nocache();
}
if (!defined('GLPI_ROOT')) {
    die("Can not access directly to this file");
}

// Reaching out to an arbitrary URL server-side requires the plugin update right,
// not merely being logged in (defends against SSRF by unprivileged users).
Session::checkRight("plugin_manufacturersimports", UPDATE);

$token_url = trim($_POST['token_url'] ?? '');

if (empty($token_url)) {
    echo json_encode([
        'success' => false,
        'message' => __('Token URL is empty', 'manufacturersimports'),
    ]);
    exit;
}

// Reject non-https URLs and hosts resolving to private/reserved IPs (SSRF).
if (!\GlpiPlugin\Manufacturersimports\Config::isSafeApiUrl($token_url)) {
    echo json_encode([
        'success' => false,
        'message' => __('Invalid or forbidden URL', 'manufacturersimports'),
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
// Do not follow redirects: a public host could otherwise bounce us to an
// internal target, bypassing the URL validation above.
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
// Pin the host to the exact IP validated above so curl cannot re-resolve it to
// an internal address (DNS-rebinding TOCTOU). Empty when a proxy is configured.
$resolve = \GlpiPlugin\Manufacturersimports\Config::getPinnedResolve($token_url);
if (!empty($resolve)) {
    curl_setopt($ch, CURLOPT_RESOLVE, $resolve);
}

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
