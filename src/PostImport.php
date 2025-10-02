<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2009-2022 by the Manufacturersimports Development Team.

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

use CommonDBTM;
use DbUtils;
use Document;
use Document_Item;
use Dropdown;
use GLPIKey;
use Html;
use Infocom;
use Session;
use Toolbox;
use Glpi\Progress\AbstractProgressIndicator;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class PostImport
 */
class PostImport extends CommonDBTM
{
    /**
     * @param      $field
     * @param bool $reverse
     *
     * @return string
     */
    public static function checkDate($field, $reverse = false)
    {
        // Date is already "reformat" according to getDateFormat()

        if ($reverse) {
            $pattern = "/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})";
            $pattern .= "([0-5][0-9]:[0-5]?[0-9]:[_][01][0-9]|2[0-3])?/";
        } else {
            $pattern = "/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})";
            $pattern .= "([_][01][0-9]|2[0-3]:[0-5][0-9]:[0-5]?[0-9])?/";
        }
        preg_match($pattern, $field, $regs);
        if (empty($regs)) {
            return "0000-00-00";
        }
        return $field;
    }


    /**
     * @param $options
     *
     * @return mixed|string
     */
    public static function cURLData($options)
    {
        global $CFG_GLPI;

        if (!function_exists('curl_init')) {
            return __('Curl PHP package not installed', 'manufacturersimports') . "\n";
        }
        $data        = '';
        $timeout     = 30;
        $proxy_host  = !empty($CFG_GLPI["proxy_name"]) ? ($CFG_GLPI["proxy_name"] . ":" . $CFG_GLPI["proxy_port"]) : false; // host:port
        $proxy_ident = !empty($CFG_GLPI["proxy_user"]) ? ($CFG_GLPI["proxy_user"] . ":" .
                                                          (new GLPIKey())->decrypt($CFG_GLPI["proxy_passwd"])) : false; // username:password

        $url = $options["url"];
        $sn = null;
        if (isset($options["sn"])) {
            $sn = $options["sn"];
        }
        $pn = null;
        if (isset($options["pn"])) {
            $pn = $options["pn"];
        }

        $ch = curl_init();
        if (
            isset($_SESSION['glpi_use_mode'])
            && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
        ) {
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            $fp = fopen(dirname(__FILE__) . '/errorlog.txt', 'w');
            curl_setopt($ch, CURLOPT_STDERR, $fp);
        }
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        if (preg_match('`^https://`i', $options["url"])) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, "cookiefile");
        curl_setopt($ch, CURLOPT_COOKIEJAR, "cookiefile"); // SAME cookiefile

        if (!empty($options['token'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
               "Authorization: Bearer " . $options['token']
            ]);
        }

        if (!empty($options['ClientID'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
               "ClientID: " . $options['ClientID']
            ]);
        }

