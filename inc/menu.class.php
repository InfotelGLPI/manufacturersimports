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

class PluginManufacturersimportsMenu extends CommonGLPI {

   static $rightname = 'plugin_manufacturersimports';

   static function getMenuName() {
      return _n('Suppliers import', 
                'Suppliers imports', 
                2, 
                'manufacturersimports');
   }

   static function getMenuContent() {
      $plugin_page              = "/plugins/manufacturersimports/front/import.php";
      $menu                     = array();
      //Menu entry in tools
      $menu['title']            = self::getMenuName();
      $menu['page']             = $plugin_page;
      $menu['links']['search']  = $plugin_page;

      if (Session::haveRight(static::$rightname, UPDATE)
            || Session::haveRight("config", UPDATE)) {
         //Entry icon in breadcrumb
         $menu['links']['config']                      = PluginManufacturersimportsConfig::getSearchURL(false);
         //Link to config page in admin plugins list
         $menu['config_page']                          = PluginManufacturersimportsConfig::getSearchURL(false);
         
         //Add a fourth level in breadcrumb for configuration page
         $menu['options']['config']['title']           = __('Setup');
         $menu['options']['config']['page']            = PluginManufacturersimportsConfig::getSearchURL(false);
         $menu['options']['config']['links']['search'] = PluginManufacturersimportsConfig::getSearchURL(false);
         $menu['options']['config']['links']['add']    = PluginManufacturersimportsConfig::getFormURL(false);
      }

      return $menu;
   }

   static function removeRightsFromSession() {
      if (isset($_SESSION['glpimenu']['tools']['types']['PluginManufacturersimportsMenu'])) {
         unset($_SESSION['glpimenu']['tools']['types']['PluginManufacturersimportsMenu']); 
      }
      if (isset($_SESSION['glpimenu']['tools']['content']['pluginmanufacturersimportsmenu'])) {
         unset($_SESSION['glpimenu']['tools']['content']['pluginmanufacturersimportsmenu']); 
      }
   }
}