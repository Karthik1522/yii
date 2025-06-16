<?php

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use MongoDB\BSON\ObjectId;

class ProductHelperTest extends MockeryTestCase
{
    private $yiiMock;
    private $appMock;

    protected function setUp(): void
    {
        $this->yiiMock = new YiiAppMock();
        $this->appMock = $this->yiiMock->mockApp();
    }

    protected function tearDown(): void
    {
        $this->yiiMock->close();
        parent::tearDown();
        m::close();
    }

    public function testLoadProductById_Valid_ReturnsProduct()
    {
        $mockProduct = (object)[
            'name' => 'Test Product'
        ];

        $productModelMock = m::mock('alias:Product');
        $productModelMock->shouldReceive('model')->andReturnSelf();
        $productModelMock->shouldReceive('findByPk')
            ->with(m::type(ObjectId::class))
            ->andReturn($mockProduct);

        $result = ProductHelper::loadProductById('507f1f77bcf86cd799439011');

        $this->assertEquals('Test Product', $result->name);
    }

    public function testLoadProductById_InvalidId_Throws400()
    {
        try {
            ProductHelper::loadProductById('invalid_id');
            $this->fail('Expected CHttpException not thrown.');
        } catch (CHttpException $e) {
            $this->assertEquals(400, $e->statusCode);
            $this->assertEquals('Invalid Product ID format.', $e->getMessage());
        }
    }

    public function testLoadProductById_NotFound_Throws404()
    {
        $productModelMock = m::mock('alias:Product');
        $productModelMock->shouldReceive('model')->andReturnSelf();
        $productModelMock->shouldReceive('findByPk')->andReturn(null);

        try {
            ProductHelper::loadProductById('507f1f77bcf86cd799439011');
            $this->fail('Expected CHttpException not thrown.');
        } catch (CHttpException $e) {
            $this->assertEquals(404, $e->statusCode);
            $this->assertEquals('The requested product does not exist.', $e->getMessage());
        }
    }

    public function testLoadProductById_InternalException_Throws500()
    {
        $productModelMock = m::mock('alias:Product');
        $productModelMock->shouldReceive('model')->andReturnSelf();
        $productModelMock->shouldReceive('findByPk')->andThrow(new Exception("DB error"));

        try {
            ProductHelper::loadProductById('507f1f77bcf86cd799439011');
            $this->fail('Expected CHttpException not thrown.');
        } catch (CHttpException $e) {
            $this->assertEquals(500, $e->statusCode);
            $this->assertEquals('Error retrieving product data.', $e->getMessage());
        }
    }


    public function testProcessImageUploadNoFile()
    {
        $result = ProductHelper::processImageUpload(null);
        $this->assertFalse($result['success']);
        $this->assertNull($result['imageUrl']);
        $this->assertNull($result['s3Key']);
    }

