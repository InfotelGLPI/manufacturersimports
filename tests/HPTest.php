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
 the Free Software Foundation; either version 2 of the License, or
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
use GlpiPlugin\Manufacturersimports\Manufacturers\HP;
use PHPUnit\Framework\TestCase;

class HPTest extends TestCase
{
    private HP $hp;

    /**
     * Real HP API response for a Z2 Tower G9 (MONSERIAL) with 2 offers (type W).
     * Used to validate parsing logic against production data.
     */
    private string $realHpJson;

    protected function setUp(): void
    {
        $this->hp = new HP();

        $this->realHpJson = json_encode([[
            'product' => [
                'serialNumber'       => 'MONSERIAL',
                'productDescription' => 'HP Z2 Tower G9 Workstation Desktop PC (4Y0H8AV)',
                'productNumber'      => null,
                'countryCode'        => 'US',
            ],
            'offers' => [
                [
                    'offerDescription'                   => 'Warranty Hardware Maintenance On-Site',
                    'serviceObligationTypeCode'          => 'W',
                    'serviceObligationLineItemStartDate' => '2022-10-05',
                    'serviceObligationLineItemEndDate'   => '2025-10-04',
                ],
                [
                    'offerDescription'                   => 'Wty: HP Support for Initial Setup',
                    'serviceObligationTypeCode'          => 'W',
                    'serviceObligationLineItemStartDate' => '2022-10-05',
                    'serviceObligationLineItemEndDate'   => '2023-01-02',
                ],
            ],
        ]]);
    }

    public function testGetSupplierInfoDefaultsReturnsHPValues(): void
    {
        $info = $this->hp->getSupplierInfo();

        $this->assertSame(Config::HP, $info['name']);
        $this->assertNotEmpty($info['supplier_url']);
        $this->assertNotEmpty($info['token_url']);
        $this->assertNotEmpty($info['warranty_url']);
    }

    public function testGetBuyDateReturnsNullWhenOffersIsEmpty(): void
    {
        $json = json_encode([[
            'offers' => [],
        ]]);

        $this->assertNull($this->hp->getBuyDate($json));
    }

    public function testGetBuyDateReturnsNullWhenOffersHaveNoDates(): void
    {
        $json = json_encode([[
            'offers' => [
                [
                    'serviceObligationTypeCode'           => 'X',
                    'serviceObligationLineItemStartDate'  => null,
                ],
            ],
        ]]);

        $this->assertNull($this->hp->getBuyDate($json));
    }

    public function testGetBuyDatePrefersTypeCodeCOverOtherCodes(): void
    {
        $json = json_encode([[
            'offers' => [
                [
                    'serviceObligationTypeCode'          => 'X',
                    'serviceObligationLineItemStartDate' => '2020-01-01T00:00:00Z',
                ],
                [
                    'serviceObligationTypeCode'          => 'C',
                    'serviceObligationLineItemStartDate' => '2023-06-15T00:00:00Z',
                ],
            ],
        ]]);

        $result = $this->hp->getBuyDate($json);

        $this->assertIsString($result);
        $this->assertStringContainsString('2023-06-15', $result);
    }

    public function testGetBuyDateReturnsLatestDateForNonCOffers(): void
    {
        $json = json_encode([[
            'offers' => [
                [
                    'serviceObligationTypeCode'          => 'X',
                    'serviceObligationLineItemStartDate' => '2020-01-01T00:00:00Z',
                ],
                [
                    'serviceObligationTypeCode'          => 'Y',
                    'serviceObligationLineItemStartDate' => '2022-06-01T00:00:00Z',
                ],
            ],
        ]]);

        $result = $this->hp->getBuyDate($json);

        $this->assertIsString($result);
        $this->assertStringContainsString('2022-06-01', $result);
    }

    public function testGetStartDateDelegatesToGetBuyDate(): void
    {
        $json = json_encode([[
            'offers' => [
                [
                    'serviceObligationTypeCode'          => 'C',
                    'serviceObligationLineItemStartDate' => '2022-03-10T00:00:00Z',
                ],
            ],
        ]]);

        $this->assertSame($this->hp->getBuyDate($json), $this->hp->getStartDate($json));
    }

