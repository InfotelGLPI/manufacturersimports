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


Html::header(PluginManufacturersimportsPreImport::getTypeName(),'',
             "tools","pluginmanufacturersimportsmenu");

$preimport = new PluginManufacturersimportsPreImport();
if ($preimport->canView() || Session::haveRight("config", UPDATE)) {
   $values = $_GET;
   if (isset($_POST["typechoice"])) {
      $values = $_POST;
   }
   PluginManufacturersimportsPreImport::searchForm($values);
   PluginManufacturersimportsPreImport::seePreImport($values);
} else {
   Html::displayRightError();
}
Html::footer();
?>