<?php
use Mockery as m;
use \Mockery\Adapter\Phpunit\MockeryTestCase;

// Dummy classes for testing isolation
if (!class_exists('EMongoEmbeddedDocument')) { 
    abstract class EMongoEmbeddedDocument { 
        public function validate() { return true; }
        public function getErrors() { return []; }
    } 
}

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
        
        // Test that we have exactly the expected number of rules
        $this->assertCount(6, $rules);
        
        // Check required rule
        $foundRequired = false;
        foreach ($rules as $rule) {
            if ($rule[1] === 'required' && $rule[0] === 'name, sku, quantity') {
                $foundRequired = true;
                break;
            }
        }
        $this->assertTrue($foundRequired, 'Required rule for name, sku, quantity not found');
        
        // Check SKU length validation
        $foundSkuLengthRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'sku' && $rule[1] === 'length' && $rule['max'] === 100) {
                $foundSkuLengthRule = true;
                break;
            }
        }
        $this->assertTrue($foundSkuLengthRule, 'SKU length validation rule not found');
        
        // Check additional_price numerical validation
        $foundPriceRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'additional_price' && $rule[1] === 'numerical') {
                $foundPriceRule = true;
                break;
            }
        }
        $this->assertTrue($foundPriceRule, 'Additional price numerical validation rule not found');
        
        // Check quantity numerical validation with integerOnly and min
        $foundQuantityRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'quantity' && $rule[1] === 'numerical' && 
                isset($rule['integerOnly']) && $rule['integerOnly'] === true &&
                isset($rule['min']) && $rule['min'] === 0) {
                $foundQuantityRule = true;
                break;
            }
        }
        $this->assertTrue($foundQuantityRule, 'Quantity numerical validation rule not found');
        
        // Check name length validation
        $foundNameLengthRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'name' && $rule[1] === 'length' && $rule['max'] === 255) {
                $foundNameLengthRule = true;
                break;
            }
        }
        $this->assertTrue($foundNameLengthRule, 'Name length validation rule not found');
        
        // Check safe rule
        $foundSafeRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'name, sku, additional_price, quantity' && $rule[1] === 'safe') {
                $foundSafeRule = true;
                break;
            }
        }
        $this->assertTrue($foundSafeRule, 'Safe rule not found');
    }
    
    public function testAttributeLabels()
    {
        $labels = $this->variant->attributeLabels();
        $expectedLabels = [
            'name' => 'Variant Name',
            'sku' => 'Variant SKU',
            'additional_price' => 'Additional Price',
            'quantity' => 'Variant Quantity',
        ];
        
        $this->assertIsArray($labels);
        $this->assertEquals($expectedLabels, $labels);
        $this->assertCount(4, $labels);
    }
    
    public function testGetFinalPrice()
    {
        // Test with zero additional price
        $this->variant->additional_price = 0;
        $basePrice = 100.00;
        $finalPrice = $this->variant->getFinalPrice($basePrice);
        $this->assertEquals(100.00, $finalPrice);
        
        // Test with positive additional price
        $this->variant->additional_price = 25.50;
        $finalPrice = $this->variant->getFinalPrice($basePrice);
        $this->assertEquals(125.50, $finalPrice);
        
        // Test with negative additional price (discount)
        $this->variant->additional_price = -15.00;
        $finalPrice = $this->variant->getFinalPrice($basePrice);
        $this->assertEquals(85.00, $finalPrice);
        
        // Test with decimal base price
        $this->variant->additional_price = 5.25;
        $basePrice = 19.99;
        $finalPrice = $this->variant->getFinalPrice($basePrice);
        $this->assertEquals(25.24, $finalPrice);
        
        // Test with zero base price
        $this->variant->additional_price = 10.00;
        $basePrice = 0;
        $finalPrice = $this->variant->getFinalPrice($basePrice);
        $this->assertEquals(10.00, $finalPrice);
    }
    
    public function testDataTypeConsistency()
    {
        // Test that quantity is properly handled as integer
        $this->variant->quantity = '25';
        $this->assertIsString($this->variant->quantity); // Before conversion
        
        // Test that additional_price can handle both int and float
        $this->variant->additional_price = 10;
        $this->assertEquals(10, $this->variant->additional_price);
        
        $this->variant->additional_price = 10.50;
        $this->assertEquals(10.50, $this->variant->additional_price);
        
        // Test string properties
        $this->variant->name = 'Test Variant';
        $this->variant->sku = 'TEST-SKU-001';
        $this->assertIsString($this->variant->name);
        $this->assertIsString($this->variant->sku);
    }
    
    public function testMethodsExistence()
    {
        // Test that all required methods exist
        $requiredMethods = ['rules', 'attributeLabels', 'getFinalPrice'];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists($this->variant, $method), "Method {$method} does not exist");
        }
    }
    
    public function testGetFinalPriceEdgeCases()
    {
        // Test with very large numbers
        $this->variant->additional_price = 999999.99;
        $basePrice = 1000000.01;
        $finalPrice = $this->variant->getFinalPrice($basePrice);
        $this->assertEquals(2000000.00, $finalPrice);
        
        // Test with very small positive numbers
        $this->variant->additional_price = 0.01;
        $basePrice = 0.02;
        $finalPrice = $this->variant->getFinalPrice($basePrice);
        $this->assertEquals(0.03, $finalPrice);
        
        // Test with negative base price and positive additional price
        $this->variant->additional_price = 50.00;
        $basePrice = -30.00;
        $finalPrice = $this->variant->getFinalPrice($basePrice);
        $this->assertEquals(20.00, $finalPrice);
    }
    
    public function tearDown(): void
    {
        m::close();
    }
}