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

use Ajax;
use CommonDBTM;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Exception\Http\AccessDeniedHttpException;
use GLPIKey;
use Html;
use Infocom;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Config
 */
class Config extends CommonDBTM
{
    public static $rightname = "plugin_manufacturersimports";
    public static $types     = ['Computer', 'Monitor',
        'NetworkEquipment',
        'Peripheral', 'Printer'];
    public $dohistory = true;

    /**
     * Secret fields stored encrypted at rest. Kept out of the history log so the
     * ciphertext never lands in glpi_logs.
     */
    public $history_blacklist = ['supplier_key', 'supplier_secret'];

    /**
     * Marker prefixing every GLPIKey-encrypted secret. Lets us tell an encrypted
     * value apart from a legacy plaintext one without ambiguity, so decryption is
     * self-migrating and never emits sodium warnings on old rows.
     */
    private const SECRET_PREFIX = 'crypt:';

    /**
     * Encrypt a secret value for storage. Empty values stay empty.
     *
     * @param string $value plaintext secret
     *
     * @return string prefixed ciphertext, or '' when empty
     */
    public static function encryptSecret(string $value): string
    {
        if ($value === '') {
            return '';
        }
        return self::SECRET_PREFIX . (new GLPIKey())->encrypt($value);
    }

    /**
     * Decrypt a stored secret. Values written before encryption was introduced
     * (no prefix) are returned as-is so existing configurations keep working.
     *
     * @param string|null $value stored value
     *
     * @return string plaintext secret
     */
    public static function decryptSecret(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (!str_starts_with($value, self::SECRET_PREFIX)) {
            // Legacy plaintext row, not yet migrated.
            return $value;
        }
        return (string) (new GLPIKey())->decrypt(substr($value, strlen(self::SECRET_PREFIX)));
    }

    /**
     * Validate that a URL is safe to fetch server-side (SSRF mitigation).
     *
     * Only https is accepted and the host must not resolve to a private,
     * loopback, link-local or otherwise reserved IP range — this blocks
     * requests to internal services (localhost, 169.254.169.254, 10.0.0.0/8...).
     * Callers must additionally disable HTTP redirect following so a public host
     * cannot bounce the request to an internal target.
     *
     * @param string $url URL to validate
     *
     * @return bool true when the URL is safe to reach out to
     */
    public static function isSafeApiUrl(string $url): bool
    {
        return self::resolveSafeIps($url) !== false;
    }

