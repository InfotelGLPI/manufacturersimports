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

use GlpiPlugin\Manufacturersimports\PreImport;
use PHPUnit\Framework\TestCase;

class PreImportTest extends TestCase
{
    public function testGetArrayUrlLinkReturnsEmptyStringForEmptyArray(): void
    {
        $this->assertSame('', PreImport::getArrayUrlLink('field', []));
    }

    public function testGetArrayUrlLinkBuildsQueryStringFromArray(): void
    {
        $result = PreImport::getArrayUrlLink('color', [0 => 'red', 1 => 'blue']);

        $this->assertStringContainsString('&color[0]=red', $result);
        $this->assertStringContainsString('&color[1]=blue', $result);
    }

    public function testGetArrayUrlLinkEncodesSpecialCharacters(): void
    {
        $result = PreImport::getArrayUrlLink('q', [0 => 'hello world']);

        $this->assertStringContainsString('hello+world', $result);
    }

    public function testGetArrayUrlLinkPrefixesEachEntryWithAmpersand(): void
    {
        $result = PreImport::getArrayUrlLink('x', [0 => 'v']);

        $this->assertStringStartsWith('&', $result);
    }

    public function testSelectSupplierWithEmptyNameReturnsEmptyString(): void
    {
        $url = PreImport::selectSupplier('', 'https://example.com', 'SN123');

        $this->assertSame('', $url);
    }

    public function testSelectSupplierWarrantyWithEmptyNameReturnsEmptyString(): void
    {
        $url = PreImport::selectSupplierWarranty('', 'https://example.com', 'SN123');

        $this->assertSame('', $url);
    }

    public function testGetSupplierPostWithEmptyNameReturnsEmptyString(): void
    {
        $post = PreImport::getSupplierPost('', 'SN123');

        $this->assertSame('', $post);
    }

    public function testGetMoreInfosSupplierWithEmptyNameReturnsEmptyString(): void
    {
        $url = PreImport::getMoreInfosSupplier('', 'https://example.com', 'SN123');

        $this->assertSame('', $url);
    }

    public function testGetJSSupplierWithEmptyNameReturnsEmptyString(): void
    {
        $js = PreImport::getJSSupplier('', 'https://example.com', 'SN123');

        $this->assertSame('', $js);
    }

    public function testImportedConstantEqualsTwo(): void
    {
        $this->assertSame(2, PreImport::IMPORTED);
    }

    public function testNotImportedConstantEqualsOne(): void
    {
        $this->assertSame(1, PreImport::NOT_IMPORTED);
    }

    public function testImportedAndNotImportedAreDistinct(): void
    {
        $this->assertNotSame(PreImport::IMPORTED, PreImport::NOT_IMPORTED);
    }
}
