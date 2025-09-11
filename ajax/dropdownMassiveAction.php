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

Session::checkLoginUser();

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (isset($_POST["action"])||isset($_POST["id"])) {
   echo Html::hidden('action', ['value' => $_POST["action"]]);
   echo Html::hidden('id', ['value' => $_POST["id"]]);
   switch ($_POST["action"]) {

      case "reinit_once":
      case "import":
         echo Html::hidden('itemtype', ['value' => $_POST["itemtype"]]);
         echo Html::hidden('start', ['value' => $_POST["start"]]);
         echo Html::hidden('manufacturers_id', ['value' => $_POST["manufacturers_id"]]);
         echo Html::hidden('imported', ['value' => $_POST["imported"]]);
         echo Html::submit(_sx('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
         break;

   }
}
