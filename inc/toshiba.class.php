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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginManufacturersimportsToshiba
 */
class PluginManufacturersimportsToshiba extends PluginManufacturersimportsManufacturer {

   /**
    * @see PluginManufacturersimportsManufacturer::showDocTitle()
    */
   function showDocTitle($output_type,$header_num) {
      return Search::showHeaderItem($output_type,__('File'),$header_num);
   }

   function getSearchField() {
      return ">Days<";
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getSupplierInfo()
    */
   function getSupplierInfo($compSerial=null,$otherSerial=null, $key=null, $supplierUrl=null) {
      $info["name"]         = PluginManufacturersimportsConfig::TOSHIBA;
      $info["supplier_url"] = "http://aps2.toshiba-tro.de/unit-details-php/unitdetails.aspx?";
      $info["url"]          = $supplierUrl.
                              "SerialNumber=".$compSerial.
                              "&openbox=warranty1";
      return $info;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getBuyDate()
    */
   function getBuyDate($contents) {
      $days           = substr($contents,118,3);
      $days           = trim($days);
      $myDate         = "0000-00-00";
      $ExpirationDate = self::getExpirationDate($contents);
      if ($ExpirationDate != "0000-00-00") {
         list($year, $month, $day) = explode('-', $ExpirationDate);
         //Drop days of warranty
         $myDate = date("Y-m-d",
                        mktime(0, 0, 0, $month, $day-$days,  $year));
      }
      return $myDate;
   }
   
   /**
    * @see PluginManufacturersimportsManufacturer::getStartDate()
    */
   function getStartDate($contents) {

      return self::getBuyDate($contents);
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getExpirationDate()
    */
   function getExpirationDate($contents) {
      $field     = "Expiration Date";
      $searchfin = stristr($contents, $field);
      $myEndDate = substr($searchfin,138,10);
      $myEndDate = trim($myEndDate);
      $myEndDate = PluginManufacturersimportsPostImport::checkDate($myEndDate);
      return $myEndDate;
   }
}