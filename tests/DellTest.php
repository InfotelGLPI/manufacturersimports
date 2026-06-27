<?php

/*
 -------------------------------------------------------------------------
 manufacturersimports plugin for GLPI
 Copyright (C) 2015-2026 by the manufacturersimports Development Team.

 https://github.com/InfotelGLPI/manufacturersimports
 -------------------------------------------------------------------------

 LICENSE

 This file is part of manufacturersimports.

 manufacturersimports is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 manufacturersimports is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with manufacturersimports. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Manufacturersimports\Tests;

use GlpiPlugin\Manufacturersimports\Config;
use GlpiPlugin\Manufacturersimports\Manufacturers\Dell;
use PHPUnit\Framework\TestCase;

class DellTest extends TestCase
{
    private Dell $dell;

    /**
     * Real Dell API response for a Latitude 5410 with 4 entitlements.
     * Used to validate parsing logic against production data.
     */
    private string $realDellJson;

    protected function setUp(): void
    {
        $this->dell = new Dell();

        $this->realDellJson = json_encode([[
            'countryCode'            => 'FR',
            'duplicated'             => null,
            'entitlements'           => [
                [
                    'itemNumber'              => '709-16269',
                    'startDate'               => '2020-10-18T23:00:00Z',
                    'endDate'                 => '2022-01-19T23:59:59.087Z',
                    'entitlementType'         => 'INITIAL',
                    'serviceLevelCode'        => 'CB',
                    'serviceLevelDescription' => 'Collect and Return Support',
                    'serviceLevelGroup'       => '5',
                ],
                [
                    'itemNumber'              => '723-42453',
                    'startDate'               => '2020-10-18T23:00:00Z',
                    'endDate'                 => '2022-01-19T23:59:59.236Z',
                    'entitlementType'         => 'INITIAL',
                    'serviceLevelCode'        => 'ND',
                    'serviceLevelDescription' => 'Onsite Service After Remote Diagnosis',
                    'serviceLevelGroup'       => '5',
                ],
                [
                    'itemNumber'              => '890-60793',
                    'startDate'               => '2020-10-30T00:00:00.098Z',
                    'endDate'                 => '2023-10-30T23:59:59.099Z',
                    'entitlementType'         => 'INITIAL',
                    'serviceLevelCode'        => 'NU',
                    'serviceLevelDescription' => 'ProSupport with Next Business Day Service',
                    'serviceLevelGroup'       => '5',
                ],
                [
                    'itemNumber'              => '890-60794',
                    'startDate'               => '2020-10-30T00:00:00.135Z',
                    'endDate'                 => '2023-10-30T23:59:59.136Z',
                    'entitlementType'         => 'EXTENDED',
                    'serviceLevelCode'        => 'NU',
                    'serviceLevelDescription' => 'ProSupport with Next Business Day Service',
                    'serviceLevelGroup'       => '5',
                ],
            ],
            'id'                     => 'XXXXXXXX',
            'invalid'                => null,
            'localChannel'           => 'ENTP',
            'orderBuid'              => '909',
            'productId'              => 'latitude-14-5410-laptop',
            'productLineDescription' => 'LATITUDE XXXX',
            'serviceTag'             => 'MONSERIAL',
            'shipDate'               => '2020-10-18T23:00:00Z',
            'systemDescription'      => 'Latitude XXXX',
        ]]);
    }

    public function testGetSupplierInfoDefaultsReturnsDellValues(): void
    {
        $info = $this->dell->getSupplierInfo();

        $this->assertSame(Config::DELL, $info['name']);
        $this->assertNotEmpty($info['supplier_url']);
        $this->assertNotEmpty($info['token_url']);
        $this->assertNotEmpty($info['warranty_url']);
    }

    public function testGetSupplierInfoWithSerialBuildsUrl(): void
    {
        $info = $this->dell->getSupplierInfo('SN12345', null, null, null, 'https://dell.com/support/');

        $this->assertSame('https://dell.com/support/SN12345', $info['url']);
    }

    public function testGetBuyDateReturnsFalseWhenShipDateAbsent(): void
    {
        $json = json_encode([[
            'entitlements' => [],
        ]]);

        $this->assertFalse($this->dell->getBuyDate($json));
    }

    public function testGetBuyDateReturnsFormattedDateWhenShipDatePresent(): void
    {
        $json = json_encode([[
            'shipDate'     => '2023-06-15T00:00:00Z',
            'entitlements' => [],
        ]]);

        $result = $this->dell->getBuyDate($json);

        $this->assertIsString($result);
        $this->assertStringContainsString('2023-06-15', $result);
    }

    public function testGetStartDateReturnsFalseWhenNoEntitlements(): void
    {
        $json = json_encode([[
            'entitlements' => [],
        ]]);

        $this->assertFalse($this->dell->getStartDate($json));
    }

    public function testGetStartDateReturnsEarliestStartDate(): void
    {
        $json = json_encode([[
            'entitlements' => [
                ['startDate' => '2023-06-01T00:00:00Z', 'endDate' => null],
                ['startDate' => '2022-01-01T00:00:00Z', 'endDate' => null],
                ['startDate' => '2024-03-01T00:00:00Z', 'endDate' => null],
            ],
        ]]);

        $result = $this->dell->getStartDate($json);

        $this->assertIsString($result);
        $this->assertStringContainsString('2022-01-01', $result);
    }

    public function testGetExpirationDateReturnsFalseWhenNoEntitlements(): void
    {
        $json = json_encode([[
            'entitlements' => [],
        ]]);

        $this->assertFalse($this->dell->getExpirationDate($json));
    }

    public function testGetExpirationDateReturnsLatestEndDate(): void
    {
        $json = json_encode([[
            'entitlements' => [
                ['startDate' => null, 'endDate' => '2024-06-30T00:00:00Z'],
                ['startDate' => null, 'endDate' => '2025-12-31T00:00:00Z'],
            ],
        ]]);

        $result = $this->dell->getExpirationDate($json);

        $this->assertIsString($result);
        $this->assertStringContainsString('2025-12-31', $result);
    }

    public function testGetWarrantyInfoReturnsFalseWhenNoEntitlements(): void
    {
        $json = json_encode([[
            'entitlements' => [],
        ]]);

        $this->assertFalse($this->dell->getWarrantyInfo($json));
    }

    public function testGetWarrantyInfoReturnsServiceLevelDescription(): void
    {
        $json = json_encode([[
            'entitlements' => [
                [
                    'endDate'                => '2025-12-31T00:00:00Z',
                    'serviceLevelDescription' => 'ProSupport Plus',
                ],
            ],
        ]]);

        $result = $this->dell->getWarrantyInfo($json);

        $this->assertSame('ProSupport Plus', $result);
    }

    public function testGetWarrantyInfoPicksEntitlementWithLatestEndDate(): void
    {
        $json = json_encode([[
            'entitlements' => [
                ['endDate' => '2024-06-30T00:00:00Z', 'serviceLevelDescription' => 'Basic'],
                ['endDate' => '2026-12-31T00:00:00Z', 'serviceLevelDescription' => 'Extended'],
            ],
        ]]);

        $result = $this->dell->getWarrantyInfo($json);

        $this->assertSame('Extended', $result);
    }

    // ── Tests with a real Dell API response (Latitude 5410, 4 entitlements) ──

    public function testRealResponseGetBuyDateReturnsShipDate(): void
    {
        // shipDate = 2020-10-18T23:00:00Z
        $result = $this->dell->getBuyDate($this->realDellJson);

        $this->assertIsString($result);
        $this->assertStringContainsString('2020-10-18', $result);
    }

    public function testRealResponseGetStartDateReturnsEarliestEntitlementStart(): void
    {
        // Entitlements 0 and 1 both start 2020-10-18 ; 2 and 3 start 2020-10-30.
        // Earliest = 2020-10-18T23:00:00Z.
        $result = $this->dell->getStartDate($this->realDellJson);

        $this->assertIsString($result);
        $this->assertStringContainsString('2020-10-18', $result);
    }

    public function testRealResponseGetExpirationDateReturnsLatestEndDate(): void
    {
        // Entitlements 0 & 1 end 2022-01-19 (shorter coverage).
        // Entitlement 2 ends 2023-10-30T23:59:59.099Z.
        // Entitlement 3 ends 2023-10-30T23:59:59.136Z (136 ms > 099 ms — latest).
        // Expected: latest = entitlement 3 → 2023-10-30.
        $result = $this->dell->getExpirationDate($this->realDellJson);

        $this->assertIsString($result);
        $this->assertStringContainsString('2023-10-30', $result);
    }

    public function testRealResponseGetWarrantyInfoReturnsDescriptionOfLatestEndDate(): void
    {
        // Entitlement 3 has the latest endDate (2023-10-30T23:59:59.136Z)
        // and serviceLevelDescription = "ProSupport with Next Business Day Service".
        $result = $this->dell->getWarrantyInfo($this->realDellJson);

        $this->assertSame('ProSupport with Next Business Day Service', $result);
    }

    public function testRealResponseGetExpirationDateIsProSupport(): void
    {
        // The ProSupport contracts (ending 2023) must be chosen over the shorter
        // "Collect and Return" contract (ending 2022).
        $result = $this->dell->getExpirationDate($this->realDellJson);

        $this->assertStringContainsString('2023', $result);
        $this->assertStringNotContainsString('2022', $result);
    }

    // ── Generic tests ─────────────────────────────────────────────────────────

    public function testGetWarrantyUrlAppendsSerialToBaseUrl(): void
    {
        $config = new \stdClass();
        $config->fields = ['warranty_url' => 'https://api.dell.com/warranty?tag='];

        $result = Dell::getWarrantyUrl($config, 'ABC123');

        $this->assertSame(['url' => 'https://api.dell.com/warranty?tag=ABC123'], $result);
    }

    public function testGetSearchFieldReturnsFalse(): void
    {
        $this->assertFalse($this->dell->getSearchField());
    }
}