    public function testGetExpirationDateReturnsFalseWhenOffersIsEmpty(): void
    {
        $json = json_encode([[
            'offers' => [],
        ]]);

        $this->assertFalse($this->hp->getExpirationDate($json));
    }

    public function testGetExpirationDatePrefersTypeCodeCOverOtherCodes(): void
    {
        $json = json_encode([[
            'offers' => [
                [
                    'serviceObligationTypeCode'         => 'X',
                    'serviceObligationLineItemEndDate'  => '2020-12-31T00:00:00Z',
                ],
                [
                    'serviceObligationTypeCode'         => 'C',
                    'serviceObligationLineItemEndDate'  => '2026-12-31T00:00:00Z',
                ],
            ],
        ]]);

        $result = $this->hp->getExpirationDate($json);

        $this->assertIsString($result);
        $this->assertStringContainsString('2026-12-31', $result);
    }

    public function testGetExpirationDateReturnsLatestDateForNonCOffers(): void
    {
        $json = json_encode([[
            'offers' => [
                [
                    'serviceObligationTypeCode'        => 'X',
                    'serviceObligationLineItemEndDate' => '2024-01-01T00:00:00Z',
                ],
                [
                    'serviceObligationTypeCode'        => 'Y',
                    'serviceObligationLineItemEndDate' => '2025-06-30T00:00:00Z',
                ],
            ],
        ]]);

        $result = $this->hp->getExpirationDate($json);

        $this->assertIsString($result);
        $this->assertStringContainsString('2025-06-30', $result);
    }

    public function testGetWarrantyInfoReturnsFalseWhenOffersIsEmpty(): void
    {
        $json = json_encode([[
            'offers' => [],
        ]]);

        $this->assertFalse($this->hp->getWarrantyInfo($json));
    }

    public function testGetWarrantyInfoReturnsOfferDescription(): void
    {
        $json = json_encode([[
            'offers' => [
                [
                    'serviceObligationTypeCode'        => 'C',
                    'serviceObligationLineItemEndDate' => '2026-12-31T00:00:00Z',
                    'offerDescription'                 => 'HP Care Pack 3Y',
                ],
            ],
        ]]);

        $result = $this->hp->getWarrantyInfo($json);

        $this->assertSame('HP Care Pack 3Y', $result);
    }

    public function testGetWarrantyUrlReturnsWarrantyApiUrlIgnoringSerial(): void
    {
        $config = new \stdClass();
        $config->fields = ['warranty_url' => 'https://warranty.api.hp.com/productwarranty/v2/queries'];

        $result = HP::getWarrantyUrl($config, 'SN999');

        $this->assertSame(['url' => 'https://warranty.api.hp.com/productwarranty/v2/queries'], $result);
    }

    public function testGetSearchFieldReturnsFalse(): void
    {
        $this->assertFalse($this->hp->getSearchField());
    }

    // ── Tests with a real HP API response (Z2 Tower G9, 2 W-type offers) ──

    public function testRealResponseGetBuyDateReturnsStartDate(): void
    {
        // Both offers start 2022-10-05; no type-C offer, so the max start date is returned.
        $result = $this->hp->getBuyDate($this->realHpJson);

        $this->assertIsString($result);
        $this->assertStringContainsString('2022-10-05', $result);
    }

    public function testRealResponseGetStartDateMatchesBuyDate(): void
    {
        $this->assertSame(
            $this->hp->getBuyDate($this->realHpJson),
            $this->hp->getStartDate($this->realHpJson)
        );
    }

    public function testRealResponseGetExpirationDateReturnsLatestEndDate(): void
    {
        // offer[0] ends 2025-10-04, offer[1] ends 2023-01-02 — latest is 2025-10-04.
        $result = $this->hp->getExpirationDate($this->realHpJson);

        $this->assertIsString($result);
        $this->assertStringContainsString('2025-10-04', $result);
        $this->assertStringNotContainsString('2023', $result);
    }

    public function testRealResponseGetWarrantyInfoReturnsDescriptionOfLatestEndDate(): void
    {
        // offer[0] has the latest end date (2025-10-04) → its offerDescription must be returned.
        $result = $this->hp->getWarrantyInfo($this->realHpJson);

        $this->assertSame('Warranty Hardware Maintenance On-Site', $result);
    }
}
