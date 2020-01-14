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
 * Class PluginManufacturersimportsConfig
 */
class PluginManufacturersimportsConfig extends CommonDBTM {

   static $rightname = "plugin_manufacturersimports";
   static $types     = ['Computer', 'Monitor',
                             'NetworkEquipment',
                             'Peripheral', 'Printer'];
   public $dohistory = true;

   //Manufacturers constants
   const DELL        = "Dell";
   const LENOVO      = "Lenovo";
   const HP          = "HP";
   const FUJITSU     = "Fujitsu";
   const TOSHIBA     = "Toshiba";
   const WORTMANN_AG = "Wortmann_AG";

   static function getTypeName($nb = 0) {
      return _n('Manufacturer', 'Manufacturers', $nb);
   }

   public function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
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

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if (in_array($item->getType(), self::getTypes(true))) {
         self::showInformationsForm(get_class($item), $item->getID());
         PluginManufacturersimportsModel::showForm(get_class($item), $item->getID());
      }
      return true;
   }

   /**
   * Preconfig datas for standard system
   * @param $type type of standard system : AD
   *@return nothing
   **/
   function preconfig($type) {

      switch ($type) {
         case self::DELL:
         case self::HP:
         case self::FUJITSU:
         case self::LENOVO:
         case self::TOSHIBA:
         case self::WORTMANN_AG:
            $supplierclass                = "PluginManufacturersimports" . $type;
            $supplier                     = new $supplierclass();
            $infos                        = $supplier->getSupplierInfo();
            $this->fields["name"]         = $infos["name"];
            $this->fields["supplier_url"] = $infos["supplier_url"];

            if ($type == self::HP) {
               $this->fields["supplier_secret"] = 'abcdefabcdefAaBBBBB';
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
         $dbu = new DbUtils();
         $query = "DELETE FROM `".$this->getTable()."`
                   WHERE `name` = '".$this->fields["name"]."'
                     AND `id` != '".$this->fields['id']."' " .
                  $dbu->getEntitiesRestrictRequest('AND', $this->getTable(),
                                             '',
                                                   $dbu->getSonsOf("glpi_entities",
                                                       $this->fields["entities_id"]));
         $DB->query($query);
      }
   }

   function post_updateItem($history = 1) {
      global $DB;

      if ($this->fields["is_recursive"]) {
         $dbu = new DbUtils();
         $query = "DELETE FROM `".$this->getTable()."`
                  WHERE `name` = '".$this->fields["name"]."'
                     AND `id` != '".$this->fields["id"]."' " .
                  $dbu->getEntitiesRestrictRequest('AND', $this->getTable(),
                                           '',
                                                   $dbu->getSonsOf("glpi_entities",
                                                     $this->fields["entities_id"]));
         $DB->query($query);
      }
   }

   static function dropdownSupplier($name, $options = []) {
      $params['value']       = 0;
      $params['toadd']       = [];
      $params['on_change']   = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $items = [];
      if (count($params['toadd']) >0) {
         $items = $params['toadd'];
      }

      $items += self::getSuppliers();
      return Dropdown::showFromArray($name, $items, $params);
   }

   static function getSuppliers() {
      $options[-1]            = Dropdown::EMPTY_VALUE;
      $options[self::DELL]    = self::DELL;
      $options[self::HP]      = self::HP;
      $options[self::FUJITSU] = self::FUJITSU;
      $options[self::TOSHIBA] = self::TOSHIBA;
      $options[self::LENOVO]  = self::LENOVO;
      $options[self::WORTMANN_AG]  = self::WORTMANN_AG;
      return $options;
   }

   /**
    * Provides search options configuration. Do not rely directly
    * on this, @see CommonDBTM::searchOptions instead.
    *
    * @since 9.3
    *
    * This should be overloaded in Class
    *
    * @return array a *not indexed* array of search options
    *
    * @see https://glpi-developer-documentation.rtfd.io/en/master/devapi/search.html
    **/
   public function rawSearchOptions() {

      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '2',
         'table'              => 'glpi_manufacturers',
         'field'              => 'name',
         'name'               => __('Manufacturer'),
         'datatype'           => 'dropdown',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'supplier_url',
         'name'               => __('Manufacturer web address', 'manufacturersimports'),
         'datatype'           => 'weblink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_suppliers',
         'field'              => 'name',
         'name'               => __('Default supplier attached', 'manufacturersimports'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'warranty_duration',
         'name'               => __('New warranty attached', 'manufacturersimports'),
         'datatype'           => 'integer',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'document_adding',
         'name'               => __('Auto add of document', 'manufacturersimports'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => 'glpi_documentcategories',
         'field'              => 'name',
         'name'               => __('Document heading'),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'comment_adding',
         'name'               => __('Add a comment line', 'manufacturersimports'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID')
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }

   function showForm ($ID, $options = []) {

      if (!$this->canView()) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         $this->check(-1, UPDATE);
         $this->getEmpty();
         if (isset($_GET['preconfig'])) {
            $this->preconfig($_GET['preconfig']);
         } else {
            $_GET['preconfig'] = -1;
         }
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";
      echo "<td class='tab_bg_2 center' colspan='2'>".__('Preconfiguration')."&nbsp;";

      $opt    = ['value' => $_GET['preconfig']];
      $rand   = self::dropdownSupplier('supplier', $opt);
      $params = ['supplier' => '__VALUE__'];

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
                     ['name'  => "manufacturers_id",
                      'value' => $this->fields["manufacturers_id"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td class='tab_bg_2 center' colspan='2'>".__('Manufacturer web address', 'manufacturersimports')."</td>";
      echo "<td class='tab_bg_2 left' colspan='2'>";
      echo "<input type='text' name='supplier_url' size='100' value='".$this->fields["supplier_url"]."'>";
      echo "</td>";
      echo "</tr>";

      if ($this->fields["name"] == self::DELL) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".__('Access token API address', 'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         echo "<input type='text' name='token_url' size='100' value='".$this->fields["token_url"]."'>";
         echo "</td>";
         echo "</tr>";
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".__('Warranty API address', 'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         echo "<input type='text' name='warranty_url' size='100' value='".$this->fields["warranty_url"]."'>";
         echo "</td>";
         echo "</tr>";
      }

      echo "<tr>";
      echo "<td class='tab_bg_2 center' colspan='2'>".__('Default supplier attached', 'manufacturersimports')."</td>";
      echo "<td class='tab_bg_2 left' colspan='2'>";
      Dropdown::show('Supplier', ['name'  => "suppliers_id",
                                  'value' => $this->fields["suppliers_id"]]);
      echo "</td>";
      echo "</tr>";

      if ($this->fields["name"] == self::FUJITSU) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".
            __('New warranty affected by default (Replace if 0)',
            'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         Dropdown::showNumber("warranty_duration", ['value' => $this->fields["warranty_duration"],
                                                     'min' => 0,
                                                     'max' => 120]);
         echo "</td>";
         echo "</tr>";
      } else {
         echo "<input type='hidden' name='warranty_duration' value='0'>\n";
      }
      if ($this->fields["name"] == self::HP) {

         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>" . __('Manufacturer API key', 'manufacturersimports') . "</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         echo "<input type='text' name='supplier_key' size='100' value='" . $this->fields["supplier_key"] . "'>";
         echo "</td>";
         echo "</tr>";

         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>" . __('Manufacturer API Secret', 'manufacturersimports') . "</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         echo "<input type='text' name='supplier_secret' size='100' value='" . $this->fields["supplier_secret"] . "'>";
         echo "</td>";
         echo "</tr>";

      } else if ($this->fields["name"] != self::DELL) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".__('Auto add of document', 'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         Dropdown::showYesNo("document_adding", $this->fields["document_adding"]);
         echo "</td>";
         echo "</tr>";

         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".__('Section for document records', 'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         Dropdown::show('DocumentCategory', ['name'  => "documentcategories_id",
                                                   'value' => $this->fields["documentcategories_id"]]);
         echo "</td>";
         echo "</tr>";
      } else { // DELL
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".__('Client id', 'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         echo "<input type='text' name='supplier_key' size='50' value='".$this->fields["supplier_key"]."'>";
         echo "</td>";
         echo "</tr>";
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>".__('Client secret', 'manufacturersimports')."</td>";
         echo "<td class='tab_bg_2 left' colspan='2'>";
         echo "<input type='text' name='supplier_secret' size='50' value='".$this->fields["supplier_secret"]."'>";
         echo "</td>";
         echo "</tr>";
      }
      echo "<tr>";
      echo "<td class='tab_bg_2 center' colspan='2'>".__('Add a comment line', 'manufacturersimports')."</td>";
      echo "<td class='tab_bg_2 left' colspan='2'>";
      Dropdown::showYesNo("comment_adding", $this->fields["comment_adding"]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      echo "<div align='center'>";
      echo "<a href='".self::getFormURL(true)."'>";
      echo __('Back');
      echo "</a>";
      echo "</div>";

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
         $dbu = new DbUtils();
         $configs = $dbu->getAllDataFromTable("glpi_plugin_manufacturersimports_configs");
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
         $dbu = new DbUtils();
         $configs = $dbu->getAllDataFromTable("glpi_plugin_manufacturersimports_configs");
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
   static function showInformationsForm($itemtype, $items_id) {

      $item = new $itemtype();
      if ($item->getFromDB($items_id)) {
         $suppliername = PluginManufacturersimportsConfig::checkManufacturerName($itemtype, $items_id);
         $model        = new PluginManufacturersimportsModel();
         $otherserial  = $model->checkIfModelNeeds($itemtype, $items_id);

         $configID = PluginManufacturersimportsConfig::checkManufacturerID($itemtype, $items_id);
         $config = new PluginManufacturersimportsConfig();
         $config->getFromDB($configID);
         $supplierkey  = (isset($config->fields["supplier_key"]))?$config->fields["supplier_key"]:false;
         $supplierurl  = (isset($config->fields["supplier_url"]))?$config->fields["supplier_url"]:false;

         $url          = PluginManufacturersimportsPreImport::selectSupplier($suppliername, $supplierurl, $item->fields['serial'], $otherserial, $supplierkey);

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
   function getSpecificMassiveActions($checkitem = null) {
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

   function showSpecificMassiveActionsParameters($input = []) {

      switch ($input['action']) {
         case "Transfert" :
            Dropdown::show('Entity');
            echo Html::submit(_sx('button', 'Post'), ['name' => 'massiveaction']);
            //echo "&nbsp;<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value='" . _sx('button', 'Post') . "'>";
            return true;
            break;

         default :
            return parent::showSpecificMassiveActionsParameters($input);
            break;
      }
      return false;
   }

   function doSpecificMassiveActions($input = []) {

      $res = ['ok' => 0, 'ko' => 0, 'noright' => 0];

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
