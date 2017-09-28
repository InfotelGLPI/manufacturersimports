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

ini_set("max_execution_time", "0");

/**
 * Class PluginManufacturersimportsDell
 */
class PluginManufacturersimportsDell extends PluginManufacturersimportsManufacturer {

   /**
    * @see PluginManufacturersimportsManufacturer::showCheckbox()
    */
   function showCheckbox($ID, $sel, $otherSerial = false) {
      return "<input type='checkbox' name='item[".$ID."]' value='1' $sel>";
   }

   /**
    * @see PluginManufacturersimportsManufacturer::showItem()
    */
   function showItem($output_type, $otherSerial = false, $item_num, $row_num) {
      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::showItemTitle()
    */
   function showItemTitle($output_type, $header_num) {
      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::showDocTitle()
    */
   function showDocTitle($output_type, $header_num) {
      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::showDocItem()
    */
   function showDocItem($output_type, $item_num, $row_num, $doc = null) {
      return Search::showEndLine($output_type);
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getSupplierInfo()
    */
   function getSupplierInfo($compSerial = null, $otherserial = null, $key=null, $supplierUrl=null) {
      $info["name"]         = PluginManufacturersimportsConfig::DELL;
      // v4
      $info['supplier_url'] = "https://api.dell.com/support/assetinfo/v4/getassetwarranty/" ;
      //$info['supplier_url'] = "https://sandbox.api.dell.com/support/assetinfo/v4/getassetwarranty/" ;
      // v4
      $info["url"] = $supplierUrl . "$compSerial?apikey=" . $key;
      return $info;
   }

   /**
    * @return bool
    */
   function getSearchField() {
      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getBuyDate()
    */
   function getBuyDate($contents) {
      $info = json_decode($contents, TRUE);
      // v4
      if( isset( $info['AssetWarrantyResponse'][0]['AssetHeaderData'][0]['ShipDate'] ) ) {
         return $info['AssetWarrantyResponse'][0]['AssetHeaderData'][0]['ShipDate'];
         
      } elseif(isset($info['AssetWarrantyResponse'][0]['AssetEntitlementData'])) {
       $nb =  count($info['AssetWarrantyResponse'][0]['AssetEntitlementData'])-1;
         if (isset($info['AssetWarrantyResponse'][0]['AssetEntitlementData'][$nb]['StartDate'])) {
            return $info['AssetWarrantyResponse'][0]['AssetEntitlementData'][$nb]['StartDate'];

         } else if (isset($info['AssetWarrantyResponse'][0]['AssetEntitlementData'][0]['StartDate'])) {
            return $info['AssetWarrantyResponse'][0]['AssetEntitlementData'][0]['StartDate'];
         }
      }

      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getStartDate()
    */
   function getStartDate($contents) {
      $info = json_decode($contents, TRUE);
      // v4
      if (isset($info['AssetWarrantyResponse'][0]['AssetEntitlementData'])) {
         if (isset($info['AssetWarrantyResponse'][0]['AssetEntitlementData'][0]['StartDate'])) {
            return $info['AssetWarrantyResponse'][0]['AssetEntitlementData'][0]['StartDate'];
         }
      }

      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getExpirationDate()
    */
   function getExpirationDate($contents) {
      $info = json_decode($contents, TRUE);
      // v4
      if( isset( $info['AssetWarrantyResponse'][0]['AssetEntitlementData'] ) ) {
          if(isset($info['AssetWarrantyResponse'][0]['AssetEntitlementData'][0])) {
             return $info['AssetWarrantyResponse'][0]['AssetEntitlementData'][0]["EndDate"];
          } else if (isset($info['AssetWarrantyResponse'][0]['AssetEntitlementData']["EndDate"])) {
             return $info['AssetWarrantyResponse'][0]['AssetEntitlementData']["EndDate"];
          }
       }
      
      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getWarrantyInfo()
    */
   function getWarrantyInfo($contents) {
      $info = json_decode($contents, TRUE);
      if( isset( $info['AssetWarrantyResponse'][0]['AssetEntitlementData'] ) ) {
         if(isset($info['AssetWarrantyResponse'][0]['AssetEntitlementData'][0])) {
            return $info['AssetWarrantyResponse'][0]['AssetEntitlementData'][0]["ServiceLevelDescription"];
         } else if (isset($info['AssetWarrantyResponse'][0]['AssetEntitlementData']["ServiceLevelDescription"])) {
            return $info['AssetWarrantyResponse'][0]['AssetEntitlementData']["ServiceLevelDescription"];
         }
      }

      return false;
   }

   /**
    * @param $name
    *
    * @return array
    */
   static function cronInfo($name)
   {

      switch ($name) {
         case "DataRecoveryDELL" :
            return array('description' => PluginManufacturersimportsModel::getTypeName(1) . " - " . __('Data recovery DELL for computer', 'manufacturersimports'));
      }
      return array();
   }


   /**
    * Run for data recovery DELL
    *
    * @param $task : object of crontask
    *
    * @return integer : 0 (nothing to do)
    *                   >0 (endded)
    **/
  static function cronDataRecoveryDELL($task) {

      $cron_status = 0;

      $cron_status = PluginManufacturersimportsImport::importCron($task, PluginManufacturersimportsConfig::DELL);

      return $cron_status;
   }

}