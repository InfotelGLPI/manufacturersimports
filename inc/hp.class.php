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
      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getSupplierInfo()
    */
   function getSupplierInfo($compSerial = null, $otherSerial = null, $key = null, $apisecret = null,
                            $supplierUrl = null) {
      $info["name"]         = PluginManufacturersimportsConfig::HP;
      $info["supplier_url"] = "https://css.api.hp.com/oauth/v1/token";

      $info["url"] = $supplierUrl;
      if (!empty($otherSerial)) {
         $info['post']['pn'] = $otherSerial;
      }
      $info['url_warranty'] = 'https://css.api.hp.com/productWarranty/v1/queries';

      $info['post'] = ['apiKey'    => $key,
                       'apiSecret' => $apisecret,
                       'grantType' => 'client_credentials',
                       'scope'     => 'warranty',
                       'sn'        => rtrim($compSerial),
      ];
      return $info;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getBuyDate()
    */
   function getBuyDate($contents) {
      $contents = json_decode($contents, true);
      $contents = reset($contents);
      if (isset($contents['startDate'])) {
         return $contents['startDate'];
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

      $contents = json_decode($contents, true);
      $contents = reset($contents);
      if (isset($contents['endDate'])) {
         return $contents['endDate'];
      }
      return false;
   }

   /**
    * @see PluginManufacturersimportsManufacturer::getWarrantyInfo()
    */
   function getWarrantyInfo($contents) {
      $contents = json_decode($contents, true);
      $contents = reset($contents);

      $warrantyInfo = "";
      if (isset($contents['status'])) {
         $warrantyInfo .= $contents['status'] . " ";
      }
      if (isset($contents['type'])) {
         $warrantyInfo .= $contents['type'] . " ";
      }
      return $warrantyInfo;
   }
}
