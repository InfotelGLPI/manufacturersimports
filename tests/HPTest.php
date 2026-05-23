<?php

namespace GlpiPlugin\Manufacturersimports\Tests;

use GlpiPlugin\Manufacturersimports\Config;
use GlpiPlugin\Manufacturersimports\Manufacturers\HP;
use PHPUnit\Framework\TestCase;

class HPTest extends TestCase
{
    private HP $hp;

    protected function setUp(): void
    {
        $this->hp = new HP();
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
}
