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

class PluginManufacturersimportsFujitsu extends PluginManufacturersimportsManufacturer {

   function showDocTitle($output_type, $header_num) {
      return Search::showHeaderItem($output_type, __('File'), $header_num);
   }

   function showWarrantyItem($ID, $supplierWarranty) {
      echo "<td>";
      Dropdown::showInteger("to_warranty_duration".
                                  $ID, $supplierWarranty, 
                                  0, 120, 1, array(-1 => __('Lifelong')));
      echo "</td>";
   }

   function getSearchField() {
      return "Service Start Date";
   }

   function getSupplierInfo($compSerial=null,$otherSerial=null) {
      $info["name"]         = PluginManufacturersimportsConfig::FUJITSU;
      $info["supplier_url"] = "http://sali.uk.ts.fujitsu.com/ServiceEntitlement/service.asp?command=search&";
      $info["url"]          = $info["supplier_url"]."snr=".$compSerial;
      return $info;
   }
   
   function getBuyDate($contents) {
      //TODO : translate variables in english
      $maDate = substr($contents,60,10);
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
      //TODO : translate variables in english
      //$field_fin = "year On-Site Service";
      preg_match('#>([0-9]+) year[s]?#', $contents, $matches);

      if (isset($matches[1])) {
         $duree = $matches[1];
      } else {
         return '';
      }

      preg_match('#>([0-9]{2})/([0-9]{2})/([0-9]{4})#', $contents, $matches);

      if (count($matches) == 4) {
         list($date, $jour, $mois, $annee) = $matches; 
      } else {
         return '';
      }

      $maDateFin = ($annee+$duree)."-".$mois."-".$jour;
      return $maDateFin;
   }
}

?>