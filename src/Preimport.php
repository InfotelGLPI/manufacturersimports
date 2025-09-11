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

namespace GlpiPlugin\Manufacturersimports;

use Ajax;
use CommonDBTM;
use DbUtils;
use Dropdown;
use Html;
use Infocom;
use Search;
use Session;
use Supplier;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class PreImport
 */
class PreImport extends CommonDBTM
{
    public static $rightname = "plugin_manufacturersimports";

    public const IMPORTED     = 2;
    public const NOT_IMPORTED = 1;

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return _n('Suppliers import', 'Suppliers imports', $nb, 'manufacturersimports');
    }


    /**
     * @param        $myname
     * @param int    $value_type
     * @param int    $value
     * @param int    $entity_restrict
     * @param string $types
     */
    public static function showAllItems($myname, $value_type = 0, $value = 0, $entity_restrict = -1, $types = '')
    {
        if (!is_array($types)) {
            $types = Config::getTypes();
        }

        $rand    = mt_rand();
        $options = [];

        foreach ($types as $type) {
            $item           = new $type();
            $options[$type] = $item::getTypeName();
        }
        asort($options);
        if (count($options)) {
            $id = "item_type$rand";
            Dropdown::showFromArray($myname, $options, ['value'               => $value,
                'id'                  => $id,
                'display_emptychoice' => true]);
        }
    }

    /**
     * Fonction to use the supplier url
     *
     * @param      $suppliername the suppliername
     * @param      $supplierUrl the supplierUrl (in plugin config)
     * @param      $compSerial the serial of the device
     * @param      $otherSerial the otherSerial (model) of the device
     * @param null $supplierkey the supplierkey
     * @param null $supplierSecret the supplierSecret
     *
     * @return $url of the supplier
     *
     */
    public static function selectSupplier(
        $suppliername,
        $supplierUrl,
        $compSerial,
        $otherserial = null,
        $supplierkey = null,
        $supplierSecret = null,
        $second_url = false
    ) {
        $url = "";
        if (!empty($suppliername)) {
            $supplierclass = "GlpiPlugin\Manufacturersimports\\" . $suppliername;
            $supplier      = new $supplierclass();
            $infos         = $supplier->getSupplierInfo(
                $compSerial,
                $otherserial,
                $supplierkey,
                $supplierSecret,
                $supplierUrl
            );
            if (!$second_url) {
                $url = $infos['url'];
            } else {
                $url = $infos['url_web'];
            }
        }
        return $url;
    }

    /**
     * Fonction to use the supplier url
     *
     * @param      $suppliername the suppliername
     * @param      $supplierUrl the supplierUrl (in plugin config)
     * @param      $compSerial the serial of the device
     * @param      $otherSerial the otherSerial (model) of the device
     * @param null $supplierkey the supplierkey
     * @param null $supplierSecret the supplierSecret
     *
     * @return $url of the supplier
     *
     */
    public static function selectSupplierWarranty(
        $suppliername,
        $supplierUrl,
        $compSerial,
        $otherserial = null,
        $supplierkey = null,
        $supplierSecret = null
    ) {
        $url_warranty = "";
        if (!empty($suppliername)) {
            $supplierclass = "GlpiPlugin\Manufacturersimports\\" . $suppliername;
            $supplier      = new $supplierclass();
            $infos         = $supplier->getSupplierInfo(
                $compSerial,
                $otherserial,
                $supplierkey,
                $supplierSecret,
                $supplierUrl
            );
            $url_warranty  = $infos['url_warranty'];
        }
        return $url_warranty;
    }

    /**
     * @param      $suppliername
     * @param      $supplierUrl
     * @param      $compSerial
     * @param null $otherserial
     * @param null $supplierkey
     *
     * @return string
     */
    public static function getMoreInfosSupplier($suppliername, $supplierUrl, $compSerial, $otherserial = null, $supplierkey = null)
    {
        $url = "";
        if (!empty($suppliername)) {
            $supplierclass = "GlpiPlugin\Manufacturersimports\\" . $suppliername;
            $supplier      = new $supplierclass();
            if (method_exists($supplier, "getSupplierMoreInfo")) {
                $url = $supplier->getSupplierMoreInfo($compSerial, $otherserial, $supplierkey, $supplierUrl);
            }
        }
        return $url;
    }

    /**
     * @param      $suppliername
     * @param      $supplierUrl
     * @param      $compSerial
     * @param null $otherserial
     * @param null $supplierkey
     *
     * @return string
     */
    public static function getJSSupplier($suppliername, $supplierUrl, $compSerial, $otherserial = null, $supplierkey = null)
    {
        $js = "";
        if (!empty($suppliername)) {
            $supplierclass = "GlpiPlugin\Manufacturersimports\\" . $suppliername;
            $supplier      = new $supplierclass();
            if (method_exists($supplier, "getJSSupplier")) {
                $js = $supplier->getJSSupplier($compSerial, $otherserial, $supplierkey, $supplierUrl);
            }
        }
        return $js;
    }

    /**
     * @param      $suppliername
     * @param      $compSerial
     * @param null $otherserial
     *
     * @return string
     */
    public static function getSupplierPost(
        $suppliername,
        $compSerial,
        $otherserial = null,
        $supplierkey = null,
        $supplierSecret = null
    ) {
        $post = "";
        if (!empty($suppliername)) {
            $supplierclass = "GlpiPlugin\Manufacturersimports\\" . $suppliername;
            $supplier      = new $supplierclass();
            $infos         = $supplier->getSupplierInfo($compSerial, $otherserial, $supplierkey, $supplierSecret);
            if (isset($infos['post'])) {
                $post = $infos['post'];
            }
        }
        return $post;
    }

    /**
     * @param $row_num
     * @param $item_num
     * @param $line
     * @param $output_type
     * @param $manufacturers_id
     * @param $status
     * @param $imported
     */
    public static function showImport(
        $row_num,
        $item_num,
        $line,
        $output_type,
        $manufacturers_id,
        $itemtype,
        $status,
        $imported
    ) {

        $infocom = new Infocom();
        $canedit = Session::haveRight(static::$rightname, UPDATE) && $infocom->canUpdate();
        $config  = new Config();
        $config->getFromDB($manufacturers_id);

        $suppliername      = $config->fields["name"];
        $supplierUrl       = $config->fields["supplier_url"];
        $supplierId        = $config->fields["suppliers_id"];
        $supplierWarranty  = $config->fields["warranty_duration"];
        $supplierkey       = $config->fields["supplier_key"];
        $supplierkeysecret = $config->fields["supplier_secret"];
        $supplierclass     = "GlpiPlugin\Manufacturersimports\\" . $suppliername;
        $supplier          = new $supplierclass();

        $row_num++;

        if ($suppliername) {

            $otherSerial = "";
            $modelitemtype = $itemtype . "Model";
            if (class_exists($modelitemtype)) {
                $dbu = new DbUtils();
                $modelfield = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($itemtype . "Model"));
                $models_id = $line[$modelfield];
                if ($models_id != 0) {
                    $modelclass = new $modelitemtype();
                    $modelclass->getfromDB($models_id);
                    $otherSerial = $modelclass->fields["product_number"];
                }
            }

            echo Search::showNewLine($output_type, $row_num % 2);
            $ic           = new Infocom();
            $output_check = "";
            if ($canedit
                && $output_type == Search::HTML_OUTPUT) {
                $sel = "";
                if (isset($_GET["select"])
                    && $_GET["select"] == "all") {
                    $sel = "checked";
                }
                $output_check = $supplier->showCheckbox($line["id"], $sel, $otherSerial);
            }

            echo Search::showItem($output_type, $output_check, $item_num, $row_num);
            $link = Toolbox::getItemTypeFormURL($line["itemtype"]);
            $ID   = "";
            if ($_SESSION["glpiis_ids_visible"]
                || empty($line["name"])) {
                $ID .= " (" . $line["id"] . ")";
            }
            $output_link = "<a href='" . $link . "?id=" . $line["id"] . "'>"
                           . $line["name"] . $ID . "</a><br>" . $line["model_name"];
            echo Search::showItem($output_type, $output_link, $item_num, $row_num);
            if (Session::isMultiEntitiesMode()) {
                echo Search::showItem(
                    $output_type,
                    Dropdown::getDropdownName(
                        "glpi_entities",
                        $line['entities_id']
                    ),
                    $item_num,
                    $row_num
                );
            }

            $url = self::selectSupplier(
                $suppliername,
                $supplierUrl,
                $line["serial"],
                $otherSerial,
                $supplierkey,
                $supplierkeysecret
            );
            //serial
            echo Search::showItem($output_type, $line["serial"], $item_num, $row_num);
            //otherserial
            echo Search::showItem($output_type, $otherSerial, $item_num, $row_num);

            //display infocoms
            $output_ic = "";
            if ($ic->getfromDBforDevice($line["itemtype"], $line["id"])) {
                $output_ic .= _n('Supplier', 'Suppliers', 1) . ":"
                              . Dropdown::getDropdownName("glpi_suppliers", $ic->fields["suppliers_id"]) . "<br>";
                $output_ic .= __('Date of purchase') . " : " . Html::convdate($ic->fields["buy_date"]) . "<br>";
                $output_ic .= __('Start date of warranty') . " : " . Html::convdate($ic->fields["warranty_date"]) . "<br>";
                if ($ic->fields["warranty_duration"] == -1) {
                    $output_ic .= __('Warranty duration') . " : " . __('Lifelong') . "<br>";
                } else {
                    $output_ic .= __('Warranty duration') . " : " . $ic->fields["warranty_duration"] . " " . __('month') . "<br>";
                }
                $tmpdat    = Infocom::getWarrantyExpir($ic->fields["warranty_date"], $ic->fields["warranty_duration"]);
                $output_ic .= sprintf(__('Valid to %s'), $tmpdat);
            } else {
                $output_ic .= "";
            }
            echo Search::showItem($output_type, $output_ic, $item_num, $row_num);

            if ($imported != self::IMPORTED) {
                //display enterprise and warranty selection
                echo "<td>";
                if (Session::isMultiEntitiesMode() && $supplierId) {
                    $item = new Supplier();
                    $item->getFromDB($supplierId);
                    if ($item->fields["is_recursive"]
                        || $item->fields["entities_id"] == $line['entities_id']) {
                        Dropdown::show('Supplier', ['name'     => "to_suppliers_id" . $line["id"],
                            'value'    => $supplierId,
                            'comments' => 0,
                            'entity'   => $line['entities_id']]);
                    } else {
                        echo "<span class='plugin_manufacturersimports_import_KO'>";
                        echo __('The choosen supplier is not recursive', 'manufacturersimports') . "</span>";
                        $name = "to_suppliers_id" . $line["id"];
                        echo Html::hidden($name, ['value' => -1]);
                    }
                } else {
                    Dropdown::show('Supplier', ['name'     => "to_suppliers_id" . $line["id"],
                        'value'    => $supplierId,
                        'comments' => 0,
                        'entity'   => $line['entities_id']]);
                }
                echo "</td>";

                $supplier->showWarrantyItem($line["id"], $supplierWarranty);
            } else {
                //display enterprise and warranty selection
                echo "<td>" . Dropdown::getDropdownName(
                    "glpi_suppliers",
                    $ic->fields["suppliers_id"]
                ) . "</td>";
                if ($ic->fields["warranty_duration"] == -1) {
                    echo "<td>" . __('Lifelong') . "</td>";
                } else {
                    echo "<td>" . $ic->fields["warranty_duration"] . "</td>";
                }
            }

            //supplier url
            //url to supplier
            $output_url = "<a href='" . $url . "' target='_blank'>"
                          . __('Manufacturer information', 'manufacturersimports') . "</a>";

            if ($suppliername == Config::LENOVO) {
                $url        = self::selectSupplier(
                    $suppliername,
                    $supplierUrl,
                    $line["serial"],
                    $otherSerial,
                    $supplierkey,
                    $supplierkeysecret,
                    true
                );
                $output_url = "<a href='" . $url . "' target='_blank'>"
                              . __('Manufacturer information', 'manufacturersimports') . "</a>";
            }
            echo Search::showItem($output_type, $output_url, $item_num, $row_num);

            //status
            if ($imported != self::IMPORTED) {
                if ($status != 2) {
                    $output_doc = __('Not yet imported', 'manufacturersimports');
                } else {
                    $output_doc = "<span class='plugin_manufacturersimports_import_KO'>"
                                  . __('Problem during the importation', 'manufacturersimports');
                    if (!empty($data["date_import"])) {
                        $output_doc .= " (" . Html::convdate($data["date_import"]) . ")";
                    }
                    $output_doc .= "</span>";
                }
            } else {
                $output_doc = "<span class='plugin_manufacturersimports_import_OK'>"
                              . __('Already imported', 'manufacturersimports');
                if (!empty($line["date_import"])) {
                    $output_doc .= " (" . Html::convdate($line["date_import"]) . ")";
                }
                $output_doc .= "</span>";
            }
            echo Search::showItem($output_type, $output_doc, $item_num, $row_num);
            //no associated doc
            echo $supplier->showDocItem($output_type, $item_num, $row_num, $line["documents_id"]);
        }
    }

    /**
     * Prints search form
     *
     * @param $manufacturer the supplier choice
     * @param $type the device type
     *
     * @return nothing (print out a table)
     *
     */
    public static function searchForm($params)
    {
        global $DB;

        // Default values of parameters
        $p['itemtype']         = '';
        $p['manufacturers_id'] = '';
        $p['imported']         = '';

        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }

        $dbu = new DbUtils();

        echo "<form name='form' method='post' action='" . PLUGIN_MANUFACTURERSIMPORTS_WEBDIR . "/front/import.php'>";
        echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
        echo "<tr><th colspan='4'>" . __('Choose inventory type and manufacturer', 'manufacturersimports') . "</th></tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>";

        $criteria = [
            'SELECT' => '*',
            'FROM' => 'glpi_plugin_manufacturersimports_configs',
            'WHERE' => [
                'glpi_plugin_manufacturersimports_configs.manufacturers_id' => ['>', 0],
            ],
            'ORDERBY'   => ['glpi_plugin_manufacturersimports_configs.entities_id',
                'glpi_plugin_manufacturersimports_configs.name'],
        ];
        $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_plugin_manufacturersimports_configs'
        );

        $iterator = $DB->request($criteria);

        if (count($iterator) > 0) {
            self::showAllItems(
                "itemtype",
                0,
                $p['itemtype'],
                -1,
                Config::getTypes()
            );
            echo "</td><td>";
            foreach ($iterator as $data) {
                //            if (Session::isMultiEntitiesMode()) {
                //               $name = Dropdown::getDropdownName("glpi_entities", $data["entities_id"]) . " > ";
                //            }
                $name = $data["name"];
                if (empty($data["name"]) || $_SESSION["glpiis_ids_visible"]) {
                    $name .= " (";
                    $name .= $data["id"] . ")";
                }
                $opt[$data["id"]] = $name;
            }

            Dropdown::showFromArray('manufacturers_id', $opt, ['value' => $p['manufacturers_id']]);

            echo "</td><td>";

            $options[self::NOT_IMPORTED] = __('Devices not imported', 'manufacturersimports');
            $options[self::IMPORTED]     = __('Devices already imported', 'manufacturersimports');
            Dropdown::showFromArray('imported', $options, ['value' => $p['imported']]);
        } else {
            if (Session::haveRight('config', UPDATE)) {
                //Please configure a supplier
                echo "<a class='submit btn btn-primary' href='" . PLUGIN_MANUFACTURERSIMPORTS_WEBDIR . "/front/config.form.php'>";
                echo __('No manufacturer available. Please configure at least one manufacturer', 'manufacturersimports');
                echo "</a>";
            } else {
                echo "<div class='alert alert-important alert-warning d-flex'>";
                echo __('No manufacturer available. Please configure at least one manufacturer', 'manufacturersimports');
                echo "</div>";
            }
        }
        echo "</td><td>";
        if (count($iterator) > 0) {
            echo Html::submit(_sx('button', 'Post'), ['name' => 'typechoice', 'class' => 'btn btn-primary']);
        }
        echo "</td>";
        echo "</tr>";

        echo "</table></div>";
        Html::closeForm();
    }

    /**
     * Prints display Search Header
     *
     * @param $fixed if fixed cells
     * @param $output_type the output_type
     *
     * @return nothing (print out a table)
     *
     */
    //   static function displaySearchHeader($output_type, $fixed = 0) {
    //      $out = "";
    //      switch ($output_type) {
    //         default :
    //            if ($fixed) {
    //               $out = "<div class='center'><table border='0' class='tab_cadre_fixehov'>\n";
    //            } else {
    //               $out = "<div class='center'><table border='0' class='tab_cadre_fixe'>\n";
    //            }
    //            break;
    //      }
    //      return $out;
    //   }

    /**
     * Prints display pre import
     *
     * @param $type the type of device
     * @param $configID the ID of the supplier config
     * @param $start for pager display
     * @param $complete to see all device (already imported and not)
     *
     * @return nothing (print out a table)
     *
     */
    public static function seePreImport($params)
    {
        global $DB, $CFG_GLPI;


        // Default values of parameters
        $p['link']             = [];
        $p['field']            = [];
        $p['contains']         = [];
        $p['searchtype']       = [];
        $p['sort']             = '1';
        $p['order']            = 'ASC';
        $p['start']            = 0;
        $p['export_all']       = 0;
        $p['link2']            = '';
        $p['contains2']        = '';
        $p['field2']           = '';
        $p['itemtype2']        = '';
        $p['searchtype2']      = '';
        $p['itemtype']         = '';
        $p['manufacturers_id'] = '';
        $p['imported']         = '';

        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }

        $target     = PLUGIN_MANUFACTURERSIMPORTS_WEBDIR . "/front/import.php";

        if ($p['itemtype'] && $p['manufacturers_id']) {
            $config = new Config();
            $config->getFromDB($p['manufacturers_id']);
            $suppliername  = $config->fields["name"] ?? "";
            $supplierclass = "GlpiPlugin\Manufacturersimports\\" . $suppliername;
            $supplier      = new $supplierclass();

            $infocom = new Infocom();
            $canedit = Session::haveRight(static::$rightname, UPDATE) && $infocom->canUpdate();

            if (!$p['start']) {
                $p['start'] = 0;
            }

            $toview = ["name" => 1];

            $query = self::queryImport($p, $config, $toview);

            $result  = $DB->doQuery($query);
            $numrows = $DB->numrows($result);

            if ($p['start'] < $numrows) {
                // Set display type for export if define
                $output_type = Search::HTML_OUTPUT;
                if (isset($_GET["display_type"])) {
                    $output_type = $_GET["display_type"];
                }
                $parameters = "itemtype=" . $p['itemtype']
                              . "&amp;manufacturers_id=" . $p['manufacturers_id']
                              . "&amp;imported=" . $p['imported'];
                $total      = 0;

                if ($output_type == Search::HTML_OUTPUT) {
                    self::printPager(
                        $p['start'],
                        $numrows,
                        $target,
                        $parameters,
                        $p['itemtype']
                    );
                }


                if (Session::isMultiEntitiesMode()) {
                    $colsup = 1;
                } else {
                    $colsup = 0;
                }
                //////////////////////HEADER///////////////
                if ($output_type == Search::HTML_OUTPUT) {
                    echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action=\"../front/massiveaction.php\">";
                }

                //echo Search::displaySearchHeader($output_type,0); //table + div
                if ($canedit) {
                    $nbcols = 11 + $colsup;
                } else {
                    $nbcols = 10 + $colsup;
                }
                $LIST_LIMIT    = $_SESSION['glpilist_limit'];
                $begin_display = $p['start'];
                $end_display   = $p['start'] + $LIST_LIMIT;

                echo Search::showHeader($output_type, $end_display - $begin_display + 1, $nbcols);
                echo Search::showNewLine($output_type);
                $header_num = 1;

                echo Search::showHeaderItem($output_type, "", $header_num);
                echo Search::showHeaderItem(
                    $output_type,
                    __('Name'),
                    $header_num,
                    '',
                    $p['sort'] == $val,
                    $p['order']
                );
                if (Session::isMultiEntitiesMode()) {
                    echo Search::showHeaderItem($output_type, __('Entity'), $header_num);
                }
                echo Search::showHeaderItem($output_type, __('Serial number'), $header_num);
                echo Search::showHeaderItem($output_type, __('Model Number', 'manufacturersimports'), $header_num);
                echo Search::showHeaderItem(
                    $output_type,
                    __('Financial and administrative information'),
                    $header_num
                );
                echo Search::showHeaderItem(
                    $output_type,
                    __('Supplier attached', 'manufacturersimports'),
                    $header_num
                );
                echo Search::showHeaderItem(
                    $output_type,
                    __('New warranty attached', 'manufacturersimports'),
                    $header_num
                );
                echo Search::showHeaderItem(
                    $output_type,
                    _n('Link', 'Links', 1),
                    $header_num
                );
                echo Search::showHeaderItem(
                    $output_type,
                    _n('Status', 'Statuses', 1),
                    $header_num
                );
                echo $supplier->showDocTitle($output_type, $header_num);

                // End Line for column headers
                echo Search::showEndLine($output_type);

                $i = $p['start'];
                if (isset($_GET['export_all'])) {
                    $i = 0;
                }
                if ($i > 0) {
                    $DB->dataSeek($result, $i);
                }

                $row_num = 1;

                while ($i < $numrows && $i < $end_display) {
                    $i++;

                    $item_num   = 1;
                    $line       = $DB->fetchArray($result);
                    $compId     = $line['id'];

                    //                    if (!$line["itemtype"]) {
                    $line["itemtype"] = $p['itemtype'];
                    //                    }

                    self::showImport(
                        $row_num,
                        $item_num,
                        $line,
                        $output_type,
                        $p['manufacturers_id'],
                        $p['itemtype'],
                        $line["import_status"],
                        $p['imported']
                    );
                    //1.show already imported items && import_status not failed
                    if ($p['imported'] == 1) {
                        $total += 1;
                    }
                }
                echo "<tr class='tab_bg_1'><td colspan='"
                     . ($canedit ? (11 + $colsup) : (10 + $colsup)) . "'>";
                echo sprintf(__(
                    'Total number of devices to import %s',
                    'manufacturersimports'
                ), $total);
                echo "</td></tr>";

                // Close Table
                $title = "";
                // Create title
                if ($output_type == Search::PDF_OUTPUT_PORTRAIT
                    || $output_type == Search::PDF_OUTPUT_LANDSCAPE) {
                    $title
                       .= PreImport::getTypeName(2)
                       . " " . $suppliername;
                }

                echo Search::showFooter($output_type, $title);

                //massive action
                if ($canedit && $output_type == Search::HTML_OUTPUT) {
                    if ($_SESSION['glpilist_limit'] < Toolbox::get_max_input_vars()) {
                        self::openArrowMassives("massiveaction_form", false);
                        self::dropdownMassiveAction(
                            $compId,
                            $p['itemtype'],
                            $p['manufacturers_id'],
                            $p['start'],
                            $p['imported']
                        );
                        self::closeArrowMassives([]);
                    } else {
                        echo "<table class='tab_cadre' width='80%'><tr class='tab_bg_1'>"
                             . "<td><span class='b'>";
                        echo __('Selection too large, massive action disabled.') . "</span>";
                        if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                            echo "<br>" . __('To increase the limit: change max_input_vars or suhosin.post.max_vars in php configuration.');
                        }
                        echo "</td></tr></table>";
                    }
                    Html::closeForm();
                } else {
                    echo "</table>";
                    echo "</div>";
                }

                echo "<br>";
                if ($output_type == Search::HTML_OUTPUT) {
                    self::printPager(
                        $p['start'],
                        $numrows,
                        $target,
                        $parameters,
                        $p['itemtype']
                    );
                }
            } else {
                echo "<div align='center'><b>"
                     . __('No device finded', 'manufacturersimports') . "</b></div>";
            }
        }
    }

    /**
     * show arrow for massives actions : opening
     *
     * @param $formname  string
     * @param $fixed     boolean  used tab_cadre_fixe in both tables (false by default)
     * @param $ontop              display on top of the list (false by default)
     * @param $onright            display on right of the list (false by default)
     *
     **/
    public static function openArrowMassives($formname, $fixed = false, $ontop = false, $onright = false)
    {
        global $CFG_GLPI;

        if ($fixed) {
            echo "<table class='tab_glpi' width='950px'>";
        } else {
            echo "<table class='tab_glpi' width='100%'>";
        }

        echo "<tr>";
        if (!$onright) {
            echo "<td><i class='ti ti-corner-left-up mx-2'></i></td>";
        } else {
            echo "<td class='left' width='80%'></td>";
        }
        echo "<td class='center' style='white-space:nowrap;'>";
        echo "<a onclick= \"if ( markCheckboxes('$formname') ) return false;\"
             href='#'>" . __('Check all') . "</a></td>";
        echo "<td>/</td>";
        echo "<td class='center' style='white-space:nowrap;'>";
        echo "<a onclick= \"if ( unMarkCheckboxes('$formname') ) return false;\"
             href='#'>" . __('Uncheck all') . "</a></td>";

        if ($onright) {
            echo "<td><i class='ti ti-corner-left-up mx-2'></i>";
        } else {
            echo "<td class='left' width='80%'>";
        }
    }


    /**
     * show arrow for massives actions : closing
     *
     * @param $actions array of action : $name -> $label
     * @param $confirm array of confirmation string (optional)
     *
     **/
    public static function closeArrowMassives($actions, $confirm = [])
    {
        if (count($actions)) {
            foreach ($actions as $name => $label) {
                if (!empty($name)) {
                    echo "<input type='submit' name='$name' ";
                    if (is_array($confirm) && isset($confirm[$name])) {
                        echo self::addConfirmationOnAction($confirm[$name]);
                    }
                    echo "value=\"" . addslashes($label) . "\" class='submit btn btn-primary'>&nbsp;";
                }
            }
        }
        echo "</td></tr>";
        echo "</table>";
    }

    /**
     * Request
     *
     * @param $p
     * @param $config
     * @param $toview
     *
     * @return string
     */
    public static function queryImport($p, $config, $toview, $isCron = false)
    {
        $dbu = new DbUtils();

        $modeltable = $dbu->getTableForItemType($p['itemtype'] . "Model");
        $modelfield = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($p['itemtype'] . "Model"));
        $item       = new $p['itemtype']();
        $itemtable  = $dbu->getTableForItemType($p['itemtype']);

        $query = "SELECT `" . $itemtable . "`.`id`,
                        `" . $itemtable . "`.`name`,
                        `" . $itemtable . "`.`serial`,
                        `" . $itemtable . "`.`$modelfield`,
                        `" . $itemtable . "`.`entities_id`,
                        `glpi_plugin_manufacturersimports_logs`.`import_status`,
                        `glpi_plugin_manufacturersimports_logs`.`items_id`,
                        `glpi_plugin_manufacturersimports_logs`.`itemtype`,
                        `glpi_plugin_manufacturersimports_logs`.`documents_id`,
                        `glpi_plugin_manufacturersimports_logs`.`date_import`,
                        '" . $p['itemtype'] . "' AS type,
                        `$modeltable`.`name` AS model_name
                  FROM `" . $itemtable . "` ";

        //model device left join
        $query .= "LEFT JOIN `$modeltable` ON (`$modeltable`.`id` = `" . $itemtable . "`.`" . $modelfield . "`) ";
        $query .= " LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id` = `" . $itemtable . "`.`entities_id`)";
        $query .= " LEFT JOIN `glpi_plugin_manufacturersimports_configs`
         ON (`glpi_plugin_manufacturersimports_configs`.`manufacturers_id` = `" . $itemtable . "`.`manufacturers_id`)";
        $query .= " LEFT JOIN `glpi_plugin_manufacturersimports_logs`
         ON (`glpi_plugin_manufacturersimports_logs`.`items_id` = `" . $itemtable . "`.`id`
         AND `glpi_plugin_manufacturersimports_logs`.`itemtype` = '" . $p['itemtype'] . "')";

        //serial must be not empty
        $query .= " WHERE `" . $itemtable . "`.`is_deleted` = '0'
          AND `" . $itemtable . "`.`is_template` = '0'
          AND `glpi_plugin_manufacturersimports_configs`.`id` = '" . $p['manufacturers_id'] . "'
          AND `" . $itemtable . "`.`serial` != '' ";
        //already imported
        if ($p['imported'] == self::IMPORTED) {
            $query .= " AND `import_status` != " . self::IMPORTED . "";
            //not imported
        } elseif ($p['imported'] == self::NOT_IMPORTED) {
            $query .= " AND (`date_import` IS NULL OR `import_status` = " . self::IMPORTED . " ";
            $query .= ") ";
        }
        $entities = "";
        if ($config->isRecursive()) {
            $entities = $dbu->getSonsOf('glpi_entities', $config->getEntityID());
        } else {
            $entities = $config->getEntityID();
        }
        if (!$isCron) {
            $query .= "" . $dbu->getEntitiesRestrictRequest(" AND", $itemtable, '', '', $item->maybeRecursive());
        }
        //// 4 - ORDER
        $ORDER = " ORDER BY `entities_id`,`" . $itemtable . "`.`name` ";

        foreach ($toview as $key => $val) {
            if ($p['sort'] == $val) {
                $ORDER = self::addOrderBy($p['itemtype'], $p['sort'], $p['order'], $key);
            }
        }
        $query .= $ORDER;

        return $query;
    }

    /**
     * Generic Function to add ORDER BY to a request
     *
     * @param $itemtype ID of the device type
     * @param $ID field to add
     * @param $order order define
     * @param $key item number
     *
     * @return select string
     *
     **/
    public static function addOrderBy($itemtype, $ID, $order, $key = 0)
    {
        global $CFG_GLPI;

        // Security test for order
        if ($order != "ASC") {
            $order = "DESC";
        }
        $searchopt = Search::getOptions($itemtype);

        $table = $searchopt[$ID]["table"];
        $field = $searchopt[$ID]["field"];

        $addtable = '';
        $dbu      = new DbUtils();

        if ($table != $dbu->getTableForItemType($itemtype)
            && $searchopt[$ID]["linkfield"] != $dbu->getForeignKeyFieldForTable($table)) {
            $addtable .= "_" . $searchopt[$ID]["linkfield"];
        }

        if (isset($searchopt[$ID]['joinparams'])) {
            $complexjoin = Search::computeComplexJoinID($searchopt[$ID]['joinparams']);

            if (!empty($complexjoin)) {
                $addtable .= "_" . $complexjoin;
            }
        }

        if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
            return " ORDER BY ITEM_$key $order ";
        }

        return " ORDER BY $table.$field $order ";
    }

    /**
     * @param     $start
     * @param     $numrows
     * @param     $target
     * @param     $parameters
     * @param int $item_type_output
     * @param int $item_type_output_param
     */
    public static function printPager($start, $numrows, $target, $parameters, $item_type_output = 0, $item_type_output_param = 0)
    {
        global $CFG_GLPI;

        $list_limit = $_SESSION['glpilist_limit'];
        // Forward is the next step forward
        $forward = $start + $list_limit;

        // This is the end, my friend
        $end = $numrows - $list_limit;

        // Human readable count starts here
        $current_start = $start + 1;

        // And the human is viewing from start to end
        $current_end = $current_start + $list_limit - 1;
        if ($current_end > $numrows) {
            $current_end = $numrows;
        }

        // Backward browsing
        if ($current_start - $list_limit <= 0) {
            $back = 0;
        } else {
            $back = $start - $list_limit;
        }

        // Print it

        echo "<table class='tab_cadre_pager'>\n";
        echo "<tr>\n";

        // Back and fast backward button
        if (!$start == 0) {
            echo "<th class='left'>";
            echo "<a href='$target?$parameters&amp;start=0'>";
            echo "<i style='font-size: 2em;' class='ti ti-chevrons-left' title=\""
                 . __s('Start') . "\"></i>";
            echo "</a></th>";
            echo "<th class='left'>";
            echo "<a href='$target?$parameters&amp;start=$back'>";
            echo "<i style='font-size: 2em;' class='ti ti-chevron-left' title=\""
                 . __s('Previous') . "\"></i>";
            echo "</a></th>";
        }

        // Print the "where am I?"
        echo "<td width='50%'  class='tab_bg_2'>";
        Html::printPagerForm("$target?$parameters&amp;start=$start");
        echo "</td>\n";

        echo "<td width='50%' class='tab_bg_2 b'>";
        //TRANS: %1$d, %2$d, %3$d are page numbers
        printf(__('From %1$d to %2$d on %3$d'), $current_start, $current_end, $numrows);
        echo "</td>\n";

        // Forward and fast forward button
        if ($forward < $numrows) {
            echo "<th class='right'>";
            echo "<a href='$target?$parameters&amp;start=$forward'>";
            echo "<i style='font-size: 2em;' class='ti ti-chevron-right' title=\""
                 . __s('Next') . "\"></i>";
            echo "</a></th>\n";

            echo "<th class='right'>";
            echo "<a href='$target?$parameters&amp;start=$end'>";
            echo "<i style='font-size: 2em;' class='ti ti-chevrons-right' title=\""
                 . __s('End') . "\"></i>";
            echo "</a></th>\n";
        }

        // End pager
        echo "</tr>\n";
        echo "</table><br>\n";
    }

    /**
     * @param $ID
     * @param $type
     * @param $manufacturer
     * @param $start
     * @param $imported
     */
    public static function dropdownMassiveAction($ID, $type, $manufacturer, $start, $imported)
    {
        global $CFG_GLPI;

        echo "<select class='form-select' name=\"massiveaction\" id='massiveaction' style='width: 20%;display: unset;'>";
        echo "<option value=\"-1\" selected>" . Dropdown::EMPTY_VALUE . "</option>";
        //not imported
        if ($imported == self::NOT_IMPORTED) {
            echo "<option value=\"import\">" . __('Import') . "</option>";
        }
        echo "<option value=\"reinit_once\">" . __('Reset the import', 'manufacturersimports') . "</option>";

        echo "</select>&nbsp;";

        $params = ['action'           => '__VALUE__',
            'manufacturers_id' => $manufacturer,
            'itemtype'         => $type,
            'start'            => $start,
            'imported'         => $imported,
            'id'               => $ID,
        ];

        Ajax::updateItemOnSelectEvent(
            "massiveaction",
            "show_massiveaction",
            PLUGIN_MANUFACTURERSIMPORTS_WEBDIR . "/ajax/dropdownMassiveAction.php",
            $params
        );

        echo "<span id='show_massiveaction'>&nbsp;</span>\n";
    }

    /**
     * @param $name
     * @param $array
     *
     * @return string
     */
    public static function getArrayUrlLink($name, $array)
    {
        $out = "";
        if (is_array($array) && count($array) > 0) {
            foreach ($array as $key => $val) {
                $out .= "&amp;" . $name . "[$key]=" . urlencode(stripslashes($val));
            }
        }
        return $out;
    }
}
