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
 * Class PluginManufacturersimportsHP
 */
class PluginManufacturersimportsHP extends PluginManufacturersimportsManufacturer {

   /**
    * @see PluginManufacturersimportsManufacturer::showDocTitle()
    */
   function showDocTitle($output_type,$header_num) {
      return Search::showHeaderItem($output_type,__('File'),$header_num);
   }
   
   function getSearchField() {
      return "Start date";
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getSupplierInfo()
    */
   function getSupplierInfo($compSerial=null,$otherSerial=null, $key=null, $supplierUrl=null) {
      $info["name"]         = PluginManufacturersimportsConfig::HP;
         
      if(empty($otherSerial)){
         $info["url"] = $supplierUrl."?rows[0].item.countryCode=FR&rows[0].item.serialNumber=$compSerial&submitButton=Envoyer";
      } else {
         $info["url"] = $supplierUrl."?rows[0].item.countryCode=FR&rows[0].item.serialNumber=$compSerial&submitButton=Envoyer&rows[0].item.productNumber=$otherSerial";
      }
      return $info;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getBuyDate()
    */
   function getBuyDate($contents) {
      $matchesarray = array();
      preg_match_all("/([A-Z][a-z][a-z] \d\d?, \d{4})/", $contents, $matchesarray);
      
      $datetimestamp = date('U');

      if(isset($matchesarray[0][0])){
         $myDate = $matchesarray[0][0];
         $myDate = str_replace(' ','-',$myDate);
         $myDate = str_replace(',','',$myDate);
         $myDate = str_replace('Jan','01',$myDate);
         $myDate = str_replace('Feb','02',$myDate);
         $myDate = str_replace('Mar','03',$myDate);
         $myDate = str_replace('Apr','04',$myDate);
         $myDate = str_replace('May','05',$myDate);
         $myDate = str_replace('Jun','06',$myDate);
         $myDate = str_replace('Jul','07',$myDate);
         $myDate = str_replace('Aug','08',$myDate);
         $myDate = str_replace('Sep','09',$myDate);
         $myDate = str_replace('Oct','10',$myDate);
         $myDate = str_replace('Nov','11',$myDate);
         $myDate = str_replace('Dec','12',$myDate);

         list($month, $day, $year) = explode('-', $myDate);
         $myDate = date("U",mktime(0, 0, 0, $month, $day, $year));
         if ($myDate < $datetimestamp) {
            $datetimestamp = $myDate;
         }
         
         $myDate = date("Y-m-d", $datetimestamp);

         return $myDate;
      } else {
         return false;
      }
   }
   
   /**
    * @see PluginManufacturersimportsManufacturer::getStartDate()
    */
   function getStartDate($contents) {
      
      $matchesarray = array();
      preg_match_all("/([A-Z][a-z][a-z] \d\d?, \d{4})/", $contents, $matchesarray);
      
      $datetimestamp = date('U');

      $index = count($matchesarray[0])-2;
      if(isset($matchesarray[0][$index])){
         $myDate = $matchesarray[0][$index];
         $myDate = str_replace(' ','-',$myDate);
         $myDate = str_replace(',','',$myDate);
         $myDate = str_replace('Jan','01',$myDate);
         $myDate = str_replace('Feb','02',$myDate);
         $myDate = str_replace('Mar','03',$myDate);
         $myDate = str_replace('Apr','04',$myDate);
         $myDate = str_replace('May','05',$myDate);
         $myDate = str_replace('Jun','06',$myDate);
         $myDate = str_replace('Jul','07',$myDate);
         $myDate = str_replace('Aug','08',$myDate);
         $myDate = str_replace('Sep','09',$myDate);
         $myDate = str_replace('Oct','10',$myDate);
         $myDate = str_replace('Nov','11',$myDate);
         $myDate = str_replace('Dec','12',$myDate);

         list($month, $day, $year) = explode('-', $myDate);
         $myDate = date("U",mktime(0, 0, 0, $month, $day, $year));
         if ($myDate < $datetimestamp) {
            $datetimestamp = $myDate;
         }
         $myDate = date("Y-m-d", $datetimestamp);

         return $myDate;
      } else {
         return false;
      }

   }

   /**
    * @see PluginManufacturersimportsManufacturer::getExpirationDate()
    */
   function getExpirationDate($contents) {
      
      preg_match_all("/([A-Z][a-z][a-z] \d\d?, \d{4})/", $contents, $matchesarray);
      
      if(isset($matchesarray[0])){
         $date = date("Y-m-d", strtotime(0));
         
         foreach ($matchesarray[0] as $myDate) {
            $myEndDate = str_replace(' ','-',$myDate);
            $myEndDate = str_replace(',','',$myEndDate);
            $myEndDate = str_replace('Jan','01',$myEndDate);
            $myEndDate = str_replace('Feb','02',$myEndDate);
            $myEndDate = str_replace('Mar','03',$myEndDate);
            $myEndDate = str_replace('Apr','04',$myEndDate);
            $myEndDate = str_replace('May','05',$myEndDate);
            $myEndDate = str_replace('Jun','06',$myEndDate);
            $myEndDate = str_replace('Jul','07',$myEndDate);
            $myEndDate = str_replace('Aug','08',$myEndDate);
            $myEndDate = str_replace('Sep','09',$myEndDate);
            $myEndDate = str_replace('Oct','10',$myEndDate);
            $myEndDate = str_replace('Nov','11',$myEndDate);
            $myEndDate = str_replace('Dec','12',$myEndDate);
            
            list($month, $day, $year) = explode('-', $myEndDate);
            $myEndDate = $year."-".$month."-".$day;
            
            if(strtotime($myEndDate) > strtotime($date)){
               $date = $myEndDate;
            }
         }
         
         return $date;
      } else {
         return false;
      }
   }
}