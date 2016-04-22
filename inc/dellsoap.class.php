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

ini_set("max_execution_time", "0");

class PluginManufacturersimportsDellSoap extends SoapClient {
   //const ADDR = 'http://xserv.dell.com/services/assetservice.asmx?WSDL';
   const ADDR = 'http://143.166.84.118/services/assetservice.asmx?WSDL';
   const GUID = 'F5EE89B0-5332-11E1-B47D-8E584824019B';

   function __construct($options = array()) {
      global $CFG_GLPI;
      
      if (!isset($options['exceptions'])) {
         $options['exceptions'] = false;
      }
      $options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
      
      if (!empty($CFG_GLPI["proxy_name"])) {
         $options['proxy_host'] = $CFG_GLPI["proxy_name"];
         $options['proxy_port'] = intval($CFG_GLPI["proxy_port"]);
         $options['proxy_login'] = $CFG_GLPI["proxy_user"];
         $options['proxy_password'] = Toolbox::decrypt($CFG_GLPI["proxy_passwd"], GLPIKEY);

      }
      parent::__construct(self::ADDR, $options);
   }

   static function obj2array($obj) {
     $out = array();
     foreach ($obj as $key => $val) {
       switch(true) {
           case is_object($val):
            $out[$key] = self::obj2array($val);
            break;
         case is_array($val):
            $out[$key] = self::obj2array($val);
            break;
         default:
           $out[$key] = $val;
       }
     }
     return $out;
   }
   
   function getInfo($tag) {
      $args = array(
         'guid'            => self::GUID,
         'applicationName' => 'GLPI',
         'serviceTags'     => $tag
      );
      $reponse = parent::__soapCall('GetAssetInformation', array($args));
         //print_r($reponse);

      if (is_soap_fault($reponse)) {
         echo "SOAP Fault: ";
         print_r($reponse);
         return NULL;
      }
      return (isset($reponse->GetAssetInformationResult) ? $reponse->GetAssetInformationResult : NULL);
   }
   
   static function getInfos ($serial, $field) {
      
      $output = "";
      $self = new self();
      $infos = $self->getInfo($serial);
      $infos = self::obj2array($infos);
      
      $dates = array();
      foreach($infos['Asset'] as $line) {
         //Sometimes it happends on Windows that the array looks a little bit different
         //so check both cases
         if (isset($line['Entitlements']['EntitlementData'])
            || isset($line['EntitlementData'])) {
            if (isset($line['Entitlements']['EntitlementData'])) {
               $tmp = $line['Entitlements']['EntitlementData'];
            } else {
               $tmp = $line['EntitlementData'];
            }
            foreach($tmp as $info) {
               if (is_array($info)) {
                  $dates[]=$info[$field];
               }
            }
          }
      }
      
      $alldates = array();
      if (!empty($dates)) {
         foreach($dates as $date) {
            $tab = explode("T",$date);
            $maDate = PluginManufacturersimportsPostImport::checkDate($tab[0]);
            $alldates[] = $maDate;
            
         }
      }
      if ($field == "StartDate") {
         $values = array_values($alldates);
         $output = end($values);
      } else {
         $output = array_shift($alldates);
      }

      return $output;
   }
   
   static function getDates($serial, $field) {

      $maDate = '0000-00-00';
      
      $infos = self::getInfos($serial, $field);
      if (!empty($infos)) {
         $maDate = $infos;
      }
      return $maDate;
   }
}

?>