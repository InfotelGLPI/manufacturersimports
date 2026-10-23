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
use Toolbox;

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
            $info['warranty_url'] = "https://warranty.api.hp.com/productwarranty/v2/jobs";
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
     * URL of the asynchronous job API for a configured warranty URL
     *
     * HP has sunset the synchronous /v2/queries endpoint (HTTP 500, github issue #178):
     * a configuration still pointing at it is redirected to /v2/jobs.
     *
     * @param string $warranty_url
     *
     * @return string
     */
    public static function getJobsUrl(string $warranty_url): string
    {
        return (string) preg_replace('#/queries/?$#', '/jobs', rtrim($warranty_url));
    }

    /**
     * Status of an asynchronous warranty job, normalized
     *
     * HP answers QUEUED, IN_PROGRESS or COMPLETED; the case is not guaranteed.
     *
     * @param mixed $contents body of GET /jobs/{jobId}
     *
     * @return string the status in upper case, '' when the body cannot be read
     */
    public static function getJobStatus($contents): string
    {
        if (!is_string($contents) || $contents === '') {
            return '';
        }
        $info = json_decode($contents, true);

        return is_array($info) && is_string($info['status'] ?? null)
            ? strtoupper(trim($info['status']))
            : '';
    }

    /**
     * Warranty of a computer through the asynchronous job API: submit the serial,
     * poll the job until HP completes it, then read its results (same payload as the
     * former synchronous query). Gives up after JOB_TIMEOUT seconds.
     *
     * @param array $options cURLData() options (url, sn, pn, token, suppliername)
     *
     * @return string|null the warranty payload, null when HP did not deliver it
     */
    public static function getWarrantyFromJob(array $options): ?string
    {
        if (empty($options['token']) || empty($options['sn'])) {
            return null;
        }
        $jobs_url = self::getJobsUrl((string) $options['url']);

        $job = json_decode((string) PostImport::cURLData(['url' => $jobs_url] + $options), true);
        $job_id = is_array($job) ? (string) ($job['jobId'] ?? '') : '';
        // The id is concatenated into the URL path: only accept a plain UUID
        if (preg_match('/^[0-9a-f-]{36}$/i', $job_id) !== 1) {
            Toolbox::logInFile(
                'manufacturersimports',
                sprintf("HP warranty job not created for serial %s\n", $options['sn']),
            );
            return null;
        }

        // The massive import runs under a time limit sized for synchronous calls: restart it
        // so that waiting for the job cannot abort the whole run
        set_time_limit(self::JOB_TIMEOUT + 60);

        $get = ['http_get' => true, 'post' => []] + $options;
        $deadline = time() + self::JOB_TIMEOUT;
        while (time() < $deadline) {
            sleep(self::JOB_POLL_INTERVAL);
            $status = self::getJobStatus(PostImport::cURLData(['url' => "$jobs_url/$job_id"] + $get));
            if ($status === 'COMPLETED') {
                $results = PostImport::cURLData(['url' => "$jobs_url/$job_id/results"] + $get);
                return is_string($results) && $results !== '' ? $results : null;
            }
            if (in_array($status, ['FAILED', 'ERROR', 'CANCELLED', 'EXPIRED'], true)) {
                break;
            }
        }
        Toolbox::logInFile(
            'manufacturersimports',
            sprintf("HP warranty job %s not completed for serial %s\n", $job_id, $options['sn']),
        );
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
