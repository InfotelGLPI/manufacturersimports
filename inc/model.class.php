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
 * Class PluginManufacturersimportsModel
 */
class PluginManufacturersimportsModel extends CommonDBTM {

   static $rightname = "plugin_manufacturersimports";

   /**
    * Return the localized name of the current Type
    * Should be overloaded in each new class
    *
    * @return string
    **/
   static function getTypeName($nb = 0) {
      return _n('Suppliers import', 'Suppliers imports',
                $nb, 'manufacturersimports');
   }


   /**
    * @param $items_id
    * @param $itemtype
    *
    * @return bool
    */
   function getFromDBbyDevice($items_id, $itemtype) {
      global $DB;

      $query = "SELECT * FROM `".$this->getTable()."` " .
               "WHERE `items_id` = '" . $items_id . "'
                  AND `itemtype` = '" . $itemtype . "' ";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 1) {
            return false;
         }
         $this->fields = $DB->fetchAssoc($result);
         if (is_array($this->fields) && count($this->fields)) {
            return true;
         } else {
            return false;
         }
      }
      return false;
   }

   /**
    * @param $itemtype
    * @param $items_id
    *
    * @return bool
    */
   function checkIfModelNeeds($itemtype, $items_id) {
      if ($this->getFromDBbyDevice($items_id, $itemtype)) {
         return $this->fields["model_name"];
      } else {
         return false;
      }
   }

   /**
    * @param $values
    *
    * @return bool
    */
   function addModel($values) {
      $tmp['model_name'] = $values['model_name'];
      $tmp['itemtype']   = $values['itemtype'];
      $tmp['items_id']   = $values['items_id'];
      if ($this->getFromDBbyDevice($values['items_id'],
                                   $values['itemtype'])) {
         $tmp['id'] = $this->getID();
         $this->update($tmp);
      } else {
         $this->add($tmp);
      }
      return true;
   }

   /**
   * Prints the model add form (into devices)
   *
   * @param $device the device ID
   * @param $type the device type
   * @return nothing (print out a table)
   *
   */
   static function showModelForm($itemtype, $items_id) {
      global $DB;

      $canedit = Session::haveRight(static::$rightname, UPDATE);

      $query = "SELECT *
               FROM `glpi_plugin_manufacturersimports_models`
               WHERE `itemtype` = '".$itemtype."'
                  AND `items_id` = '".$items_id."'";
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      $config_url = PluginManufacturersimportsConfig::getFormUrl(true);
      echo "<form method='post' action='".$config_url."'>";
      echo "<div align='center'><table class='tab_cadre_fixe'>";
      echo "<tr>";
      echo "<th>".PluginManufacturersimportsPreImport::getTypeName(2)."</th>";
      echo "<th>".__('Model Number', 'manufacturersimports')."</th>";
      echo "</tr>";

      if ($number == 1) {
         while ($line = $DB->fetchArray($result)) {
            $ID = $line["id"];
            echo "<tr class='tab_bg_1'>";
            echo "<td class='left'>";
            echo Html::input('model_name', ['value' => $line["model_name"], 'size' => 30]);
            echo "</td>";
            if ($canedit) {
               echo "<td class='center' class='tab_bg_2'>";
               Html::showSimpleForm($config_url, 'delete_model',
                                    _x('button', 'Delete permanently'),
                                    ['id' => $ID]);
               echo "</td>";
            } else {
               echo "<td>";
               echo "</td>";
            }
            echo "</tr>";
         }

      } else if ($canedit) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2'>";
         echo Html::input('model_name', ['size' => 30]);
         echo Html::hidden('items_id', ['value' => $items_id]);
         echo Html::hidden('itemtype', ['value' => $itemtype]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update_model']);
         echo "</td></tr>";
      }

      echo "</table></div>";
      Html::closeForm();
   }

   /**
    * Class-specific method used to show the fields to specify the massive action
    *
    * @since version 0.85
    *
    * @param $ma the current massive action object
    *
    * @return false if parameters displayed ?
    **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case "add_model" :
            echo "<input type=\"text\" name=\"model_name\">&nbsp;";
            echo Html::submit(_sx('button', 'Post'), ['name' => 'massiveaction']);
            return true;
            break;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case "add_model":
            $model=new PluginManufacturersimportsModel();
            $input = $ma->getInput();
            foreach ($ma->items as $itemtype => $myitem) {
               foreach ($myitem as $key => $value) {
                  $input = ['model_name' => $ma->POST['model_name'],
                                 'items_id'   => $key,
                                 'itemtype'   => $itemtype];
                  if ($model->addModel($input)) {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                  }
               }
            }
            break;
      }
   }
}
