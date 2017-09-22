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
   function showCheckbox($ID,$sel,$otherSerial=false) {

      return "<input type='checkbox' name='item[".$ID."]' value='1' $sel>";

   }

   /**
    * @see PluginManufacturersimportsManufacturer::showItemTitle()
    */
   function showItemTitle($output_type,$header_num) {

      return Search::showHeaderItem($output_type,__('Model number', 'manufacturersimports'),$header_num);

   }

   /**
    * @see PluginManufacturersimportsManufacturer::showDocTitle()
    */
   function showDocTitle($output_type,$header_num) {

      return Search::showHeaderItem($output_type,__('File'),$header_num);

   }
   
   /**
    * @see PluginManufacturersimportsManufacturer::showItem()
    */
   function showItem($output_type,$otherSerial,$item_num,$row_num) {

      return Search::showItem($output_type,$otherSerial,$item_num,$row_num);
   }

   function getSearchField() {

      return false;

   }
   
   /**
    * @see PluginManufacturersimportsManufacturer::getSupplierInfo()
    */
   function getSupplierInfo($compSerial=null, $otherserial=null, $key=null, $supplierUrl=null) {

      $info["name"]         = PluginManufacturersimportsConfig::LENOVO;
      $info["supplier_url"] = "http://www3.lenovo.com/us/en/warranty-upgrades?serial-number=";
      $info["url"] = "http://www3.lenovo.com/us/en/warranty/".$compSerial."?machineType=";
      return $info;
   }

   function getWarrantyInfo($contents) {
      $warranty_info = "";
      $temp_date = json_decode($contents, true);
	  if(isset($temp_date['description'])){
		  $warranty_info = $temp_date['description'];
          return $warranty_info;
	  }
      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getBuyDate()
    */
   function getBuyDate($contents) {
      $buy_date = NULL;

      $temp_date = json_decode($contents, true);
	  if(isset($temp_date['startDate'])){
		  $temp_date = DateTime::createFromFormat('m/d/y',$temp_date['startDate']);
          $buy_date = $temp_date->format('Y-m-d');
          return PluginManufacturersimportsPostImport::checkDate($buy_date);
	  }

      return false;
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
      $expiration_date = NULL;

      $temp_date = json_decode($contents, true);
	  if(isset($temp_date['expirationDate'])){
		  $temp_date = DateTime::createFromFormat('m/d/y',$temp_date['expirationDate']);
          $expiration_date = $temp_date->format('Y-m-d');
          return PluginManufacturersimportsPostImport::checkDate($expiration_date);
	  }

      return false;
   }
}
