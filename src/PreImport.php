<?php

/**
 * -------------------------------------------------------------------------
 * manufacturersimports plugin for GLPI
 * Copyright (C) 2015-2026 by the manufacturersimports Development Team.
 *
 * https://github.com/InfotelGLPI/manufacturersimports
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of manufacturersimports.
 *
 * manufacturersimports is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * manufacturersimports is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with manufacturersimports. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Manufacturersimports;

use CommonDBTM;
use DBmysqlIterator;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Infocom;
use Glpi\DBAL\QueryExpression;
use Search;
use Session;
use Supplier;
use GlpiPlugin\Manufacturersimports\Manufacturers\Manufacturer;
use Toolbox;

/**
 * Class PreImport
 */
class PreImport extends CommonDBTM
{
    public static string $rightname = "plugin_manufacturersimports";

    public const IMPORTED     = 2;
    public const NOT_IMPORTED = 1;
    public const IMPORT_ERROR = 3;

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
    //    public static function showAllItems($myname, $value_type = 0, $value = 0, $entity_restrict = -1, $types = '')
    //    {
    //        if (!is_array($types)) {
    //            $types = Config::getTypes();
    //        }
    //
    //        $rand    = mt_rand();
    //        $options = [];
    //
    //        foreach ($types as $type) {
    //            $item           = new $type();
    //            $options[$type] = $item::getTypeName();
    //        }
    //        asort($options);
    //        if (count($options)) {
    //            $id = "item_type$rand";
    //            Dropdown::showFromArray($myname, $options, ['value'               => $value,
    //                'id'                  => $id,
    //                'display_emptychoice' => true]);
    //        }
    //    }

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
        // Resolve against the whitelist: the name is config data, never trust it in `new`.
        $supplierclass = Config::resolveSupplierClass($suppliername);
        if ($supplierclass !== null) {
            $supplier      = new $supplierclass();
            $infos         = $supplier->getSupplierInfo(
                $compSerial,
                $otherserial,
                $supplierkey,
                $supplierSecret,
                $supplierUrl,
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
        // Resolve against the whitelist: the name is config data, never trust it in `new`.
        $supplierclass = Config::resolveSupplierClass($suppliername);
        if ($supplierclass !== null) {
            $supplier      = new $supplierclass();
            $infos         = $supplier->getSupplierInfo(
                $compSerial,
                $otherserial,
                $supplierkey,
                $supplierSecret,
                $supplierUrl,
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
        // Resolve against the whitelist: the name is config data, never trust it in `new`.
        $supplierclass = Config::resolveSupplierClass($suppliername);
        if ($supplierclass !== null) {
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
        // Resolve against the whitelist: the name is config data, never trust it in `new`.
        $supplierclass = Config::resolveSupplierClass($suppliername);
        if ($supplierclass !== null) {
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
        // Resolve against the whitelist: the name is config data, never trust it in `new`.
        $supplierclass = Config::resolveSupplierClass($suppliername);
        if ($supplierclass !== null) {
            $supplier      = new $supplierclass();
            $infos         = $supplier->getSupplierInfo($compSerial, $otherserial, $supplierkey, $supplierSecret);
            if (isset($infos['post'])) {
                $post = $infos['post'];
            }
        }
        return $post;
    }


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
            self::IMPORT_ERROR => __('Devices with import error', 'manufacturersimports'),
        ];

        TemplateRenderer::getInstance()->display('@manufacturersimports/search_form.html.twig', [
            'target'           => PLUGIN_MANUFACTURERSIMPORTS_WEBDIR . '/front/import.php',
            'config_url'       => PLUGIN_MANUFACTURERSIMPORTS_WEBDIR . '/front/config.form.php',
            'has_configs'      => count($manufacturer_opts) > 0,
            'can_config'       => Session::haveRight(\Config::$rightname, UPDATE),
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

        // The config id comes from the request: check the entity perimeter before
        // listing items against another entity's configuration.
        $config = Config::getCheckedConfig($p['manufacturers_id']);
        if ($config === null) {
            return;
        }
        $suppliername = $config->fields['name'] ?? '';
        // Guard against an unexpected config name that maps to no manufacturer
        // class, which would otherwise fatal on instantiation: resolveSupplierClass()
        // combines the whitelist and the existence check in a single place.
        $supplierclass = Config::resolveSupplierClass($suppliername);
        if ($supplierclass === null) {
            return;
        }
        $supplier = new $supplierclass();

        $infocom = new Infocom();
        $canedit = Session::haveRight(static::$rightname, UPDATE) && $infocom->canUpdate();

        $p['start'] = (int) ($p['start'] ?? 0);
        $toview     = ['name' => 1];
        $iterator   = self::queryImport($p, $config, $toview);
        $numrows    = count($iterator);

        $LIST_LIMIT  = $_SESSION['glpilist_limit'];
        $end_display = $p['start'] + $LIST_LIMIT;
        $target      = PLUGIN_MANUFACTURERSIMPORTS_WEBDIR . '/front/import.php';
        // Build a well-formed query string for the pager links (components/pager.html.twig):
        // itemtype is url-encoded and the numeric filters are cast to int.
        $parameters  = 'itemtype=' . rawurlencode($p['itemtype'])
                       . '&manufacturers_id=' . (int) $p['manufacturers_id']
                       . '&imported=' . (int) $p['imported'];

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

        if ($p['start'] > 0 && $numrows > 0) {
            $iterator->seek($p['start']);
        } else {
            $iterator->rewind();
        }

        $entries = [];
        $total   = 0;
        $comp_id = 0;
        $i       = $p['start'];

        while ($iterator->valid() && $i < $end_display) {
            $line             = $iterator->current();
            $iterator->next();
            $i++;
            $line['itemtype'] = $p['itemtype'];
            $comp_id          = $line['id'];

            $entries[] = self::buildRowEntry(
                $line,
                $config->fields,
                $supplier,
                $canedit,
                $has_doc_col,
                (int) $p['imported'],
            );

            if ((int) $p['imported'] !== self::IMPORTED) {
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
            'can_import'       => (int) $p['imported'] === self::NOT_IMPORTED,
            'empty_value'      => Dropdown::EMPTY_VALUE,
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
        $supplierkey       = Config::decryptSecret($config_fields['supplier_key']);
        $supplierkeysecret = Config::decryptSecret($config_fields['supplier_secret']);

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

        $link      = Config::getItemFormLink($line['itemtype'], (int) $line['id']);
        $id_suffix = ($_SESSION['glpiis_ids_visible'] || empty($line['name']))
            ? ' (' . $line['id'] . ')'
            : '';
        $entry['name'] = "<a href='" . htmlescape($link) . "'>"
                         . htmlescape($line['name'] ?? '') . $id_suffix . '</a><br>'
                         . htmlescape($line['model_name'] ?? '');

        if (Session::isMultiEntitiesMode()) {
            // raw_html formatter: getDropdownName() returns the unescaped DB value,
            // so escape it here to prevent stored XSS via a crafted entity name.
            $entry['entity'] = htmlescape(Dropdown::getDropdownName('glpi_entities', $line['entities_id']));
        }

        $entry['serial'] = htmlescape($line['serial'] ?? '');
        $entry['model']  = htmlescape($otherSerial);

        $ic        = new Infocom();
        $ic_loaded = $ic->getFromDBforDevice($line['itemtype'], $line['id']);
        $output_ic = '';
        if ($ic_loaded) {
            $output_ic .= _n('Supplier', 'Suppliers', 1) . ': '
                          . htmlescape(Dropdown::getDropdownName('glpi_suppliers', $ic->fields['suppliers_id'])) . '<br>';
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
            $supplier_usable = true;
            if (Session::isMultiEntitiesMode() && $supplierId) {
                $item = new Supplier();
                $item->getFromDB($supplierId);
                $supplier_usable = $item->fields['is_recursive']
                                   || $item->fields['entities_id'] == $line['entities_id'];
            }
            if ($supplier_usable) {
                $entry['supplier'] = Dropdown::show(Supplier::class, [
                    'name'     => 'to_suppliers_id' . $line['id'],
                    'value'    => $supplierId,
                    'comments' => 0,
                    'entity'   => $line['entities_id'],
                    'display'  => false,
                ]);
            } else {
                $entry['supplier'] = "<span class='plugin_manufacturersimports_import_KO'>"
                                     . htmlescape(__('The choosen supplier is not recursive', 'manufacturersimports'))
                                     . '</span>'
                                     . Html::hidden('to_suppliers_id' . $line['id'], ['value' => -1]);
            }

            $entry['warranty'] = $supplier->getWarrantyItem($line['id']);
        } else {
            $entry['supplier'] = $ic_loaded
                ? htmlescape(Dropdown::getDropdownName('glpi_suppliers', $ic->fields['suppliers_id']))
                : '';
            $entry['warranty'] = $ic_loaded
                ? (($ic->fields['warranty_duration'] == -1)
                    ? __('Lifelong')
                    : (string) $ic->fields['warranty_duration'])
                : '';
        }

        $url = self::selectSupplier(
            $suppliername,
            $supplierUrl,
            $line['serial'],
            $otherSerial,
            $supplierkey,
            $supplierkeysecret,
        );
        if ($suppliername === Config::LENOVO) {
            $url = self::selectSupplier(
                $suppliername,
                $supplierUrl,
                $line['serial'],
                $otherSerial,
                $supplierkey,
                $supplierkeysecret,
                true,
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
     * Request
     *
     * @param $p
     * @param $config
     * @param $toview
     *
     * @return DBmysqlIterator
     */
    public static function queryImport($p, $config, $toview, $isCron = false): DBmysqlIterator
    {
        global $DB;

        $dbu = new DbUtils();

        if (!in_array($p['itemtype'], Config::getTypes(true), true)) {
            return $DB->request(['FROM' => Config::getTable(), 'LIMIT' => 0]);
        }

        $modeltable = $dbu->getTableForItemType($p['itemtype'] . 'Model');
        $modelfield = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($p['itemtype'] . 'Model'));
        $item       = getItemForItemtype($p['itemtype']);
        $itemtable  = $dbu->getTableForItemType($p['itemtype']);
        $p['manufacturers_id'] = (int) $p['manufacturers_id'];

        $where = [
            "$itemtable.is_deleted"                          => 0,
            "$itemtable.is_template"                         => 0,
            'glpi_plugin_manufacturersimports_configs.id'    => $p['manufacturers_id'],
            ["$itemtable.serial"                             => ['!=', '']],
        ];

        if ($p['imported'] == self::IMPORTED) {
            $where[] = ['glpi_plugin_manufacturersimports_logs.import_status' => 1];
        } elseif ($p['imported'] == self::NOT_IMPORTED) {
            if ($isCron) {
                // Cron retries previously failed imports as well
                $where[] = ['OR' => [
                    ['glpi_plugin_manufacturersimports_logs.date_import'    => null],
                    ['glpi_plugin_manufacturersimports_logs.import_status'  => 2],
                ]];
            } else {
                $where[] = ['glpi_plugin_manufacturersimports_logs.date_import' => null];
            }
        } elseif ($p['imported'] == self::IMPORT_ERROR) {
            $where[] = ['glpi_plugin_manufacturersimports_logs.import_status' => 2];
        }

        if (!$isCron) {
            $where = array_merge(
                $where,
                $dbu->getEntitiesRestrictCriteria($itemtable, '', '', $item->maybeRecursive()),
            );
        }

        // Every custom asset definition shares glpi_assets_assets: without its
        // own criteria the listing would mix all the custom asset types
        // together. Classic itemtypes return none, so this is a no-op there.
        $where += $p['itemtype']::getSystemSQLCriteria($itemtable);

        $order = new QueryExpression("`entities_id`,`$itemtable`.`name`");
        foreach ($toview as $key => $val) {
            if ($p['sort'] == $val) {
                $raw   = trim(self::addOrderBy($p['itemtype'], $p['sort'], $p['order'], $key));
                $order = new QueryExpression(preg_replace('/^ORDER BY\s+/i', '', $raw));
            }
        }

        return $DB->request([
            'SELECT'    => [
                "$itemtable.id",
                "$itemtable.name",
                "$itemtable.serial",
                "$itemtable.$modelfield",
                "$itemtable.entities_id",
                'glpi_plugin_manufacturersimports_logs.import_status',
                'glpi_plugin_manufacturersimports_logs.items_id',
                'glpi_plugin_manufacturersimports_logs.itemtype',
                'glpi_plugin_manufacturersimports_logs.documents_id',
                'glpi_plugin_manufacturersimports_logs.date_import',
                new QueryExpression($DB::quoteValue($p['itemtype']) . ' AS `type`'),
                "$modeltable.name AS model_name",
            ],
            'FROM'      => $itemtable,
            'LEFT JOIN' => [
                $modeltable => [
                    'ON' => [$modeltable => 'id', $itemtable => $modelfield],
                ],
                'glpi_entities' => [
                    'ON' => ['glpi_entities' => 'id', $itemtable => 'entities_id'],
                ],
                'glpi_plugin_manufacturersimports_configs' => [
                    'ON' => [
                        'glpi_plugin_manufacturersimports_configs' => 'manufacturers_id',
                        $itemtable                                 => 'manufacturers_id',
                    ],
                ],
                'glpi_plugin_manufacturersimports_logs' => [
                    'ON' => [
                        'glpi_plugin_manufacturersimports_logs' => 'items_id',
                        $itemtable                              => 'id',
                        ['AND' => ['glpi_plugin_manufacturersimports_logs.itemtype' => $p['itemtype']]],
                    ],
                ],
            ],
            'WHERE'     => $where,
            'ORDER'     => $order,
        ]);
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
