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

function plugin_manufacturersimports_install() {
   global $DB;

   include_once (GLPI_ROOT."/plugins/manufacturersimports/inc/profile.class.php");
   include_once (GLPI_ROOT."/plugins/manufacturersimports/inc/config.class.php");
   $migration = new Migration("1.7.0");
   $update    = false;
   
   //Root of SQL files for DB installation or upgrade
   $sql_root  = GLPI_ROOT ."/plugins/manufacturersimports/sql/";
   
   if (!TableExists("glpi_plugin_manufacturersimports_configs")) {

      $DB->runFile($sql_root."/empty-1.7.0.sql");

   } else if (TableExists("glpi_plugin_suppliertag_config") 
      && !FieldExists("glpi_plugin_suppliertag_config", "FK_entities")) {

      $update = true;
      $DB->runFile($sql_root."/update-1.1.sql");
      $DB->runFile($sql_root."/update-1.2.0.sql");
      $DB->runFile($sql_root."/update-1.3.0.sql");
      $DB->runFile($sql_root."/update-1.4.1.sql");
      $DB->runFile($sql_root."/update-1.5.0.sql");
      $DB->runFile($sql_root."/update-1.7.0.sql");

   } else if (TableExists("glpi_plugin_suppliertag_profiles") 
      && FieldExists("glpi_plugin_suppliertag_profiles", "interface")) {

      $update = true;
      $DB->runFile($sql_root."/update-1.2.0.sql");
      $DB->runFile($sql_root."/update-1.3.0.sql");
      $DB->runFile($sql_root."/update-1.4.1.sql");
      $DB->runFile($sql_root."/update-1.5.0.sql");
      $DB->runFile($sql_root."/update-1.7.0.sql");

   } else if (!TableExists("glpi_plugin_manufacturersimports_profiles")
                && !FieldExists("glpi_plugin_manufacturersimports_configs", "supplier_key")) {

      $update = true;
      $DB->runFile($sql_root."/update-1.3.0.sql");
      $DB->runFile($sql_root."/update-1.4.1.sql");
      $DB->runFile($sql_root."/update-1.5.0.sql");
      $DB->runFile($sql_root."/update-1.7.0.sql");
      
   } else if (!FieldExists("glpi_plugin_manufacturersimports_configs", "supplier_key")) {
      $DB->runFile($sql_root."/update-1.7.0.sql");
   }

   $query = "UPDATE `glpi_plugin_manufacturersimports_configs` 
             SET `Supplier_url` = 'https://api.dell.com/support/assetinfo/v4/getassetwarranty/' 
             WHERE `name` ='".PluginManufacturersimportsConfig::DELL."'";
   $DB->query($query);
   
   $query = "UPDATE `glpi_plugin_manufacturersimports_configs` 
             SET `Supplier_url` = 'http://shop.lenovo.com/SEUILibrary/controller/e/web/LenovoPortal/en_US/config.workflow:VerifyWarranty?country-code=897&' 
             WHERE `name` ='".PluginManufacturersimportsConfig::LENOVO."'";
   $DB->query($query);
   
   $query = "UPDATE `glpi_plugin_manufacturersimports_configs` 
             SET `Supplier_url` = 'https://support.ts.fujitsu.com/Warranty/WarrantyStatus.asp?lng=EN&IDNR=' 
             WHERE `name` ='".PluginManufacturersimportsConfig::FUJITSU."'";
   $DB->query($query);

   if ($update) {
      foreach ($DB->request('glpi_plugin_manufacturersimports_profiles') as $data) {
         $query = "UPDATE `glpi_plugin_manufacturersimports_profiles`
                   SET `profiles_id` = '".$data["id"]."'
                   WHERE `id` = '".$data["id"]."';";
         $DB->query($query);
      }
      
      $migration->dropField('glpi_plugin_manufacturersimports_profiles', 'name');

       Plugin::migrateItemType(
          array(2150 => 'PluginManufacturersimportsModel',
                2151 => 'PluginManufacturersimportsConfig'),
          array("glpi_bookmarks", "glpi_bookmarks_users", "glpi_displaypreferences",
                "glpi_documents_items", "glpi_infocoms", "glpi_logs", "glpi_tickets"),
         array("glpi_plugin_manufacturersimports_models","glpi_plugin_manufacturersimports_logs"));
   }

   

   //Migrate profiles to the system introduced in 0.85
   PluginManufacturersimportsProfile::initProfile();
   PluginManufacturersimportsProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
   //Drop old profile table : not used anymore
   $migration->dropTable('glpi_plugin_manufacturersimports_profiles');

   return true;
}

