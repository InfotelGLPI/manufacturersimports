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

Session::checkLoginUser();

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (isset($_POST["action"])||isset($_POST["id"])) {
   echo "<input type='hidden' name='action' value='".$_POST["action"]."'>";
   echo "<input type='hidden' name='id' value='".$_POST["id"]."'>";
   switch($_POST["action"]) {

      case "import":
         echo "<input type='hidden' name='itemtype' value='".$_POST["itemtype"]."'>";
         echo "<input type='hidden' name='start' value='".$_POST["start"]."'>";
         echo "<input type='hidden' name='manufacturers_id' value='".$_POST["manufacturers_id"]."'>";
         echo "<input type='hidden' name='imported' value='".$_POST["imported"]."'>";
         echo "&nbsp;<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value='"._sx('button', 'Post')."' >";
         break;
      case "reinit_once":
         echo "<input type='hidden' name='itemtype' value='".$_POST["itemtype"]."'>";
         echo "<input type='hidden' name='start' value='".$_POST["start"]."'>";
         echo "<input type='hidden' name='manufacturers_id' value='".$_POST["manufacturers_id"]."'>";
         echo "<input type='hidden' name='imported' value='".$_POST["imported"]."'>";
         echo "&nbsp;<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value='"._sx('button', 'Post')."' >";
         break;

   }
}

?>