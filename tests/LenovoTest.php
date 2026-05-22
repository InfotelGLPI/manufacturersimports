<?php

namespace GlpiPlugin\Manufacturersimports\Tests;

use GlpiPlugin\Manufacturersimports\Config;
use GlpiPlugin\Manufacturersimports\Lenovo;
use PHPUnit\Framework\TestCase;

class LenovoTest extends TestCase
{
    private Lenovo $lenovo;

    protected function setUp(): void
    {
        $this->lenovo = new Lenovo();
    }

    // Builds a minimal HTML page embedding the window.ds_warranties JS variable.
    private function buildPage(array $data): string
    {
        return '<html><script>window.ds_warranties || ' . json_encode($data) . ';</script></html>';
    }

    public function testGetSupplierInfoBuildsUrlWithSerial(): void
    {
        $info = $this->lenovo->getSupplierInfo('ABCD1234');

        $this->assertSame(Config::LENOVO, $info['name']);
        $this->assertStringContainsString('ABCD1234', $info['url']);
        $this->assertStringContainsString('ABCD1234', $info['supplier_url']);
    }

    public function testGetSearchFieldReturnsFalse(): void
    {
        $this->assertFalse($this->lenovo->getSearchField());
    }

    // getBuyDate -----------------------------------------------------------

    public function testGetBuyDateReturnsMachineStartDate(): void
    {
        $page = $this->buildPage([
            'BaseWarranties' => [
                ['Category' => 'MACHINE', 'Start' => '2020-03-01', 'End' => '2023-02-28', 'Type' => 'Depot', 'Name' => 'Base Warranty'],
            ],
        ]);

        $result = $this->lenovo->getBuyDate($page);

        $this->assertStringContainsString('2020-03-01', $result);
    }

    public function testGetBuyDateFallsBackToShipedWhenNoMachineCategory(): void
    {
        $page = $this->buildPage([
            'BaseWarranties' => [
                ['Category' => 'SOFTWARE', 'Start' => '2020-01-01', 'End' => '2022-12-31', 'Type' => 'T', 'Name' => 'N'],
            ],
            'Shiped' => '2020-02-10',
        ]);

        $result = $this->lenovo->getBuyDate($page);

        $this->assertStringContainsString('2020-02-10', $result);
    }

    public function testGetBuyDateReturnsZeroDateWhenNoData(): void
    {
        $page = $this->buildPage(['BaseWarranties' => []]);

        $result = $this->lenovo->getBuyDate($page);

        $this->assertSame('0000-00-00', $result);
    }

    // getExpirationDate ----------------------------------------------------

    public function testGetExpirationDateReturnsLatestEndDateFromBase(): void
    {
        $page = $this->buildPage([
            'BaseWarranties' => [
                ['Category' => 'MACHINE', 'Start' => '2020-01-01', 'End' => '2022-12-31', 'Type' => 'T', 'Name' => 'N'],
                ['Category' => 'MACHINE', 'Start' => '2020-01-01', 'End' => '2025-03-15', 'Type' => 'T', 'Name' => 'N'],
            ],
        ]);

        $result = $this->lenovo->getExpirationDate($page);

        $this->assertStringContainsString('2025-03-15', $result);
    }

    public function testGetExpirationDatePrefersUpmaOverBaseWhenLater(): void
    {
        $page = $this->buildPage([
            'BaseWarranties' => [
                ['Category' => 'MACHINE', 'Start' => '2020-01-01', 'End' => '2023-01-01', 'Type' => 'Depot', 'Name' => 'Base'],
            ],
            'UpmaWarranties' => [
                ['Category' => 'MACHINE', 'Start' => '2021-06-01', 'End' => '2026-06-01', 'Type' => 'Onsite', 'Name' => 'Premier'],
            ],
        ]);

        $result = $this->lenovo->getExpirationDate($page);

        $this->assertStringContainsString('2026-06-01', $result);
    }

