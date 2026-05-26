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
use GlpiPlugin\Manufacturersimports\Manufacturers\Wortmann_ag;
use PHPUnit\Framework\TestCase;

class WortmannTest extends TestCase
{
    private Wortmann_ag $wortmann;

    // Minimal HTML snippet reproducing the Wortmann page structure.
    // "but de la service" (17 chars) + "</td><td>" (9 chars) = offset 26 → DD/MM/YYYY date.
    // "Fin de service"    (14 chars) + "</td><td>" (9 chars) = offset 23 → DD/MM/YYYY date.
    private function buildPage(string $buy_date, string $end_date): string
    {
        return '<html><body><table>'
            . '<tr><td>But de la service</td><td>' . $buy_date . '</td></tr>'
            . '<tr><td>Fin de service</td><td>' . $end_date . '</td></tr>'
            . '</table></body></html>';
    }

    protected function setUp(): void
    {
        $this->wortmann = new Wortmann_ag();
    }

    // getSupplierInfo / getSearchField / getTestUrlField ------------------

    public function testGetSupplierInfoBuildsUrlWithSerial(): void
    {
        $info = $this->wortmann->getSupplierInfo(
            'R5333110',
            null,
            null,
            null,
            'https://www.wortmann.de/fr-fr/profile/snsearch.aspx?SN='
        );

        $this->assertSame(Config::WORTMANN_AG, $info['name']);
        // URL matching the example https://www.wortmann.de/fr-fr/profile/snsearch.aspx?SN=R5333110
        $this->assertSame('https://www.wortmann.de/fr-fr/profile/snsearch.aspx?SN=R5333110', $info['url']);
    }

    public function testGetSupplierInfoContainsDefaultSupplierUrl(): void
    {
        $info = $this->wortmann->getSupplierInfo('ANYSERIAL', null, null, null, 'https://www.wortmann.de/fr-fr/profile/snsearch.aspx?SN=');

        $this->assertStringContainsString('wortmann.de', $info['supplier_url']);
    }

    public function testGetSearchFieldReturnsSearch(): void
    {
        $this->assertSame('search', $this->wortmann->getSearchField());
    }

    public function testGetTestUrlFieldReturnsSupplierUrl(): void
    {
        $this->assertSame('supplier_url', $this->wortmann->getTestUrlField());
    }

    // getBuyDate ----------------------------------------------------------

    public function testGetBuyDateConvertsFromDdMmYyyy(): void
    {
        $page   = $this->buildPage('25/02/2016', '03/03/2019');
        $result = $this->wortmann->getBuyDate($page);

        $this->assertSame('2016-02-25', $result);
    }

    public function testGetBuyDateIsCaseInsensitiveOnFieldName(): void
    {
        // The page uses mixed case; stristr must still find it.
        $page   = str_replace('But de la service', 'BUT DE LA SERVICE', $this->buildPage('10/06/2018', '10/06/2021'));
        $result = $this->wortmann->getBuyDate($page);

        $this->assertSame('2018-06-10', $result);
    }

    public function testGetBuyDateReturnsZeroDateWhenFieldAbsent(): void
    {
        $result = $this->wortmann->getBuyDate('<html>no warranty info here</html>');

        $this->assertSame('0000-00-00', $result);
    }

    // getStartDate (delegates to getBuyDate) ------------------------------

    public function testGetStartDateReturnsSameAsBuyDate(): void
    {
        $page = $this->buildPage('14/07/2020', '14/07/2023');

        $this->assertSame(
            $this->wortmann->getBuyDate($page),
            $this->wortmann->getStartDate($page)
        );
    }

    // getExpirationDate ---------------------------------------------------

    public function testGetExpirationDateConvertsFromDdMmYyyy(): void
    {
        $page   = $this->buildPage('25/02/2016', '03/03/2019');
        $result = $this->wortmann->getExpirationDate($page);

        $this->assertSame('2019-03-03', $result);
    }

    public function testGetExpirationDateIsCaseInsensitiveOnFieldName(): void
    {
        $page   = str_replace('Fin de service', 'FIN DE SERVICE', $this->buildPage('01/01/2020', '31/12/2024'));
        $result = $this->wortmann->getExpirationDate($page);

        $this->assertSame('2024-12-31', $result);
    }

    public function testGetExpirationDateReturnsZeroDateWhenFieldAbsent(): void
    {
        $result = $this->wortmann->getExpirationDate('<html>no warranty info here</html>');

        $this->assertSame('0000-00-00', $result);
    }

    public function testGetExpirationDateDiffersFromBuyDate(): void
    {
        $page = $this->buildPage('25/02/2016', '03/03/2019');

        $this->assertNotSame(
            $this->wortmann->getBuyDate($page),
            $this->wortmann->getExpirationDate($page)
        );
    }
}
