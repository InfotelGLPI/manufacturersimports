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

class PluginManufacturersimportsToshiba extends PluginManufacturersimportsManufacturer {

   function showDocTitle($output_type,$header_num) {
      return Search::showHeaderItem($output_type,__('File'),$header_num);
   }

   function getSearchField() {
      return ">Days<";
   }

   function getSupplierInfo($compSerial=null,$otherSerial=null) {
      $info["name"]         = PluginManufacturersimportsConfig::TOSHIBA;
      $info["supplier_url"] = "http://aps2.toshiba-tro.de/unit-details-php/unitdetails.aspx?";
      $info["url"]          = $info["supplier_url"].
                              "SerialNumber=".$compSerial.
                              "&openbox=warranty1";
      return $info;
   }

   function getBuyDate($contents) {
      $days           = substr($contents,118,3);
      $days           = trim($days);
      $maDate         = "0000-00-00";
      $ExpirationDate = self::getExpirationDate($contents);
      //TODO translate variables in english
      if ($ExpirationDate != "0000-00-00") {
         list($annee, $mois, $jour) = explode('-', $ExpirationDate);
         //Drop days of warranty
         $maDate = date("Y-m-d",
                        mktime(0, 0, 0, $mois, $jour-$days,  $annee));
      }
      return $maDate;
   }

   function getExpirationDate($contents) {
      //TODO translate variables in english
      $field     = "Expiration Date";
      $searchfin = stristr($contents, $field);
      $maDateFin = substr($searchfin,138,10);
      $maDateFin = trim($maDateFin);
      $maDateFin = PluginManufacturersimportsPostImport::checkDate($maDateFin);
      return $maDateFin;
   }
}

?>