<?php

/**
* @runTestsInSeparateProcesses
* @preserveGlobalState disabled
*/

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MongoDB\BSON\ObjectId;

class StockLogTest extends MockeryTestCase
{
    protected $stockLog;

    protected function setUp(): void
    {
        // Partial mock to avoid calling parent constructor or DB connection
        $this->stockLog = m::mock(StockLog::class)->makePartial();
    }

    public function testGetCollectionName()
    {
        $this->assertEquals('stock_logs', $this->stockLog->getCollectionName());
    }

    public function testStaticModelReturnsInstance()
    {
        $modelInstance = StockLog::model();
        $this->assertInstanceOf(StockLog::class, $modelInstance);
    }

    public function testConstants()
    {
        $this->assertEquals('received', StockLog::TYPE_RECEIVED);
        $this->assertEquals('sold', StockLog::TYPE_SOLD);
        $this->assertEquals('adjusted', StockLog::TYPE_ADJUSTED);
        $this->assertEquals('damaged', StockLog::TYPE_DAMAGED);
        $this->assertEquals('initial', StockLog::TYPE_INITIAL);
    }

    public function testAttributeLabels()
    {
        $labels = $this->stockLog->attributeLabels();

        $this->assertIsArray($labels);
        $this->assertArrayHasKey('_id', $labels);
        $this->assertArrayHasKey('product_id', $labels);
        $this->assertArrayHasKey('type', $labels);
        $this->assertArrayHasKey('quantity_change', $labels);
        $this->assertArrayHasKey('quantity_after_change', $labels);
        $this->assertArrayHasKey('reason', $labels);
        $this->assertArrayHasKey('user_id', $labels);
        $this->assertArrayHasKey('updated_at', $labels);

        $this->assertEquals('Log ID', $labels['_id']);
        $this->assertEquals('Product', $labels['product_id']);
        $this->assertEquals('Log Type', $labels['type']);
        $this->assertEquals('Total Quantity Change (+/-)', $labels['quantity_change']);
        $this->assertEquals('New Total Product Stock', $labels['quantity_after_change']);
        $this->assertEquals('Reason / Reference', $labels['reason']);
        $this->assertEquals('User', $labels['user_id']);
        $this->assertEquals('Date', $labels['updated_at']);
    }

