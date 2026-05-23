<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2015-2026 by the Manufacturersimports Development Team.

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
use Glpi\Application\View\TemplateRenderer;
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
//    public static function showImport(
//        $row_num,
//        $item_num,
//        $line,
//        $output_type,
//        $manufacturers_id,
//        $itemtype,
//        $status,
//        $imported
//    ) {
//
//        $infocom = new Infocom();
//        $canedit = Session::haveRight(static::$rightname, UPDATE) && $infocom->canUpdate();
//        $config  = new Config();
//        $config->getFromDB($manufacturers_id);
//
//        $suppliername      = $config->fields["name"];
//        $supplierUrl       = $config->fields["supplier_url"];
//        $supplierId        = $config->fields["suppliers_id"];
//        $supplierWarranty  = $config->fields["warranty_duration"];
//        $supplierkey       = $config->fields["supplier_key"];
//        $supplierkeysecret = $config->fields["supplier_secret"];
//        $supplierclass     = "GlpiPlugin\Manufacturersimports\\" . $suppliername;
//        $supplier          = new $supplierclass();
//
//        $row_num++;
//
//        if ($suppliername) {
//
//            $otherSerial = "";
//            $modelitemtype = $itemtype . "Model";
//            if (class_exists($modelitemtype)) {
//                $dbu = new DbUtils();
//                $modelfield = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($itemtype . "Model"));
//                $models_id = $line[$modelfield];
//                if ($models_id != 0) {
//                    $modelclass = new $modelitemtype();
//                    $modelclass->getfromDB($models_id);
//                    $otherSerial = $modelclass->fields["product_number"];
//                }
//            }
//
//            echo Search::showNewLine($output_type, $row_num % 2);
//            $ic           = new Infocom();
//            $output_check = "";
//            if ($canedit
//                && $output_type == Search::HTML_OUTPUT) {
//                $sel = "";
//                if (isset($_GET["select"])
//                    && $_GET["select"] == "all") {
//                    $sel = "checked";
//                }
//                $output_check = $supplier->showCheckbox($line["id"], $sel, $otherSerial);
//            }
//
//            echo Search::showItem($output_type, $output_check, $item_num, $row_num);
//            $link = Toolbox::getItemTypeFormURL($line["itemtype"]);
//            $ID   = "";
//            if ($_SESSION["glpiis_ids_visible"]
//                || empty($line["name"])) {
//                $ID .= " (" . $line["id"] . ")";
//            }
//            $output_link = "<a href='" . $link . "?id=" . $line["id"] . "'>"
//                           . $line["name"] . $ID . "</a><br>" . $line["model_name"];
//            echo Search::showItem($output_type, $output_link, $item_num, $row_num);
//            if (Session::isMultiEntitiesMode()) {
//                echo Search::showItem(
//                    $output_type,
//                    Dropdown::getDropdownName(
//                        "glpi_entities",
//                        $line['entities_id']
//                    ),
//                    $item_num,
//                    $row_num
//                );
//            }
//
//            $url = self::selectSupplier(
//                $suppliername,
//                $supplierUrl,
//                $line["serial"],
//                $otherSerial,
//                $supplierkey,
//                $supplierkeysecret
//            );
//            //serial
//            echo Search::showItem($output_type, $line["serial"], $item_num, $row_num);
//            //otherserial
//            echo Search::showItem($output_type, $otherSerial, $item_num, $row_num);
//
//            //display infocoms
//            $output_ic = "";
//            if ($ic->getfromDBforDevice($line["itemtype"], $line["id"])) {
//                $output_ic .= _n('Supplier', 'Suppliers', 1) . ":"
//                              . Dropdown::getDropdownName("glpi_suppliers", $ic->fields["suppliers_id"]) . "<br>";
//                $output_ic .= __('Date of purchase') . " : " . Html::convdate($ic->fields["buy_date"]) . "<br>";
//                $output_ic .= __('Start date of warranty') . " : " . Html::convdate($ic->fields["warranty_date"]) . "<br>";
//                if ($ic->fields["warranty_duration"] == -1) {
//                    $output_ic .= __('Warranty duration') . " : " . __('Lifelong') . "<br>";
//                } else {
//                    $output_ic .= __('Warranty duration') . " : " . $ic->fields["warranty_duration"] . " " . __('month') . "<br>";
//                }
//                $tmpdat    = Infocom::getWarrantyExpir($ic->fields["warranty_date"], $ic->fields["warranty_duration"]);
//                $output_ic .= sprintf(__('Valid to %s'), $tmpdat);
//            } else {
//                $output_ic .= "";
//            }
//            echo Search::showItem($output_type, $output_ic, $item_num, $row_num);
//
//            if ($imported != self::IMPORTED) {
//                //display enterprise and warranty selection
//                echo "<td>";
//                if (Session::isMultiEntitiesMode() && $supplierId) {
//                    $item = new Supplier();
//                    $item->getFromDB($supplierId);
//                    if ($item->fields["is_recursive"]
//                        || $item->fields["entities_id"] == $line['entities_id']) {
//                        Dropdown::show('Supplier', ['name'     => "to_suppliers_id" . $line["id"],
//                            'value'    => $supplierId,
//                            'comments' => 0,
//                            'entity'   => $line['entities_id']]);
//                    } else {
//                        echo "<span class='plugin_manufacturersimports_import_KO'>";
//                        echo __('The choosen supplier is not recursive', 'manufacturersimports') . "</span>";
//                        $name = "to_suppliers_id" . $line["id"];
//                        echo Html::hidden($name, ['value' => -1]);
//                    }
//                } else {
//                    Dropdown::show('Supplier', ['name'     => "to_suppliers_id" . $line["id"],
//                        'value'    => $supplierId,
//                        'comments' => 0,
//                        'entity'   => $line['entities_id']]);
//                }
//                echo "</td>";
//
//                $supplier->showWarrantyItem($line["id"], $supplierWarranty);
//            } else {
//                //display enterprise and warranty selection
//                echo "<td>" . Dropdown::getDropdownName(
//                    "glpi_suppliers",
//                    $ic->fields["suppliers_id"]
//                ) . "</td>";
//                if ($ic->fields["warranty_duration"] == -1) {
//                    echo "<td>" . __('Lifelong') . "</td>";
//                } else {
//                    echo "<td>" . $ic->fields["warranty_duration"] . "</td>";
//                }
//            }
//
//            //supplier url
//            //url to supplier
//            $output_url = "<a href='" . $url . "' target='_blank'>"
//                          . __('Manufacturer information', 'manufacturersimports') . "</a>";
//
//            if ($suppliername == Config::LENOVO) {
//                $url        = self::selectSupplier(
//                    $suppliername,
//                    $supplierUrl,
//                    $line["serial"],
//                    $otherSerial,
//                    $supplierkey,
//                    $supplierkeysecret,
//                    true
//                );
//                $output_url = "<a href='" . $url . "' target='_blank'>"
//                              . __('Manufacturer information', 'manufacturersimports') . "</a>";
//            }
//            echo Search::showItem($output_type, $output_url, $item_num, $row_num);
//
//            //status
//            if ($imported != self::IMPORTED) {
//                if ($status != 2) {
//                    $output_doc = __('Not yet imported', 'manufacturersimports');
//                } else {
//                    $output_doc = "<span class='plugin_manufacturersimports_import_KO'>"
//                                  . __('Problem during the importation', 'manufacturersimports');
//                    if (!empty($data["date_import"])) {
//                        $output_doc .= " (" . Html::convdate($data["date_import"]) . ")";
//                    }
//                    $output_doc .= "</span>";
//                }
//            } else {
//                $output_doc = "<span class='plugin_manufacturersimports_import_OK'>"
//                              . __('Already imported', 'manufacturersimports');
//                if (!empty($line["date_import"])) {
//                    $output_doc .= " (" . Html::convdate($line["date_import"]) . ")";
//                }
//                $output_doc .= "</span>";
//            }
//            echo Search::showItem($output_type, $output_doc, $item_num, $row_num);
//            //no associated doc
//            echo $supplier->showDocItem($output_type, $item_num, $row_num, $line["documents_id"]);
//        }
//    }

    /**
     * Prints search form
     *
     * @param $manufacturer
     * @param $type
     *
     * @return
     *
     */
    public static function searchForm($params)
    {
        global $DB;

        $p = [
            'itemtype'         => '',
            'manufacturers_id' => '',
            'imported'         => '',
        ];

        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }

        $criteria = [
            'SELECT'  => '*',
            'FROM'    => 'glpi_plugin_manufacturersimports_configs',
            'WHERE'   => [
                'glpi_plugin_manufacturersimports_configs.manufacturers_id' => ['>', 0],
            ],
            'ORDERBY' => [
                'glpi_plugin_manufacturersimports_configs.entities_id',
                'glpi_plugin_manufacturersimports_configs.name',
            ],
        ];
        $criteria['WHERE'] += getEntitiesRestrictCriteria('glpi_plugin_manufacturersimports_configs');

        $manufacturer_opts = [];
        foreach ($DB->request($criteria) as $data) {
            $name = $data['name'];
            if (empty($data['name']) || $_SESSION['glpiis_ids_visible']) {
                $name .= ' (' . $data['id'] . ')';
            }
            $manufacturer_opts[$data['id']] = $name;
        }

        $type_opts = [];
        foreach (Config::getTypes() as $type) {
            $item              = new $type();
            $type_opts[$type]  = $item::getTypeName();
        }
        asort($type_opts);

        $imported_opts = [
            self::NOT_IMPORTED => __('Devices not imported', 'manufacturersimports'),
            self::IMPORTED     => __('Devices already imported', 'manufacturersimports'),
        ];

        TemplateRenderer::getInstance()->display('@manufacturersimports/search_form.html.twig', [
            'target'           => PLUGIN_MANUFACTURERSIMPORTS_WEBDIR . '/front/import.php',
            'config_url'       => PLUGIN_MANUFACTURERSIMPORTS_WEBDIR . '/front/config.form.php',
            'has_configs'      => count($manufacturer_opts) > 0,
            'can_config'       => Session::haveRight('config', UPDATE),
            'itemtype'         => $p['itemtype'],
            'manufacturers_id' => $p['manufacturers_id'],
            'imported'         => $p['imported'],
            'type_opts'        => $type_opts,
            'manufacturer_opts' => $manufacturer_opts,
            'imported_opts'    => $imported_opts,
        ]);

        return true;
    }

    /**
     * Prints display pre import
     *
     */
    public static function seePreImport($params)
    {
        global $DB;

        $p = [
            'link'             => [],
            'field'            => [],
            'contains'         => [],
            'searchtype'       => [],
            'sort'             => '1',
            'order'            => 'ASC',
            'start'            => 0,
            'export_all'       => 0,
            'link2'            => '',
            'contains2'        => '',
            'field2'           => '',
            'itemtype2'        => '',
            'searchtype2'      => '',
            'itemtype'         => '',
            'manufacturers_id' => '',
            'imported'         => '',
        ];

        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }

        if (!$p['itemtype'] || !$p['manufacturers_id']) {
            return;
        }

        $config = new Config();
        $config->getFromDB($p['manufacturers_id']);
        $suppliername  = $config->fields['name'] ?? '';
        $supplierclass = 'GlpiPlugin\\Manufacturersimports\\' . $suppliername;
        $supplier      = new $supplierclass();

        $infocom = new Infocom();
        $canedit = Session::haveRight(static::$rightname, UPDATE) && $infocom->canUpdate();

        $p['start'] = (int) ($p['start'] ?? 0);
        $toview     = ['name' => 1];
        $query      = self::queryImport($p, $config, $toview);
        $result     = $DB->doQuery($query);
        $numrows    = $DB->numrows($result);

        $LIST_LIMIT  = $_SESSION['glpilist_limit'];
        $end_display = $p['start'] + $LIST_LIMIT;
        $target      = PLUGIN_MANUFACTURERSIMPORTS_WEBDIR . '/front/import.php';
        $parameters  = 'itemtype=' . $p['itemtype']
                       . '&manufacturers_id=' . $p['manufacturers_id']
                       . '&imported=' . $p['imported'];

        if ($p['start'] >= $numrows) {
            TemplateRenderer::getInstance()->display('@manufacturersimports/pre_import_list.html.twig', [
                'no_results' => true,
            ]);
            return;
        }

        $has_doc_col = ($supplier->showDocTitle(Search::HTML_OUTPUT, 1) !== false);

        $columns = [];
        if ($canedit) {
            $columns['_check'] = '';
        }
        $columns['name'] = __('Name');
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = __('Entity');
        }
        $columns['serial']   = __('Serial number');
        $columns['model']    = __('Model Number', 'manufacturersimports');
        $columns['infocom']  = __('Financial and administrative information');
        $columns['supplier'] = __('Supplier attached', 'manufacturersimports');
        $columns['warranty'] = __('New warranty attached', 'manufacturersimports');
        $columns['link']     = _n('Link', 'Links', 1);
        $columns['status']   = _n('Status', 'Statuses', 1);
        if ($has_doc_col) {
            $columns['document'] = __('File');
        }

        $formatters = array_fill_keys(array_keys($columns), 'raw_html');

        if ($p['start'] > 0) {
            $DB->dataSeek($result, $p['start']);
        }

        $entries = [];
        $total   = 0;
        $comp_id = 0;
        $i       = $p['start'];

        while ($i < $numrows && $i < $end_display) {
            $i++;
            $line             = $DB->fetchArray($result);
            $line['itemtype'] = $p['itemtype'];
            $comp_id          = $line['id'];

            $entries[] = self::buildRowEntry(
                $line,
                $config->fields,
                $supplier,
                $canedit,
                $has_doc_col,
                (int) $p['imported']
            );

            if ((int) $p['imported'] === self::NOT_IMPORTED) {
                $total++;
            }
        }

        TemplateRenderer::getInstance()->display('@manufacturersimports/pre_import_list.html.twig', [
            'no_results'       => false,
            'columns'          => $columns,
            'formatters'       => $formatters,
            'entries'          => $entries,
            'total_number'     => count($entries),
            'filtered_number'  => count($entries),
            'total_devices'    => $total,
            'canedit'          => $canedit,
            'start'            => $p['start'],
            'numrows'          => $numrows,
            'target'           => $target,
            'parameters'       => $parameters,
            'suppliername'     => $suppliername,
            'comp_id'          => $comp_id,
            'itemtype'         => $p['itemtype'],
            'manufacturers_id' => (int) $p['manufacturers_id'],
            'imported'         => (int) $p['imported'],
            'list_limit'       => $LIST_LIMIT,
            'max_input_vars'   => Toolbox::get_max_input_vars(),
            'plugin_webdir'    => PLUGIN_MANUFACTURERSIMPORTS_WEBDIR,
        ]);
    }

    /**
     * Build a datatable row entry for one device.
     */
    private static function buildRowEntry(
        array $line,
        array $config_fields,
        Manufacturer $supplier,
        bool $canedit,
        bool $has_doc_col,
        int $imported
    ): array {
        $suppliername      = $config_fields['name'];
        $supplierUrl       = $config_fields['supplier_url'];
        $supplierId        = $config_fields['suppliers_id'];
        $supplierWarranty  = $config_fields['warranty_duration'];
        $supplierkey       = $config_fields['supplier_key'];
        $supplierkeysecret = $config_fields['supplier_secret'];

        $otherSerial   = '';
        $modelitemtype = $line['itemtype'] . 'Model';
        if (class_exists($modelitemtype)) {
            $dbu        = new DbUtils();
            $modelfield = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($modelitemtype));
            $models_id  = $line[$modelfield] ?? 0;
            if ($models_id != 0) {
                $modelclass  = new $modelitemtype();
                $modelclass->getFromDB($models_id);
                $otherSerial = $modelclass->fields['product_number'] ?? '';
            }
        }

        $entry = [];

        if ($canedit) {
            $sel             = (isset($_GET['select']) && $_GET['select'] === 'all') ? 'checked' : '';
            $entry['_check'] = $supplier->showCheckbox($line['id'], $sel, $otherSerial);
        }

        $link      = Toolbox::getItemTypeFormURL($line['itemtype']);
        $id_suffix = ($_SESSION['glpiis_ids_visible'] || empty($line['name']))
            ? ' (' . $line['id'] . ')'
            : '';
        $entry['name'] = "<a href='" . $link . "?id=" . $line['id'] . "'>"
                         . htmlescape($line['name'] ?? '') . $id_suffix . '</a><br>'
                         . htmlescape($line['model_name'] ?? '');

        if (Session::isMultiEntitiesMode()) {
            $entry['entity'] = Dropdown::getDropdownName('glpi_entities', $line['entities_id']);
        }

        $entry['serial'] = htmlescape($line['serial'] ?? '');
        $entry['model']  = htmlescape($otherSerial);

        $ic        = new Infocom();
        $ic_loaded = $ic->getFromDBforDevice($line['itemtype'], $line['id']);
        $output_ic = '';
        if ($ic_loaded) {
            $output_ic .= _n('Supplier', 'Suppliers', 1) . ': '
                          . Dropdown::getDropdownName('glpi_suppliers', $ic->fields['suppliers_id']) . '<br>';
            $output_ic .= __('Date of purchase') . ': ' . Html::convdate($ic->fields['buy_date']) . '<br>';
            $output_ic .= __('Start date of warranty') . ': ' . Html::convdate($ic->fields['warranty_date']) . '<br>';
            if ($ic->fields['warranty_duration'] == -1) {
                $output_ic .= __('Warranty duration') . ': ' . __('Lifelong') . '<br>';
            } else {
                $output_ic .= __('Warranty duration') . ': ' . $ic->fields['warranty_duration'] . ' ' . __('month') . '<br>';
            }
            $tmpdat     = Infocom::getWarrantyExpir($ic->fields['warranty_date'], $ic->fields['warranty_duration']);
            $output_ic .= sprintf(__('Valid to %s'), $tmpdat);
        }
        $entry['infocom'] = $output_ic;

        if ($imported !== self::IMPORTED) {
            ob_start();
            if (Session::isMultiEntitiesMode() && $supplierId) {
                $item = new Supplier();
                $item->getFromDB($supplierId);
                if ($item->fields['is_recursive'] || $item->fields['entities_id'] == $line['entities_id']) {
                    Dropdown::show('Supplier', [
                        'name'     => 'to_suppliers_id' . $line['id'],
                        'value'    => $supplierId,
                        'comments' => 0,
                        'entity'   => $line['entities_id'],
                    ]);
                } else {
                    echo "<span class='plugin_manufacturersimports_import_KO'>";
                    echo __('The choosen supplier is not recursive', 'manufacturersimports');
                    echo '</span>';
                    echo Html::hidden('to_suppliers_id' . $line['id'], ['value' => -1]);
                }
            } else {
                Dropdown::show('Supplier', [
                    'name'     => 'to_suppliers_id' . $line['id'],
                    'value'    => $supplierId,
                    'comments' => 0,
                    'entity'   => $line['entities_id'],
                ]);
            }
            $entry['supplier'] = ob_get_clean();

            ob_start();
            $supplier->showWarrantyItem($line['id'], $supplierWarranty);
            $warranty_html = ob_get_clean();
            if (preg_match('/<td[^>]*>(.*)<\/td>/s', $warranty_html, $m)) {
                $warranty_html = $m[1];
            }
            $entry['warranty'] = $warranty_html;
        } else {
            $entry['supplier'] = $ic_loaded
                ? Dropdown::getDropdownName('glpi_suppliers', $ic->fields['suppliers_id'])
                : '';
            $entry['warranty'] = $ic_loaded
                ? (($ic->fields['warranty_duration'] == -1)
                    ? __('Lifelong')
                    : (string) $ic->fields['warranty_duration'])
                : '';
        }

        $url = self::selectSupplier(
            $suppliername, $supplierUrl, $line['serial'], $otherSerial, $supplierkey, $supplierkeysecret
        );
        if ($suppliername === Config::LENOVO) {
            $url = self::selectSupplier(
                $suppliername, $supplierUrl, $line['serial'], $otherSerial, $supplierkey, $supplierkeysecret, true
            );
        }
        $entry['link'] = "<a href='" . htmlescape($url) . "' target='_blank'>"
                         . __('Manufacturer information', 'manufacturersimports') . '</a>';

        if ($imported !== self::IMPORTED) {
            if (($line['import_status'] ?? 0) != 2) {
                $entry['status'] = __('Not yet imported', 'manufacturersimports');
            } else {
                $entry['status'] = "<span class='plugin_manufacturersimports_import_KO'>"
                                   . __('Problem during the importation', 'manufacturersimports');
                if (!empty($line['date_import'])) {
                    $entry['status'] .= ' (' . Html::convdate($line['date_import']) . ')';
                }
                $entry['status'] .= '</span>';
            }
        } else {
            $entry['status'] = "<span class='plugin_manufacturersimports_import_OK'>"
                               . __('Already imported', 'manufacturersimports');
            if (!empty($line['date_import'])) {
                $entry['status'] .= ' (' . Html::convdate($line['date_import']) . ')';
            }
            $entry['status'] .= '</span>';
        }

        if ($has_doc_col) {
            $doc_html = $supplier->showDocItem(Search::HTML_OUTPUT, 1, 1, $line['documents_id'] ?? null);
            if (preg_match('/<td[^>]*>(.*)<\/td>/s', $doc_html, $m)) {
                $doc_html = $m[1];
            }
            $entry['document'] = $doc_html;
        }

        return $entry;
    }

    /**
     * show arrow for massives actions : opening
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
                    echo "value=\"" . htmlescape($label) . "\" class='submit btn btn-primary'>&nbsp;";
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
        if (!in_array($p['itemtype'], Config::$types, true)) {
            return '';
        }
        $p['manufacturers_id'] = (int) $p['manufacturers_id'];

        $dbu = new DbUtils();

        $modeltable = $dbu->getTableForItemType($p['itemtype'] . "Model");
        $modelfield = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($p['itemtype'] . "Model"));
        $item       = getItemForItemtype($p['itemtype']);
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
     * @param $itemtype
     * @param $ID
     * @param $order
     * @param $key
     *
     * @return string string
     *
     **/
    public static function addOrderBy($itemtype, $id, $order, $key = 0)
    {
        global $CFG_GLPI;

        // Security test for order
        if ($order != "ASC") {
            $order = "DESC";
        }
        $searchopt = Search::getOptions($itemtype);

        $table = $searchopt[$id]["table"];
        $field = $searchopt[$id]["field"];

        $addtable = '';
        $dbu      = new DbUtils();

        if ($table != $dbu->getTableForItemType($itemtype)
            && $searchopt[$id]["linkfield"] != $dbu->getForeignKeyFieldForTable($table)) {
            $addtable .= "_" . $searchopt[$id]["linkfield"];
        }

        if (isset($searchopt[$id]['joinparams'])) {
            $complexjoin = Search::computeComplexJoinID($searchopt[$id]['joinparams']);

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
            echo "<a href='$target?$parameters&start=0'>";
            echo "<i style='font-size: 2em;' class='ti ti-chevrons-left' title=\""
                 . __s('Start') . "\"></i>";
            echo "</a></th>";
            echo "<th class='left'>";
            echo "<a href='$target?$parameters&start=$back'>";
            echo "<i style='font-size: 2em;' class='ti ti-chevron-left' title=\""
                 . __s('Previous') . "\"></i>";
            echo "</a></th>";
        }

        // Print the "where am I?"
        echo "<td width='50%'  class='tab_bg_2'>";
        Html::printPagerForm("$target?$parameters&start=$start");
        echo "</td>\n";

        echo "<td width='50%' class='tab_bg_2 b'>";
        //TRANS: %1$d, %2$d, %3$d are page numbers
        printf(__('From %1$d to %2$d on %3$d'), $current_start, $current_end, $numrows);
        echo "</td>\n";

        // Forward and fast forward button
        if ($forward < $numrows) {
            echo "<th class='right'>";
            echo "<a href='$target?$parameters&start=$forward'>";
            echo "<i style='font-size: 2em;' class='ti ti-chevron-right' title=\""
                 . __s('Next') . "\"></i>";
            echo "</a></th>\n";

            echo "<th class='right'>";
            echo "<a href='$target?$parameters&start=$end'>";
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
                $out .= "&" . $name . "[$key]=" . urlencode($val);
            }
        }
        return $out;
    }
}
