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
 * Class PluginManufacturersimportsManufacturer
 */
class PluginManufacturersimportsManufacturer extends CommonDBTM {

   /**
    * @param $ID
    * @param $sel
    * @param bool $otherSerial
    * @return string
    */
   function showCheckbox($ID, $sel, $otherSerial = false) {
      return "<input type='checkbox' name='item[".$ID."]' value='1' $sel>";
   }

   /**
    * @param $output_type
    * @param $header_num
    * @return bool
    */
   function showItemTitle($output_type, $header_num) {
      return false;
   }

   /**
    * @param $output_type
    * @param bool $otherSerial
    * @param $item_num
    * @param $row_num
    * @return bool
    */
   function showItem($output_type, $otherSerial = false, $item_num, $row_num) {
      return false;
   }

   /**
    * @param $output_type
    * @param $header_num
    * @return bool
    */
   function showDocTitle($output_type, $header_num) {
      return false;

   }

   /**
    * @param $output_type
    * @param $item_num
    * @param $row_num
    * @param null $doc
    * @return string
    */
   function showDocItem($output_type, $item_num, $row_num, $documents_id = null) {
      $doc = new document();
      if ($doc->getFromDB($documents_id)) {
         return  Search::showItem($output_type,
                                  $doc->getDownloadLink(),
                                  $item_num, $row_num);
      }
      return Search::showItem($output_type, "", $item_num, $row_num);

   }

   /**
    *
    * @param type $ID
    * @param type $supplierWarranty
    */
   function showWarrantyItem($ID, $supplierWarranty) {
      echo "<td>".__('Automatic');
      echo "<input type='hidden' name='to_warranty_duration".$ID."' value='0'>";
      echo "</td>";
   }

   /**
    * Get supplier information with url
    *
    * @param null $compSerial
    * @param null $otherserial
    * @param null $key
    * @param null $supplierUrl
    * @return mixed
    */
   function getSupplierInfo($compSerial = null, $otherSerial = null, $key = null, $apisecret = null,
                            $supplierUrl = null) {

   }

   /**
    * Get buy date of object
    *
    * @param $contents
    */
   function getBuyDate($contents) {

   }

   /**
    * Get start date of warranty
    *
    * @param $contents
    * @return mixed
    */
   function getStartDate($contents) {
      return false;
   }

   /**
    * Get expiration date of warranty
    *
    * @param $contents
    */
   function getExpirationDate($contents) {

   }

   /**
    * Get warranty info
    *
    * @param $contents
    */
   function getWarrantyInfo($contents) {

   }

   /**
    * Summary of getToken
    * @param  $config
    * @return mixed
    */
   static function getToken($config) {
      return false;
   }


   /**
    * Summary of getWarrantyUrl
    * @param  $config 
    * @param  $compSerial 
    * @return string[]|boolean
    */
   static function getWarrantyUrl($config, $compSerial) {
      return false;
   }

}
