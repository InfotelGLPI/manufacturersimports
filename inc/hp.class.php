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

class PluginManufacturersimportsHP extends PluginManufacturersimportsManufacturer {

   function showDocTitle($output_type,$header_num) {
      return Search::showHeaderItem($output_type,__('File'),$header_num);
   }
   
   function getSearchField() {
      return "Start date";
   }
   
   function getSupplierInfo($compSerial=null,$otherSerial=null, $key=null, $supplierUrl=null) {
      $info["name"]         = PluginManufacturersimportsConfig::HP;
         
      if(empty($otherSerial)){
         $info["url"] = $supplierUrl."?rows[0].item.countryCode=FR&rows[0].item.serialNumber=$compSerial&submitButton=Envoyer";
      } else {
         $info["url"] = $supplierUrl."?rows[0].item.countryCode=FR&rows[0].item.serialNumber=$compSerial&submitButton=Envoyer&rows[0].item.productNumber=$otherSerial";
      }
      return $info;
   }

   function getBuyDate($contents) {
      //TODO translate variables in english
      $matchesarray = array();
      preg_match_all("/([A-Z][a-z][a-z] \d\d?, \d{4})/", $contents, $matchesarray);
      
      $datetimestamp = date('U');

      if(isset($matchesarray[0][0])){
         $maDate = $matchesarray[0][0];
         $maDate = str_replace(' ','-',$maDate);
         $maDate = str_replace(',','',$maDate);
         $maDate = str_replace('Jan','01',$maDate);
         $maDate = str_replace('Feb','02',$maDate);
         $maDate = str_replace('Mar','03',$maDate);
         $maDate = str_replace('Apr','04',$maDate);
         $maDate = str_replace('May','05',$maDate);
         $maDate = str_replace('Jun','06',$maDate);
         $maDate = str_replace('Jul','07',$maDate);
         $maDate = str_replace('Aug','08',$maDate);
         $maDate = str_replace('Sep','09',$maDate);
         $maDate = str_replace('Oct','10',$maDate);
         $maDate = str_replace('Nov','11',$maDate);
         $maDate = str_replace('Dec','12',$maDate);

         list($mois, $jour, $annee) = explode('-', $maDate);
         $maDate = date("U",mktime(0, 0, 0, $mois, $jour, $annee));
         if ($maDate < $datetimestamp) {
            $datetimestamp = $maDate;
         }
         
         $maDate = date("Y-m-d", $datetimestamp);

         return $maDate;
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
         $maDate = $matchesarray[0][$index];
         $maDate = str_replace(' ','-',$maDate);
         $maDate = str_replace(',','',$maDate);
         $maDate = str_replace('Jan','01',$maDate);
         $maDate = str_replace('Feb','02',$maDate);
         $maDate = str_replace('Mar','03',$maDate);
         $maDate = str_replace('Apr','04',$maDate);
         $maDate = str_replace('May','05',$maDate);
         $maDate = str_replace('Jun','06',$maDate);
         $maDate = str_replace('Jul','07',$maDate);
         $maDate = str_replace('Aug','08',$maDate);
         $maDate = str_replace('Sep','09',$maDate);
         $maDate = str_replace('Oct','10',$maDate);
         $maDate = str_replace('Nov','11',$maDate);
         $maDate = str_replace('Dec','12',$maDate);

         list($mois, $jour, $annee) = explode('-', $maDate);
         $maDate = date("U",mktime(0, 0, 0, $mois, $jour, $annee));
         if ($maDate < $datetimestamp) {
            $datetimestamp = $maDate;
         }
         $maDate = date("Y-m-d", $datetimestamp);

         return $maDate;
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
         
         foreach ($matchesarray[0] as $maDate) {
            $maDateFin = str_replace(' ','-',$maDate);
            $maDateFin = str_replace(',','',$maDateFin);
            $maDateFin = str_replace('Jan','01',$maDateFin);
            $maDateFin = str_replace('Feb','02',$maDateFin);
            $maDateFin = str_replace('Mar','03',$maDateFin);
            $maDateFin = str_replace('Apr','04',$maDateFin);
            $maDateFin = str_replace('May','05',$maDateFin);
            $maDateFin = str_replace('Jun','06',$maDateFin);
            $maDateFin = str_replace('Jul','07',$maDateFin);
            $maDateFin = str_replace('Aug','08',$maDateFin);
            $maDateFin = str_replace('Sep','09',$maDateFin);
            $maDateFin = str_replace('Oct','10',$maDateFin);
            $maDateFin = str_replace('Nov','11',$maDateFin);
            $maDateFin = str_replace('Dec','12',$maDateFin);
            
            list($mois, $jour, $annee) = explode('-', $maDateFin);
            $maDateFin = $annee."-".$mois."-".$jour;
            
            if(strtotime($maDateFin) > strtotime($date)){
               $date = $maDateFin;
            }
         }
         
         return $date;
      } else {
         return false;
      }
   }
}

?>