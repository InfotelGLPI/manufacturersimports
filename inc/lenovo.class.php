<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2009-2022 by the Manufacturersimports Development Team.

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
 * Class PluginManufacturersimportsLenovo
 */
class PluginManufacturersimportsLenovo extends PluginManufacturersimportsManufacturer {

   /**
    * @see PluginManufacturersimportsManufacturer::showCheckbox()
    */
   function showCheckbox($ID, $sel, $otherSerial = false) {
      $name = "item[" . $ID . "]";
      return Html::getCheckbox(["name" => $name, "value" => 1, "selected" => $sel]);

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
   function showItem($output_type, $item_num, $row_num, $otherSerial = false) {
      return false;
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
      $info["supplier_url"] = "https://SupportAPI.lenovo.com/v2.5/Warranty";
      //      $info["url"]          = $supplierUrl . $compSerial."?machineType=&btnSubmit";
      $info["url"]          = $supplierUrl . "?Serial=".$compSerial;
      $info["url_web"]      = "https://pcsupport.lenovo.com/products/$compSerial/warranty";
      return $info;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getBuyDate()
    */
   function getBuyDate($contents) {

      $contents = json_decode($contents, true);

      if (isset($contents['Purchased'])) {

         if(strpos($contents['Purchased'], '0001-01-01') !== false){
            if(strpos($contents['Shipped'], '0001-01-01') !== false) {
               if(isset($contents['Warranty']) && !empty($contents['Warranty'])){
                  $minStart = 0;
                  $start = 0;
                  $n = 0;
                  foreach ($contents['Warranty'] as $id => $warranty){
                     $myDate= trim($warranty['start']);
                     $dateStart = strtotime($myDate);
                     if($n === 0){
                        $minStart = $dateStart;
                        $myDate = strtotime(trim($warranty['Start']));
                     }
                     if($dateStart > $minStart){
                        $minStart = $dateStart;
                        $myDate = strtotime(trim($warranty['Start']));
                     }
                     $n++;
                  }
               }
            }else{
               $myDate = trim($contents['Shipped']);
            }
         }else{
            $myDate = trim($contents['Purchased']);
         }
         //         $myDate = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
         $myDate = date("Y-m-d", strtotime($myDate));


         return PluginManufacturersimportsPostImport::checkDate($myDate);
      }
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getStartDate()
    */
   function getStartDate($contents) {
      //TODO change to have good start date with new json
      $contents = json_decode($contents, true);
      if(isset($contents['Warranty']) && !empty($contents['Warranty'])){
         $maxEnd = 0;
         $start = 0;
         foreach ($contents['Warranty'] as $id => $warranty){
            $myDate = trim($warranty['End']);
            $dateEnd = strtotime($myDate);
            if($dateEnd > $maxEnd){
               $maxEnd = $dateEnd;
               $start = strtotime(trim($warranty['Start']));
            }
         }

      }

      if(isset($start)) {
         $myDate = date("Y-m-d", $start);

         return PluginManufacturersimportsPostImport::checkDate($myDate);
      }

   }

   /**
    * @see PluginManufacturersimportsManufacturer::getExpirationDate()
    */
   function getExpirationDate($contents) {
      $contents = json_decode($contents, true);
      //TODO change to have good expiration date with new json
      if(isset($contents['Warranty']) && !empty($contents['Warranty'])){
         $maxEnd = 0;

         foreach ($contents['Warranty'] as $id => $warranty){
            $myDate = trim($warranty['End']);
            $dateEnd = strtotime($myDate);
            if($dateEnd > $maxEnd){
               $maxEnd = $dateEnd;
            }
         }

      }

      if(isset($maxEnd)) {
         $myDate = date("Y-m-d", $maxEnd);

         return PluginManufacturersimportsPostImport::checkDate($myDate);
      }
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getWarrantyInfo()
    */
   function getWarrantyInfo($contents) {
      $contents = json_decode($contents, true);

      //TODO change to have good information with new json
      $warranty_info = false;
      if(isset($contents['Warranty']) && !empty($contents['Warranty'])){
         $maxEnd = 0;

         foreach ($contents['Warranty'] as $id => $warranty){
            $myDate = trim($warranty['End']);
            $dateEnd = strtotime($myDate);
            if($dateEnd > $maxEnd){
               $maxEnd = $dateEnd;
               if(isset($warranty["Description"])){
                  $warranty_info = $warranty["Description"];
               }else{
                  $warranty_info = $warranty["Type"]." - ".$warranty["Name"];
               }
            }
         }

      }
      if (strlen($warranty_info) > 255) {
         $warranty_info = substr($warranty_info, 0, 254);
      }
      return $warranty_info;
   }
}
