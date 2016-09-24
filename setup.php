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

// Init the hooks of the plugins -Needed
function plugin_init_manufacturersimports() {
   global $PLUGIN_HOOKS,$CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['manufacturersimports'] = true;

   $plugin = new Plugin();
   if ($plugin->isInstalled('manufacturersimports') 
      && Session::getLoginUserID()) {
      Plugin::registerClass('PluginManufacturersimportsProfile',
                            array('addtabon' => 'Profile'));

      //Display menu entry only if user has right to see it !
      if (Session::haveRight('plugin_manufacturersimports', READ)) {
         $PLUGIN_HOOKS["menu_toadd"]['manufacturersimports'] 
            = array('tools'  => 'PluginManufacturersimportsMenu');
      }

      if (Session::haveRight('config', UPDATE)) {
         $PLUGIN_HOOKS['config_page']['manufacturersimports'] 
            = 'front/config.php';
         $PLUGIN_HOOKS['use_massive_action']['manufacturersimports'] = 1;
      }

      // End init, when all types are registered
      $PLUGIN_HOOKS['post_init']['manufacturersimports'] 
         = 'plugin_manufacturersimports_postinit';
   }
   
   // Add specific files to add to the header : javascript or css
      $PLUGIN_HOOKS['add_css']['manufacturersimports'] = array(
         "manufacturersimports.css",
      );

}

// Get the name and the version of the plugin - Needed
function plugin_version_manufacturersimports() {
   return array ('name'           => _n('Suppliers import', 'Suppliers imports', 2, 
                                        'manufacturersimports'),
                 'oldname'        => 'suppliertag',
                 'version'        => '1.9.0',
                 'license'        => 'GPLv2+',
                 'author'         => "<a href='http://infotel.com/services/expertise-technique/glpi/'>Infotel</a>",
                 'homepage'       => 'https://github.com/InfotelGLPI/manufacturersimports/',
                 'minGlpiVersion' => '0.90',
   );
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_manufacturersimports_check_prerequisites() {

   if (version_compare(GLPI_VERSION,'0.90','lt') 
      || version_compare(GLPI_VERSION,'9.2','ge')) {
      echo __('This plugin requires GLPI >= 0.90', 
              'manufacturersimports');
      return false;
   } elseif (!extension_loaded("soap")) {
      echo __('Incompatible PHP Installation. Requires module', 
              'manufacturersimports'). " soap";
      return false;
   } elseif (!extension_loaded("curl")) {
      echo __('Incompatible PHP Installation. Requires module', 
              'manufacturersimports'). " curl";
      return false;
   } elseif (!extension_loaded("json")) {
      echo __('Incompatible PHP Installation. Requires module', 
              'manufacturersimports'). " json";
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_manufacturersimports_check_config() {
   return true;
}

?>
