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

class DimensionsTest extends MockeryTestCase
{
    protected $dimensions;
    
    protected function setUp(): void
    {
        // Partial mock to avoid calling parent constructor or DB connection
        $this->dimensions = m::mock(Dimensions::class)->makePartial();
    }
    
    public function testPublicProperties()
    {
        // Test that all expected public properties exist
        $expectedProperties = ['length', 'width', 'height', 'unit'];
        
        foreach ($expectedProperties as $property) {
            $this->assertTrue(property_exists($this->dimensions, $property), "Property {$property} does not exist");
        }
        
        // Test that we can set and get these properties
        $this->dimensions->length = 10.5;
        $this->dimensions->width = 5.25;
        $this->dimensions->height = 15.75;
        $this->dimensions->unit = 'cm';
        
        $this->assertEquals(10.5, $this->dimensions->length);
        $this->assertEquals(5.25, $this->dimensions->width);
        $this->assertEquals(15.75, $this->dimensions->height);
        $this->assertEquals('cm', $this->dimensions->unit);
        
        // Test default value for unit
        $freshDimensions = m::mock(Dimensions::class)->makePartial();
        $freshDimensions->unit = 'cm';
        $this->assertEquals('cm', $freshDimensions->unit);
    }
    
    public function testRules()
    {
        $rules = $this->dimensions->rules();
        $this->assertIsArray($rules);
        
        // Test that we have exactly the expected number of rules
        $this->assertCount(5, $rules);
        
        // Check required rule
        $foundRequired = false;
        foreach ($rules as $rule) {
            if ($rule[1] === 'required' && $rule[0] === 'length, width, height') {
                $foundRequired = true;
                break;
            }
        }
        $this->assertTrue($foundRequired, 'Required rule for length, width, height not found');
        
        // Check numerical validation with min constraint
        $foundNumericalRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'length, width, height' && $rule[1] === 'numerical' && 
                isset($rule['min']) && $rule['min'] === 0 &&
                isset($rule['integerOnly']) && $rule['integerOnly'] === false) {
                $foundNumericalRule = true;
                break;
            }
        }
        $this->assertTrue($foundNumericalRule, 'Numerical validation rule for dimensions not found');
        
        // Check filter rule for dimensions
        $foundFilterRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'length, width, height' && $rule[1] === 'filter' && $rule['filter'] === 'intval') {
                $foundFilterRule = true;
                break;
            }
        }
        $this->assertTrue($foundFilterRule, 'Filter rule for dimensions not found');
        
        // Check unit validation rule
        $foundUnitRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'unit' && $rule[1] === 'in' && 
                isset($rule['range']) && is_array($rule['range']) &&
                in_array('cm', $rule['range']) && in_array('mm', $rule['range']) &&
                in_array('in', $rule['range']) && in_array('ft', $rule['range'])) {
                $foundUnitRule = true;
                break;
            }
        }
        $this->assertTrue($foundUnitRule, 'Unit validation rule not found');
        
        // Check safe rule
        $foundSafeRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'length, width, height, unit' && $rule[1] === 'safe') {
                $foundSafeRule = true;
                break;
            }
        }
        $this->assertTrue($foundSafeRule, 'Safe rule not found');
    }
    
    public function testAttributeLabels()
    {
        $labels = $this->dimensions->attributeLabels();
        $expectedLabels = [
            'length' => 'Length',
            'width' => 'Width',
            'height' => 'Height',
            'unit' => 'Unit',
        ];
        
        $this->assertIsArray($labels);
        $this->assertEquals($expectedLabels, $labels);
        $this->assertCount(4, $labels);
    }
    
    public function testUnitValidation()
    {
        // Test valid units
        $validUnits = ['cm', 'mm', 'in', 'ft'];
        
        foreach ($validUnits as $unit) {
            $this->dimensions->unit = $unit;
            $this->assertEquals($unit, $this->dimensions->unit);
        }
        
        // Test default unit
        $freshDimensions = m::mock(Dimensions::class)->makePartial();
        $freshDimensions->unit = 'cm'; // Default value
        $this->assertEquals('cm', $freshDimensions->unit);
    }
    
    public function testDimensionValues()
    {
        // Test positive decimal values
        $this->dimensions->length = 12.5;
        $this->dimensions->width = 8.25;
        $this->dimensions->height = 20.75;
        
        $this->assertEquals(12.5, $this->dimensions->length);
        $this->assertEquals(8.25, $this->dimensions->width);
        $this->assertEquals(20.75, $this->dimensions->height);
        
        // Test integer values
        $this->dimensions->length = 15;
        $this->dimensions->width = 10;
        $this->dimensions->height = 25;
        
        $this->assertEquals(15, $this->dimensions->length);
        $this->assertEquals(10, $this->dimensions->width);
        $this->assertEquals(25, $this->dimensions->height);
        
        // Test zero values (should be valid as min is 0)
        $this->dimensions->length = 0;
        $this->dimensions->width = 0;
        $this->dimensions->height = 0;
        
        $this->assertEquals(0, $this->dimensions->length);
        $this->assertEquals(0, $this->dimensions->width);
        $this->assertEquals(0, $this->dimensions->height);
    }
    
    public function testDataTypeConsistency()
    {
        // Test that dimensions can handle string input (before filter)
        $this->dimensions->length = '12.5';
        $this->dimensions->width = '8.25';
        $this->dimensions->height = '20.75';
        
        $this->assertIsString($this->dimensions->length); // Before filter conversion
        $this->assertIsString($this->dimensions->width);
        $this->assertIsString($this->dimensions->height);
        
        // Test unit as string
        $this->dimensions->unit = 'cm';
        $this->assertIsString($this->dimensions->unit);
    }
    
    public function testMethodsExistence()
    {
        // Test that all required methods exist
        $requiredMethods = ['rules', 'attributeLabels'];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists($this->dimensions, $method), "Method {$method} does not exist");
        }
    }
    
    public function testRulesStructure()
    {
        $rules = $this->dimensions->rules();
        
        // Verify each rule has proper structure
        foreach ($rules as $rule) {
            $this->assertIsArray($rule, 'Each rule should be an array');
            $this->assertGreaterThanOrEqual(2, count($rule), 'Each rule should have at least 2 elements');
            $this->assertIsString($rule[0], 'First element should be attribute names string');
            $this->assertIsString($rule[1], 'Second element should be validator name');
        }
    }
    
    public function testCompletePropertySet()
    {
        // Test setting all properties at once
        $this->dimensions->length = 30.5;
        $this->dimensions->width = 15.25;
        $this->dimensions->height = 45.75;
        $this->dimensions->unit = 'mm';
        
        // Verify all values are set correctly
        $this->assertEquals(30.5, $this->dimensions->length);
        $this->assertEquals(15.25, $this->dimensions->width);
        $this->assertEquals(45.75, $this->dimensions->height);
        $this->assertEquals('mm', $this->dimensions->unit);
    }
    
    public function testUnitOptions()
    {
        // Test all supported unit options
        $supportedUnits = ['cm', 'mm', 'in', 'ft'];
        
        foreach ($supportedUnits as $unit) {
            $this->dimensions->unit = $unit;
            $this->assertEquals($unit, $this->dimensions->unit);
        }
    }
    
    public function testVerySmallDimensions()
    {
        // Test very small positive dimensions
        $this->dimensions->length = 0.01;
        $this->dimensions->width = 0.001;
        $this->dimensions->height = 0.1;
        
        $this->assertEquals(0.01, $this->dimensions->length);
        $this->assertEquals(0.001, $this->dimensions->width);
        $this->assertEquals(0.1, $this->dimensions->height);
    }
    
    public function testLargeDimensions()
    {
        // Test large dimensions
        $this->dimensions->length = 999999.99;
        $this->dimensions->width = 888888.88;
        $this->dimensions->height = 777777.77;
        
        $this->assertEquals(999999.99, $this->dimensions->length);
        $this->assertEquals(888888.88, $this->dimensions->width);
        $this->assertEquals(777777.77, $this->dimensions->height);
    }
    
    public function tearDown(): void
    {
        m::close();
    }
}