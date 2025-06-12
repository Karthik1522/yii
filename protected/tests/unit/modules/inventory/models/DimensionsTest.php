<?php

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

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

        // Check that required rule contains length, width, height
        $foundRequired = false;
        foreach ($rules as $rule) {
            if ($rule[1] === 'required' && strpos($rule[0], 'length') !== false
                && strpos($rule[0], 'width') !== false
                && strpos($rule[0], 'height') !== false) {
                $foundRequired = true;
                break;
            }
        }

        $this->assertTrue($foundRequired, 'Required rule for length, width, height not found');

        // Check for numerical validation on dimensions
        $foundNumerical = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'length, width, height' && $rule[1] === 'numerical') {
                $foundNumerical = true;
                $this->assertEquals(0, $rule['min']);
                $this->assertFalse($rule['integerOnly']);
                break;
            }
        }
        $this->assertTrue($foundNumerical, 'Numerical rule for dimensions not found');

        // Check for unit validation
        $foundUnitValidation = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'unit' && $rule[1] === 'in') {
                $foundUnitValidation = true;
                $this->assertEquals(['cm', 'mm', 'in', 'ft'], $rule['range']);
                break;
            }
        }
        $this->assertTrue($foundUnitValidation, 'Unit validation rule not found');

        // Check for filter validation
        $foundFilter = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'length, width, height' && $rule[1] === 'filter') {
                $foundFilter = true;
                $this->assertEquals('intval', $rule['filter']);
                break;
            }
        }
        $this->assertTrue($foundFilter, 'Filter rule for dimensions not found');
    }

    public function testAttributeLabels()
    {
        $labels = $this->dimensions->attributeLabels();

        $this->assertIsArray($labels);
        $this->assertArrayHasKey('length', $labels);
        $this->assertArrayHasKey('width', $labels);
        $this->assertArrayHasKey('height', $labels);
        $this->assertArrayHasKey('unit', $labels);

        $this->assertEquals('Length', $labels['length']);
        $this->assertEquals('Width', $labels['width']);
        $this->assertEquals('Height', $labels['height']);
        $this->assertEquals('Unit', $labels['unit']);
    }

    public function testDefaultValues()
    {
        $dimensions = new Dimensions();

        $this->assertEquals('cm', $dimensions->unit);
    }

    public function testValidDimensionsWithValidUnit()
    {
        $dimensions = new Dimensions();
        $dimensions->length = 10.5;
        $dimensions->width = 5.2;
        $dimensions->height = 8.7;
        $dimensions->unit = 'cm';

        $this->assertTrue($dimensions->validate());
        $this->assertFalse($dimensions->hasErrors());
    }

    public function testValidDimensionsWithDifferentUnits()
    {
        $validUnits = ['cm', 'mm', 'in', 'ft'];

        foreach ($validUnits as $unit) {
            $dimensions = new Dimensions();
            $dimensions->length = 10;
            $dimensions->width = 5;
            $dimensions->height = 8;
            $dimensions->unit = $unit;

            $this->assertTrue($dimensions->validate(), "Validation failed for unit: {$unit}");
            $this->assertFalse($dimensions->hasErrors(), "Has errors for unit: {$unit}");
        }
    }

    public function testInvalidUnit()
    {
        $dimensions = new Dimensions();
        $dimensions->length = 10;
        $dimensions->width = 5;
        $dimensions->height = 8;
        $dimensions->unit = 'invalid_unit';

        $this->assertFalse($dimensions->validate());
        $this->assertTrue($dimensions->hasErrors('unit'));
    }

    public function testRequiredFieldsValidation()
    {
        $dimensions = new Dimensions();
        // Don't set required fields

        $this->assertFalse($dimensions->validate());
        $this->assertTrue($dimensions->hasErrors('length'));
        $this->assertTrue($dimensions->hasErrors('width'));
        $this->assertTrue($dimensions->hasErrors('height'));
    }

    public function testNegativeDimensionsValidation()
    {
        $dimensions = new Dimensions();
        $dimensions->length = -10;
        $dimensions->width = -5;
        $dimensions->height = -8;
        $dimensions->unit = 'cm';

        $this->assertFalse($dimensions->validate());
        $this->assertTrue($dimensions->hasErrors('length'));
        $this->assertTrue($dimensions->hasErrors('width'));
        $this->assertTrue($dimensions->hasErrors('height'));
    }

    public function testZeroDimensionsValidation()
    {
        $dimensions = new Dimensions();
        $dimensions->length = 0;
        $dimensions->width = 0;
        $dimensions->height = 0;
        $dimensions->unit = 'cm';

        $this->assertTrue($dimensions->validate());
        $this->assertFalse($dimensions->hasErrors());
    }

    public function testFloatingPointDimensions()
    {
        $dimensions = new Dimensions();
        $dimensions->length = 10.5;
        $dimensions->width = 5.25;
        $dimensions->height = 8.75;
        $dimensions->unit = 'cm';

        $this->assertTrue($dimensions->validate());
        $this->assertFalse($dimensions->hasErrors());
    }

    public function testEmptyUnitUsesDefault()
    {
        $dimensions = new Dimensions();
        $dimensions->length = 10;
        $dimensions->width = 5;
        $dimensions->height = 8;
        // Don't set unit, should use default

        $this->assertEquals('cm', $dimensions->unit);
        $this->assertTrue($dimensions->validate());
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }
}
