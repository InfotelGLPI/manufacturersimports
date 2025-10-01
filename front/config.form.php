<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2003-2016 by the Manufacturersimports Development Team.

 https://github.com/InfotelGLPI/manufacturersimports
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Manufacturersimports.

 Manufacturersimports is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Manufacturersimports is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Manufacturersimports. If not, see <http://www.gnu.org/licenses/>.
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
