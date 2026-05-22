<?php

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