    /**
     * Resolve a URL's host and return the list of IPs it maps to, but only when
     * every one of them is public. Returns false when the URL is not https, is
     * malformed, cannot be resolved, or resolves to any private/reserved range.
     *
     * @param string $url URL to validate
     *
     * @return string[]|false Validated public IPs, or false when unsafe
     */
    private static function resolveSafeIps(string $url)
    {
        $url = trim($url);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false
            || ($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])) {
            return false;
        }

        $host = $parts['host'];

        // Collect every IP the host maps to.
        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $record) {
                    if (isset($record['ip'])) {
                        $ips[] = $record['ip'];
                    } elseif (isset($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }
        }

        if (empty($ips)) {
            // Host could not be resolved: refuse rather than let curl resolve it.
            return false;
        }

        foreach ($ips as $ip) {
            if (filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) === false) {
                return false;
            }
        }

        return $ips;
    }

    /**
     * Build CURLOPT_RESOLVE entries pinning the URL host to the exact IP(s)
     * validated by resolveSafeIps(). This closes the DNS-rebinding TOCTOU window:
     * without it, curl performs its own second resolution and could reach an
     * internal IP that looked public at validation time.
     *
     * Returns [] when the URL is unsafe or when a proxy is configured (the proxy
     * resolves the host itself, so client-side pinning would have no effect).
     *
     * @param string $url URL to fetch server-side
     *
     * @return string[] CURLOPT_RESOLVE entries, e.g. ['api.example.com:443:203.0.113.5']
     */
    public static function getPinnedResolve(string $url): array
    {
        global $CFG_GLPI;

        if (!empty($CFG_GLPI['proxy_name'])) {
            return [];
        }

        $ips = self::resolveSafeIps($url);
        if ($ips === false) {
            return [];
        }

        $parts = parse_url($url);
        $host  = $parts['host'] ?? '';
        $port  = $parts['port'] ?? 443;

        $entries = [];
        foreach ($ips as $ip) {
            // Bracket IPv6 literals as required by the HOST:PORT:ADDRESS format.
            $addr      = (strpos($ip, ':') !== false) ? '[' . $ip . ']' : $ip;
            $entries[] = $host . ':' . $port . ':' . $addr;
        }

        return $entries;
    }

    //Manufacturers constants
    public const DELL        = "Dell";
    public const LENOVO      = "Lenovo";
    public const HP          = "HP";
    public const FUJITSU     = "Fujitsu";
    public const TOSHIBA     = "Toshiba";
    public const WORTMANN_AG = "Wortmann_ag";

    /**
     * Single source of truth for the supported manufacturer names.
     *
     * @return string[]
     */
    public static function getAllowedSuppliers(): array
    {
        return [self::DELL, self::HP, self::FUJITSU, self::LENOVO, self::TOSHIBA, self::WORTMANN_AG];
    }

    /**
     * Resolve a Manufacturers\* class from a supplier name. The name is free-form
     * config data (editable by any user holding the plugin right), so it must be
     * validated against the whitelist AND existence-checked before it is used in a
     * `new $class()` expression, otherwise a bogus name triggers a fatal error.
     *
     * @param string $suppliername
     * @return class-string|null the fully-qualified class name, or null if invalid
     */
    public static function resolveSupplierClass($suppliername): ?string
    {
        if (empty($suppliername) || !in_array($suppliername, self::getAllowedSuppliers(), true)) {
            return null;
        }
        $class = "GlpiPlugin\\Manufacturersimports\\Manufacturers\\" . $suppliername;
        return class_exists($class) ? $class : null;
    }

    /**
     * Load a plugin configuration after checking the caller may read it from its
     * own entity perimeter. The config id always comes from the request, and the
     * rows are entity-scoped: holding the plugin right in one entity says nothing
     * about another entity's row, whose (decrypted) API credentials would
     * otherwise be used for the outbound manufacturer call.
     *
     * @param mixed $configID
     * @return self|null the loaded config, or null when it is unreadable here
     */
    public static function getCheckedConfig($configID): ?self
    {
        $config = new self();
        // can() loads the row itself and returns false when it does not exist
        // or falls outside the caller's entities.
        if (!$config->can((int) $configID, READ)) {
            return null;
        }
        return $config;
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Manufacturer', 'Manufacturers', $nb);
    }

    public static function getIcon()
    {
        return Menu::getIcon();
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }

    /**
     * Preconfig datas for standard system
     *
     * @param $type type of standard system : AD
     *
     * @return nothing
     **/
    public function preconfig($suppliername)
    {
        switch ($suppliername) {
            case self::DELL:
            case self::HP:
            case self::FUJITSU:
            case self::LENOVO:
            case self::TOSHIBA:
            case self::WORTMANN_AG:
                // Whitelisted above by the switch, but existence-check before `new`
                // to stay fatal-safe if a manufacturer class is ever renamed/removed.
                $supplierclass = self::resolveSupplierClass($suppliername);
                if ($supplierclass === null) {
                    $this->post_getEmpty();
                    break;
                }
                $supplier                     = new $supplierclass();
                $infos                        = $supplier->getSupplierInfo();
                $this->fields["name"]         = $infos["name"];
                $this->fields["supplier_url"] = $infos["supplier_url"];
                if ($suppliername == self::HP) {
                    $this->fields["token_url"] = $infos["token_url"];
                    $this->fields["warranty_url"] = $infos["warranty_url"];
                }
                if ($suppliername == self::DELL) {
                    $this->fields["token_url"] = $infos["token_url"];
                    $this->fields["warranty_url"] = $infos["warranty_url"];
                }

                if ($suppliername == self::HP || $suppliername == self::DELL) {
                    $this->fields["supplier_key"] = '123456789';
                    $this->fields["supplier_secret"] = '987654321';
                }
                break;
            default:
                $this->post_getEmpty();
                break;
        }
    }

    public function post_addItem()
    {
        global $DB;

        if ($this->fields["is_recursive"]) {
            $dbu      = new DbUtils();
            $criteria = array_merge(
                [
                    'name' => $this->fields["name"],
                    ['id' => ['<>', (int) $this->fields['id']]],
                ],
                $dbu->getEntitiesRestrictCriteria(
                    $this->getTable(),
                    '',
                    $dbu->getSonsOf("glpi_entities", $this->fields["entities_id"]),
                ),
            );
            $DB->delete($this->getTable(), $criteria);
        }
    }

    public function post_updateItem($history = 1)
    {
        global $DB;

        if ($this->fields["is_recursive"]) {
            $dbu      = new DbUtils();
            $criteria = array_merge(
                [
                    'name' => $this->fields["name"],
                    ['id' => ['<>', (int) $this->fields["id"]]],
                ],
                $dbu->getEntitiesRestrictCriteria(
                    $this->getTable(),
                    '',
                    $dbu->getSonsOf("glpi_entities", $this->fields["entities_id"]),
                ),
            );
            $DB->delete($this->getTable(), $criteria);
        }
    }

    public static function dropdownSupplier($name, $options = [])
    {
        $params['value']     = 0;
        $params['toadd']     = [];
        $params['on_change'] = '';

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $items = [];
        if (count($params['toadd']) > 0) {
            $items = $params['toadd'];
        }

        $items += self::getSuppliers();
        return Dropdown::showFromArray($name, $items, $params);
    }

    public static function getSuppliers()
    {
        $options[-1]                = Dropdown::EMPTY_VALUE;
        $options[self::DELL]        = self::DELL;
        $options[self::HP]          = self::HP;
        $options[self::FUJITSU]     = self::FUJITSU;
        $options[self::TOSHIBA]     = self::TOSHIBA;
        $options[self::LENOVO]      = self::LENOVO;
        $options[self::WORTMANN_AG] = self::WORTMANN_AG;
        return $options;
    }

    /**
     * Provides search options configuration. Do not rely directly
     * on this, @return array a *not indexed* array of search options
     *
     * @since 9.3
     *
     * This should be overloaded in Class
     *
     * @see CommonDBTM::searchOptions instead.
     *
     * @see https://glpi-developer-documentation.rtfd.io/en/master/devapi/search.html
     **/
    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'            => '2',
            'table'         => 'glpi_manufacturers',
            'field'         => 'name',
            'name'          => __('Manufacturer'),
            'datatype'      => 'dropdown',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => '3',
            'table'         => $this->getTable(),
            'field'         => 'supplier_url',
            'name'          => __('Manufacturer web address', 'manufacturersimports'),
            'datatype'      => 'weblink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => '4',
            'table'    => 'glpi_suppliers',
            'field'    => 'name',
            'name'     => __('Default supplier attached', 'manufacturersimports'),
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id'            => '5',
            'table'         => $this->getTable(),
            'field'         => 'warranty_duration',
            'name'          => __('New warranty attached', 'manufacturersimports'),
            'datatype'      => 'integer',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => '6',
            'table'         => $this->getTable(),
            'field'         => 'document_adding',
            'name'          => __('Auto add of document', 'manufacturersimports'),
            'datatype'      => 'bool',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => '7',
            'table'         => 'glpi_documentcategories',
            'field'         => 'name',
            'name'          => __('Document heading'),
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => '8',
            'table'    => $this->getTable(),
            'field'    => 'comment_adding',
            'name'     => __('Add a comment line', 'manufacturersimports'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id'    => '30',
            'table' => $this->getTable(),
            'field' => 'id',
            'name'  => __('ID'),
        ];

        $tab[] = [
            'id'       => '80',
            'table'    => 'glpi_entities',
            'field'    => 'completename',
            'name'     => __('Entity'),
            'datatype' => 'dropdown',
        ];

        return $tab;
    }

    public function showForm($ID, $options = [])
    {
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

        $preconfig      = (int) ($_GET['preconfig'] ?? -1);
        $preconfig_rand = mt_rand();
        $supplier_name  = $this->fields['name'] ?? '';

        $test_field  = '';
        $is_api_test = false;
        $test_label  = '';
        $test_icon   = '';
        $row_label   = '';
        $base_url    = '';
        $test_mode   = '';

        // Whitelist + class_exists() in one place, like every other call site.
        $supplier_class = self::resolveSupplierClass($supplier_name);
        if ($supplier_class !== null) {
            $supplier_obj = new $supplier_class();
            $test_field   = $supplier_obj->getTestUrlField();
            $is_api_test  = ($test_field === 'token_url');
            $test_label   = $is_api_test
                ? __('Test connection', 'manufacturersimports')
                : __('Test warranty page', 'manufacturersimports');
            $test_icon    = $is_api_test ? 'ti-plug-connected' : 'ti-world-www';
            $row_label    = $is_api_test
                ? __('API connection test', 'manufacturersimports')
                : __('Warranty page test', 'manufacturersimports');
            $base_url     = $this->fields[$test_field] ?? '';
            $test_mode    = $is_api_test ? 'oauth' : 'head';
        }

        // Only expose the decrypted API secrets to a caller allowed to edit this
        // config. showForm() is reachable with READ alone (front/config.form.php
        // display branch calls checkGlobal(READ)), so a read-only profile must not
        // receive the cleartext client_id/secret in the rendered form. Editors keep
        // the pre-filled value so prepareInputForUpdate()'s "empty = unchanged"
        // round-trip is preserved and the stored secret is never wiped on save.
        $can_edit = ($ID > 0) ? $this->can($ID, UPDATE) : static::canUpdate();

        TemplateRenderer::getInstance()->display('@manufacturersimports/config_form.html.twig', [
            'item'          => $this,
            'params'        => $options,
            'preconfig'     => $preconfig,
            'preconfig_rand' => $preconfig_rand,
            'is_new'        => ($ID <= 0),
            'test_field'    => $test_field,
            'is_api_test'   => $is_api_test,
            'test_label'    => $test_label,
            'test_icon'     => $test_icon,
            'row_label'     => $row_label,
            'base_url'      => $base_url,
            'test_mode'     => $test_mode,
            'action_url'    => self::getFormURL(true),
            'supplier_key_value'    => $can_edit ? self::decryptSecret($this->fields['supplier_key'] ?? '') : '',
            'supplier_secret_value' => $can_edit ? self::decryptSecret($this->fields['supplier_secret'] ?? '') : '',
        ]);

        return true;
    }

    public function prepareInputForAdd($input)
    {
        $allowed = [
            'id', 'name', 'supplier_url', 'token_url', 'warranty_url',
            'supplier_key', 'supplier_secret', 'manufacturers_id', 'suppliers_id',
            'documentcategories_id', 'document_adding', 'comment_adding',
            'warranty_duration', 'entities_id', 'is_recursive',
        ];
        $input = array_intersect_key($input, array_flip($allowed));

        $input += [
            'supplier_key'    => '',
            'supplier_secret' => '',
            'token_url'       => '',
            'warranty_url'    => '',
        ];

        // Encrypt secrets before they reach the database.
        $input['supplier_key']    = self::encryptSecret((string) $input['supplier_key']);
        $input['supplier_secret'] = self::encryptSecret((string) $input['supplier_secret']);

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $allowed = [
            'id', 'name', 'supplier_url', 'token_url', 'warranty_url',
            'supplier_key', 'supplier_secret', 'manufacturers_id', 'suppliers_id',
            'documentcategories_id', 'document_adding', 'comment_adding',
            'warranty_duration', 'entities_id', 'is_recursive',
        ];
        $input = array_intersect_key($input, array_flip($allowed));

        // Secrets arrive as plaintext from the form. Skip the field when it is
        // unchanged (compared against the decrypted stored value), otherwise
        // re-encrypt the new value.
        foreach (['supplier_key', 'supplier_secret'] as $secret_field) {
            if (!array_key_exists($secret_field, $input)) {
                continue;
            }
            $submitted = (string) $input[$secret_field];
            $stored    = self::decryptSecret($this->fields[$secret_field] ?? '');
            if ($submitted === $stored) {
                unset($input[$secret_field]);
            } else {
                $input[$secret_field] = self::encryptSecret($submitted);
            }
        }

        return $input;
    }

    /**
     * For other plugins, add a type to the linkable types
     *
     * @param $type string class name
     **@since version 1.5.0
     *
     */
    public static function registerType($type)
    {
        if (!in_array($type, self::$types)) {
            self::$types[] = $type;
        }
    }


    /**
     * Build the URL of an item form, whatever its itemtype.
     *
     * Custom assets override getFormURL() to append the ?class= discriminator
     * that identifies their definition, something the static
     * Toolbox::getItemTypeFormURL() helper knows nothing about. The id must
     * then be appended with the right separator.
     *
     * @param string $itemtype
     * @param int    $items_id
     * @return string
     */
    public static function getItemFormLink(string $itemtype, int $items_id): string
    {
        if (!is_a($itemtype, CommonDBTM::class, true)) {
            return '';
        }

        $url = $itemtype::getFormURL();

        return $url . (str_contains($url, '?') ? '&' : '?') . 'id=' . $items_id;
    }

    /**
     * GLPI 11 custom asset classes the plugin can work with.
     *
     * A definition qualifies only when it provides everything an import needs:
     *  - the financial information capacity, since warranties are written into
     *    the item infocom. That capacity is precisely what registers the
     *    concrete class into $CFG_GLPI['infocom_types'], which
     *    Infocom::canApplyOn() reads back;
     *  - the serial number field, which is what identifies the item for the
     *    manufacturer;
     *  - the manufacturer field, which is what binds the item to a plugin
     *    configuration.
     * Unlike the classic itemtypes, those fields are opt-in per definition, and
     * an asset missing any of them could never be imported. Inactive
     * definitions are skipped: their classes are not bootstrapped.
     *
     * @return array<class-string> concrete asset class names
     */
    public static function getCustomAssetTypes(): array
    {
        if (!class_exists(AssetDefinitionManager::class)) {
            return [];
        }

        $types = [];
        foreach (AssetDefinitionManager::getInstance()->getDefinitions(true) as $definition) {
            $classname = $definition->getAssetClassName();
            if (!class_exists($classname) || !Infocom::canApplyOn($classname)) {
                continue;
            }

            $displayed_fields = $definition->getFieldOrder();
            if (
                !in_array('serial', $displayed_fields, true)
                || !in_array('manufacturers_id', $displayed_fields, true)
            ) {
                continue;
            }

            $types[] = $classname;
        }

        return $types;
    }

    /**
     * Type than could be linked to a Rack
     *
     * @param $all boolean, all type, or only allowed ones
     *
     * @return array of types
     **/
    public static function getTypes($all = false)
    {
        // Custom assets are appended on the fly: definitions can be created,
        // activated or lose their infocom capacity at any time, so the list
        // cannot be frozen in the static property.
        $types = array_merge(self::$types, self::getCustomAssetTypes());

        if ($all) {
            return $types;
        }

        // Only allowed types
        foreach ($types as $key => $itemtype) {
            // A type registered by another plugin may no longer exist: drop it,
            // callers instantiate whatever this list returns.
            if (!class_exists($itemtype)) {
                unset($types[$key]);
                continue;
            }

            if (!$itemtype::canView()) {
                unset($types[$key]);
            }
        }
        return $types;
    }

    public static function checkManufacturerName($itemtype, $items_id)
    {
        $item = getItemForItemtype($itemtype);
        if (!$item) {
            return false;
        }
        $name = false;

        if ($item->getFromDB($items_id)) {
            $dbu     = new DbUtils();
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

    public static function checkManufacturerID($itemtype, $items_id)
    {
        $item = getItemForItemtype($itemtype);
        if (!$item) {
            return false;
        }
        $id   = false;

        if ($item->getFromDB($items_id)) {
            $dbu     = new DbUtils();
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

    //    }

    //Massive action
    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            if (Session::haveRight('transfer', READ)
                && Session::isMultiEntitiesMode()) {
                $actions['Transfert'] = __('Transfer');
            }
        }

        return $actions;
    }

    public function showSpecificMassiveActionsParameters($input = [])
    {
        switch ($input['action']) {
            case "Transfert":
                Dropdown::show('Entity');
                echo Html::submit(_sx('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
                return true;
                break;

            default:
                return parent::showSpecificMassiveActionsParameters($input);
                break;
        }
        return false;
    }

    public function doSpecificMassiveActions($input = [])
    {
        $res = ['ok' => 0, 'ko' => 0, 'noright' => 0];

        switch ($input['action']) {
            case "Transfert":

                if ($input['itemtype'] == Config::class) {
                    foreach ($input["item"] as $key => $val) {
                        if ($val == 1) {
                            // Re-check the entity perimeter per item: CommonDBTM::update()
                            // does not replay the right/entity control, so a forged
                            // massive-action POST must not move a config record from an
                            // entity the caller cannot access.
                            if (!$this->can((int) $key, UPDATE)) {
                                $res['noright']++;
                                continue;
                            }
                            $values["id"]          = $key;
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
            default:
                return parent::doSpecificMassiveActions($input);
                break;
        }
        return $res;
    }

    /**
     * Display the current tag dropdown in form header of items
     *
     * @param item the CommonDBTM object
     *
     * @return nothing
     */
    public static function showForInfocom($item)
    {
        if (in_array($item->getType(), self::getTypes(true))) {
            $suppliername = Config::checkManufacturerName($item->getType(), $item->getID());

            $otherserial = "";
            if (class_exists($item->getType() . "Model")) {
                $modelfield = getForeignKeyFieldForTable(getTableForItemType($item->getType() . "Model"));
                $models_id = $item->fields[$modelfield];
                if ($models_id != 0) {
                    $modelitemtype = $item->getType() . "Model";
                    $modelclass = new $modelitemtype();
                    $modelclass->getfromDB($models_id);
                    $otherserial = $modelclass->fields["product_number"];
                }
            }

            $configID = Config::checkManufacturerID($item->getType(), $item->getID());
            $config   = new Config();
            $config->getFromDB($configID);
            $supplierkey = (isset($config->fields["supplier_key"])) ? self::decryptSecret($config->fields["supplier_key"]) : false;
            $supplierurl = (isset($config->fields["supplier_url"])) ? $config->fields["supplier_url"] : false;

            if ($suppliername == Config::LENOVO) {
                $url = PreImport::selectSupplier($suppliername, $supplierurl, $item->fields['serial'], $otherserial, $supplierkey, null, true);
            } else {
                $url = PreImport::selectSupplier($suppliername, $supplierurl, $item->fields['serial'], $otherserial, $supplierkey);
            }
            // Rendered via Twig so the manufacturer URL (which embeds the raw,
            // user-controlled serial number) is auto-escaped in the href,
            // preventing a stored XSS breakout of the attribute.
            TemplateRenderer::getInstance()->display('@manufacturersimports/warranty_infocom.html.twig', [
                'title'        => PreImport::getTypeName(2),
                'url'          => $url,
                'target'       => Config::getFormUrl(true),
                'itemtype'     => $item->getType(),
                'items_id'     => $item->getID(),
                'button_label' => _sx('button', 'Retrieve warranty from manufacturer', 'manufacturersimports'),
            ]);
        }
        return $item;
    }

    public static function retrieveOneWarranty($itemtype, $items_id)
    {
        // Restrict to the itemtypes this plugin actually handles before touching
        // anything derived from the user-supplied itemtype.
        if (!in_array($itemtype, self::getTypes(true), true)) {
            throw new AccessDeniedHttpException();
        }
        $item = getItemForItemtype($itemtype);
        if (!$item) {
            return;
        }
        // The plugin UPDATE right alone does not prove the caller may act on THIS
        // item: can() combines the global right with the entity perimeter, so a
        // user restricted to entity A cannot target an item of entity B by id.
        if (!$item->can($items_id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }
        if ($item->getFromDB($items_id)) {
            $log = new Log();
            $log->reinitializeImport($itemtype, $items_id);

            $config       = new Config();
            $suppliername = Config::checkManufacturerName($itemtype, $items_id);
            if ($config->getFromDBByCrit(['name' => $suppliername])) {
                $suppliername = $config->fields["name"];
                $supplierUrl  = $config->fields["supplier_url"];
                $supplierkey  = self::decryptSecret($config->fields["supplier_key"]);

                $url = PreImport::selectSupplier(
                    $suppliername,
                    $supplierUrl,
                    $item->fields['serial'],
                    $item->fields['otherserial'],
                    $supplierkey,
                );

                $post = PreImport::getSupplierPost(
                    $suppliername,
                    $item->fields['serial'],
                    $item->fields['otherserial'],
                );

                $data    = [];
                $options = ["url"     => $url,
                    "post"    => $post,
                    "type"    => $itemtype,
                    "ID"      => $items_id,
                    "config"  => $config,
                    "line"    => $data,
                    "display" => false];


                // Static calls below still require a whitelisted, existing class.
                $supplierclass = self::resolveSupplierClass($suppliername);
                if ($supplierclass === null) {
                    return;
                }
                $token                          = $supplierclass::getToken($config);
                $warranty_url                   = $supplierclass::getWarrantyUrl($config, $item->fields['serial']);
                $options['token']               = $token;
                $options['line']['entities_id'] = $item->fields['entities_id'];
                if (isset($warranty_url['url'])) {
                    $options['url'] = $warranty_url['url'];
                }
                if (isset($item->fields['serial'])) {
                    $options['sn'] = $item->fields['serial'];
                }

                $otherserial = "";
                if (class_exists($item->getType() . "Model")) {
                    $modelfield = getForeignKeyFieldForTable(getTableForItemType($item->getType() . "Model"));
                    $models_id = $item->fields[$modelfield];
                    if ($models_id != 0) {
                        $modelitemtype = $item->getType() . "Model";
                        $modelclass = new $modelitemtype();
                        $modelclass->getfromDB($models_id);
                        $otherserial = $modelclass->fields["product_number"];
                    }
                }

                if (!empty($otherserial)) {
                    $options['pn'] = $otherserial;
                }
                if (
                    isset($_SESSION['glpi_use_mode'])
                    && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
                ) {
                    // Never dump $options as-is: it carries the freshly obtained
                    // OAuth bearer token (replayable against the manufacturer API
                    // until it expires) and the whole config object. Log only the
                    // fields that are useful to diagnose an import.
                    Toolbox::loginfo([
                        'url'       => $options['url'] ?? '',
                        'type'      => $options['type'] ?? '',
                        'ID'        => $options['ID'] ?? '',
                        'sn'        => $options['sn'] ?? '',
                        'pn'        => $options['pn'] ?? '',
                        'has_token' => !empty($options['token']),
                    ]);
                }
                PostImport::saveImport($options);
            }
        }
    }

    public static function showItemImport($params)
    {
        $item = $params['item'];

        if ($item
            && in_array($item->getType(), self::getTypes(true))
            && $item->fields['is_template'] == 0) {

            $config = new self();
            $log    = new Log();

            $suppliername = Config::checkManufacturerName($item->getType(), $item->getID());
            if (!empty($suppliername) && !empty($item->fields['serial'])) {
                $NotAlreadyImported = $log->checkIfAlreadyImported($item->getType(), $item->getID());
                if (!$NotAlreadyImported) {
                    echo "<div class='alert alert-warning d-flex'>";
                    echo __("You did not import the warranty for this item. Do you want to get it back?", "manufacturersimports");
                    $target = Config::getFormUrl(true);
                    echo "&nbsp;";
                    Html::showSimpleForm(
                        $target,
                        'retrieve_warranty',
                        _sx('button', 'Retrieve warranty from manufacturer', 'manufacturersimports'),
                        ['itemtype' => $item->getType(),
                            'items_id' => $item->getID()],
                        'ti-cloud-download',
                    );
                    echo "</div>";
                }
            }
        }
    }
}
