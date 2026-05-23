<?php

namespace GlpiPlugin\Manufacturersimports\Tests;

use GlpiPlugin\Manufacturersimports\Config;
use GlpiPlugin\Manufacturersimports\Manufacturers\Toshiba;
use PHPUnit\Framework\TestCase;

class ToshibaTest extends TestCase
{
    private Toshiba $toshiba;

    // Real API response for a Toshiba Tecra A50-C-1H9 (serial MONSERIAL, shipped 2016-02-25).
    // customerPurchaseDate is intentionally empty in this response.
    private string $realJson = '{"serviceTypes":{},"commonBean":{"customerTitle":"","warOnsiteDate":"2016-02-03 00:00:00.0","mdlmCountryList":"LOCAL","warrantyFlag":"LOCAL","serialNumber":"MONSERIAL","manufacturingBuildId":"284063501","operatingSystem":"","dmi":"","warrantyLabour":"365","countryManufactured":"China","warrantyOnsiteExpiryDate":"2016-02-03 00:00:00.0","countryPurchased":"","modelShortNumber":"PS57HE","defaultWarrantyParts":"365","defaultWarrantyOnsite":"0","defaultWarrantyLaborCallCenter":"365","assetTagNumber":"","mfgblastUpdateDate":"2019-07-12 01:22:04.0","registrationAddressLastUpdateDate":"","registrationLastUpdateDate":"","serviceProgramFlag":"","serviceProgramCountryList":"","defaultWarrantyLabor":"365","warrantyCallCenter":"365","modelMasterId":"607619","serviceDuration":"365","modelMasterUpdateDate":"2015-12-07 09:55:57.0","warrantyCountryFlag":"","listPrice":"","listPriceCurrency":"","regExpDate":"2017-02-02 00:00:00.0","regLabExpDate":"2017-02-02 00:00:00.0","warrantyParts":"365","warrantyOnsite":"0","companyName":"","partNumber":"PS57HE-00F001FR","warrantyExpiryDate":"2017-02-02 00:00:00.0","certifications":"CE","modelSub":"TRO","productCategory":"Portable","customerPurchaseDate":"","customerFirstName":"","customerLastName":"","registrationNumber":"","shipDate":"2016-02-25 00:00:00.0","assetProductionDate":"2016-02-03 00:00:00.0","modelName":"A50-C-1H9","subsidiarySubmittedBy":"TRO","countrySold":"France","warrantyCallCenterDate":"2017-02-02 00:00:00.0","subsidiaryModel":"TRO","assetStatus":"Active","warrantyLaborExpiryDate":"2017-02-02 00:00:00.0","warrantyPartsExpiryDate":"2017-02-02 00:00:00.0","assetFirstBootDate":"","modelFamily":"TECRA"},"svcPgm":[],"warranty":"Warranty expired","registrationInfo":"/support/productRegister?serialnumber=MONSERIAL&modelPartNumber=PS57HE-00F001FR","hasWarrantyExpired":true}';

    protected function setUp(): void
    {
        $this->toshiba = new Toshiba();
    }

    // getSupplierInfo / getWarrantyUrl ------------------------------------

    public function testGetSupplierInfoBuildsUrlWithSerial(): void
    {
        $info = $this->toshiba->getSupplierInfo('MYSERIAL', null, null, null, 'https://support.dynabook.com/support/warrantyResults?');

        $this->assertSame(Config::TOSHIBA, $info['name']);
        $this->assertStringContainsString('MYSERIAL', $info['url']);
    }

    public function testGetWarrantyUrlAppendsSerialToHardcodedUrl(): void
    {
        $config = new \stdClass();
        $config->fields = [];

        $result = Toshiba::getWarrantyUrl($config, 'SN12345');

        $this->assertStringContainsString('SN12345', $result['url']);
        $this->assertStringContainsString('dynabook.com', $result['url']);
    }

    public function testGetSearchFieldReturnsFalse(): void
    {
        $this->assertFalse($this->toshiba->getSearchField());
    }

    // getBuyDate — real response -----------------------------------------

    public function testRealResponseGetBuyDateReturnsShipDate(): void
    {
        $result = $this->toshiba->getBuyDate($this->realJson);

        $this->assertStringContainsString('2016-02-25', $result);
    }

    // getBuyDate — edge cases -------------------------------------------

    public function testGetBuyDateReturnsZeroDateWhenShipDateAbsent(): void
    {
        $result = $this->toshiba->getBuyDate('{"commonBean":{}}');

        $this->assertSame('0000-00-00', $result);
    }

    public function testGetBuyDateExtractsDateFromMinimalJson(): void
    {
        // shipDate (8) + `":"` (3) = offset 11
        $json = '"shipDate":"2021-07-14 00:00:00.0"';

        $result = $this->toshiba->getBuyDate($json);

        $this->assertStringContainsString('2021-07-14', $result);
    }

    // getStartDate — real response (empty customerPurchaseDate) ----------

    public function testRealResponseGetStartDateReturnsZeroDateWhenCustomerPurchaseDateEmpty(): void
    {
        $result = $this->toshiba->getStartDate($this->realJson);

        // customerPurchaseDate is "" in this response → no valid date
        $this->assertSame('0000-00-00', $result);
    }

    // getStartDate — with a present date --------------------------------

    public function testGetStartDateExtractsDateWhenPresent(): void
    {
        // customerPurchaseDate (20) + `":"` (3) = offset 23
        $json = '"customerPurchaseDate":"2020-03-10 00:00:00.0"';

        $result = $this->toshiba->getStartDate($json);

        $this->assertStringContainsString('2020-03-10', $result);
    }

    public function testGetStartDateReturnsZeroDateWhenAbsent(): void
    {
        $result = $this->toshiba->getStartDate('{"commonBean":{}}');

        $this->assertSame('0000-00-00', $result);
    }

    // getExpirationDate — real response ----------------------------------

    public function testRealResponseGetExpirationDateReturnsWarrantyExpiryDate(): void
    {
        $result = $this->toshiba->getExpirationDate($this->realJson);

        $this->assertStringContainsString('2017-02-02', $result);
    }

    public function testRealResponseGetExpirationDateIsNotOnsiteDate(): void
    {
        // warrantyOnsiteExpiryDate (2016-02-03) must not be returned;
        // warrantyExpiryDate (2017-02-02) is the correct field.
        $result = $this->toshiba->getExpirationDate($this->realJson);

        $this->assertStringNotContainsString('2016-02-03', $result);
    }

    // getExpirationDate — edge cases ------------------------------------

    public function testGetExpirationDateExtractsDateFromMinimalJson(): void
    {
        // warrantyExpiryDate (18) + `":"` (3) = offset 21
        $json = '"warrantyExpiryDate":"2025-12-31 00:00:00.0"';

        $result = $this->toshiba->getExpirationDate($json);

        $this->assertStringContainsString('2025-12-31', $result);
    }

    public function testGetExpirationDateReturnsZeroDateWhenAbsent(): void
    {
        $result = $this->toshiba->getExpirationDate('{"commonBean":{}}');

        $this->assertSame('0000-00-00', $result);
    }
}
