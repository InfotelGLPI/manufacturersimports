<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2003-2011 by the Manufacturersimports Development Team.

 https://forge.indepnet.net/projects/manufacturersimports
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

class PluginManufacturersimportsLenovo extends PluginManufacturersimportsManufacturer {

   function showCheckbox($ID,$sel,$otherSerial=false) {

      return "<input type='checkbox' name='item[".$ID."]' value='1' $sel>";

   }

   function showItemTitle($output_type,$header_num) {

      return Search::showHeaderItem($output_type,__('Model number', 'manufacturersimports'),$header_num);

   }

   function showDocTitle($output_type,$header_num) {

      return Search::showHeaderItem($output_type,__('File'),$header_num);

   }

   function showItem($output_type,$otherSerial,$item_num,$row_num) {

      return Search::showItem($output_type,$otherSerial,$item_num,$row_num);
   }

   function getSearchField() {

      $field = "WarrantyStatusInfoWrap";

      return $field;
   }

   function getSupplierInfo($compSerial=null, $otherserial=null) {

      $info["name"]         = PluginManufacturersimportsConfig::LENOVO;
      $info["supplier_url"] = "http://shop.lenovo.com/SEUILibrary/controller/e/web/LenovoPortal/en_US/config.workflow:VerifyWarranty?country-code=897&";
      $info["url"] = $info["supplier_url"]."serial-number=".$compSerial."&machine-type=".$otherserial."&btnSubmit";
      return $info;
   }
   
   
   function getBuyDate($contents) {
      $buy_date = NULL;

      //cut html 
      $contents = substr($contents, 0, strpos($contents, "remindMeWrap"));

      //find dates in html content
      preg_match_all("/([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{1,4})/s", $contents, $dates);
      
      if (isset($dates[0])) {
          // get first of found dates
         $buy_date_raw = array_shift($dates[0]);

         //extract date parts
         preg_match("/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{1,4})/", $buy_date_raw, $buy_date_parts);
         if (count($buy_date_parts) === 4) {

            // forge final date
            $year  = $buy_date_parts[3];
            $month = $buy_date_parts[1];
            $day   = $buy_date_parts[2];
            $buy_date = "2".str_pad($year, 3, "0", STR_PAD_LEFT)."-".
                        str_pad($month, 2, "0", STR_PAD_LEFT)."-".
                        str_pad($day, 2, "0", STR_PAD_LEFT).
                        " 00:00:00";
         }
      }

      return PluginManufacturersimportsPostImport::checkDate($buy_date);
   }

   function getExpirationDate($contents) {
      $expiration_date = NULL;

      //cut html 
      $contents = substr($contents, 0, strpos($contents, "remindMeWrap"));

      //find dates in html content
      preg_match_all("/([0-9]{1,2}\/[0-9]{1,2}\/[0-9]{1,4})/s", $contents, $dates);
      if (isset($dates[1])) {
         // get last of found dates
         $expiration_date_raw = array_pop($dates[0]);

         //extract date parts
         preg_match("/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{1,4})/", $expiration_date_raw, $expiration_date_parts);
         if (count($expiration_date_parts) === 4) {

            // forge final date
            $year  = $expiration_date_parts[3];
            $month = $expiration_date_parts[1];
            $day   = $expiration_date_parts[2];
            $expiration_date = "2".str_pad($year, 3, "0", STR_PAD_LEFT)."-".
                               str_pad($month, 2, "0", STR_PAD_LEFT)."-".
                               str_pad($day, 2, "0", STR_PAD_LEFT).
                               " 00:00:00";
         }
      } 

      return PluginManufacturersimportsPostImport::checkDate($expiration_date);
   }
}

?>