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
 * Class PluginManufacturersimportsHP
 */
class PluginManufacturersimportsHP extends PluginManufacturersimportsManufacturer
{
    /**
     * @see PluginManufacturersimportsManufacturer::showDocTitle()
     */
    public function showDocTitle($output_type, $header_num)
    {
        return Search::showHeaderItem($output_type, __('File'), $header_num);
    }

    public function getSearchField()
    {
        return false;
    }

    /**
     * @see PluginManufacturersimportsManufacturer::getSupplierInfo()
     */
    public function getSupplierInfo(
        $compSerial = null,
        $otherSerial = null,
        $key = null,
        $apisecret = null,
        $supplierUrl = null
    ) {
        $info["name"]         = PluginManufacturersimportsConfig::HP;
        $info["supplier_url"] = "https://warrantyapiproxy.azurewebsites.net/api/HP?serial=";

        $info['url_warranty'] = "https://warrantyapiproxy.azurewebsites.net/api/HP?serial=";
        $info["url"]          = $supplierUrl.$compSerial;

        return $info;
    }

    /**
     * @see PluginManufacturersimportsManufacturer::getBuyDate()
     */
    public function getBuyDate($contents)
    {
        $info = json_decode($contents, true);

        if (isset($info['StartDate'])) {
            return $info['StartDate'];
        }
    }

    /**
     * @see PluginManufacturersimportsManufacturer::getStartDate()
     */
    public function getStartDate($contents)
    {
        return self::getBuyDate($contents);
    }

    /**
     * @see PluginManufacturersimportsManufacturer::getExpirationDate()
     */
    public function getExpirationDate($contents)
    {
        $info = json_decode($contents, true);
        if (isset($info['EndDate'])) {
            return $info['EndDate'];
        }
        return false;
    }

    /**
     * @see PluginManufacturersimportsManufacturer::getWarrantyInfo()
     */
    public function getWarrantyInfo($contents)
    {
        $warrantyInfo = "";

        return $warrantyInfo;
    }

    /**
     * Summary of getWarrantyUrl
     *
     * @param  $config
     * @param  $compSerial
     *
     * @return string[]
     */
    public static function getWarrantyUrl($config, $compSerial)
    {
        return ["url" => $config->fields['warranty_url'] . "$compSerial"];
    }
}
