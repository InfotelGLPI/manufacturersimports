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

use CommonDBTM;
use Document;
use Html;
use Search;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Manufacturer
 */
class Manufacturer extends CommonDBTM
{
    /**
     * @param $ID
     * @param $sel
     * @param bool $otherSerial
     * @return string
     */
    public function showCheckbox($ID, $sel, $otherSerial = false)
    {
        $name = "item[" . $ID . "]";
        return Html::getCheckbox(["name" => $name, "value" => 1, "selected" => $sel]);
    }

    /**
     * @param $output_type
     * @param $header_num
     * @return bool
     */
    public function showItemTitle($output_type, $header_num)
    {
        return false;
    }

    /**
     * @param $output_type
     * @param bool $otherSerial
     * @param $item_num
     * @param $row_num
     * @return bool
     */
    public function showItem($output_type, $item_num, $row_num, $otherSerial = false)
    {
        return false;
    }

    /**
     * @param $output_type
     * @param $header_num
     * @return bool
     */
    public function showDocTitle($output_type, $header_num)
    {
        return false;
    }

    /**
     * @param $output_type
     * @param $item_num
     * @param $row_num
     * @param null $doc
     * @return string
     */
    public function showDocItem($output_type, $item_num, $row_num, $documents_id = null)
    {
        $doc = new Document();
        if ($doc->getFromDB($documents_id)) {
            return  Search::showItem(
                $output_type,
                $doc->getDownloadLink(),
                $item_num,
                $row_num
            );
        }
        return Search::showItem($output_type, "", $item_num, $row_num);
    }

    /**
     *
     * @param type $ID
     * @param type $supplierWarranty
     */
    public function showWarrantyItem($ID, $supplierWarranty)
    {
        echo "<td>".__('Automatic', 'manufacturersimports');
        $name = "to_warranty_duration".$ID;
        echo Html::hidden($name, ['value' => 0]);
        echo "</td>";
    }

    /**
     * Get supplier information with url
     *
     * @param null $compSerial
     * @param null $otherserial
     * @param null $key
     * @param null $supplierUrl
     * @return mixed
     */
    public function getSupplierInfo(
        $compSerial = null,
        $otherSerial = null,
        $key = null,
        $apisecret = null,
        $supplierUrl = null
    )
    {
    }

    /**
     * Get buy date of object
     *
     * @param $contents
     */
    public function getBuyDate($contents)
    {
    }

    /**
     * Get start date of warranty
     *
     * @param $contents
     * @return mixed
     */
    public function getStartDate($contents)
    {
        return false;
    }

    /**
     * Get expiration date of warranty
     *
     * @param $contents
     */
    public function getExpirationDate($contents)
    {
    }

    /**
     * Get warranty info
     *
     * @param $contents
     */
    public function getWarrantyInfo($contents)
    {
    }

    /**
     * Summary of getToken
     * @param  $config
     * @return mixed
     */
    public static function getToken($config)
    {
        return false;
    }


    /**
     * Summary of getWarrantyUrl
     * @param  $config
     * @param  $compSerial
     * @return string[]|boolean
     */
    public static function getWarrantyUrl($config, $compSerial)
    {
        return false;
    }
}
