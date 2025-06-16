<?php

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use MongoDB\BSON\ObjectId;

class ReportHelperTest extends MockeryTestCase
{
    private $mongoMock;
    private $yiiAppMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mongoMock = new MongoMock();
        $this->yiiAppMock = new YiiAppMock();
    }

    protected function tearDown(): void
    {
        $this->mongoMock->close();
        $this->yiiAppMock->close();
        m::close();
        parent::tearDown();
    }

    // ========== generateStockLevelReport Tests ==========

    /**
     * Test generateStockLevelReport success scenario with cache miss
     */
    public function testGenerateStockLevelReport_CacheMiss_Success()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();
        $mockCache->shouldReceive('get')
            ->with('report_stock_level')
            ->once()
            ->andReturn(false); // Cache miss
        $mockCache->shouldReceive('set')
            ->with('report_stock_level', m::type('array'), 3600)
            ->once();

        // Mock MongoAggregator
        $aggregationResults = [
            'ok' => 1.0,
            'result' => [
                [
                    '_id' => '507f1f77bcf86cd799439011',
                    'sku' => 'PROD-001',
                    'name' => 'Product 1',
                    'category_id' => '507f1f77bcf86cd799439012',
                    'quantity' => 10,
                    'price' => 100.0,
                    'stock_value' => 1000.0
                ],
                [
                    '_id' => '507f1f77bcf86cd799439013',
                    'sku' => 'PROD-002',
                    'name' => 'Product 2',
                    'category_id' => '507f1f77bcf86cd799439014',
                    'quantity' => 5,
                    'price' => 50.0,
                    'stock_value' => 250.0
                ]
            ]
        ];

        $mockAggregator = m::mock();
        $mockAggregator->shouldReceive('setCollection')->with(m::any())->once()->andReturnSelf();
        $mockAggregator->shouldReceive('aggregate')->with(m::type('array'))->once()->andReturn($aggregationResults);

        m::mock('alias:MongoAggregator')
            ->shouldReceive('getInstance')
            ->once()
            ->andReturn($mockAggregator);

        // Mock Product::model()->getCollection()
        $mockCollection = m::mock();
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('getCollection')->once()->andReturn($mockCollection);

        m::mock('alias:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Category::model()->findAll()
        $categoryModels = [
            (object)['_id' => new ObjectId('507f1f77bcf86cd799439012'), 'name' => 'Category 1'],
            (object)['_id' => new ObjectId('507f1f77bcf86cd799439014'), 'name' => 'Category 2']
        ];

        $this->mongoMock->mockFindAll('Category', $categoryModels);

        $result = ReportHelper::generateStockLevelReport();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals(1250.0, $result['totalValue']);
        $this->assertEquals('Category 1', $result['data'][0]['categoryName']);
        $this->assertEquals('Category 2', $result['data'][1]['categoryName']);
    }

    /**
     * Test generateStockLevelReport with cache hit
     */
    public function testGenerateStockLevelReport_CacheHit_Success()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache hit
        $cachedData = [
            'data' => [
                ['name' => 'Cached Product', 'stock_value' => 500.0]
            ],
            'totalValue' => 500.0
        ];

        $mockCache->shouldReceive('get')
            ->with('report_stock_level')
            ->once()
            ->andReturn($cachedData);

        $result = ReportHelper::generateStockLevelReport();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($cachedData['data'], $result['data']);
        $this->assertEquals(500.0, $result['totalValue']);
    }

    /**
     * Test generateStockLevelReport with cache hit containing error
     */
    public function testGenerateStockLevelReport_CacheHit_WithError()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache hit with error
        $cachedData = [
            'data' => [],
            'totalValue' => 0,
            'error' => true
        ];

        $mockCache->shouldReceive('get')
            ->with('report_stock_level')
            ->once()
            ->andReturn($cachedData);

        $result = ReportHelper::generateStockLevelReport();

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals([], $result['data']);
        $this->assertEquals(0, $result['totalValue']);
        $this->assertEquals('Could not generate stock report (cached error).', $result['message']);
    }

    /**
     * Test generateStockLevelReport with aggregation failure
     */
    public function testGenerateStockLevelReport_AggregationFailure()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache miss
        $mockCache->shouldReceive('get')
            ->with('report_stock_level')
            ->once()
            ->andReturn(false);
        $mockCache->shouldReceive('set')
            ->with('report_stock_level', ['data' => [], 'totalValue' => 0], 3600)
            ->once();

        // Mock CVarDumper
        m::mock('alias:CVarDumper')
            ->shouldReceive('dumpAsString')
            ->with(m::any())
            ->once()
            ->andReturn('aggregation_failure_details');

        // Mock failed aggregation
        $aggregationResults = [
            'ok' => 0.0,
            'error' => 'Aggregation failed'
        ];

        $mockAggregator = m::mock();
        $mockAggregator->shouldReceive('setCollection')->with(m::any())->once()->andReturnSelf();
        $mockAggregator->shouldReceive('aggregate')->with(m::type('array'))->once()->andReturn($aggregationResults);

        m::mock('alias:MongoAggregator')
            ->shouldReceive('getInstance')
            ->once()
            ->andReturn($mockAggregator);

        // Mock Product::model()->getCollection()
        $mockCollection = m::mock();
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('getCollection')->once()->andReturn($mockCollection);

        m::mock('alias:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        $result = ReportHelper::generateStockLevelReport();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals([], $result['data']);
        $this->assertEquals(0, $result['totalValue']);
    }

    /**
     * Test generateStockLevelReport with aggregation exception
     */
    public function testGenerateStockLevelReport_AggregationException()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache miss
        $mockCache->shouldReceive('get')
            ->with('report_stock_level')
            ->once()
            ->andReturn(false);
        $mockCache->shouldReceive('set')
            ->with('report_stock_level', ['data' => [], 'totalValue' => 0, 'error' => true], 600)
            ->once();

        // Mock exception in aggregation
        $mockAggregator = m::mock();
        $mockAggregator->shouldReceive('setCollection')->with(m::any())->once()->andReturnSelf();
        $mockAggregator->shouldReceive('aggregate')->with(m::type('array'))->once()->andThrow(new Exception('Database error'));

        m::mock('alias:MongoAggregator')
            ->shouldReceive('getInstance')
            ->once()
            ->andReturn($mockAggregator);

        // Mock Product::model()->getCollection()
        $mockCollection = m::mock();
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('getCollection')->once()->andReturn($mockCollection);

        m::mock('alias:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        $result = ReportHelper::generateStockLevelReport();

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals([], $result['data']);
        $this->assertEquals(0, $result['totalValue']);
        $this->assertEquals('Could not generate stock report due to a server error.', $result['message']);
    }

    /**
     * Test generateStockLevelReport with outer exception
     */
    public function testGenerateStockLevelReport_OuterException()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache to throw exception
        $mockCache->shouldReceive('get')
            ->with('report_stock_level')
            ->once()
            ->andThrow(new Exception('Cache error'));

        $result = ReportHelper::generateStockLevelReport();

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals([], $result['data']);
        $this->assertEquals(0, $result['totalValue']);
        $this->assertEquals('Error generating stock level report.', $result['message']);
    }

    // ========== generateProductsPerCategoryReport Tests ==========

    /**
     * Test generateProductsPerCategoryReport success scenario with cache miss
     */
    public function testGenerateProductsPerCategoryReport_CacheMiss_Success()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache miss
        $mockCache->shouldReceive('get')
            ->with('report_products_per_category_simple')
            ->once()
            ->andReturn(false);
        $mockCache->shouldReceive('set')
            ->with('report_products_per_category_simple', m::type('array'), 3600)
            ->once();

        // Mock aggregation results
        $aggregationResults = [
            'ok' => 1.0,
            'result' => [
                ['categoryId' => '507f1f77bcf86cd799439011', 'productCount' => 5],
                ['categoryId' => '507f1f77bcf86cd799439012', 'productCount' => 3]
            ]
        ];

        $mockAggregator = m::mock();
        $mockAggregator->shouldReceive('setCollection')->with(m::any())->once()->andReturnSelf();
        $mockAggregator->shouldReceive('aggregate')->with(m::type('array'))->once()->andReturn($aggregationResults);

        m::mock('alias:MongoAggregator')
            ->shouldReceive('getInstance')
            ->once()
            ->andReturn($mockAggregator);

        // Mock Product::model()->getCollection()
        $mockCollection = m::mock();
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('getCollection')->once()->andReturn($mockCollection);

        m::mock('alias:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Category::model()->findAll()
        $categoryModels = [
            (object)['_id' => new ObjectId('507f1f77bcf86cd799439011'), 'name' => 'Electronics'],
            (object)['_id' => new ObjectId('507f1f77bcf86cd799439012'), 'name' => 'Books']
        ];

        $this->mongoMock->mockFindAll('Category', $categoryModels);

        $result = ReportHelper::generateProductsPerCategoryReport();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('Electronics', $result['data'][0]['categoryName']);
        $this->assertEquals('Books', $result['data'][1]['categoryName']);
        $this->assertEquals(5, $result['data'][0]['productCount']);
        $this->assertEquals(3, $result['data'][1]['productCount']);
    }

    /**
     * Test generateProductsPerCategoryReport with cache hit
     */
    public function testGenerateProductsPerCategoryReport_CacheHit_Success()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache hit
        $cachedData = [
            ['categoryId' => '123', 'categoryName' => 'Cached Category', 'productCount' => 10]
        ];

        $mockCache->shouldReceive('get')
            ->with('report_products_per_category_simple')
            ->once()
            ->andReturn($cachedData);

        $result = ReportHelper::generateProductsPerCategoryReport();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($cachedData, $result['data']);
    }

    /**
     * Test generateProductsPerCategoryReport with aggregation failure
     */
    public function testGenerateProductsPerCategoryReport_AggregationFailure()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache miss
        $mockCache->shouldReceive('get')
            ->with('report_products_per_category_simple')
            ->once()
            ->andReturn(false);

        // Mock failed aggregation
        $aggregationResults = [
            'ok' => 0.0,
            'error' => 'Aggregation failed'
        ];

        $mockAggregator = m::mock();
        $mockAggregator->shouldReceive('setCollection')->with(m::any())->once()->andReturnSelf();
        $mockAggregator->shouldReceive('aggregate')->with(m::type('array'))->once()->andReturn($aggregationResults);

        m::mock('alias:MongoAggregator')
            ->shouldReceive('getInstance')
            ->once()
            ->andReturn($mockAggregator);

        // Mock Product::model()->getCollection()
        $mockCollection = m::mock();
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('getCollection')->once()->andReturn($mockCollection);

        m::mock('alias:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        $result = ReportHelper::generateProductsPerCategoryReport();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals([], $result['data']);
    }

    /**
     * Test generateProductsPerCategoryReport with aggregation exception
     */
    public function testGenerateProductsPerCategoryReport_AggregationException()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache miss
        $mockCache->shouldReceive('get')
            ->with('report_products_per_category_simple')
            ->once()
            ->andReturn(false);

        // Mock exception in aggregation
        $mockAggregator = m::mock();
        $mockAggregator->shouldReceive('setCollection')->with(m::any())->once()->andReturnSelf();
        $mockAggregator->shouldReceive('aggregate')->with(m::type('array'))->once()->andThrow(new Exception('Database connection failed'));

        m::mock('alias:MongoAggregator')
            ->shouldReceive('getInstance')
            ->once()
            ->andReturn($mockAggregator);

        // Mock Product::model()->getCollection()
        $mockCollection = m::mock();
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('getCollection')->once()->andReturn($mockCollection);

        m::mock('alias:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        $result = ReportHelper::generateProductsPerCategoryReport();

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals([], $result['data']);
        $this->assertEquals('Could not generate products per category report.', $result['message']);
    }

    /**
     * Test generateProductsPerCategoryReport with outer exception
     */
    public function testGenerateProductsPerCategoryReport_OuterException()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache to throw exception
        $mockCache->shouldReceive('get')
            ->with('report_products_per_category_simple')
            ->once()
            ->andThrow(new Exception('Cache error'));

        $result = ReportHelper::generateProductsPerCategoryReport();

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals([], $result['data']);
        $this->assertEquals('Error generating products per category report.', $result['message']);
    }

    // ========== generatePriceRangeReport Tests ==========

    /**
     * Test generatePriceRangeReport success scenario with cache miss
     */
    public function testGeneratePriceRangeReport_CacheMiss_Success()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache miss
        $mockCache->shouldReceive('get')
            ->with('report_price_range')
            ->once()
            ->andReturn(false);
        $mockCache->shouldReceive('set')
            ->with('report_price_range', m::type('array'), 3600)
            ->once();

        // Mock aggregation results
        $aggregationResults = [
            'ok' => 1.0,
            'result' => [
                ['priceRange' => '0-50', 'productCount' => 10, 'totalStockValueInBucket' => 1000.0],
                ['priceRange' => '50-100', 'productCount' => 5, 'totalStockValueInBucket' => 750.0]
            ]
        ];

        $mockAggregator = m::mock();
        $mockAggregator->shouldReceive('setCollection')->with(m::any())->once()->andReturnSelf();
        $mockAggregator->shouldReceive('aggregate')->with(m::type('array'))->once()->andReturn($aggregationResults);

        m::mock('alias:MongoAggregator')
            ->shouldReceive('getInstance')
            ->once()
            ->andReturn($mockAggregator);

        // Mock Product::model()->getCollection()
        $mockCollection = m::mock();
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('getCollection')->once()->andReturn($mockCollection);

        m::mock('alias:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        $result = ReportHelper::generatePriceRangeReport();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('0-50', $result['data'][0]['priceRange']);
        $this->assertEquals('50-100', $result['data'][1]['priceRange']);
    }

    /**
     * Test generatePriceRangeReport with cache hit
     */
    public function testGeneratePriceRangeReport_CacheHit_Success()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache hit
        $cachedData = [
            ['priceRange' => 'Over 1000', 'productCount' => 2, 'totalStockValueInBucket' => 5000.0]
        ];

        $mockCache->shouldReceive('get')
            ->with('report_price_range')
            ->twice() // Called twice in the method
            ->andReturn($cachedData);

        $result = ReportHelper::generatePriceRangeReport();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($cachedData, $result['data']);
    }

    /**
     * Test generatePriceRangeReport with cache hit containing error
     */
    public function testGeneratePriceRangeReport_CacheHit_WithError()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache hit with error
        $cachedData = ['error' => true];

        $mockCache->shouldReceive('get')
            ->with('report_price_range')
            ->twice() // Called twice in the method
            ->andReturn($cachedData);

        $result = ReportHelper::generatePriceRangeReport();

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals([], $result['data']);
        $this->assertEquals('Could not generate price range report (cached error).', $result['message']);
    }

    /**
     * Test generatePriceRangeReport with aggregation failure
     */
    public function testGeneratePriceRangeReport_AggregationFailure()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache miss
        $mockCache->shouldReceive('get')
            ->with('report_price_range')
            ->once()
            ->andReturn(false);
        $mockCache->shouldReceive('set')
            ->with('report_price_range', [], 3600)
            ->once();

        // Mock CVarDumper
        m::mock('alias:CVarDumper')
            ->shouldReceive('dumpAsString')
            ->with(m::any())
            ->once()
            ->andReturn('aggregation_failure_details');

        // Mock failed aggregation
        $aggregationResults = [
            'ok' => 0.0,
            'error' => 'Aggregation failed'
        ];

        $mockAggregator = m::mock();
        $mockAggregator->shouldReceive('setCollection')->with(m::any())->once()->andReturnSelf();
        $mockAggregator->shouldReceive('aggregate')->with(m::type('array'))->once()->andReturn($aggregationResults);

        m::mock('alias:MongoAggregator')
            ->shouldReceive('getInstance')
            ->once()
            ->andReturn($mockAggregator);

        // Mock Product::model()->getCollection()
        $mockCollection = m::mock();
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('getCollection')->once()->andReturn($mockCollection);

        m::mock('alias:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        $result = ReportHelper::generatePriceRangeReport();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals([], $result['data']);
    }

    /**
     * Test generatePriceRangeReport with aggregation exception
     */
    public function testGeneratePriceRangeReport_AggregationException()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache miss
        $mockCache->shouldReceive('get')
            ->with('report_price_range')
            ->once()
            ->andReturn(false);
        $mockCache->shouldReceive('set')
            ->with('report_price_range', ['error' => true], 600)
            ->once();

        // Mock exception in aggregation
        $mockAggregator = m::mock();
        $mockAggregator->shouldReceive('setCollection')->with(m::any())->once()->andReturnSelf();
        $mockAggregator->shouldReceive('aggregate')->with(m::type('array'))->once()->andThrow(new Exception('Connection timeout'));

        m::mock('alias:MongoAggregator')
            ->shouldReceive('getInstance')
            ->once()
            ->andReturn($mockAggregator);

        // Mock Product::model()->getCollection()
        $mockCollection = m::mock();
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('getCollection')->once()->andReturn($mockCollection);

        m::mock('alias:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        $result = ReportHelper::generatePriceRangeReport();

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals([], $result['data']);
        $this->assertEquals('Could not generate price range report.', $result['message']);
    }

    /**
     * Test generatePriceRangeReport with outer exception
     */
    public function testGeneratePriceRangeReport_OuterException()
    {
        // Mock Yii application and cache
        $this->yiiAppMock->mockApp();
        $mockCache = $this->yiiAppMock->mockCache();

        // Mock cache to throw exception
        $mockCache->shouldReceive('get')
            ->with('report_price_range')
            ->once()
            ->andThrow(new Exception('Application error'));

        $result = ReportHelper::generatePriceRangeReport();

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals([], $result['data']);
        $this->assertEquals('Error generating price range report.', $result['message']);
    }

    // ========== createReportDataProvider Tests ==========

    /**
     * Test createReportDataProvider with default config
     */
    public function testCreateReportDataProvider_DefaultConfig()
    {
        $data = [
            ['_id' => '1', 'name' => 'Item 1'],
            ['_id' => '2', 'name' => 'Item 2']
        ];

        $result = ReportHelper::createReportDataProvider($data);

        $this->assertInstanceOf('CArrayDataProvider', $result);
    }

    /**
     * Test createReportDataProvider with custom config
     */
    public function testCreateReportDataProvider_CustomConfig()
    {
        $data = [
            ['id' => '1', 'name' => 'Item 1'],
            ['id' => '2', 'name' => 'Item 2']
        ];

        $customConfig = [
            'keyField' => 'id',
            'pagination' => ['pageSize' => 10],
            'sort' => ['attributes' => ['name', 'id']]
        ];

        $result = ReportHelper::createReportDataProvider($data, $customConfig);

        $this->assertInstanceOf('CArrayDataProvider', $result);
    }

    /**
     * Test createReportDataProvider with empty data
     */
    public function testCreateReportDataProvider_EmptyData()
    {
        $data = [];

        $result = ReportHelper::createReportDataProvider($data);

        $this->assertInstanceOf('CArrayDataProvider', $result);
    }

    // ========== handleReportFlashMessages Tests ==========

    /**
     * Test handleReportFlashMessages with success result
     */
    public function testHandleReportFlashMessages_Success()
    {
        $result = [
            'success' => true,
            'data' => ['some' => 'data']
        ];

        // Mock Yii application and user
        $this->yiiAppMock->mockApp();
        $mockUser = $this->yiiAppMock->mockAppComponent('user');
        $mockUser->shouldNotReceive('setFlash'); // Should not be called for success

        // This should not throw any exceptions or set any flash messages
        ReportHelper::handleReportFlashMessages($result);
    }

    /**
     * Test handleReportFlashMessages with failure result and message
     */
    public function testHandleReportFlashMessages_FailureWithMessage()
    {
        $result = [
            'success' => false,
            'data' => [],
            'message' => 'Report generation failed'
        ];

        // Mock Yii application and user
        $this->yiiAppMock->mockApp();
        $mockUser = $this->yiiAppMock->mockAppComponent('user');
        $mockUser->shouldReceive('setFlash')
            ->with('error', 'Report generation failed')
            ->once();

        ReportHelper::handleReportFlashMessages($result);
    }

    /**
     * Test handleReportFlashMessages with failure result but no message
     */
    public function testHandleReportFlashMessages_FailureWithoutMessage()
    {
        $result = [
            'success' => false,
            'data' => []
        ];

        // Mock Yii application and user
        $this->yiiAppMock->mockApp();
        $mockUser = $this->yiiAppMock->mockAppComponent('user');
        $mockUser->shouldNotReceive('setFlash'); // Should not be called without message

        ReportHelper::handleReportFlashMessages($result);
    }

    
}
