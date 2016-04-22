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

class PluginManufacturersimportsConfig extends CommonDBTM {

   static $rightname = "plugin_manufacturersimports";
   static $types     = array('Computer', 'Monitor', 
                             'NetworkEquipment', 
                             'Peripheral', 'Printer');
   public $dohistory = true;
   
   //Manufacturers constants
   const DELL        = "Dell";
   const LENOVO      = "Lenovo";
   const HP          = "HP";
   const FUJITSU     = "Fujitsu";
   const TOSHIBA     = "Toshiba";
   
   static function getTypeName($nb=0) {
      return _n('Manufacturer', 'Manufacturers', $nb);
   }

   public function defineTabs($options = array()) {
      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      if (in_array($item->getType(), self::getTypes(true))
          && Session::haveRight(static::$rightname, READ)
            && !isset($withtemplate) || empty($withtemplate)) {

         $suppliername = self::checkManufacturerName($item->getType(), 
                                                     $item->getID());
         if ($suppliername) {
            return PluginManufacturersimportsPreImport::getTypeName(2);
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      if (in_array($item->getType(), self::getTypes(true))) {
         self::showInformationsForm(get_class($item),$item->getID());
         PluginManufacturersimportsModel::showForm(get_class($item),$item->getID());
      }
      return true;
   }

   /**
   * Preconfig datas for standard system
   * @param $type type of standard system : AD
   *@return nothing
   **/
   function preconfig($type) {

      switch($type) {
         case self::DELL:
         case self::HP:
         case self::FUJITSU:
         case self::LENOVO:
         case self::TOSHIBA:
            $supplierclass                = "PluginManufacturersimports".$type;
            $supplier                     = new $supplierclass();
            $infos                        = $supplier->getSupplierInfo();
            $this->fields["name"]         = $infos["name"];
            $this->fields["supplier_url"] = $infos["supplier_url"];
            if ($type == self::DELL) {
               $this->fields["supplier_key"] = "1adecee8a60444738f280aad1cd87d0e";
            }
            break;
         default:
            $this->post_getEmpty();
            break;
      }
   }

   function post_addItem() {
      global $DB;

      if ($this->fields["is_recursive"]) {
         $query = "DELETE FROM `".$this->getTable()."`
                   WHERE `name` = '".$this->fields["name"]."'
                     AND `id` != '".$this->fields['id']."' " .
                  getEntitiesRestrictRequest('AND', $this->getTable(),
                                             '',
                                             getSonsOf("glpi_entities",
                                                       $this->fields["entities_id"]));
         $result=$DB->query($query);
      }
   }

   function post_updateItem($history=1) {
      global $DB;

      if ($this->fields["is_recursive"]) {
         $query = "DELETE FROM `".$this->getTable()."`
                  WHERE `name` = '".$this->fields["name"]."'
                     AND `id` != '".$this->fields["id"]."' " .
                getEntitiesRestrictRequest('AND', $this->getTable(),
                                           '',
                                           getSonsOf("glpi_entities",
                                                     $this->fields["entities_id"]));
         $result=$DB->query($query);
      }
   }

   static function dropdownSupplier($name, $options=array()) {
      $params['value']       = 0;
      $params['toadd']       = array();
      $params['on_change']   = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $items = array();
      if (count($params['toadd']) >0) {
         $items = $params['toadd'];
      }

      $items += self::getSuppliers();
      return Dropdown::showFromArray($name, $items, $params);
   }

   static function getSuppliers() {
      $options[-1]            = Dropdown::EMPTY_VALUE;
      $options[self::DELL]    = self::DELL;
      //$options[self::HP]      = self::HP;
      $options[self::FUJITSU] = self::FUJITSU;
      $options[self::TOSHIBA] = self::TOSHIBA;
      $options[self::LENOVO]  = self::LENOVO;
      return $options;
   }

   function getSearchOptions() {

      $tab = array();

      $tab['common']             = self::getTypeName(2);

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['itemlink_type']   = $this->getType();

      $tab[2]['table']           = 'glpi_manufacturers';
      $tab[2]['field']           = 'name';
      $tab[2]['name']            = __('Manufacturer');
      $tab[2]['field']           = 'dropdown';
      $tab[2]['massiveaction']   = false;

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'supplier_url';
      $tab[3]['name']            = __('Manufacturer web address', 
                                      'manufacturersimports');
      $tab[3]['datatype']        = 'weblink';
      $tab[3]['massiveaction']   = false;

      $tab[4]['table']           = 'glpi_suppliers';
      $tab[4]['field']           =  'name';
      $tab[4]['name']            =  __('Default supplier attached',
                                       'manufacturersimports');
      $tab[4]['datatype']        = 'dropdown';

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'warranty_duration';
      $tab[5]['name']            = __('New warranty attached', 
                                      'manufacturersimports');
      $tab[5]['datatype']        = 'integer';
      $tab[5]['massiveaction']   = false;

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'document_adding';
      $tab[6]['name']            = __('Auto add of document', 
                                      'manufacturersimports');
      $tab[6]['datatype']        = 'bool';
      $tab[6]['massiveaction']   = false;

      $tab[7]['table']           = 'glpi_documentcategories';
      $tab[7]['field']           = 'name';
      $tab[7]['name']            = __('Document heading');
      $tab[7]['massiveaction']   = false;

      $tab[8]['table']           = $this->getTable();
      $tab[8]['field']           = 'comment_adding';
      $tab[8]['name']            = __('Add a comment line', 
                                      'manufacturersimports');
      $tab[8]['datatype']        = 'bool';

      $tab[30]['table']          = $this->getTable();
      $tab[30]['field']          = 'id';
      $tab[30]['name']           = __('ID');

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['datatype']       = 'dropdown';

      $tab[86]['table']          = $this->getTable();
      $tab[86]['field']          = 'is_recursive';
      $tab[86]['name']           = __('Child entities');
      $tab[86]['datatype']       = 'bool';

      return $tab;
   }

   function showForm ($ID, $options=array()) {
      global $CFG_GLPI;

      if (!$this->canView()) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID ,READ);
      } else {
         $this->check(-1, UPDATE);
         $this->getEmpty();
         if (isset($_GET['preconfig'])) {
            $this->preconfig($_GET['preconfig']);
         } else {
            $_GET['preconfig'] = -1;
         }
      }

      $input   = array("name"         => $this->fields["name"],
                       "supplier_url" => $this->fields["supplier_url"]);
      $canedit = $this->can($ID, UPDATE, $input);
      $canrecu = $this->can($ID,'recursive',$input);

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";
      echo "<td class='tab_bg_2 center' colspan='2'>".__('Preconfiguration')."&nbsp;";
      
      $opt    = array('value' => $_GET['preconfig']);
      $rand   = self::dropdownSupplier('supplier', $opt);
      $params = array('supplier' => '__VALUE__');
      
      Ajax::updateItemOnSelectEvent("dropdown_supplier$rand", 
                                    "show_preconfig",
                                    "../ajax/dropdownSuppliers.php",
                                    $params);
      echo "<span id='show_preconfig'>";
      echo "</span>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";

      if ($_GET['preconfig'] == -1 && $ID <= 0) {
         $style = "style='display:none;'";
      } else {
         $style = "style='display:block;'";
      }
      echo "<div id='show_form' $style>";

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr>";
      echo "<td class='tab_bg_2 center'>".__('Name')."</td>";
      echo "<td class='tab_bg_2 left'>";
      echo $this->fields["name"];
      echo "<input type='hidden' name='name' value=\"".$this->fields["name"]."\">\n";
      echo "</td>";

      echo "<td class='tab_bg_2 center'>".__('Manufacturer')."</td>";
      echo "<td class='tab_bg_2 left'>";
      Dropdown::show('Manufacturer', 
                     array('name'  => "manufacturers_id",
                           'value' => $this->fields["manufacturers_id"]));
      echo "</td>";
      echo "</tr>";
      
      if ($this->fields["name"] != self::DELL) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".__('Manufacturer web address', 'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         echo "<input type='text' name='supplier_url' size='100' value='".$this->fields["supplier_url"]."'>";
         echo "</td>";
         echo "</tr>";
      }
      echo "<tr>";
      echo "<td class='tab_bg_2 center' colspan='2'>".__('Default supplier attached', 'manufacturersimports')."</td>";
      echo "<td class='tab_bg_2 left' colspan='2'>";
      Dropdown::show('Supplier', array('name'  => "suppliers_id",
                                       'value' => $this->fields["suppliers_id"]));
      echo "</td>";
      echo "</tr>";

