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

use GlpiPlugin\Manufacturersimports\Config;
use GlpiPlugin\Manufacturersimports\Menu;

if (!isset($_GET["id"])) {
    $_GET["id"] = 0;
}
if (!isset($_GET["preconfig"])) {
    $_GET["preconfig"] = -1;
}

$config = new Config();

if (isset($_POST["add"])) {
    Session::checkRight("plugin_manufacturersimports", CREATE);
    $config->add($_POST);
    Html::back();

} else if (isset($_POST["update"])) {

    Session::checkRight("plugin_manufacturersimports", UPDATE);
    $config->update($_POST);
    Html::back();

} else if (isset($_POST["delete"])) {

    Session::checkRight("plugin_manufacturersimports", PURGE);
    $config->delete($_POST, true);
    Html::redirect("./config.php");

} else if (isset($_POST["purge"])) {

    Session::checkRight("plugin_manufacturersimports", PURGE);
    $config->delete($_POST, true);
    Html::redirect("./config.php");

} else if (isset($_POST["test_connection"])) {
    Session::checkLoginUser();
    // CSRF is already validated (and preserved) by CheckCsrfListener for XHR requests.
    header("Content-Type: application/json; charset=UTF-8");

    $token_url = trim($_POST['token_url'] ?? '');
    $test_mode = trim($_POST['test_mode'] ?? 'head');

    if (empty($token_url)) {
        echo json_encode(['success' => false, 'message' => __('Token URL is empty', 'manufacturersimports')]);
        exit;
    }
    if (!function_exists('curl_init')) {
        echo json_encode(['success' => false, 'message' => __('Curl PHP package not installed', 'manufacturersimports')]);
        exit;
    }

    global $CFG_GLPI;

    if ($test_mode === 'oauth') {
        $supplier_key    = trim($_POST['supplier_key'] ?? '');
        $supplier_secret = trim($_POST['supplier_secret'] ?? '');

        $ch = curl_init($token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id'     => $supplier_key,
            'client_secret' => $supplier_secret,
            'grant_type'    => 'client_credentials',
        ]));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $supplier_key . ':' . $supplier_secret);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if (!empty($CFG_GLPI['proxy_name'])) {
            curl_setopt($ch, CURLOPT_PROXY, $CFG_GLPI['proxy_name'] . ':' . $CFG_GLPI['proxy_port']);
            if (!empty($CFG_GLPI['proxy_user'])) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $CFG_GLPI['proxy_user'] . ':' . (new GLPIKey())->decrypt($CFG_GLPI['proxy_passwd']));
            }
        }
        $response   = curl_exec($ch);
        $http_code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error !== '') {
            echo json_encode(['success' => false, 'message' => $curl_error]);
            exit;
        }

        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            echo json_encode(['success' => true, 'message' => __('Authentication successful (token received)', 'manufacturersimports')]);
        } else {
            $api_error = $data['error_description'] ?? $data['error'] ?? __('No access token in response', 'manufacturersimports');
            echo json_encode(['success' => false, 'message' => sprintf('HTTP %d — %s', $http_code, $api_error)]);
        }
        exit;
    }

    // HEAD reachability test (supplier_url for Fujitsu, Toshiba, Wortmann, Lenovo).
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
    $http_code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error !== '' || $http_code === 0) {
        $msg = $curl_error !== '' ? $curl_error : __('No response from server', 'manufacturersimports');
        echo json_encode(['success' => false, 'message' => $msg]);
    } else {
        echo json_encode(['success' => true, 'message' => sprintf(__('Server reachable (HTTP %d)', 'manufacturersimports'), $http_code)]);
    }
    exit;

} else if (isset($_POST["retrieve_warranty"])) {
    Session::checkRight("plugin_manufacturersimports", UPDATE);

    Config::retrieveOneWarranty($_POST["itemtype"], $_POST["items_id"]);

    Html::back();

} else {

    Html::header(__('Setup'), '', "tools", Menu::class, "config");

    $config->checkGlobal(READ);
    $config->display($_GET);
    Html::footer();
}
