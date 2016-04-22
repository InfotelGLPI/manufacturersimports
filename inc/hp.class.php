<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2003-2016 by the Manufacturersimports Development Team.

 https://github.com/InfotelGLPI
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
   
   function getSupplierInfo($compSerial=null,$otherSerial=null) {
      $info["name"]         = PluginManufacturersimportsConfig::HP;
      $info["supplier_url"] = "http://h20000.www2.hp.com/bizsupport/TechSupport/WarrantyResults.jsp?";
      $info["url"]         = $info["supplier_url"]."nickname=&sn=".$compSerial."&pn=".$otherSerial."&country=FR&lang=en&cc=us&find=Display+Warranty+Information+%BB&";
      return $info;
   }

   function getBuyDate($contents) {
      //TODO translate variables in english
      $matchesarray = array();
      preg_match_all("/(\d\d [A-Z][a-z][a-z] \d{4})/", $contents, $matchesarray);

      $datetimestamp = date('U');
      for ($i = 0; $i < ((count($matchesarray[0]) /2)); $i++) {
         $maDate = $matchesarray[0][($i * 2)];
         $maDate = str_replace(' ','-',$maDate);
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
         list($jour, $mois, $annee) = explode('-', $maDate);
         $maDate = date("U",mktime(0, 0, 0, $mois, $jour, $annee));
         if ($maDate < $datetimestamp) {
            $datetimestamp = $maDate;
         }
      }
      $maDate = date("Y-m-d", $datetimestamp);
      return $maDate;
   }

   function getExpirationDate($contents) {
      //TODO translate variables in english
      $field_fin    = "End Date";
      $matchesarray = array();
      $searchfin    = stristr($contents, $field_fin);
      preg_match_all("/(\d\d [A-Z][a-z][a-z] \d{4})/", $searchfin, $matchesarray);
      $maDateFin = $matchesarray[0][1];
      $maDateFin = str_replace(' ','-',$maDateFin);
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

      list($jour, $mois, $annee) = explode('-', $maDateFin);
      $maDateFin = $annee."-".$mois."-".$jour;
      return $maDateFin;
   }
}

?>