    public function testGetExpirationDateIgnoresNonMachineCategories(): void
    {
        $page = $this->buildPage([
            'BaseWarranties' => [
                ['Category' => 'SOFTWARE', 'Start' => '2020-01-01', 'End' => '2030-12-31', 'Type' => 'T', 'Name' => 'N'],
                ['Category' => 'MACHINE',  'Start' => '2020-01-01', 'End' => '2023-01-01', 'Type' => 'T', 'Name' => 'N'],
            ],
        ]);

        $result = $this->lenovo->getExpirationDate($page);

        $this->assertStringContainsString('2023-01-01', $result);
        $this->assertStringNotContainsString('2030', $result);
    }

    public function testGetExpirationDateReturnsZeroDateWhenNoMachineWarranty(): void
    {
        $page = $this->buildPage(['BaseWarranties' => []]);

        $result = $this->lenovo->getExpirationDate($page);

        $this->assertSame('0000-00-00', $result);
    }

    // getStartDate ---------------------------------------------------------

    public function testGetStartDateReturnsStartOfWarrantyWithLatestEnd(): void
    {
        $page = $this->buildPage([
            'BaseWarranties' => [
                ['Category' => 'MACHINE', 'Start' => '2019-06-01', 'End' => '2022-05-31', 'Type' => 'T', 'Name' => 'N'],
                ['Category' => 'MACHINE', 'Start' => '2021-03-10', 'End' => '2024-03-09', 'Type' => 'T', 'Name' => 'N'],
            ],
        ]);

        $result = $this->lenovo->getStartDate($page);

        // start date of the warranty that ends latest (2024-03-09)
        $this->assertStringContainsString('2021-03-10', $result);
    }

    // getWarrantyInfo ------------------------------------------------------

    public function testGetWarrantyInfoReturnsTypeAndName(): void
    {
        $page = $this->buildPage([
            'BaseWarranties' => [
                ['Category' => 'MACHINE', 'Start' => '2020-01-01', 'End' => '2023-01-01', 'Type' => 'Depot', 'Name' => 'Base Warranty'],
            ],
        ]);

        $result = $this->lenovo->getWarrantyInfo($page);

        $this->assertSame('Depot - Base Warranty', $result);
    }

    public function testGetWarrantyInfoPrefersUpmaWarrantyWhenPresent(): void
    {
        $page = $this->buildPage([
            'BaseWarranties' => [
                ['Category' => 'MACHINE', 'Start' => '2020-01-01', 'End' => '2023-01-01', 'Type' => 'Depot', 'Name' => 'Base'],
            ],
            'UpmaWarranties' => [
                ['Category' => 'MACHINE', 'Start' => '2021-06-01', 'End' => '2026-06-01', 'Type' => 'Onsite', 'Name' => 'Premier Support'],
            ],
        ]);

        $result = $this->lenovo->getWarrantyInfo($page);

        $this->assertSame('Onsite - Premier Support', $result);
    }

    public function testGetWarrantyInfoIgnoresNonMachineCategories(): void
    {
        $page = $this->buildPage([
            'BaseWarranties' => [
                ['Category' => 'SOFTWARE', 'Start' => '2020-01-01', 'End' => '2025-12-31', 'Type' => 'T', 'Name' => 'N'],
            ],
        ]);

        $result = $this->lenovo->getWarrantyInfo($page);

        $this->assertSame('', $result);
    }

    public function testGetWarrantyInfoTruncatesAt255Characters(): void
    {
        $long_name = str_repeat('X', 260);
        $page = $this->buildPage([
            'BaseWarranties' => [
                ['Category' => 'MACHINE', 'Start' => '2020-01-01', 'End' => '2023-01-01', 'Type' => 'T', 'Name' => $long_name],
            ],
        ]);

        $result = $this->lenovo->getWarrantyInfo($page);

        $this->assertLessThanOrEqual(254, strlen($result));
    }
}