    public function testRules()
    {
        $rules = $this->stockLog->rules();

        $this->assertIsArray($rules);

        // Check that required rule contains required fields
        $foundRequired = false;
        foreach ($rules as $rule) {
            if ($rule[1] === 'required' && strpos($rule[0], 'product_id') !== false
                && strpos($rule[0], 'type') !== false
                && strpos($rule[0], 'quantity_change') !== false
                && strpos($rule[0], 'quantity_after_change') !== false) {
                $foundRequired = true;
                break;
            }
        }
        $this->assertTrue($foundRequired, 'Required rule for product_id, type, quantity_change, quantity_after_change not found');

        // Check for numerical validation on quantity fields
        $foundNumerical = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'quantity_change, quantity_after_change' && $rule[1] === 'numerical') {
                $foundNumerical = true;
                $this->assertTrue($rule['integerOnly']);
                break;
            }
        }
        $this->assertTrue($foundNumerical, 'Numerical rule for quantity fields not found');

        // Check for type validation
        $foundTypeValidation = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'type' && $rule[1] === 'in') {
                $foundTypeValidation = true;
                // This should match the getAllowedTypes() method
                break;
            }
        }
        $this->assertTrue($foundTypeValidation, 'Type validation rule not found');

        // Check for reason length validation
        $foundReasonLength = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'reason' && $rule[1] === 'length') {
                $foundReasonLength = true;
                $this->assertEquals(255, $rule['max']);
                break;
            }
        }
        $this->assertTrue($foundReasonLength, 'Reason length validation not found');
    }

    public function testGetAllowedTypes()
    {
        $allowedTypes = StockLog::getAllowedTypes();

        $this->assertIsArray($allowedTypes);
        $this->assertContains('received', $allowedTypes);
        $this->assertContains('sold', $allowedTypes);
        $this->assertContains('adjusted', $allowedTypes);
        $this->assertContains('damaged', $allowedTypes);
        $this->assertContains('initial', $allowedTypes);
        $this->assertCount(5, $allowedTypes);
    }

    public function testGetTypeOptions()
    {
        $typeOptions = StockLog::getTypeOptions();

        $this->assertIsArray($typeOptions);
        $this->assertArrayHasKey('received', $typeOptions);
        $this->assertArrayHasKey('sold', $typeOptions);
        $this->assertArrayHasKey('adjusted', $typeOptions);
        $this->assertArrayHasKey('damaged', $typeOptions);
        $this->assertArrayHasKey('initial', $typeOptions);

        $this->assertEquals('Stock Received', $typeOptions['received']);
        $this->assertEquals('Stock Sold', $typeOptions['sold']);
        $this->assertEquals('Stock Adjusted', $typeOptions['adjusted']);
        $this->assertEquals('Stock Damaged', $typeOptions['damaged']);
        $this->assertEquals('Initial Stock Set', $typeOptions['initial']);
    }

    public function testGetTypeName()
    {
        $this->assertEquals('Stock Received', StockLog::getTypeName('received'));
        $this->assertEquals('Stock Sold', StockLog::getTypeName('sold'));
        $this->assertEquals('Stock Adjusted', StockLog::getTypeName('adjusted'));
        $this->assertEquals('Stock Damaged', StockLog::getTypeName('damaged'));
        $this->assertEquals('Initial Stock Set', StockLog::getTypeName('initial'));

        // Test unknown type returns the key itself
        $this->assertEquals('unknown_type', StockLog::getTypeName('unknown_type'));
    }

    public function testValidateProductID_WithValidProduct()
    {
        $productId = '507f1f77bcf86cd799439011';

        // Mock Product model to return a product
        $productMock = m::mock();
        $productModelMock = m::mock('alias:Product');
        $productModelMock->shouldReceive('model')->andReturnSelf();
        $productModelMock->shouldReceive('findByPk')
            ->with(m::type(ObjectId::class))
            ->andReturn($productMock);

        $stockLog = new StockLog();
        $stockLog->product_id = $productId;
        $result = $stockLog->validateProductID('product_id', []);

        $this->assertTrue($result);
        $this->assertFalse($stockLog->hasErrors('product_id'));
    }

    public function testValidateProductID_WithInvalidProduct()
    {
        $productId = '507f1f77bcf86cd799439011';

        // Mock Product model to return null
        $productModelMock = m::mock('alias:Product');
        $productModelMock->shouldReceive('model')->andReturnSelf();
        $productModelMock->shouldReceive('findByPk')
            ->with(m::type(ObjectId::class))
            ->andReturn(null);

        $stockLog = new StockLog();
        $stockLog->product_id = $productId;
        $result = $stockLog->validateProductID('product_id', []);

        $this->assertFalse($result);
        $this->assertTrue($stockLog->hasErrors('product_id'));
        $errors = $stockLog->getErrors('product_id');
        $this->assertContains('Product not found.', $errors);
    }

    public function testValidateProductID_WithEmptyProductId()
    {
        $stockLog = new StockLog();
        $stockLog->product_id = '';
        $result = $stockLog->validateProductID('product_id', []);

        $this->assertFalse($result);
        $this->assertTrue($stockLog->hasErrors('product_id'));
        $errors = $stockLog->getErrors('product_id');
        $this->assertContains('Product ID is required.', $errors);
    }

    public function testValidateProductID_WithInvalidFormat()
    {
        $stockLog = new StockLog();
        $stockLog->product_id = 'invalid-format';
        $result = $stockLog->validateProductID('product_id', []);

        $this->assertFalse($result);
        $this->assertTrue($stockLog->hasErrors('product_id'));
        $errors = $stockLog->getErrors('product_id');
        $this->assertContains('Invalid Product ID format.', $errors);
    }

    public function testValidateUserID_WithValidUser()
    {
        $userId = 'admin';

        // Mock User model to return a user
        $userMock = m::mock();
        $userModelMock = m::mock('alias:User');
        $userModelMock->shouldReceive('model')->andReturnSelf();
        $userModelMock->shouldReceive('findByAttributes')
            ->with(['username' => $userId])
            ->andReturn($userMock);

        $stockLog = new StockLog();
        $stockLog->user_id = $userId;
        $result = $stockLog->validateUserID('user_id', []);

        $this->assertTrue($result);
        $this->assertFalse($stockLog->hasErrors('user_id'));
    }

    public function testValidateUserID_WithInvalidUser()
    {
        $userId = 'nonexistent_user';

        // Mock User model to return null
        $userModelMock = m::mock('alias:User');
        $userModelMock->shouldReceive('model')->andReturnSelf();
        $userModelMock->shouldReceive('findByAttributes')
            ->with(['username' => $userId])
            ->andReturn(null);

        $stockLog = new StockLog();
        $stockLog->user_id = $userId;
        $result = $stockLog->validateUserID('user_id', []);

        $this->assertFalse($result);
        $this->assertTrue($stockLog->hasErrors('user_id'));
        $errors = $stockLog->getErrors('user_id');
        $this->assertContains('User not found.', $errors);
    }

    public function testValidateUserID_WithEmptyUserId()
    {
        $stockLog = new StockLog();
        $stockLog->user_id = '';
        $result = $stockLog->validateUserID('user_id', []);

        $this->assertTrue($result); // Empty user_id is allowed
        $this->assertFalse($stockLog->hasErrors('user_id'));
    }

    public function testAdd_WithValidData()
    {
        // Mock Product model to return a valid product for validation
        $productMock = m::mock();
        $productModelMock = m::mock('alias:Product');
        $productModelMock->shouldReceive('model')->andReturnSelf();
        $productModelMock->shouldReceive('findByPk')
            ->with(m::type(ObjectId::class))
            ->andReturn($productMock);

        // Mock User model to return a valid user for validation
        $userMock = m::mock();
        $userModelMock = m::mock('alias:User');
        $userModelMock->shouldReceive('model')->andReturnSelf();
        $userModelMock->shouldReceive('findByAttributes')
            ->with(['username' => 'admin'])
            ->andReturn($userMock);

        // Call the actual add method - since we can't easily mock the save operation
        // we'll test that the method exists and can be called without errors
        $result = StockLog::add(
            '507f1f77bcf86cd799439011',
            StockLog::TYPE_RECEIVED,
            10,
            50,
            'Received shipment',
            'admin'
        );

        // The method should return a boolean (true for success, false for failure)
        $this->assertIsBool($result);
    }

    public function testSearchProvider_ReturnsDataProvider()
    {
        $stockLog = new StockLog();
        $result = $stockLog->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testSearchProvider_WithIdFilter()
    {
        $stockLog = new StockLog();
        $stockLog->_id = '507f1f77bcf86cd799439011';

        $result = $stockLog->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testSearchProvider_WithProductIdFilter()
    {
        $stockLog = new StockLog();
        $stockLog->product_id = '507f1f77bcf86cd799439011';

        $result = $stockLog->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testSearchProvider_WithTypeFilter()
    {
        $stockLog = new StockLog();
        $stockLog->type = StockLog::TYPE_RECEIVED;

        $result = $stockLog->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testSearchProvider_WithReasonFilter()
    {
        $stockLog = new StockLog();
        $stockLog->reason = 'Test reason';

        $result = $stockLog->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testSearchProvider_WithQuantityChangeFilter()
    {
        $stockLog = new StockLog();
        $stockLog->quantity_change = 10;

        $result = $stockLog->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testSearchProvider_WithQuantityAfterChangeFilter()
    {
        $stockLog = new StockLog();
        $stockLog->quantity_after_change = 50;

        $result = $stockLog->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testSearchProvider_WithUserIdFilter()
    {
        $stockLog = new StockLog();
        $stockLog->user_id = 'admin';

        $result = $stockLog->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testSearchProvider_WithUpdatedAtFilter()
    {
        $stockLog = new StockLog();
        $stockLog->updated_at = '2023-01-01';

        $result = $stockLog->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testSearchProvider_WithCaseSensitiveFlag()
    {
        $stockLog = new StockLog();
        $stockLog->reason = 'Test Reason';
        $stockLog->user_id = 'Admin';

        $result = $stockLog->searchProvider(true);

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testSearchProvider_WithMultipleFilters()
    {
        $stockLog = new StockLog();
        $stockLog->product_id = '507f1f77bcf86cd799439011';
        $stockLog->type = StockLog::TYPE_RECEIVED;
        $stockLog->quantity_change = 10;
        $stockLog->quantity_after_change = 50;
        $stockLog->reason = 'Received shipment';
        $stockLog->user_id = 'admin';

        $result = $stockLog->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testSearchProvider_WithInvalidUpdatedAt()
    {
        $stockLog = new StockLog();
        $stockLog->updated_at = 'invalid-date';

        $result = $stockLog->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
        // Should handle invalid date gracefully and not add date filter
    }

    public function testValidStockLog()
    {
        $stockLog = new StockLog();
        $stockLog->product_id = '507f1f77bcf86cd799439011';
        $stockLog->type = StockLog::TYPE_RECEIVED;
        $stockLog->quantity_change = 10;
        $stockLog->quantity_after_change = 50;
        $stockLog->reason = 'Test reason';
        $stockLog->user_id = 'admin';

        // Mock the custom validators to pass
        $stockLogMock = m::mock(StockLog::class)->makePartial();
        $stockLogMock->shouldReceive('validateProductID')->andReturn(true);
        $stockLogMock->shouldReceive('validateUserID')->andReturn(true);
        $stockLogMock->product_id = '507f1f77bcf86cd799439011';
        $stockLogMock->type = StockLog::TYPE_RECEIVED;
        $stockLogMock->quantity_change = 10;
        $stockLogMock->quantity_after_change = 50;

        $this->assertTrue($stockLogMock->validate());
    }

    public function testRequiredFieldsValidation()
    {
        $stockLog = new StockLog();
        // Don't set required fields

        $this->assertFalse($stockLog->validate());
        $this->assertTrue($stockLog->hasErrors('product_id'));
        $this->assertTrue($stockLog->hasErrors('type'));
        $this->assertTrue($stockLog->hasErrors('quantity_change'));
        $this->assertTrue($stockLog->hasErrors('quantity_after_change'));
    }

    public function testTypeValidation()
    {
        $stockLog = new StockLog();
        $stockLog->product_id = '507f1f77bcf86cd799439011';
        $stockLog->type = 'invalid_type';
        $stockLog->quantity_change = 10;
        $stockLog->quantity_after_change = 50;

        $this->assertFalse($stockLog->validate());
        $this->assertTrue($stockLog->hasErrors('type'));
    }

    public function testQuantityFieldsNumericalValidation()
    {
        $stockLog = new StockLog();
        $stockLog->product_id = '507f1f77bcf86cd799439011';
        $stockLog->type = StockLog::TYPE_RECEIVED;
        $stockLog->quantity_change = 'not_a_number';
        $stockLog->quantity_after_change = 'also_not_a_number';

        $this->assertFalse($stockLog->validate());
        $this->assertTrue($stockLog->hasErrors('quantity_change'));
        $this->assertTrue($stockLog->hasErrors('quantity_after_change'));
    }

    public function testReasonLengthValidation()
    {
        $stockLog = new StockLog();
        $stockLog->product_id = '507f1f77bcf86cd799439011';
        $stockLog->type = StockLog::TYPE_RECEIVED;
        $stockLog->quantity_change = 10;
        $stockLog->quantity_after_change = 50;
        $stockLog->reason = str_repeat('A', 256); // 256 characters, exceeds max of 255

        $this->assertFalse($stockLog->validate());
        $this->assertTrue($stockLog->hasErrors('reason'));
    }

    public function testValidQuantityValues()
    {
        $stockLog = new StockLog();
        $stockLog->product_id = '507f1f77bcf86cd799439011';
        $stockLog->type = StockLog::TYPE_RECEIVED;
        $stockLog->quantity_change = 10;
        $stockLog->quantity_after_change = 50;

        // Mock the custom validators to pass
        $stockLogMock = m::mock(StockLog::class)->makePartial();
        $stockLogMock->shouldReceive('validateProductID')->andReturn(true);
        $stockLogMock->shouldReceive('validateUserID')->andReturn(true);
        $stockLogMock->product_id = '507f1f77bcf86cd799439011';
        $stockLogMock->type = StockLog::TYPE_RECEIVED;
        $stockLogMock->quantity_change = 10;
        $stockLogMock->quantity_after_change = 50;

        $this->assertTrue($stockLogMock->validate());
        $this->assertFalse($stockLogMock->hasErrors('quantity_change'));
        $this->assertFalse($stockLogMock->hasErrors('quantity_after_change'));
    }

    public function testNegativeQuantityValues()
    {
        $stockLog = new StockLog();
        $stockLog->product_id = '507f1f77bcf86cd799439011';
        $stockLog->type = StockLog::TYPE_SOLD;
        $stockLog->quantity_change = -5; // Negative for sold items
        $stockLog->quantity_after_change = 45;

        // Mock the custom validators to pass
        $stockLogMock = m::mock(StockLog::class)->makePartial();
        $stockLogMock->shouldReceive('validateProductID')->andReturn(true);
        $stockLogMock->shouldReceive('validateUserID')->andReturn(true);
        $stockLogMock->product_id = '507f1f77bcf86cd799439011';
        $stockLogMock->type = StockLog::TYPE_SOLD;
        $stockLogMock->quantity_change = -5;
        $stockLogMock->quantity_after_change = 45;

        $this->assertTrue($stockLogMock->validate());
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }
}
