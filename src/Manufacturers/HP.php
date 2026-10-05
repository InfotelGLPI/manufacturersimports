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

namespace GlpiPlugin\Manufacturersimports\Manufacturers;

use GlpiPlugin\Manufacturersimports\Config;
use GlpiPlugin\Manufacturersimports\PostImport;
use Search;

/**
 * Class HP
 */
class HP extends Manufacturer
{
    /** Maximum wait, in seconds, for an asynchronous warranty job */
    private const JOB_TIMEOUT = 60;

    /** Delay, in seconds, between two status checks of an asynchronous warranty job */
    private const JOB_POLL_INTERVAL = 5;

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

    public function getTestUrlField(): string
    {
        return 'token_url';
    }

    public function getSupplierInfo(
        $compSerial = null,
        $otherSerial = null,
        $key = null,
        $apisecret = null,
        $supplierUrl = null
    ) {
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
                'client_id' => Config::decryptSecret($config->fields["supplier_key"]),
                'client_secret' => Config::decryptSecret($config->fields["supplier_secret"]),
                'grant_type' => 'client_credentials',
            ],
            "suppliername" => $config->fields["name"],
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
     * Whether the synchronous warranty query was turned down by HP: it then answers HTTP 200
     * with a {"message": "..."} object instead of the list of products.
     *
     * @param mixed $contents
     */
    public static function isQueryRejected($contents): bool
    {
        if (!is_string($contents) || $contents === '') {
            return false;
        }
        $info = json_decode($contents, true);

        return is_array($info) && !array_is_list($info) && isset($info['message']);
    }

    /**
     * Fallback on the asynchronous job API when the synchronous query is turned down: submit the
     * serial, poll the job until HP completes it, then read its results (same payload as the
     * synchronous query). Gives up after JOB_TIMEOUT seconds.
     *
     * @param array $options cURLData() options of the synchronous query (url, sn, pn, token)
     *
     * @return string|null the warranty payload, null when the job did not complete in time
     */
    public static function getWarrantyFromJob(array $options): ?string
    {
        if (empty($options['token']) || empty($options['sn'])) {
            return null;
        }
        // https://warranty.api.hp.com/productwarranty/v2/queries -> .../v2/jobs
        $jobs_url = preg_replace('#/queries/?$#', '/jobs', (string) $options['url']);
        if ($jobs_url === null || $jobs_url === $options['url']) {
            return null;
        }

        $job = json_decode((string) PostImport::cURLData(['url' => $jobs_url] + $options), true);
        $job_id = is_array($job) ? (string) ($job['jobId'] ?? '') : '';
        // The id is concatenated into the URL path: only accept a plain UUID
        if (preg_match('/^[0-9a-f-]{36}$/i', $job_id) !== 1) {
            return null;
        }

        // The massive import runs under a time limit sized for synchronous calls: restart it
        // so that waiting for the job cannot abort the whole run
        set_time_limit(self::JOB_TIMEOUT + 60);

        $get = ['http_get' => true, 'post' => []] + $options;
        $deadline = time() + self::JOB_TIMEOUT;
        while (time() < $deadline) {
            sleep(self::JOB_POLL_INTERVAL);
            $status = json_decode((string) PostImport::cURLData(['url' => "$jobs_url/$job_id"] + $get), true);
            if (($status['status'] ?? '') === 'Completed') {
                $results = PostImport::cURLData(['url' => "$jobs_url/$job_id/results"] + $get);
                return is_string($results) && $results !== '' ? $results : null;
            }
        }
        return null;
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
