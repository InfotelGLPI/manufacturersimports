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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

ini_set("max_execution_time", "0");

/**
 * Class PluginManufacturersimportsLenovo
 */
class PluginManufacturersimportsLenovo extends PluginManufacturersimportsManufacturer {

    /**
     * @see PluginManufacturersimportsManufacturer::showCheckbox()
     */
    function showCheckbox($ID, $sel, $otherSerial = false) {
        $name = "item[" . $ID . "]";
        return Html::getCheckbox(["name" => $name, "value" => 1, "selected" => $sel]);

    }

    /**
     * @see PluginManufacturersimportsManufacturer::showItemTitle()
     */
    function showItemTitle($output_type, $header_num) {
        return Search::showHeaderItem($output_type, __('Model number', 'manufacturersimports'), $header_num);
    }

    /**
     * @see PluginManufacturersimportsManufacturer::showDocTitle()
     */
    function showDocTitle($output_type, $header_num) {
        return Search::showHeaderItem($output_type, __('File'), $header_num);
    }

    /**
     * @see PluginManufacturersimportsManufacturer::showItem()
     */
    function showItem($output_type, $item_num, $row_num, $otherSerial = false) {
        return false;
    }

    function getSearchField() {
        return false;
    }

    /**
     * @see PluginManufacturersimportsManufacturer::getSupplierInfo()
     */
    function getSupplierInfo($compSerial = null, $otherSerial = null, $key = null, $apisecret = null,
                             $supplierUrl = null) {

        $info["name"]         = PluginManufacturersimportsConfig::LENOVO;
        $info["supplier_url"] = "https://pcsupport.lenovo.com/products/$compSerial/warranty";
        //      $info["url"]          = $supplierUrl . $compSerial."?machineType=&btnSubmit";
        $info["url"]     = "https://pcsupport.lenovo.com/products/$compSerial/warranty";
        $info["url_web"] = "https://pcsupport.lenovo.com/products/$compSerial/warranty";
        return $info;
    }

    /**
     * @see PluginManufacturersimportsManufacturer::getBuyDate()
     */
    function getBuyDate($contents) {
            $json=stristr($contents,'window.ds_warranties');
            $json = substr($json,strlen('window.ds_warranties || '));
            $json = strtok($json, ";");
            $data = json_decode($json,true);
            $myDate = '';
            if (isset($data['BaseWarranties']) && !empty($data['BaseWarranties'])) {
                    foreach ($data['BaseWarranties'] as $warranty) {
                            if (!empty($warranty['Category']) && $warranty['Category'] == 'MACHINE') {
                                    $myDate = $warranty["Start"];
                            }
                    }
            }
            if (empty($myDate) && !empty($data['Shiped'])) {
                    $myDate = $data['Shiped'];
            }
            $myDate = PluginManufacturersimportsPostImport::checkDate($myDate);
            return $myDate;
    }

    /**
     * @see PluginManufacturersimportsManufacturer::getStartDate()
     */
    function getStartDate($contents) {
            $json=stristr($contents,'window.ds_warranties');
            $json = substr($json,strlen('window.ds_warranties || '));
            $json = strtok($json, ";");
            $data = json_decode($json,true);
            $myDate = "";
            $maxEnd = 0;
            $start  = '';
            if (isset($data['BaseWarranties']) && !empty($data['BaseWarranties'])) {
                    foreach ($data['BaseWarranties'] as $warranty) {
                            if (!empty($warranty['Category']) && $warranty['Category'] == 'MACHINE') {
                                    $myDate  = trim($warranty['End']);
                                    $dateEnd = strtotime($myDate);
                                    if ($dateEnd > $maxEnd) {
                                            $maxEnd = $dateEnd;
                                            $start  = strtotime(trim($warranty['Start']));
                                    }
                            }
                    }

            }
            if (isset($data['UpmaWarranties']) && !empty($data['UpmaWarranties'])) {
                    foreach ($data['UpmaWarranties'] as $warranty) {
                            if (!empty($warranty['Category']) && $warranty['Category'] == 'MACHINE') {
                                    $myDate  = trim($warranty['End']);
                                    $dateEnd = strtotime($myDate);
                                    if ($dateEnd > $maxEnd) {
                                            $maxEnd = $dateEnd;
                                            $start  = strtotime(trim($warranty['Start']));
                                    }
                            }
                    }
            }

            if (!empty($start)) {
                    $myDate = date("Y-m-d", $start);
            }
            return PluginManufacturersimportsPostImport::checkDate($myDate);
    }

