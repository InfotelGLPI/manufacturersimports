<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2003-2011 by the Manufacturersimports Development Team.

 https://forge.indepnet.net/projects/manufacturersimports
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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class PluginManufacturersimportsIBM extends CommonDBTM {
   
   function getSupplierInfo($compSerial=null,$otherSerial=null) {
   
      $info["name"]="IBM";
      $info["supplier_url"] = "http://www-304.ibm.com/jct01004c/systems/support/supportsite.wss/warranty?";
      $info["url"] = $info["supplier_url"]."type=".$otherSerial."&serial=".$compSerial."&brandind=5000008&Submit=Submit&action=warranty";
      return $info;
   }
   
}

?>