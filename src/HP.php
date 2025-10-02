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

use Search;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class HP
 */
class HP extends Manufacturer
{
    /**
     * @see Manufacturer::showDocTitle()
     */
    public function showDocTitle($output_type, $header_num)
    {
        return Search::showHeaderItem($output_type, __('File'), $header_num);
    }

    public function getSearchField()
    {
        return false;
    }


    public function getSupplierInfo(
        $compSerial = null,
        $otherSerial = null,
        $key = null,
        $apisecret = null,
        $supplierUrl = null
    )
    {
        if (!$compSerial) {
            // by default
            $info["name"] = Config::HP;
            $info['supplier_url'] = "https://support.hp.com/fr-fr/check-warranty/";
            $info['token_url'] = "https://warranty.api.hp.com/oauth/v1/token";
            $info['warranty_url'] = "https://warranty.api.hp.com/productwarranty/v2/queries";
            $info["supplier_key"]    = "123456789";
            $info["supplier_secret"] = "987654321";
            return $info;
        }

        $info["url"] = $supplierUrl;
        return $info;
    }

    public static function getToken($config)
    {
        $token = false;
//        $info['token_url'] = "https://warranty.api.hp.com/oauth/v1/token";
        // must manage token
        $options = [
            "url" => $config->fields["token_url"],
            "download" => false,
            "file" => false,
            "post" => [
                'client_id' => $config->fields["supplier_key"],
                'client_secret' => $config->fields["supplier_secret"],
                'grant_type' => 'client_credentials'
            ],
            "suppliername" => $config->fields["name"]
        ];
        $contents = PostImport::cURLData($options);
        // must extract from $contents the token bearer
        $response = json_decode($contents, true);
        if (isset($response['access_token'])) {
            $token = $response['access_token'];
        }
        return $token;
    }

    /**
     * @see Manufacturer::getBuyDate()
     */
    public function getBuyDate($contents)
    {

        $info = json_decode($contents, true);

        $max_date = false;
        if (isset($info[0]['offers'])) {
            foreach ($info[0]['offers'] as $d) {
                //by default try to use HP Hardware Maintenance Onsite Support
                if ($d['serviceObligationTypeCode'] && $d['serviceObligationTypeCode'] == "C") {
                    if ($d['serviceObligationLineItemStartDate']) {
                        $date = new \DateTime($d['serviceObligationLineItemStartDate']);
                        $max_date = $date;
                    }
                } else {
                    // when several dates are available, will take the last one
                    if ($d['serviceObligationLineItemStartDate']) {
                        $date = new \DateTime($d['serviceObligationLineItemStartDate']);
                        if ($max_date == false || $date > $max_date) {
                            $max_date = $date;
                        }
                    }
                }
            }

            if ($max_date) {
                return $max_date->format('c');
            }
        }
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
        $info = json_decode($contents, true);

        $max_date = false;
        if (isset($info[0]['offers'])) {
            foreach ($info[0]['offers'] as $k => $d) {

                //by default try to use HP Hardware Maintenance Onsite Support
                if ($d['serviceObligationTypeCode'] && $d['serviceObligationTypeCode'] == "C") {
                    if ($d['serviceObligationLineItemEndDate']) {
                        $date = new \DateTime($d['serviceObligationLineItemEndDate']);
                        $max_date = $date;
                    }
                } else {
                    // when several dates are available, will take the last one
                    if ($d['serviceObligationLineItemEndDate']) {
                        $date = new \DateTime($d['serviceObligationLineItemEndDate']);
                        if ($max_date == false || $date > $max_date) {
                            $max_date = $date;
                        }
                    }
                }

            }

            if ($max_date) {
                return $max_date->format('c');
            }
        }
        return false;
    }

    /**
     * @see Manufacturer::getWarrantyInfo()
     */
    public function getWarrantyInfo($contents)
    {
        $info = json_decode($contents, true);

        $max_date = false;
        $i        = false;
        if (isset($info[0]['offers'])) {
            foreach ($info[0]['offers'] as $k => $d) {

                //by default try to use HP Hardware Maintenance Onsite Support
                if ($d['serviceObligationTypeCode'] && $d['serviceObligationTypeCode'] == "C") {
                    if ($d['serviceObligationLineItemEndDate']) {
                        $date = new \DateTime($d['serviceObligationLineItemEndDate']);
                        $max_date = $date;
                        $i = $k;
                    }
                } else {
                    // when several dates are available, will take the last one
                    if ($d['serviceObligationLineItemEndDate']) {
                        $date = new \DateTime($d['serviceObligationLineItemEndDate']);
                        if ($max_date == false || $date > $max_date) {
                            $max_date = $date;
                            $i = $k;
                        }
                    }
                }
            }
        }

        if ($i !== false) {
            return $info[0]['offers'][$i]['offerDescription'];
        }

        return false;
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
        return ["url" => $config->fields['warranty_url']];
    }
}
