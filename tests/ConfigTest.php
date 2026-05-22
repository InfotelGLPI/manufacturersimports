<?php

namespace GlpiPlugin\Manufacturersimports\Tests;

use GlpiPlugin\Manufacturersimports\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testGetSuppliersContainsAllExpectedVendors(): void
    {
        $suppliers = Config::getSuppliers();

        $this->assertArrayHasKey(Config::DELL, $suppliers);
        $this->assertArrayHasKey(Config::HP, $suppliers);
        $this->assertArrayHasKey(Config::FUJITSU, $suppliers);
        $this->assertArrayHasKey(Config::TOSHIBA, $suppliers);
        $this->assertArrayHasKey(Config::LENOVO, $suppliers);
        $this->assertArrayHasKey(Config::WORTMANN_AG, $suppliers);
    }

    public function testGetSuppliersIncludesEmptyEntry(): void
    {
        $suppliers = Config::getSuppliers();

        $this->assertArrayHasKey(-1, $suppliers);
    }

    public function testVendorConstantValues(): void
    {
        $this->assertSame('Dell', Config::DELL);
        $this->assertSame('HP', Config::HP);
        $this->assertSame('Fujitsu', Config::FUJITSU);
        $this->assertSame('Toshiba', Config::TOSHIBA);
        $this->assertSame('Lenovo', Config::LENOVO);
        $this->assertSame('Wortmann_AG', Config::WORTMANN_AG);
    }

    public function testPrepareInputForAddKeepsAllowedFields(): void
    {
        $config = new Config();

        $input = [
            'name'         => 'Dell',
            'supplier_url' => 'https://example.com',
            'entities_id'  => 1,
            'is_recursive' => 1,
        ];

        $result = $config->prepareInputForAdd($input);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('supplier_url', $result);
        $this->assertArrayHasKey('entities_id', $result);
        $this->assertArrayHasKey('is_recursive', $result);
    }

    public function testPrepareInputForAddDefaultsCredentialFieldsWhenAbsent(): void
    {
        // Fujitsu/Toshiba/Wortmann configs have no supplier_secret in the form.
        // The column has no DB default, so prepareInputForAdd must supply one.
        $config = new Config();

        $result = $config->prepareInputForAdd(['name' => 'Fujitsu', 'entities_id' => 0]);

        $this->assertArrayHasKey('supplier_secret', $result);
        $this->assertArrayHasKey('supplier_key', $result);
        $this->assertArrayHasKey('token_url', $result);
        $this->assertArrayHasKey('warranty_url', $result);
        $this->assertSame('', $result['supplier_secret']);
    }

    public function testPrepareInputForAddDoesNotOverrideProvidedCredentials(): void
    {
        $config = new Config();

        $result = $config->prepareInputForAdd([
            'name'            => 'Dell',
            'supplier_key'    => 'my-client-id',
            'supplier_secret' => 'my-secret',
        ]);

        $this->assertSame('my-client-id', $result['supplier_key']);
        $this->assertSame('my-secret', $result['supplier_secret']);
    }

    public function testPrepareInputForAddRemovesUnknownFields(): void
    {
        $config = new Config();

        $input = [
            'name'          => 'Dell',
            'unknown_field' => 'should_be_removed',
            'inject_sql'    => "'; DROP TABLE --",
        ];

        $result = $config->prepareInputForAdd($input);

        $this->assertArrayNotHasKey('unknown_field', $result);
        $this->assertArrayNotHasKey('inject_sql', $result);
        $this->assertArrayHasKey('name', $result);
    }

    public function testPrepareInputForUpdateKeepsAllowedFields(): void
    {
        $config = new Config();

        $input = [
            'id'           => 5,
            'supplier_key' => 'abc123',
            'token_url'    => 'https://token.example.com',
        ];

        $result = $config->prepareInputForUpdate($input);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('supplier_key', $result);
        $this->assertArrayHasKey('token_url', $result);
    }

    public function testPrepareInputForUpdateRemovesUnknownFields(): void
    {
        $config = new Config();

        $input = [
            'id'         => 5,
            'bad_field'  => 'should_not_pass',
        ];

        $result = $config->prepareInputForUpdate($input);

        $this->assertArrayNotHasKey('bad_field', $result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testDefaultTypesContainKnownItemtypes(): void
    {
        $this->assertContains('Computer', Config::$types);
        $this->assertContains('Monitor', Config::$types);
        $this->assertContains('Printer', Config::$types);
    }

    public function testRegisterTypeAddsNewItemtype(): void
    {
        $originalTypes = Config::$types;

        Config::registerType('SomeCustomAsset');
        $this->assertContains('SomeCustomAsset', Config::$types);

        Config::$types = $originalTypes;
    }

    public function testRegisterTypeDoesNotAddDuplicate(): void
    {
        $originalTypes = Config::$types;

        Config::registerType('Computer');
        $occurrences = count(array_keys(Config::$types, 'Computer'));
        $this->assertSame(1, $occurrences);

        Config::$types = $originalTypes;
    }

    public function testGetTypesAllReturnsAllTypes(): void
    {
        $types = Config::getTypes(true);

        $this->assertNotEmpty($types);
        $this->assertContains('Computer', $types);
    }
}
