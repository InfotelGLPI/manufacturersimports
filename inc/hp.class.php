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
   function showDocTitle($output_type, $header_num) {
      return Search::showHeaderItem($output_type, __('File'), $header_num);
   }

   function getSearchField() {
	  return false;	// Do nothing: So, that all the content is returned 
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getSupplierInfo()
    */
   function getSupplierInfo($compSerial = null, $otherSerial = null, $key = null, $supplierUrl = null, $secret = null) {
      $info["name"]          = PluginManufacturersimportsConfig::HP;
      $info["supplier_url"] = "https://css.api.hp.com/oauth/v1/token";
      if (empty($otherSerial)) {
		$info["url"] = $supplierUrl;
      } else {
        $info["url"] = $supplierUrl;
        $info['post']['pn'] = $otherSerial;
	  }

	  $info['post'] = [ 'apiKey' => $key, 
		'apiSecret' => $secret, 
		'grantType' => 'client_credentials', 
		'scope'     => 'warranty',
		'sn'        => $compSerial,
	  ];
	  
      return $info;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getBuyDate()
    */
   function getBuyDate($contents) {
      $exp = explode(',', $contents);
      foreach( $exp as $e => $val) {
         $elt = explode(':', $val);
         if( in_array('"startDate"', $elt)) {		// BuyDate is identical to startDate
            $startDate = str_replace('"','', $elt[1]);
            return $startDate; // BuyDate found
         }
      }
      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getStartDate()
    */
   function getStartDate($contents) {
      $exp = explode(',', $contents);
      foreach( $exp as $e => $val) {
         $elt = explode(':', $val);
         if( in_array('"startDate"', $elt)) {
            $startDate = str_replace('"','', $elt[1]);
            return $startDate; // StartDate found
         }
      }
      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getExpirationDate()
    */
   function getExpirationDate($contents) {
      $exp = explode(',', $contents);
      foreach( $exp as $e => $val) {
         $elt = explode(':', $val);
         if( in_array('"endDate"', $elt)) {
            $endDate = str_replace('"','', $elt[1]);
            return $endDate; // EndDate found
         }
      }
      return false;	  
	}

   /**
    * @see PluginManufacturersimportsManufacturer::getWarrantyInfo()
    */
   function getWarrantyInfo($contents) {
      $exp = explode(',', $contents);
      foreach( $exp as $e => $val) {
         $elt = explode(':', $val);
         if( in_array('"status"', $elt)) {
           $warrantyInfo = str_replace('"','', $elt[1]);
           return $warrantyInfo; // warrantyInfo found
         }
      }
      return false;
   }
}
