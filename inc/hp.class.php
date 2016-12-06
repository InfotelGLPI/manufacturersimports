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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginManufacturersimportsHP extends PluginManufacturersimportsManufacturer {

   function showDocTitle($output_type,$header_num) {
      return false;
   }
   
   function getSearchField() {
      return false;
   }

   function getSupplierInfo($compSerial=null,$otherSerial=null, $key=null, $supplierUrl=null) { 
      $info["name"]         = PluginManufacturersimportsConfig::HP;
      $info["supplier_url"] = "https://hpscm-pro.glb.itcs.hp.com/mobileweb/hpsupport.asmx/GetEntitlementDetails?";
      $info["url"]         = $info["supplier_url"]."productNo=".$otherSerial."&serialNo=".$compSerial."&countryCode=null";
      return $info;
   }

   function getBuyDate($contents) {
      $info = json_decode(simplexml_load_string($contents), TRUE);
      if( isset( $info[0]['startDate'] ) ) {
          return $info[0]['startDate'] ;
      }

      return false;
   }

   function getExpirationDate($contents) {
      $info = json_decode(simplexml_load_string($contents), TRUE);
      if( isset( $info[0]['endDate'] ) ) {
          return $info[0]['endDate'] ;
      }

      return false;
   }

   function getWarrantyInfo($contents) {
      $info = json_decode(simplexml_load_string($contents), TRUE);
      if( isset( $info[0]['serviceLevel'] ) ) {
          return $info[0]['serviceLevel'] ;
      }

      return false;
   }
}

?>