      if ($this->fields["name"] == self::FUJITSU) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".
            __('New warranty affected by default (Replace if 0)', 
            'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         Dropdown::showInteger("warranty_duration", 
                               $this->fields["warranty_duration"], 
                               0, 120);
         echo "</td>";
         echo "</tr>";
      } else {
         echo "<input type='hidden' name='warranty_duration' value='0'>\n";
      }
      if ($this->fields["name"] != self::DELL) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".__('Auto add of document', 'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         Dropdown::showYesNo("document_adding",$this->fields["document_adding"]);
         echo "</td>";
         echo "</tr>";

         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".__('Section for document records', 'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         Dropdown::show('DocumentCategory', array('name'  => "documentcategories_id",
                                                   'value' => $this->fields["documentcategories_id"]));
         echo "</td>";
         echo "</tr>";
      } else {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".__('Manufacturer API key', 'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         echo "<input type='text' name='supplier_key' size='32' value='".$this->fields["supplier_key"]."'>";
         echo "</td>";
         echo "</tr>";
      }
      echo "<tr>";
      echo "<td class='tab_bg_2 center' colspan='2'>".__('Add a comment line', 'manufacturersimports')."</td>";
      echo "<td class='tab_bg_2 left' colspan='2'>";
      Dropdown::showYesNo("comment_adding",$this->fields["comment_adding"]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }

   /**
    * For other plugins, add a type to the linkable types
    *
    * @since version 1.5.0
    *
    * @param $type string class name
   **/
   static function registerType($type) {
      if (!in_array($type, self::$types)) {
         self::$types[] = $type;
      }
   }


   /**
    * Type than could be linked to a Rack
    *
    * @param $all boolean, all type, or only allowed ones
    *
    * @return array of types
   **/
   static function getTypes($all = false) {

      if ($all) {
         return self::$types;
      }

      // Only allowed types
      $types = self::$types;

      foreach ($types as $key => $itemtype) {
         if (!class_exists($itemtype)) {
            continue;
         }

         if (!$itemtype::canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }

   static function checkManufacturerName($itemtype, $items_id) {

      $item = new $itemtype();
      $name = false;

      if ($item->getFromDB($items_id)) {
         $configs = getAllDatasFromTable("glpi_plugin_manufacturersimports_configs");
         if (!empty($configs)) {
            foreach ($configs as $config) {
               if ($item->fields["manufacturers_id"] == $config['manufacturers_id']) {
                  $name = $config["name"];
               }
            }
         }
      }
      return $name;
   }
   
   static function checkManufacturerID($itemtype, $items_id) {

      $item = new $itemtype();
      $id = false;

      if ($item->getFromDB($items_id)) {
         $configs = getAllDatasFromTable("glpi_plugin_manufacturersimports_configs");
         if (!empty($configs)) {
            foreach ($configs as $config) {
               if ($item->fields["manufacturers_id"] == $config['manufacturers_id']) {
                  $id = $config["id"];
               }
            }
         }
      }
      return $id;
   }

   /**
   * Prints the url to manufacturer informations on items
   *
   * @param $device the device ID
   * @param $type the device type
   * @return nothing (print out a table)
   *
   */
   static function showInformationsForm($itemtype,$items_id) {
      global $DB,$CFG_GLPI;

      $item = new $itemtype();
      if ($item->getFromDB($items_id)) {
         $suppliername = PluginManufacturersimportsConfig::checkManufacturerName($itemtype, $items_id);
         $model        = new PluginManufacturersimportsModel();
         $otherserial  = $model->checkIfModelNeeds($itemtype, $items_id);
         
         $configID = PluginManufacturersimportsConfig::checkManufacturerID($itemtype, $items_id);
         $config = new PluginManufacturersimportsConfig();
         $config->getFromDB($configID);
         $supplierkey  = (isset($config->fields["supplier_key"]))?$config->fields["supplier_key"]:false;
         
         $url          = PluginManufacturersimportsPreImport::selectSupplier($suppliername, $item->fields['serial'], $otherserial,$supplierkey);

         echo "<div align=\"center\"><table class=\"tab_cadre_fixe\"  cellspacing=\"2\" cellpadding=\"2\">";
         echo "<tr>";
         echo "<th colspan='2'>".PluginManufacturersimportsPreImport::getTypeName(2)."</th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>";
         echo _n('Link', 'Links', 1);
         echo "</td>";
         echo "<td>";

         echo "<a href='".$url."' target='_blank'>".__('Manufacturer information', 'manufacturersimports')."</a>";
         echo "</td></tr>";
         echo "</table></div>";

      }
   }

   //Massive action
   function getSpecificMassiveActions($checkitem = NULL) {
      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         if (Session::haveRight('transfer', READ)
            && Session::isMultiEntitiesMode() ) {
            $actions['Transfert'] = __('Transfer');
         }
      }

      return $actions;
   }

   function showSpecificMassiveActionsParameters($input = array()) {

      switch ($input['action']) {
         case "Transfert" :
            Dropdown::show('Entity');
            echo Html::submit(_sx('button', 'Post'), array('name' => 'massiveaction'));
            //echo "&nbsp;<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value='" . _sx('button', 'Post') . "'>";
            return true;
            break;

         default :
            return parent::showSpecificMassiveActionsParameters($input);
            break;
      }
      return false;
   }

   function doSpecificMassiveActions($input = array()) {

      $res = array('ok' => 0, 'ko' => 0, 'noright' => 0);

      switch ($input['action']) {
         case "Transfert" :

            if ($input['itemtype']=='PluginManufacturersimportsConfig') {
               foreach ($input["item"] as $key => $val) {
                  if ($val == 1) {

                     $values["id"] = $key;
                     $values["entities_id"] = $input['entities_id'];
                     if ($this->update($values)) {
                        $res['ok']++;
                     } else {
                        $res['ko']++;
                     }
                  }
               }
            }
            break;
         default :
            return parent::doSpecificMassiveActions($input);
            break;
      }
      return $res;
   }
}

?>