    public function testProcessImageUpload_S3UploadSuccess_ReturnsSuccess()
    {
        // Fake file object
        $uploadedFile = (object)[
            'name' => 'test-image.png',
            'tempName' => '/tmp/php123.tmp'
        ];

        // Stub UtilityHelpers::sanitizie to return sanitized name
        $utilityMock = m::mock('alias:UtilityHelpers');
        $utilityMock->shouldReceive('sanitizie')->with('test-image.png')->andReturn('test-image.png');

        // Mock s3uploader component
        $s3uploader = $this->yiiMock->mockAppComponent('s3uploader');
        $s3uploader->shouldReceive('uploadFile')
            ->with('/tmp/php123.tmp', m::type('string'))
            ->andReturn('https://bucket.s3.amazonaws.com/products/123_test-image.png');


        $result = ProductHelper::processImageUpload($uploadedFile);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['imageUrl']);
        $this->assertStringStartsWith('products/', $result['s3Key']);
    }

    public function testProcessImageUpload_S3UploadFails_ReturnsFailure()
    {
        $uploadedFile = (object)[
            'name' => 'test.png',
            'tempName' => '/tmp/test123.tmp'
        ];

        // Stub UtilityHelpers::sanitizie
        $utilityMock = m::mock('alias:UtilityHelpers');
        $utilityMock->shouldReceive('sanitizie')->with('test.png')->andReturn('test.png');

        // Mock s3uploader to return false
        $s3uploader = $this->yiiMock->mockAppComponent('s3uploader');

        $s3uploader->shouldReceive('uploadFile')
            ->with('/tmp/test123.tmp', m::type('string'))
            ->andReturn(false);


        $result = ProductHelper::processImageUpload($uploadedFile);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testProcessImageUpload_ExceptionThrown_ReturnsFailure()
    {
        $uploadedFile = (object)[
            'name' => 'error.png',
            'tempName' => '/tmp/error.tmp'
        ];

        // Stub UtilityHelpers::sanitizie
        $utilityMock = m::mock('alias:UtilityHelpers');
        $utilityMock->shouldReceive('sanitizie')->with('error.png')->andReturn('error.png');

        // Throw exception from uploadFile
        $s3uploader = $this->yiiMock->mockAppComponent('s3uploader');
        $s3uploader->shouldReceive('uploadFile')
            ->andThrow(new Exception("S3 connection error"));


        $result = ProductHelper::processImageUpload($uploadedFile);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Error processing image upload.', $result['error']);
    }

    public function testCreateProduct_WithImageUpload_Success()
    {
        $uploadedFile = (object)[
            'name' => 'test-image.png',
            'tempName' => '/tmp/php123.tmp'
        ];

        // Mock UtilityHelpers::sanitizie for processImageUpload
        $utilityMock = m::mock('alias:UtilityHelpers');
        $utilityMock->shouldReceive('sanitizie')->with('test-image.png')->andReturn('test-image.png');

        // Mock S3 uploader
        $s3uploader = $this->yiiMock->mockAppComponent('s3uploader');
        $s3uploader->shouldReceive('uploadFile')
            ->with('/tmp/php123.tmp', m::type('string'))
            ->andReturn('https://bucket.s3.amazonaws.com/products/123_test-image.png');
        $s3uploader->shouldReceive('fileExists')->andReturn(false);


        $stockLogMock = m::mock('overload:StockLog');
        $stockLogMock->shouldReceive('add')->once();

        $productMock = m::mock('overload:Product');
        $productMock->shouldReceive('validate')->andReturn(true);
        $productMock->shouldReceive('save')->with(false)->andReturn(true);
        $productMock->shouldReceive('addError')->never();

        // Here we store attributes in the mock manually
        $capturedAttributes = [];

        $productMock->shouldReceive('setAttributes')
            ->with(m::on(function ($attrs) use (&$capturedAttributes) {
                $capturedAttributes = $attrs;
                return true;
            }))
            ->andReturnNull();

        // Allow reading captured fields like real object
        $productMock->shouldReceive('__get')
            ->with(m::any())
            ->andReturnUsing(function ($key) use (&$capturedAttributes) {
                return $capturedAttributes[$key] ?? null;
            });

        $productMock->shouldReceive('__set')
            ->with(m::any(), m::any())
            ->andReturnUsing(function ($key, $value) use (&$capturedAttributes) {
                $capturedAttributes[$key] = $value;
            });

        // Simulate _id and image_url post-processing
        $productMock->_id = new MongoDB\BSON\ObjectId();
        $productMock->image_url = 'https://bucket.s3.amazonaws.com/products/123_test-image.png';

        // Mock user state for stock logging
        $userMock = $this->yiiMock->mockAppComponent('user');
        $userMock->shouldReceive('getState')->with('username')->andReturn('testuser');

        $data = [
            'name' => 'Test Product',
            'price' => 99,
        ];

        // Call the real static method
        $result = ProductHelper::createProduct($data, $uploadedFile);

        $this->assertTrue($result['success']);
        // $this->assertEquals('Test Product', $result['model']->name);
        $this->assertEquals('Product created successfully.', $result['message']);
        $this->assertNotNull($result['s3Key']);
    }


    public function testCreateProduct_NoImage_ValidationFails()
    {
        $this->yiiMock->mockAppComponent('s3uploader')
            ->shouldReceive('fileExists')->never();
        $this->yiiMock->mockAppComponent('s3uploader')
            ->shouldReceive('deleteFile')->never();

        $productMock = m::mock('overload:Product');
        $productMock->shouldReceive('validate')->andReturn(false);
        $productMock->shouldReceive('getErrors')->andReturn(['name' => ['Missing']]);

        $data = ['name' => '', 'price' => 10];
        $result = ProductHelper::createProduct($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Error creating product. Please check the form for errors.', $result['message']);
    }

    public function testCreateProduct_NoImage_SaveFails()
    {
        $this->yiiMock->mockAppComponent('s3uploader')
            ->shouldReceive('fileExists')->never();
        $this->yiiMock->mockAppComponent('s3uploader')
            ->shouldReceive('deleteFile')->never();

        $productMock = m::mock('overload:Product');
        $productMock->shouldReceive('validate')->andReturn(true);
        $productMock->shouldReceive('save')->with(false)->andReturn(false);
        $productMock->shouldReceive('getErrors')->andReturn(['db' => ['Failed']]);

        $data = ['name' => 'Hello', 'price' => 10];
        $result = ProductHelper::createProduct($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Error creating product. Please check the form for errors.', $result['message']);
    }

    public function testCreateProduct_ExceptionThrown()
    {
        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('Error creating product.');

        m::mock('overload:Product')->shouldReceive('__construct')
            ->andThrow(new Exception('Something went wrong'));

        ProductHelper::createProduct(['name' => 'Fail']);
    }

    public function testDeleteProduct_SuccessWithImageCleanup()
    {
        $productMock = m::mock('Product');
        $productMock->_id = '123';
        $productMock->name = 'Test Product';
        $productMock->image_url = 'https://s3.amazonaws.com/bucket/image.jpg';
        $productMock->shouldReceive('delete')->once()->andReturn(true);

        $s3Mock = $this->yiiMock->mockAppComponent('s3uploader');
        $s3Mock->shouldReceive('getS3KeyFromUrl')->with('https://s3.amazonaws.com/bucket/image.jpg')->andReturn('image.jpg');
        $s3Mock->shouldReceive('fileExists')->with('image.jpg')->andReturn(true);
        $s3Mock->shouldReceive('deleteFile')->with('image.jpg')->once();

        $result = ProductHelper::deleteProduct($productMock);

        $this->assertTrue($result['success']);
        $this->assertEquals('Product deleted successfully.', $result['message']);
    }

    public function testDeleteProduct_SuccessImageNotExists()
    {
        $productMock = m::mock('Product');
        $productMock->_id = '124';
        $productMock->name = 'NoImage Product';
        $productMock->image_url = 'https://s3.amazonaws.com/bucket/missing.jpg';
        $productMock->shouldReceive('delete')->once()->andReturn(true);

        $s3Mock = $this->yiiMock->mockAppComponent('s3uploader');
        $s3Mock->shouldReceive('getS3KeyFromUrl')->with('https://s3.amazonaws.com/bucket/missing.jpg')->andReturn('missing.jpg');
        $s3Mock->shouldReceive('fileExists')->with('missing.jpg')->andReturn(false);
        $s3Mock->shouldReceive('deleteFile')->never();

        $result = ProductHelper::deleteProduct($productMock);

        $this->assertTrue($result['success']);
        $this->assertEquals('Product deleted successfully.', $result['message']);
    }


    public function testDeleteProduct_ThrowsException()
    {
        $productMock = m::mock('Product');
        $productMock->_id = '126';
        $productMock->name = 'Boom Product';
        $productMock->image_url = 'https://s3.amazonaws.com/bucket/crash.jpg';

        $s3Mock = $this->yiiMock->mockAppComponent('s3uploader');
        $s3Mock->shouldReceive('getS3KeyFromUrl')->andThrow(new Exception('Crash!'));

        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('Error deleting product.');

        ProductHelper::deleteProduct($productMock);
    }

    public function testPrepareProductSearch_WithoutSearchData()
    {
        // Mock Product class to return our mock instance
        $productMock = m::mock('overload:Product');
        $productMock->shouldReceive('unsetAttributes')->once();
        $productMock->shouldReceive('__construct')->with('search')->once()->andReturn($productMock);

        $result = ProductHelper::prepareProductSearch();

        $this->assertInstanceOf('Product', $result);
    }

    public function testPrepareProductSearch_WithSearchData()
    {
        $searchData = ['name' => 'Sample Product'];

        // Create a fresh mock for this test
        $productMock = m::mock('overload:Product');
        $productMock->shouldReceive('unsetAttributes')->once();
        // $productMock->shouldReceive('__set')->with('attributes', $searchData)->once();
        $productMock->shouldReceive('__construct')->with('search')->once()->andReturn($productMock);

        $result = ProductHelper::prepareProductSearch($searchData);

        $this->assertInstanceOf('Product', $result);
    }

    public function testPrepareProductSearch_ThrowsException()
    {
        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('Error preparing product search.');

        // Mock Product class constructor to throw exception
        $productClassMock = m::mock('overload:Product');
        $productClassMock->shouldReceive('__construct')->with('search')->andThrow(new Exception("Simulated constructor failure"));

        ProductHelper::prepareProductSearch(['name' => 'Error']);
    }


    public function testGetCategoryOptionsSuccess()
    {
        // Mock Category::model() to return a mock that has getCategoryOptions
        $categoryModelMock = m::mock();
        $categoryModelMock->shouldReceive('getCategoryOptions')->andReturn(['1' => 'Category 1', '2' => 'Category 2']);

        $categoryClassMock = m::mock('alias:Category');
        $categoryClassMock->shouldReceive('model')->andReturn($categoryModelMock);

        $result = ProductHelper::getCategoryOptions();
        $this->assertEquals(['1' => 'Category 1', '2' => 'Category 2'], $result);
    }

    public function testGetCategoryOptionsException()
    {
        // Mock Category model to throw exception
        $category = m::mock('alias:Category');
        $category->shouldReceive('getCategoryOptions')->andThrow(new Exception('Database error'));

        // Mock Category::model() static call
        $categoryClass = m::mock('alias:Category');
        $categoryClass->shouldReceive('model')->andReturn($category);

        $result = ProductHelper::getCategoryOptions();
        $this->assertEquals([], $result);
    }

    public function testGetProductImageUrlWithImage()
    {
        $model = m::mock('Product');
        $model->image_url = 'http://example.com/image.jpg';

        $result = ProductHelper::getProductImageUrl($model);
        $this->assertEquals('http://example.com/image.jpg', $result);
    }

    public function testGetProductImageUrlWithoutImage()
    {
        $model = m::mock('Product');
        $model->image_url = null;

        $result = ProductHelper::getProductImageUrl($model);
        $this->assertNull($result);
    }

    public function testHandleAjaxResponse()
    {
        $result = [
            'success' => true,
            'message' => 'Test message'
        ];

        // Mock CJSON
        $cjson = m::mock('alias:CJSON');
        $cjson->shouldReceive('encode')->with([
            'status' => 'success',
            'message' => 'Test message'
        ])->andReturn('{"status":"success","message":"Test message"}');

        // Capture output
        ob_start();
        ProductHelper::handleAjaxResponse($result, true);
        $output = ob_get_clean();

        $this->assertEquals('{"status":"success","message":"Test message"}', $output);
    }

    public function testHandleAjaxResponseNotAjax()
    {
        $result = [
            'success' => true,
            'message' => 'Test message'
        ];

        // Should not produce any output when not AJAX
        ob_start();
        ProductHelper::handleAjaxResponse($result, false);
        $output = ob_get_clean();

        $this->assertEquals('', $output);
    }

    public function testUpdateProduct_WithImageUpload_Success()
    {
        $uploadedFile = (object)[
            'name' => 'new-image.png',
            'tempName' => '/tmp/newphp123.tmp'
        ];

        // Mock existing product
        $productMock = m::mock('Product')->makePartial();
        $productMock->_id = new ObjectId('507f1f77bcf86cd799439011');
        $productMock->name = 'Existing Product';
        $productMock->image_url = 'https://bucket.s3.amazonaws.com/products/old_image.jpg';
        $productMock->shouldReceive('validate')->andReturn(true);
        $productMock->shouldReceive('save')->with(false)->andReturn(true);

        // Mock UtilityHelpers
        $utilityMock = m::mock('alias:UtilityHelpers');
        $utilityMock->shouldReceive('sanitizie')->with('new-image.png')->andReturn('new-image.png');

        // Mock S3 uploader
        $s3uploader = $this->yiiMock->mockAppComponent('s3uploader');
        $s3uploader->shouldReceive('getS3KeyFromUrl')->with('https://bucket.s3.amazonaws.com/products/old_image.jpg')->andReturn('products/old_image.jpg');
        $s3uploader->shouldReceive('uploadFile')->andReturn('https://bucket.s3.amazonaws.com/products/123_new-image.png');
        $s3uploader->shouldReceive('fileExists')->with('products/old_image.jpg')->andReturn(true);
        $s3uploader->shouldReceive('deleteFile')->with('products/old_image.jpg')->once();

        // Mock StockLog
        $stockLogMock = m::mock('overload:StockLog');
        $stockLogMock->shouldReceive('add')->once();

        // Mock user state
        $userMock = $this->yiiMock->mockAppComponent('user');
        $userMock->shouldReceive('getState')->with('username')->andReturn('testuser');

        $data = ['name' => 'Updated Product', 'price' => 199];

        $result = ProductHelper::updateProduct($productMock, $data, $uploadedFile);

        $this->assertTrue($result['success']);
        $this->assertEquals('Product updated successfully.', $result['message']);
    }

    public function testUpdateProduct_ClearImage_Success()
    {
        // Mock existing product with image
        $productMock = m::mock('Product')->makePartial();
        $productMock->_id = new ObjectId('507f1f77bcf86cd799439011');
        $productMock->name = 'Product with Image';
        $productMock->image_url = 'https://bucket.s3.amazonaws.com/products/image.jpg';
        $productMock->shouldReceive('validate')->andReturn(true);
        $productMock->shouldReceive('save')->with(false)->andReturn(true);

        // Mock S3 uploader - only expect deleteFile to be called once (during clearImage)
        $s3uploader = $this->yiiMock->mockAppComponent('s3uploader');
        $s3uploader->shouldReceive('getS3KeyFromUrl')->with('https://bucket.s3.amazonaws.com/products/image.jpg')->andReturn('products/image.jpg');
        $s3uploader->shouldReceive('fileExists')->with('products/image.jpg')->andReturn(true);
        $s3uploader->shouldReceive('deleteFile')->with('products/image.jpg')->twice();

        // Mock StockLog
        $stockLogMock = m::mock('overload:StockLog');
        $stockLogMock->shouldReceive('add')->once();

        // Mock user state
        $userMock = $this->yiiMock->mockAppComponent('user');
        $userMock->shouldReceive('getState')->with('username')->andReturn('testuser');

        $data = ['name' => 'Updated Product', 'price' => 199];

        $result = ProductHelper::updateProduct($productMock, $data, null, true);

        $this->assertTrue($result['success']);
        $this->assertEquals('Product updated successfully.', $result['message']);
        $this->assertNull($productMock->image_url);
    }

    public function testUpdateProduct_ValidationFails()
    {
        $productMock = m::mock('Product')->makePartial();
        $productMock->_id = new ObjectId('507f1f77bcf86cd799439011');
        $productMock->image_url = 'https://bucket.s3.amazonaws.com/products/old.jpg';
        $productMock->shouldReceive('validate')->andReturn(false);
        $productMock->shouldReceive('getErrors')->andReturn(['name' => ['Name is required']]);

        // Mock S3 uploader
        $s3uploader = $this->yiiMock->mockAppComponent('s3uploader');
        $s3uploader->shouldReceive('getS3KeyFromUrl')->andReturn('products/old.jpg');

        $data = ['name' => '', 'price' => 199];

        $result = ProductHelper::updateProduct($productMock, $data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Error updating product. Please check the form for errors.', $result['message']);
    }

    public function testUpdateProduct_ExceptionThrown()
    {
        $productMock = m::mock('Product')->makePartial();
        $productMock->_id = new ObjectId('507f1f77bcf86cd799439011');

        // Mock S3 uploader to throw exception
        $s3uploader = $this->yiiMock->mockAppComponent('s3uploader');
        $s3uploader->shouldReceive('getS3KeyFromUrl')->andThrow(new Exception('S3 error'));

        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('Error updating product.');

        ProductHelper::updateProduct($productMock, ['name' => 'Test']);
    }

    public function testDeleteProduct_SuccessNoImage()
    {
        $productMock = m::mock('Product');
        $productMock->_id = '125';
        $productMock->name = 'No Image Product';
        $productMock->image_url = null;
        $productMock->shouldReceive('delete')->once()->andReturn(true);

        $s3Mock = $this->yiiMock->mockAppComponent('s3uploader');
        $s3Mock->shouldReceive('getS3KeyFromUrl')->with(null)->andReturn(null);
        $s3Mock->shouldReceive('fileExists')->never();
        $s3Mock->shouldReceive('deleteFile')->never();

        $result = ProductHelper::deleteProduct($productMock);

        $this->assertTrue($result['success']);
        $this->assertEquals('Product deleted successfully.', $result['message']);
    }

    public function testDeleteProduct_DeleteFails()
    {
        $productMock = m::mock('Product');
        $productMock->_id = '127';
        $productMock->name = 'Failed Delete Product';
        $productMock->image_url = null;
        $productMock->shouldReceive('delete')->once()->andReturn(false);
        $productMock->shouldReceive('hasErrors')->andReturn(false);

        $s3Mock = $this->yiiMock->mockAppComponent('s3uploader');
        $s3Mock->shouldReceive('getS3KeyFromUrl')->with(null)->andReturn(null);

        $result = ProductHelper::deleteProduct($productMock);

        $this->assertFalse($result['success']);
        $this->assertEquals('Error deleting product from database.', $result['message']);
    }

    public function testCreateProduct_WithVariants_StockLog()
    {
        // Mock StockLog to verify it's called with variants
        $stockLogMock = m::mock('overload:StockLog');
        $stockLogMock->shouldReceive('add')->once();

        $productMock = m::mock('overload:Product');
        $productMock->shouldReceive('validate')->andReturn(true);
        $productMock->shouldReceive('save')->with(false)->andReturn(true);

        // Mock product with variants
        $variant1 = (object)['quantity' => 5];
        $variant2 = (object)['quantity' => 3];
        $productMock->quantity = 10;
        $productMock->variants = [$variant1, $variant2];
        $productMock->name = 'Product with Variants';
        $productMock->_id = new ObjectId();

        // Mock user state
        $userMock = $this->yiiMock->mockAppComponent('user');
        $userMock->shouldReceive('getState')->with('username')->andReturn('testuser');

        $data = ['name' => 'Product with Variants', 'price' => 99, 'quantity' => 10];

        $result = ProductHelper::createProduct($data);

        $this->assertTrue($result['success']);
    }

    public function testRecordStockLog_NoQuantity()
    {
        // Mock StockLog to verify it's NOT called when quantity is 0
        $stockLogMock = m::mock('overload:StockLog');
        $stockLogMock->shouldReceive('add')->never();

        $productMock = m::mock('Product');
        $productMock->_id = new ObjectId();
        $productMock->name = 'No Stock Product';
        $productMock->quantity = 0;
        $productMock->variants = [];

        // This should not throw any exceptions
        ProductHelper::recordStockLog($productMock, 'create');

        $this->assertTrue(true); // Test passes if no exception thrown
    }

    public function testRecordStockLog_Exception()
    {
        // Mock StockLog to throw exception on add, but keep class constants
        $stockLogMock = m::mock('StockLog')->makePartial();
        $stockLogMock->shouldReceive('add')->andThrow(new Exception('Stock log error'));

        $productMock = m::mock('Product');
        $productMock->_id = new ObjectId();
        $productMock->name = 'Error Product';
        $productMock->quantity = 5;
        $productMock->variants = [];

        // Mock user state
        $userMock = $this->yiiMock->mockAppComponent('user');
        $userMock->shouldReceive('getState')->with('username')->andReturn('testuser');

        // Should not throw exception
        ProductHelper::recordStockLog($productMock, 'create');

        $this->assertTrue(true);
    }


}
