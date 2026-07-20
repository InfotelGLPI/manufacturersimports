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

if (strpos($_SERVER['PHP_SELF'], "dropdownSuppliers.php")) {
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}
if (!defined('GLPI_ROOT')) {
   die("Can not access directly to this file");
}

Session::checkLoginUser();
Session::checkRight("plugin_manufacturersimports", READ);

$config = new Config();

$supplier          = $_POST['supplier'] ?? -1;
$allowed_suppliers = [
   Config::DELL, Config::HP, Config::FUJITSU,
   Config::LENOVO, Config::TOSHIBA, Config::WORTMANN_AG,
];

// Only emit the link for a known supplier: this whitelist prevents any
// reflected value from ending up in the generated markup.
if (in_array($supplier, $allowed_suppliers, true)) {
   $url = $config->getFormURL() . "?preconfig=" . rawurlencode($supplier);
   echo "&nbsp;<a class='submit btn btn-primary' href='" . htmlescape($url) . "'>" . _sx('button', 'Update') . "</a>";
}
