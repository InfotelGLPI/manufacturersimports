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
   function getSupplierInfo($compSerial = null, $otherSerial = null, $key = null, $apisecret = null,
                            $supplierUrl = null) {

      if (!$compSerial) {
         // by default
         $info["name"]            = PluginManufacturersimportsConfig::DELL;
         $info['supplier_url']    = "https://www.dell.com/support/home/product-support/servicetag/";
         $info['token_url']       = "https://apigtwb2c.us.dell.com/auth/oauth/v2/token";
         $info['warranty_url']    = "https://apigtwb2c.us.dell.com/PROD/sbil/eapi/v5/asset-entitlements?servicetags=";
         $info["supplier_key"]    = "123456789";
         $info["supplier_secret"] = "987654321";
         return $info;
      }

      $info["url"] = $supplierUrl. "$compSerial";
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
      $info = json_decode($contents, true);
      // v5
      if (isset($info[0]['shipDate'])) {
         return $info[0]['shipDate'];
      }

      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getStartDate()
    */
   function getStartDate($contents) {
      $info = json_decode($contents, true);
      // v5
       $max_date = false;
       if (isset($info[0]['entitlements'])) {
           foreach ($info[0]['entitlements'] as $d) {
               $date = new \DateTime($d['startDate']);
               if ($max_date == false || $date > $max_date) {
                   $max_date = $date;
               }
           }

           if ($max_date) {
               return $max_date->format('c');
           }
       }

      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getExpirationDate()
    */
   function getExpirationDate($contents) {
      $info = json_decode($contents, true);
      // v5
      // when several dates are available, will take the last one
      $max_date = false;
      if (isset($info[0]['entitlements'])) {
         foreach ($info[0]['entitlements'] as $d) {
            $date = new \DateTime($d['endDate']);
            if ($max_date == false || $date > $max_date) {
               $max_date = $date;
            }
         }

         if ($max_date) {
            return $max_date->format('c');
         }
      }


      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getWarrantyInfo()
    */
   function getWarrantyInfo($contents) {
      $info = json_decode($contents, true);

      Toolbox::logWarning($info);
      // v5
      // when several warranties are available, will take the last one
      $max_date = false;
      $i = false;
      if (isset($info[0]['entitlements'])) {
         foreach ($info[0]['entitlements'] as $k => $d) {
            $date = new \DateTime($d['endDate']);
            if ($max_date == false || $date > $max_date) {
               $max_date = $date;
               $i        = $k;
            }
         }
      }

      if ($max_date && $i) {
         return $info[0]['entitlements'][$i]['serviceLevelDescription'];
      }

      return false;
   }

   /**
    * @param $name
    *
    * @return array
    */
   static function cronInfo($name) {

      switch ($name) {
         case "DataRecoveryDELL" :
            return ['description' => PluginManufacturersimportsModel::getTypeName(1) . " - " . __('Data recovery DELL for computer', 'manufacturersimports')];
      }
      return [];
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

      $cron_status = PluginManufacturersimportsImport::importCron($task, PluginManufacturersimportsConfig::DELL);

      return $cron_status;
   }

   /**
    * Summary of getToken
    * @param  $config
    * @return mixed
    */
   static function getToken($config){
      $token = false;
      // must manage token
      $options = ["url"          => $config->fields["token_url"],
                  "download"     => false,
                  "file"         => false,
                  "post"         => ['client_id' => $config->fields["supplier_key"],
                                     'client_secret' => $config->fields["supplier_secret"],
                                     'grant_type' => 'client_credentials'],
                  "suppliername" => $config->fields["name"]];
      $contents    = PluginManufacturersimportsPostImport::cURLData($options);
      // must extract from $contents the token bearer
      $response = json_decode($contents, true);
      if (isset($response['access_token'])) {
         $token = $response['access_token'];
      }
      return $token;
   }


   /**
    * Summary of getWarrantyUrl
    * @param  $config 
    * @param  $compSerial 
    * @return string[]
    */
   static function getWarrantyUrl($config, $compSerial) {
      return ["url" => $config->fields['warranty_url']. "$compSerial"];
   }
}
