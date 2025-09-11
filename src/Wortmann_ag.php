<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Manufacturersimports plugin for GLPI
 Copyright (C) 2003-2016 by the Manufacturersimports Development Team.

 https://github.com/InfotelGLPI
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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Wortmann_ag
 */
class Wortmann_ag extends Manufacturer
{
    /**
     * @see Manufacturer::showDocTitle()
     */
    public function showDocTitle($output_type, $header_num)
    {
        return false;
    }

    public function getSearchField()
    {
        return "search";
    }

    /**
     * @see Manufacturer::getSupplierInfo()
     */
    public function getSupplierInfo(
        $compSerial = null,
        $otherSerial = null,
        $key = null,
        $apisecret = null,
        $supplierUrl = null
    )
    {
        $info["name"]         = Config::WORTMANN_AG;
        $info["supplier_url"] = "https://www.wortmann.de/fr-fr/profile/snsearch.aspx?SN=";
        $info["url"]          = $supplierUrl.$compSerial;
        return $info;
    }

    /**
     * @see Manufacturer::getBuyDate()
     */
    public function getBuyDate($contents)
    {
        $field     = "but de la service";
        $searchstart = stristr($contents, $field);
        $myDate = substr($searchstart, 26, 10);

        $myDate = trim($myDate);
        $myDate = str_replace('/', '-', $myDate);

        $myDate = PostImport::checkDate($myDate, true);

        if ($myDate != "0000-00-00") {
            list($day, $month, $year) = explode('-', $myDate);
            $myDate = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
        }

        return $myDate;
    }

    /**
     * @see Manufacturer::getStartDate()
     */
    public function getStartDate($contents)
    {
        return self::getBuyDate($contents);
    }

    /**
     * @see Manufacturer::getExpirationDate()
     */
    public function getExpirationDate($contents)
    {
        $field     = "Fin de service";
        $searchstart = stristr($contents, $field);
        $myDate = substr($searchstart, 23, 10);

        $myDate = trim($myDate);
        $myDate = str_replace('/', '-', $myDate);

        $myDate = PostImport::checkDate($myDate, true);

        if ($myDate != "0000-00-00") {
            list($day, $month, $year) = explode('-', $myDate);
            $myDate = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
        }
        return $myDate;
    }
}