function plugin_manufacturersimports_uninstall() {
   global $DB;

   include_once (GLPI_ROOT."/plugins/manufacturersimports/inc/profile.class.php");
   include_once (GLPI_ROOT."/plugins/manufacturersimports/inc/menu.class.php");

   $migration = new Migration("1.7.0");
   $tables    = array("glpi_plugin_manufacturersimports_configs",
                      "glpi_plugin_manufacturersimports_models",
                      "glpi_plugin_manufacturersimports_logs");
   foreach($tables as $table) {
      $migration->dropTable($table);
   }

   //old versions
   $tables = array("glpi_plugin_suppliertag_config",
                   "glpi_plugin_suppliertag_profiles",
                   "glpi_plugin_suppliertag_models",
                   "glpi_plugin_suppliertag_imported");
   foreach($tables as $table) {
      $migration->dropTable($table);
   }
   
   $profileRight = new ProfileRight();

   foreach (PluginManufacturersimportsProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(array('name' => $right['field']));
   }
   
   //Remove rigth from $_SESSION['glpiactiveprofile'] if exists
   PluginManufacturersimportsProfile::removeRightsFromSession();
   
   //Remove entries in GLPI's menu and breadcrumb
   PluginManufacturersimportsMenu::removeRightsFromSession();
   return true;
}

function plugin_manufacturersimports_postinit() {
   foreach (PluginManufacturersimportsConfig::getTypes(true) as $type) {
      CommonGLPI::registerStandardTab($type, 'PluginManufacturersimportsConfig');
   }
}

// Define dropdown relations
function plugin_manufacturersimports_getDatabaseRelations() {
   $plugin = new Plugin();
   if ($plugin->isActivated("manufacturersimports")) {
      return array ("glpi_entities" 
                        => array ("glpi_plugin_manufacturersimports_configs" 
                                 => "entities_id"),
                    "glpi_manufacturers" 
                        => array ("glpi_plugin_manufacturersimports_configs" 
                                 => "manufacturers_id"),
                     "glpi_suppliers" 
                        => array ("glpi_plugin_manufacturersimports_configs" 
                                 => "suppliers_id"),
                     "glpi_documentcategories" 
                        => array ("glpi_plugin_manufacturersimports_configs" 
                                 => "documentcategories_id"),
                     "glpi_documents" 
                        => array ("glpi_plugin_manufacturersimports_logs" 
                                 => "documents_id")
                );
   } else {
      return array ();
   }
}

////// SEARCH FUNCTIONS ///////() {

function plugin_manufacturersimports_getAddSearchOptions($itemtype) {

    $sopt=array();

    if (in_array($itemtype, PluginManufacturersimportsConfig::getTypes())) {
      //TODO change right manufacturersimports READ
      if (Session::haveRight('config', READ)) {
        $sopt[2150]['table']         = 'glpi_plugin_manufacturersimports_models';
        $sopt[2150]['field']         = 'model_name';
        $sopt[2150]['linkfield']     = '';
        $sopt[2150]['name']          = _n('Suppliers import', 
                                          'Suppliers imports', 
                                          2 , 
                                          'manufacturersimports')
                                          ." - ".__('Model number', 'manufacturersimports');
        $sopt[2150]['forcegroupby']  = true;
        $sopt[2150]['joinparams']    = array('jointype'  => 'itemtype_item');
        $sopt[2150]['massiveaction'] = false;
      }
   }
   return $sopt;
}

//force groupby for multible links to items
function plugin_manufacturersimports_forceGroupBy($type) {

   return true;
   switch ($type) {
      case 'PluginManufacturersimportsModel':
         return true;
         break;
   }
   return false;
}


////// SPECIFIC MODIF MASSIVE FUNCTIONS ///////
function plugin_manufacturersimports_MassiveActions($type) {
   if (in_array($type, PluginManufacturersimportsConfig::getTypes(true))) {
      return array('PluginManufacturersimportsModel'.MassiveAction::CLASS_ACTION_SEPARATOR."add_model"
                  =>__('Add new material brand number', 'manufacturersimports'));
   }
   return array();
}

?>