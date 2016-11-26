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

class PluginManufacturersimportsWORTMANN_AG extends PluginManufacturersimportsManufacturer {

   function showDocTitle($output_type, $header_num) {
      return false;
   }

   function getSearchField() {
      return "search";
   }

   function getSupplierInfo($compSerial=null,$otherSerial=null, $key=null, $supplierUrl=null) {
      $info["name"]         = PluginManufacturersimportsConfig::WORTMANN_AG;
      $info["supplier_url"] = "https://www.wortmann.de/fr-fr/profile/snsearch.aspx?SN=";
      $info["url"]          = $supplierUrl.$compSerial;
      return $info;
   }
   
   function getBuyDate($contents) {
      $field     = "but de la garantie";
      $searchstart = stristr($contents, $field);
      $maDate = substr($searchstart,27,10);

      $maDate = trim($maDate);
      $maDate = str_replace('/','-',$maDate);

      $maDate = PluginManufacturersimportsPostImport::checkDate($maDate, true);

      if ($maDate != "0000-00-00") {
         list($jour, $mois, $annee) = explode('-', $maDate);
         $maDate = date("Y-m-d", mktime(0, 0, 0, $mois, $jour, $annee));
      }
      
      return $maDate;
   }
   
   function getExpirationDate($contents) {
      $field     = "Fin de garantie";
      $searchstart = stristr($contents, $field);
      $maDate = substr($searchstart,24,10);

      $maDate = trim($maDate);
      $maDate = str_replace('/','-',$maDate);

      $maDate = PluginManufacturersimportsPostImport::checkDate($maDate, true);
      
      if ($maDate != "0000-00-00") {
         list($jour, $mois, $annee) = explode('-', $maDate);
         $maDate = date("Y-m-d", mktime(0, 0, 0, $mois, $jour, $annee));
      }
      return $maDate;
   }
}

?>
