<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2003-2016 by the Manufacturersimports Development Team.

 https://github.com/InfotelGLPI
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

include ('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$log=new PluginManufacturersimportsLog();
$config = new PluginManufacturersimportsConfig();

Html::header(_n('Suppliers import', 'Suppliers imports', 2, 'manufacturersimports'),
               $_SERVER['PHP_SELF'],"tools", "pluginmanufacturersimportsmenu");

$config->checkGlobal(UPDATE);

if (isset($_POST["action"])&&isset($_POST["id"])&&isset($_POST["item"])&&count($_POST["item"])) {
   switch($_POST["action"]) {
      case "import":
         PluginManufacturersimportsPostImport::massiveimport($_POST);
         break;

      case "reinit_once":
         foreach ($_POST["item"] as $key => $val) {
            if ($val==1) {
               $log->reinitializeImport($_POST["itemtype"],$key);
            }
      }
      Session::addMessageAfterRedirect(__('Operation successful'));
      Html::redirect($_SERVER['HTTP_REFERER']."?itemtype=".$_POST["itemtype"].
                     "&manufacturers_id=".$_POST["manufacturers_id"].
                     "&start=".$_POST["start"].
                     "&imported=".$_POST["imported"]);
      break;
   }
} else {
        echo "<div align='center'>";
        echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br>";
        echo "<b>".__('No selected element or badly defined operation')."</b></div>";
}

Html::footer();

?>