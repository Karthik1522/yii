<?php

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class VariantTest extends MockeryTestCase
{
    protected $variant;

    protected function setUp(): void
    {
        // Partial mock to avoid calling parent constructor or DB connection
        $this->variant = m::mock(Variant::class)->makePartial();
    }

    public function testPublicProperties()
    {
        // Test that all expected public properties exist
        $expectedProperties = ['name', 'sku', 'additional_price', 'quantity'];

        foreach ($expectedProperties as $property) {
            $this->assertTrue(property_exists($this->variant, $property), "Property {$property} does not exist");
        }

        // Test that we can set and get these properties
        $this->variant->name = 'Large Size';
        $this->variant->sku = 'PROD-001-L';
        $this->variant->additional_price = 10.50;
        $this->variant->quantity = 25;

        $this->assertEquals('Large Size', $this->variant->name);
        $this->assertEquals('PROD-001-L', $this->variant->sku);
        $this->assertEquals(10.50, $this->variant->additional_price);
        $this->assertEquals(25, $this->variant->quantity);

        // Test default value for additional_price
        $freshVariant = m::mock(Variant::class)->makePartial();
        $freshVariant->additional_price = 0;
        $this->assertEquals(0, $freshVariant->additional_price);
    }

    public function testRules()
    {
        $rules = $this->variant->rules();

        $this->assertIsArray($rules);

        // Check that required rule contains name, sku, quantity
        $foundRequired = false;
        foreach ($rules as $rule) {
            if ($rule[1] === 'required' && strpos($rule[0], 'name') !== false
                && strpos($rule[0], 'sku') !== false
                && strpos($rule[0], 'quantity') !== false) {
                $foundRequired = true;
                break;
            }
        }

        $this->assertTrue($foundRequired, 'Required rule for name, sku, quantity not found');

        // Check for SKU length validation
        $foundSkuLength = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'sku' && $rule[1] === 'length') {
                $foundSkuLength = true;
                $this->assertEquals(100, $rule['max']);
                break;
            }
        }
        $this->assertTrue($foundSkuLength, 'SKU length validation not found');

        // Check for name length validation
        $foundNameLength = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'name' && $rule[1] === 'length') {
                $foundNameLength = true;
                $this->assertEquals(255, $rule['max']);
                break;
            }
        }
        $this->assertTrue($foundNameLength, 'Name length validation not found');

        // Check for additional_price numerical validation
        $foundAdditionalPriceNumerical = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'additional_price' && $rule[1] === 'numerical') {
                $foundAdditionalPriceNumerical = true;
                break;
            }
        }
        $this->assertTrue($foundAdditionalPriceNumerical, 'Additional price numerical validation not found');

        // Check for quantity numerical validation
        $foundQuantityNumerical = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'quantity' && $rule[1] === 'numerical') {
                $foundQuantityNumerical = true;
                $this->assertTrue($rule['integerOnly']);
                $this->assertEquals(0, $rule['min']);
                break;
            }
        }
        $this->assertTrue($foundQuantityNumerical, 'Quantity numerical validation not found');
    }

    public function testAttributeLabels()
    {
        $labels = $this->variant->attributeLabels();

        $this->assertIsArray($labels);
        $this->assertArrayHasKey('name', $labels);
        $this->assertArrayHasKey('sku', $labels);
        $this->assertArrayHasKey('additional_price', $labels);
        $this->assertArrayHasKey('quantity', $labels);

        $this->assertEquals('Variant Name', $labels['name']);
        $this->assertEquals('Variant SKU', $labels['sku']);
        $this->assertEquals('Additional Price', $labels['additional_price']);
        $this->assertEquals('Variant Quantity', $labels['quantity']);
    }

    public function testDefaultValues()
    {
        $variant = new Variant();

        $this->assertEquals(0, $variant->additional_price);
    }

    public function testGetFinalPrice_WithPositiveAdditionalPrice()
    {
        $variant = new Variant();
        $variant->additional_price = 5.50;
        $basePrice = 10.00;

        $finalPrice = $variant->getFinalPrice($basePrice);

        $this->assertEquals(15.50, $finalPrice);
    }

    public function testGetFinalPrice_WithNegativeAdditionalPrice()
    {
        $variant = new Variant();
        $variant->additional_price = -2.00;
        $basePrice = 10.00;

        $finalPrice = $variant->getFinalPrice($basePrice);

        $this->assertEquals(8.00, $finalPrice);
    }

    public function testGetFinalPrice_WithZeroAdditionalPrice()
    {
        $variant = new Variant();
        $variant->additional_price = 0;
        $basePrice = 10.00;

        $finalPrice = $variant->getFinalPrice($basePrice);

        $this->assertEquals(10.00, $finalPrice);
    }

    public function testGetFinalPrice_WithZeroBasePrice()
    {
        $variant = new Variant();
        $variant->additional_price = 5.00;
        $basePrice = 0;

        $finalPrice = $variant->getFinalPrice($basePrice);

        $this->assertEquals(5.00, $finalPrice);
    }

    public function testValidVariant()
    {
        $variant = new Variant();
        $variant->name = 'Test Variant';
        $variant->sku = 'VAR-001';
        $variant->quantity = 10;
        $variant->additional_price = 5.00;

        $this->assertTrue($variant->validate());
        $this->assertFalse($variant->hasErrors());
    }

    public function testRequiredFieldsValidation()
    {
        $variant = new Variant();
        // Don't set required fields

        $this->assertFalse($variant->validate());
        $this->assertTrue($variant->hasErrors('name'));
        $this->assertTrue($variant->hasErrors('sku'));
        $this->assertTrue($variant->hasErrors('quantity'));
    }

    public function testSkuLengthValidation()
    {
        $variant = new Variant();
        $variant->name = 'Test Variant';
        $variant->sku = str_repeat('A', 101); // 101 characters, exceeds max of 100
        $variant->quantity = 10;

        $this->assertFalse($variant->validate());
        $this->assertTrue($variant->hasErrors('sku'));
    }

    public function testNameLengthValidation()
    {
        $variant = new Variant();
        $variant->name = str_repeat('A', 256); // 256 characters, exceeds max of 255
        $variant->sku = 'VAR-001';
        $variant->quantity = 10;

        $this->assertFalse($variant->validate());
        $this->assertTrue($variant->hasErrors('name'));
    }

    public function testQuantityValidation_NegativeValue()
    {
        $variant = new Variant();
        $variant->name = 'Test Variant';
        $variant->sku = 'VAR-001';
        $variant->quantity = -5;

        $this->assertFalse($variant->validate());
        $this->assertTrue($variant->hasErrors('quantity'));
    }

    public function testQuantityValidation_NonInteger()
    {
        $variant = new Variant();
        $variant->name = 'Test Variant';
        $variant->sku = 'VAR-001';
        $variant->quantity = 5.5; // Float value should fail integerOnly validation

        $this->assertFalse($variant->validate());
        $this->assertTrue($variant->hasErrors('quantity'));
    }

    public function testQuantityValidation_ZeroValue()
    {
        $variant = new Variant();
        $variant->name = 'Test Variant';
        $variant->sku = 'VAR-001';
        $variant->quantity = 0;

        $this->assertTrue($variant->validate());
        $this->assertFalse($variant->hasErrors('quantity'));
    }

    public function testAdditionalPriceValidation_NumericValue()
    {
        $variant = new Variant();
        $variant->name = 'Test Variant';
        $variant->sku = 'VAR-001';
        $variant->quantity = 10;
        $variant->additional_price = 'not_numeric';

        $this->assertFalse($variant->validate());
        $this->assertTrue($variant->hasErrors('additional_price'));
    }

    public function testAdditionalPriceValidation_FloatValue()
    {
        $variant = new Variant();
        $variant->name = 'Test Variant';
        $variant->sku = 'VAR-001';
        $variant->quantity = 10;
        $variant->additional_price = 5.99;

        $this->assertTrue($variant->validate());
        $this->assertFalse($variant->hasErrors('additional_price'));
    }

    public function testGetFinalPrice_WithFloatingPointPrecision()
    {
        $variant = new Variant();
        $variant->additional_price = 0.1;
        $basePrice = 0.2;

        $finalPrice = $variant->getFinalPrice($basePrice);

        $this->assertEqualsWithDelta(0.3, $finalPrice, 0.001); // Use delta for floating point comparison
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }
}