        //Do we have post field to send?
        if (!empty($options["post"])
            && (empty($options['token']) && ($options['suppliername'] == Config::HP
                    || $options['suppliername'] == Config::DELL))) {

            //curl_setopt($ch, CURLOPT_POST,true);
            $post = '';
            foreach ($options['post'] as $key => $value) {
                $post .= $key . '=' . $value . '&';
            }
            $post = rtrim($post, '&');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:application/x-www-form-urlencoded"]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTREDIR, 2);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

            // ADDED FOR HP
            if ($options['suppliername'] == Config::HP
            || $options['suppliername'] == Config::DELL) {
                if (empty($options['token'])) {
                    $client_id = $options['post']['client_id'];
                    $client_secret = $options['post']['client_secret'];
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch, CURLOPT_USERPWD, "$client_id:$client_secret");
                }
            }
        }

        if ($options['suppliername'] == Config::HP) {
            if (!empty($options['token'])) {

                $authorization = "Authorization: Bearer ".$options['token']; // Prepare the authorisation token
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
                curl_setopt($ch, CURLOPT_POST, true);
                $table_serial["sn"] = $sn;
                $table_serial["pn"] = $pn;
                $postdata = "[".json_encode($table_serial)."]";
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                curl_setopt($ch, CURLOPT_POSTREDIR, 2);
            }
        }

        if (!$options["download"]) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        }

        // Activation de l'utilisation d'un serveur proxy
        if (!empty($CFG_GLPI["proxy_name"])) {
            // Définition de l'adresse du proxy
            curl_setopt($ch, CURLOPT_PROXY, $proxy_host);

            // Définition des identifiants si le proxy requiert une identification
            if ($proxy_ident) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_ident);
            }
        }

        if ($options["download"]) {
            $fp = fopen($options["file"], "w");
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_exec($ch);
        } else {
            $data = curl_exec($ch);
        }

        if (
            isset($_SESSION['glpi_use_mode'])
            && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
        ) {
            $errors   = curl_error($ch);
            $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            Toolbox::logInfo($errors);
            Toolbox::logInfo($response);
        }
        if (!$options["download"] && !$data) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch); // make sure we closeany current curl sessions
        //die($http_code.' Unable to connect to server. Please come back later.');
        } else {
            curl_close($ch);
        }

        if ($options["download"]) {
            fclose($fp);
        }
        if (!$options["download"] && $data) {
            return $data;
        }
    }

    /**
     * @param $values
     */
    public static function massiveimport($values)
    {
        $config = new Config();
        $log    = new Log();

        $_SESSION["glpi_plugin_manufacturersimports_total"] = 0;

//        Html::progressBar('import', ['create'  => true, 'message' =>__('Launching of imports', 'manufacturersimports')]);

        echo "<table class='tab_cadre' width='70%' cellpadding='2'>";
        echo "<tr><th colspan='6'>" . __('Post import', 'manufacturersimports') . "</th></tr>";
        echo "<tr><th>" . __('Name') . "</th>";
        echo "<th>" . __('Serial number') . "</th>";

        $config->getFromDB($values["manufacturers_id"]);
        $suppliername = $config->fields["name"];

        echo "<th>" . _n('Link', 'Links', 1) . "</th>";
        echo "<th>" . __('Result', 'manufacturersimports') . "</th>";
        echo "<th>" . __('Details', 'manufacturersimports') . "</th>";
        echo "</tr>";

        $pos = 0;
        foreach ($values["item"] as $key => $val) {
            if ($val == 1) {
                $NotAlreadyImported = $log->checkIfAlreadyImported($values["itemtype"], $key);
                if (!$NotAlreadyImported) {
                    self::seePostImport(
                        $values["itemtype"],
                        $key,
                        $values["to_suppliers_id$key"],
                        $values["to_warranty_duration$key"],
                        $values["manufacturers_id"]
                    );
                }
            }
            $pos += 1;
//            Html::changeProgressBarPosition(
//                $pos,
//                count($values["item"]),
//                __('Import in progress', 'manufacturersimports') . " (" . $values['itemtype']::getTypeName(2) . " : " . $suppliername . ")"
//            );
        }
//        Html::changeProgressBarPosition(
//            $pos,
//            count($values["item"]),
//            __('Done')
//        );

        echo "<tr class='tab_bg_1'><td colspan='6'>";
        $total = $_SESSION["glpi_plugin_manufacturersimports_total"];
        echo sprintf(__('Total number of devices imported %s', 'manufacturersimports'), $total);
        echo "</td></tr>";
        echo "</table>";
        echo "<br><div class='center'>";
        echo "<a class='submit btn btn-primary' href='" . PLUGIN_MANUFACTURERSIMPORTS_WEBDIR . "/front/import.php?back=back&amp;itemtype=" .
             $values["itemtype"] . "&amp;manufacturers_id=" . $values["manufacturers_id"] . "&amp;start=" . $values["start"] .
             "&amp;imported=" . $values["imported"] . "'>";
        echo __('Back');
        echo "</a>";
        echo "</div>";
    }

    /**
     * Fonction to select the search field on the website of the supplier
     *
     * @param $suppliername the suppliername
     *
     * @return $field for date and warranty searching
     *
     */
    public static function selectSupplierField($suppliername)
    {
        $field = '';
        if (!empty($suppliername)) {
            $supplierclass = "GlpiPlugin\Manufacturersimports\\".$suppliername;
            $supplier      = new $supplierclass();
            $field         = $supplier->getSearchField();
        }

        return $field;
    }

    /**
     * @param $suppliername
     * @param $contents
     *
     * @return mixed
     */
    public static function importDate($suppliername, $contents)
    {
        $supplierclass = "GlpiPlugin\Manufacturersimports\\".$suppliername;
        $supplier      = new $supplierclass();
        $importDate    = $supplier->getBuyDate($contents);

        return $importDate;
    }

    /**
     * @param $suppliername
     * @param $contents
     *
     * @return mixed
     */
    public static function importStartDate($suppliername, $contents)
    {
        $supplierclass   = "GlpiPlugin\Manufacturersimports\\".$suppliername;
        $supplier        = new $supplierclass();
        $importStartDate = $supplier->getStartDate($contents);

        return $importStartDate;
    }

    /**
     * @param $suppliername
     * @param $contents
     *
     * @return mixed
     */
    public static function importWarrantyInfo($suppliername, $contents)
    {
        $supplierclass      = "GlpiPlugin\Manufacturersimports\\".$suppliername;
        $supplier           = new $supplierclass();
        $importWarrantyInfo = $supplier->getWarrantyInfo($contents);

        return $importWarrantyInfo;
    }

    //static function importWarranty($suppliername, $maDate, $contents, $warranty) {
    //   if ($warranty==0) {
   //      if ($suppliername == Config::DELL) {
   //         $maDateFin = DellSoap::getDates($contents, "EndDate");
   //      } else {
   //         $supplierclass = "GlpiPlugin\Manufacturersimports\\".$suppliername;
   //         $supplier      = new $supplierclass();
   //         $maDateFin     = $supplier->getExpirationDate($contents);
   //      }

   //      if ($maDateFin != "0000-00-00") {
   //         list ($adebut, $mdebut, $jdebut) = explode ('-', $maDate);
   //         list ($afin, $mfin, $jfin)       = explode ('-', $maDateFin);

   //         $warranty = 0;
   //         $warranty = 12 - $mdebut;
   //         for ($year = $adebut + 1; $year < $afin;$year++) {
   //            $warranty += 12;
   //         }
   //         $warranty += $mfin;
   //      }
    //   }
    //   return $warranty;
    //}

    /**
     * @param $suppliername
     * @param $contents
     *
     * @return mixed
     */
    public static function importDateFin($suppliername, $contents)
    {
        $supplierclass = "GlpiPlugin\Manufacturersimports\\".$suppliername;
        $supplier      = new $supplierclass();
        $maDateFin     = $supplier->getExpirationDate($contents);

        return $maDateFin;
    }

    /**
     * Prints display post import
     *
     * @param $type the type of device
     * @param $ID the ID of device
     * @param $fromsupplier selection on pre import
     * @param $fromwarranty selection on pre import
     * @param $configID ID of supplier plugin config
     *
     * @return results of data import
     *
     */
    public static function seePostImport($type, $ID, $fromsupplier, $fromwarranty, $configID)
    {
        global $DB;

        $config = new Config();
        $config->getFromDB($configID);
        $manufacturerId = $config->fields["manufacturers_id"];

        if ($fromsupplier) {
            $supplierId = $fromsupplier;
        } else {
            $supplierId = $config->fields["suppliers_id"];
        }
        $suppliername   = $config->fields["name"];
        $supplierUrl    = $config->fields["supplier_url"];
        $supplierkey    = $config->fields["supplier_key"];
        $supplierSecret = $config->fields["supplier_secret"];

        $dbu       = new DbUtils();
        $itemtable = $dbu->getTableForItemType($type);

        $modelfield = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($type . "Model"));

        $query  = "SELECT `" . $itemtable . "`.`id`,
                        `" . $itemtable . "`.`name`,
                        `" . $itemtable . "`.`entities_id`,
                        `" . $itemtable . "`.`serial`,
                         `" . $itemtable . "`.`$modelfield`
          FROM `" . $itemtable . "`, `glpi_manufacturers`
          WHERE `" . $itemtable . "`.`manufacturers_id` = `glpi_manufacturers`.`id`
          AND `" . $itemtable . "`.`is_deleted` = '0'
          AND `" . $itemtable . "`.`is_template` = '0'
          AND `glpi_manufacturers`.`id` = '" . $manufacturerId . "'
          AND `" . $itemtable . "`.`serial` != ''
          AND `" . $itemtable . "`.`id` = '" . $ID . "' ";
        $query  .= " ORDER BY `" . $itemtable . "`.`name`";
        $result = $DB->doQuery($query);

        $supplierclass = "GlpiPlugin\Manufacturersimports\\".$suppliername;
        $token         = $supplierclass::getToken($config);

        while ($line = $DB->fetchArray($result)) {
            $compSerial = $line['serial'];
            $ID         = $line['id'];
            echo "<tr class='tab_bg_1' ><td>";
            $link        = Toolbox::getItemTypeFormURL($type);
            $dID         = "";

            $models_id = $line[$modelfield];

            $otherSerial = "";
            if (class_exists($type."Model") && $models_id != 0) {
                $modelitemtype =$type."Model";
                $modelclass = new $modelitemtype();
                $modelclass->getfromDB($models_id);
                $otherSerial = $modelclass->fields["product_number"];
            }

            if ($_SESSION["glpiis_ids_visible"] || empty($line["name"])) {
                $dID .= " (" . $line["id"] . ")";
            }
            echo "<a href='" . $link . "?id=" . $ID . "'>" . $line["name"] . $dID . "</a><br>" . $otherSerial . "</td>";

            $url          = PreImport::selectSupplier(
                $suppliername,
                $supplierUrl,
                $compSerial,
                $otherSerial,
                $supplierkey,
                $supplierSecret
            );
            $post         = PreImport::getSupplierPost(
                $suppliername,
                $compSerial,
                $otherSerial,
                $supplierkey,
                $supplierSecret
            );
            $warranty_url = $supplierclass::getWarrantyUrl($config, $compSerial);

            //On complete l url du support du fournisseur avec le serial
            echo "<td>" . $compSerial . "</td>";
            echo "<td>";
            echo "<a href='" . $url . "' target='_blank'>" . _n('Manufacturer', 'Manufacturers', 1) . "</a>";
            echo "</td>";

            $options = [
                "url" => isset($warranty_url['url']) ? $warranty_url['url'] : $url,
                "sn" => $line['serial'],
                "pn" => $otherSerial,
                "post" => $post,
                "type" => $type,
                "ID" => $ID,
                "config" => $config,
                "line" => $line,
                "fromsupplier" => $fromsupplier,
                "fromwarranty" => $fromwarranty,
                "display" => true,
                "token" => $token
            ];

            if ($suppliername == Config::LENOVO) {
                $options['ClientID'] = $supplierkey;
            }

            self::saveImport($options);

            echo "</tr>\n";
        }
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public static function saveImport($params = [])
    {
        $default_values                 = [];
        $default_values['url']          = "";
        $default_values['sn']           = "";
        $default_values['pn']           = "";
        $default_values['url_warranty'] = "";
        $default_values['post']         = "";
        $default_values['display']      = false;
        $default_values['type']         = "";
        $default_values['ID']           = 0;
        $default_values['fromsupplier'] = 0;
        $default_values['fromwarranty'] = 0;
        $default_values['line']         = [];
        $default_values['config']       = new Config();
        $default_values['token']        = false;

        $values = [];
        foreach ($default_values as $key => $val) {
            if (isset($params[$key])) {
                $values[$key] = $params[$key];
            } else {
                $values[$key] = $val;
            }
        }

        $config = $values['config'];

        if ($values['fromsupplier']) {
            $supplierId = $values['fromsupplier'];
        } else {
            $supplierId = $config->fields["suppliers_id"];
        }
        $suppliername = $config->fields["name"];
        $adddoc       = $config->fields["document_adding"];
        $rubrique     = $config->fields["documentcategories_id"];
        $addcomments  = $config->fields["comment_adding"];
        $url_warranty = $config->fields["supplier_url"];
        $supplier_key = $config->fields["supplier_key"];

        if (isset($params['fromwarranty']) && $params['fromwarranty']) {
            $warranty = $values['fromwarranty'];
        } else {
            $warranty = $config->fields["warranty_duration"];
        }

        $contents = "";
        //$msgerr   = "";

        $options = [
            "url" => $values['url'],
            "sn" => $values['sn'],
            "pn" => $values['pn'],
            "download" => false,
            "file" => false,
            "post" => $values['post'],
            "suppliername" => $suppliername,
            "token" => $values['token']
        ];

        if ($suppliername == Config::LENOVO
            && $supplier_key != null) {
            $options["ClientID"] = $supplier_key;
            $contents            = self::cURLData($options);
        }
//        elseif ($suppliername == Config::HP) {
//            $json = json_decode($contents);
//            if (isset($json->access_token)) {
//                $options['access_token'] = $json->access_token;
//                $options['url']          = $url_warranty;
//                $contents                = self::cURLData($options); // Getting Warranty Data
//            }
//        }
        else {
            $contents = self::cURLData($options);
        }
        if (
            isset($_SESSION['glpi_use_mode'])
            && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
        ) {
            Toolbox::loginfo($contents);
        }
        // On extrait la date de garantie de la variable contents.
        $field = self::selectSupplierfield($suppliername);

        if ($field != false) {
            $contents = stristr($contents, $field);
        }

        if (!$contents === false) {
            $maBuyDate    = self::importDate($suppliername, $contents);
            $maDate       = self::importStartDate($suppliername, $contents);
            $maDateFin    = self::importDateFin($suppliername, $contents);
            $warrantyinfo = self::importWarrantyInfo($suppliername, $contents);
        }

        if (isset($maDate)
            && $maDate != "0000-00-00"
            && $maDate != false
            && isset($maDateFin)
            && $maDateFin != "0000-00-00"
            && $maDateFin != false) {
            list($adebut, $mdebut, $jdebut) = explode('-', $maDate);
            list($afin, $mfin, $jfin) = explode('-', $maDateFin);
            $warranty = 0;
            $warranty = 12 - $mdebut;
            for ($year = $adebut + 1; $year < $afin; $year++) {
                $warranty += 12;
            }
            $warranty += $mfin;
        }

        if (isset($maDate)
            && $maDate != "0000-00-00"
            && $maDate != false) {
            //warranty for life
            if ($warranty > 120) {
                $warranty = -1;
            }

            $date    = date("Y-m-d");
            $options = ["itemtype"      => $values['type'],
                        "ID"            => $values['ID'],
                        "date"          => $date,
                        "supplierId"    => $supplierId,
                        "warranty"      => $warranty,
                        "suppliername"  => $suppliername,
                        "addcomments"   => $addcomments,
                        "maDate"        => $maDate,
                        "buyDate"       => $maBuyDate,
                        "warranty_info" => $warrantyinfo];
            self::saveInfocoms($options, $values['display']);

            // on cree un doc dans GLPI qu'on va lier au materiel
            if ($adddoc != 0
                && $suppliername != Config::DELL
                && $suppliername != Config::HP) {
                $options                = ["itemtype"     => $values['type'],
                                           "ID"           => $values['ID'],
                                           "url"          => $values['url'],
                                           "entities_id"  => $values['line']["entities_id"],
                                           "rubrique"     => $rubrique,
                                           "suppliername" => $suppliername];
                $values["documents_id"] = self::addDocument($options);
            }

            //insert base locale
            $values["import_status"] = 1;
            $values["items_id"]      = $values['ID'];
            $values["itemtype"]      = $values['type'];
            $values["date_import"]   = $date;
            $log                     = new Log();
            $log->add($values);

            // cleanup Log
            $log_clean = new Log();
            $log_clean->deleteByCriteria(
                [
                                            'items_id'      => $values['ID'],
                                            'itemtype'      => $values['type'],
                                            'import_status' => 2,
                                            'LIMIT'         => 1
                                         ]
            );
            if (isset($_SESSION["glpi_plugin_manufacturersimports_total"])) {
                $_SESSION["glpi_plugin_manufacturersimports_total"] += 1;
            }

            return true;
        } else { // Failed check contents
            if ($values['display']) {
                self::isInError($suppliername, $values['type'], $values['ID'], $contents);
            } else {
                self::isInError($suppliername, $values['type'], $values['ID'], null, $values['display']);
                return false;
            }
        }
        return false;
    }

    /**
     * Adding infocoms date of purchase and warranty
     *
     * @param      $options
     * @param bool $display
     */
    public static function saveInfocoms($options, $display = false)
    {
        //Original values
        $warranty_date     = "";
        $buy_date          = "";
        $warranty_duration = "";
        $warranty_info     = "";
        $suppliers_id      = "";
        $ic_comments       = "";

        //New values
        $input_infocom = [];
        if ($options["supplierId"] != 0) {
            $input_infocom["suppliers_id"] = $options["supplierId"];
        }
        $input_infocom["warranty_date"]     = date("Y-m-d", strtotime($options["maDate"]));
        $input_infocom["warranty_duration"] = $options["warranty"];
        $input_infocom["warranty_info"]     = $options["warranty_info"];
        $input_infocom["buy_date"]          = date("Y-m-d", strtotime($options["buyDate"]));
        $input_infocom["items_id"]          = $options["ID"];
        $input_infocom["itemtype"]          = $options["itemtype"];

        //add new infocoms
        $ic = new Infocom();
        if ($ic->getfromDBforDevice($options["itemtype"], $options["ID"])) {
            //Original values
            $warranty_date     = Html::convdate($ic->fields["warranty_date"]);
            $warranty_duration = $ic->fields["warranty_duration"];
            $warranty_info     = $ic->fields["warranty_info"];
            $buy_date          = $ic->fields["buy_date"];
            $suppliers_id      = Dropdown::getDropdownName("glpi_suppliers", $ic->fields["suppliers_id"]);
            $ic_comment        = $ic->fields["comment"];

            //New values
            $input_infocom["id"] = $ic->fields["id"];

            if ($options["addcomments"]) {
                $input_infocom["comment"] = $ic_comment . "\n" .
                                            __('Imported from web site', 'manufacturersimports') . " " . $options["suppliername"] . " " .
                                            __('With the manufacturersimports plugin', 'manufacturersimports') . " (" . Html::convdate($options["date"]) . ")";
            }
            $input_infocom["comment"] = addslashes($input_infocom["comment"]);
            $infocom = new Infocom();
            $infocom->update($input_infocom);
        } else {
            if ($options["addcomments"]) {
                $input_infocom["comment"] = __('Imported from web site', 'manufacturersimports') .
                                            " " . $options["suppliername"] . " " . __('With the manufacturersimports plugin', 'manufacturersimports') .
                                            " (" . Html::convdate($options["date"]) . ")";
            }
            $infocom = new Infocom();
            $input_infocom["comment"] = addslashes($input_infocom["comment"]);
            $infocom->add($input_infocom);
        }

        if ($display) {
            //post message
            echo "<td><span class='plugin_manufacturersimports_import_OK'>";
            echo __('Import OK', 'manufacturersimports') . " (" . Html::convdate($options["date"]) . ")";
            echo "</span></td>";
            echo "<td>";
            echo _n('Supplier', 'Suppliers', 1) . ": ";
            if ($options["supplierId"] != 0) {
                echo $suppliers_id . "->" . Dropdown::getDropdownName("glpi_suppliers", $options["supplierId"]) . "<br>";
            }
            echo __('Date of purchase') . ": ";
            echo Html::convdate($buy_date) . "->" . Html::convdate($options["buyDate"]) . "<br>";
            echo __('Start date of warranty') . ": ";
            echo $warranty_date . "->" . Html::convdate($options["maDate"]) . "<br>";
            if ($warranty_duration == -1) {
                $warranty_duration = __('Lifelong');
                $warranty          = __('Lifelong');
            } else {
                $warranty = $options["warranty"];
            }
            echo __('Warranty duration') . ": " . $warranty_duration . "->" . $warranty . "<br>";
            echo "</td>";
        }
    }

    /**
     * @param $options
     *
     * @return int
     */
    public static function addDocument($options)
    {
        //configure adding doc

        $time_file = date("Y-m-d-H-i");
        $name      = "";
        switch ($options["itemtype"]) {
            case 'Computer':
                $name = "computer";
                break;
            case 'NetworkEquipment':
                $name = "networking";
                break;
            case 'Peripheral':
                $name = "peripheral";
                break;
            case 'Monitor':
                $name = "monitor";
                break;
            case 'Printer':
                $name = "printer";
                break;
            case 'PluginRacksRack':
                $name = "rack";
                break;
        }

        $filename =
           "infocoms_" . $options["suppliername"]
           . "_" . $name . "_" . $options["ID"] . ".html";

        //on enregistre
        $path     = GLPI_DOC_DIR . "/_uploads/";
        $filepath = $path . $filename;
        $datas    = ["url"          => $options["url"],
                     "download"     => true,
                     "file"         => $filepath,
                     "suppliername" => $options["suppliername"]];
        self::cURLData($datas);

        $doc = new Document();

        $input                          = [];
        $input["entities_id"]           = $options["entities_id"];
        $input["name"]                  =
           addslashes("infocoms_" . $options["suppliername"] . "_" . $name . "_" . $options["ID"]);
        $input["upload_file"]           = $filename;
        $input["documentcategories_id"] = $options["rubrique"];
        $input["mime"]                  = "text/html";
        $input["date_mod"]              = date("Y-m-d H:i:s");
        $input["users_id"]              = Session::getLoginUserID();

        $newdoc  = $doc->add($input);
        $docitem = new Document_Item();
        $docitem->add(['documents_id' => $newdoc,
                       'itemtype'     => $options["itemtype"],
                       'items_id'     => $options["ID"],
                       'entities_id'  => $input["entities_id"]]);

        $temp = new Log();
        $temp->deleteByCriteria(['itemtype' => $options["itemtype"],
                                 'items_id' => $options["ID"]]);

        return $newdoc;
    }


    /**
     * @param      $type
     * @param      $ID
     * @param null $contents
     */
    public static function isInError($suppliername, $type, $ID, $contents = null, $display = true)
    {
        $msgerr = "";
        $date   = date("Y-m-d");
        if ($display) {
            echo "<td>";
            echo "<span class='plugin_manufacturersimports_import_KO'>";
            echo __('Import failed', 'manufacturersimports') . " (";
            echo Html::convdate($date) . ")</span></td>";
        }

        $temp = new Log();
        $temp->deleteByCriteria(['itemtype' => $type,
                                 'items_id' => $ID]);

        //insert base locale
        $values["import_status"] = 2;
        $values["items_id"]      = $ID;
        $values["itemtype"]      = $type;
        $values["date_import"]   = $date;
        $log                     = new Log();
        $log->add($values);

        if ($display) {
            if (!empty($contents)) {
                switch ($suppliername) {
                    case Config::LENOVO :
                        $msgerr = self::importWarrantyInfo($suppliername, $contents);
                        break;
                    default:
                        $msgerr = __('Connection failed/data download from manufacturer web site', 'manufacturersimports');
                }
            }
            echo "<td>$msgerr</td>";
        }
    }
}
