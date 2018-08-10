<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2003-2011 by the Manufacturersimports Development Team.

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
 * Class PluginManufacturersimportsLenovo
 */
class PluginManufacturersimportsLenovo extends PluginManufacturersimportsManufacturer {

   /**
    * @see PluginManufacturersimportsManufacturer::showCheckbox()
    */
   function showCheckbox($ID, $sel, $otherSerial = false) {

      return "<input type='checkbox' name='item[".$ID."]' value='1' $sel>";

   }

   /**
    * @see PluginManufacturersimportsManufacturer::showItemTitle()
    */
   function showItemTitle($output_type, $header_num) {

      return Search::showHeaderItem($output_type, __('Model number', 'manufacturersimports'), $header_num);

   }

   /**
    * @see PluginManufacturersimportsManufacturer::showDocTitle()
    */
   function showDocTitle($output_type, $header_num) {

      return Search::showHeaderItem($output_type, __('File'), $header_num);

   }

   /**
    * @see PluginManufacturersimportsManufacturer::showItem()
    */
   function showItem($output_type, $otherSerial, $item_num, $row_num) {

      return Search::showItem($output_type, $otherSerial, $item_num, $row_num);
   }

   function getSearchField() {

      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getSupplierInfo()
    */
   function getSupplierInfo($compSerial = null, $otherSerial = null, $key = null, $apisecret = null,
                            $supplierUrl = null) {

      $info["name"]         = PluginManufacturersimportsConfig::LENOVO;
      $info["supplier_url"] = "http://www3.lenovo.com/us/en/warranty/";
      $info["url"]          = $supplierUrl . $compSerial . "?machineType=" . $otherSerial . "&btnSubmit";
      return $info;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getBuyDate()
    */
   function getBuyDate($contents) {
      $buy_date = null;
      $contents = json_decode($contents, true);

      if (isset($contents['startDate'])) {
         $myDate = trim($contents['startDate']);
         list($month, $day, $year) = explode('/', $myDate);
         $myDate = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));

         return PluginManufacturersimportsPostImport::checkDate($myDate);;
      }
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
        $contents = json_decode($contents, true);
  
        $myDate = '';
  
        if (isset($contents['expirationDate']) && strlen($contents['expirationDate']) == 8) {
           $myDate = $contents['expirationDate'];
        }
        if (isset($contents['upgrade_expirationDate']) && strlen($contents['upgrade_expirationDate']) == 8) {
           $myDate = $contents['upgrade_expirationDate'];
        }
        if ($myDate === '') {
  
            return false;
        } else {
           list($month, $day, $year) = explode('/', $myDate);
           $myDate = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
  
           return PluginManufacturersimportsPostImport::checkDate($myDate);
        }
    }
  
     /**
      * @see PluginManufacturersimportsManufacturer::getWarrantyInfo()
      */
    function getWarrantyInfo($contents) {
        $contents = json_decode($contents, true);
        $warranty_info = '';
        if (isset($contents['upgrade_sdfDesc'])) {
            $warranty_info = $contents['upgrade_sdfDesc'];
        }
  
        if (isset($contents['description'])) {
            $warranty_info .= $contents['description'];
        }
  
           return ($warranty_info === '') ? false : $warranty_info;
    }
}