    /**
     * @see PluginManufacturersimportsManufacturer::getExpirationDate()
     */
    function getExpirationDate($contents) {
            $json=stristr($contents,'window.ds_warranties');
            $json = substr($json,strlen('window.ds_warranties || '));
            $json = strtok($json, ";");
            $data = json_decode($json,true);
            $myDate = "";
            $maxEnd = 0;
            $start  = '';
            if (isset($data['BaseWarranties']) && !empty($data['BaseWarranties'])) {
                    foreach ($data['BaseWarranties'] as $warranty) {
                            if (!empty($warranty['Category']) && $warranty['Category'] == 'MACHINE') {
                                    $myDate  = trim($warranty['End']);
                                    $dateEnd = strtotime($myDate);
                                    if ($dateEnd > $maxEnd) {
                                            $maxEnd = $dateEnd;
                                            $start  = strtotime(trim($warranty['Start']));
                                    }
                            }
                    }

            }
            if (isset($data['UpmaWarranties']) && !empty($data['UpmaWarranties'])) {
                    foreach ($data['UpmaWarranties'] as $warranty) {
                            if (!empty($warranty['Category']) && $warranty['Category'] == 'MACHINE') {
                                    $myDate  = trim($warranty['End']);
                                    $dateEnd = strtotime($myDate);
                                    if ($dateEnd > $maxEnd) {
                                            $maxEnd = $dateEnd;
                                            $start  = strtotime(trim($warranty['Start']));
                                    }
                            }
                    }

            }
            if (!empty($maxEnd)) {
                    $myDate = date("Y-m-d", $maxEnd);
            }
            return PluginManufacturersimportsPostImport::checkDate($myDate);
    }

    /**
     * @see PluginManufacturersimportsManufacturer::getWarrantyInfo()
     */
    function getWarrantyInfo($contents) {
            $json=stristr($contents,'window.ds_warranties');
            $json = substr($json,strlen('window.ds_warranties || '));
            $json = strtok($json, ";");
            $data = json_decode($json,true);
            $myDate = "";
            $warranty_desc = "";
            if (isset($data['BaseWarranties']) && !empty($data['BaseWarranties'])) {
                    foreach ($data['BaseWarranties'] as $warranty) {
                            if (!empty($warranty['Category']) && $warranty['Category'] == 'MACHINE') {
                                    #if (!empty($warranty['Description'])) {
                                    #        $warranty_desc = $warranty['Description'];
                                    #} else {
                                            $warranty_desc = $warranty["Type"] . " - " . $warranty["Name"];
                                    #}
                            }
                    }

            }
            if (isset($data['UpmaWarranties']) && !empty($data['UpmaWarranties'])) {
                    foreach ($data['UpmaWarranties'] as $warranty) {
                            if (!empty($warranty['Category']) && $warranty['Category'] == 'MACHINE') {
                                    #if (!empty($warranty['Description'])) {
                                    #        $warranty_desc = $warranty['Description'];
                                    #} else {
                                            $warranty_desc = $warranty["Type"] . " - " . $warranty["Name"];
                                    #}
                            }
                    }

            }
            if (strlen($warranty_desc) > 255) {
                    $warranty_desc = substr($warranty_desc, 0, 254);
            }
            return $warranty_desc;
    }
}
