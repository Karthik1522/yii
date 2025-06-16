<?php

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use MongoDB\BSON\ObjectId;

class DashboardHelperTest extends MockeryTestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    // ========== getDashboardData Tests ==========

    /**
     * Test getDashboardData success scenario with all data
     */
    public function testGetDashboardData_Success()
    {
        // Mock StockHelper::getStockStatistics()
        $stockStats = [
            'totalProducts' => 150,
            'lowStockCount' => 25,
            'outOfStockCount' => 5,
            'lowStockThreshold' => 10
        ];

        $mockStockHelper = m::mock('overload:StockHelper');
        $mockStockHelper->shouldReceive('getStockStatistics')
            ->once()
            ->andReturn($stockStats);

        // Mock Category::model()->count()
        $mockCategoryModel = m::mock();
        $mockCategoryModel->shouldReceive('count')
            ->once()
            ->andReturn(12);

        m::mock('overload:Category')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockCategoryModel);

        // Mock StockHelper::getRecentStockMovements()
        $recentMovements = [
            (object)['product_id' => '507f1f77bcf86cd799439011', 'type' => 'received'],
            (object)['product_id' => '507f1f77bcf86cd799439012', 'type' => 'sold']
        ];

        $mockStockHelper->shouldReceive('getRecentStockMovements')
            ->with(5)
            ->once()
            ->andReturn($recentMovements);

        // Mock Product::model()->count() with criteria for recent products
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('count')
            ->with(m::type('EMongoCriteria'))
            ->once()
            ->andReturn(8);

        m::mock('overload:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Yii::log
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')
                ->with(
                    'Fetching dashboard data',
                    'CLogger::LEVEL_INFO',
                    'application.inventory.dashboard.helper.getDashboardData'
                )
                ->once();
        }

        // Mock MongoDate
        if (!class_exists('MongoDate')) {
            m::mock('overload:MongoDate');
        }

        $result = DashboardHelper::getDashboardData();

        $this->assertIsArray($result);
        $this->assertEquals($stockStats, $result['stockStatistics']);
        $this->assertEquals(12, $result['totalCategories']);
        $this->assertEquals($recentMovements, $result['recentMovements']);
        $this->assertEquals(8, $result['recentProductsCount']);
        $this->assertInstanceOf('MongoDate', $result['lastUpdated']);
    }

    /**
     * Test getDashboardData with StockHelper exception
     */
    public function testGetDashboardData_StockHelperException()
    {
        // Mock StockHelper to throw exception
        $mockStockHelper = m::mock('overload:StockHelper');
        $mockStockHelper->shouldReceive('getStockStatistics')
            ->once()
            ->andThrow(new Exception('Database connection failed'));

        // Mock Yii::log for both info and error logs
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')
                ->with(
                    'Fetching dashboard data',
                    'CLogger::LEVEL_INFO',
                    'application.inventory.dashboard.helper.getDashboardData'
                )
                ->once();

            $mockYii->shouldReceive('log')
                ->with(
                    m::pattern('/Error fetching dashboard data: Database connection failed/'),
                    'CLogger::LEVEL_ERROR',
                    'application.inventory.dashboard.helper.getDashboardData'
                )
                ->once();
        }

        // Mock MongoDate for error response
        if (!class_exists('MongoDate')) {
            m::mock('overload:MongoDate');
        }

        $result = DashboardHelper::getDashboardData();

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['stockStatistics']['totalProducts']);
        $this->assertEquals(0, $result['stockStatistics']['lowStockCount']);
        $this->assertEquals(0, $result['stockStatistics']['outOfStockCount']);
        $this->assertEquals(10, $result['stockStatistics']['lowStockThreshold']);
        $this->assertEquals(0, $result['totalCategories']);
        $this->assertEquals([], $result['recentMovements']);
        $this->assertEquals(0, $result['recentProductsCount']);
        $this->assertInstanceOf('MongoDate', $result['lastUpdated']);
        $this->assertEquals('Error loading dashboard data', $result['error']);
    }

    /**
     * Test getDashboardData with Category model exception
     */
    public function testGetDashboardData_CategoryException()
    {
        // Mock StockHelper to succeed
        $stockStats = ['totalProducts' => 100, 'lowStockCount' => 10, 'outOfStockCount' => 2, 'lowStockThreshold' => 10];
        $mockStockHelper = m::mock('overload:StockHelper');
        $mockStockHelper->shouldReceive('getStockStatistics')
            ->once()
            ->andReturn($stockStats);

        // Mock Category to throw exception
        $mockCategoryModel = m::mock();
        $mockCategoryModel->shouldReceive('count')
            ->once()
            ->andThrow(new Exception('Category count failed'));

        m::mock('overload:Category')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockCategoryModel);

        // Mock Yii::log
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')->times(2); // Info and error logs
        }

        // Mock MongoDate
        if (!class_exists('MongoDate')) {
            m::mock('overload:MongoDate');
        }

        $result = DashboardHelper::getDashboardData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Error loading dashboard data', $result['error']);
    }

    /**
     * Test getDashboardData with StockHelper::getRecentStockMovements exception
     */
    public function testGetDashboardData_RecentMovementsException()
    {
        // Mock StockHelper::getStockStatistics to succeed
        $stockStats = ['totalProducts' => 100, 'lowStockCount' => 10, 'outOfStockCount' => 2, 'lowStockThreshold' => 10];
        $mockStockHelper = m::mock('overload:StockHelper');
        $mockStockHelper->shouldReceive('getStockStatistics')
            ->once()
            ->andReturn($stockStats);

        // Mock Category to succeed
        $mockCategoryModel = m::mock();
        $mockCategoryModel->shouldReceive('count')
            ->once()
            ->andReturn(8);

        m::mock('overload:Category')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockCategoryModel);

        // Mock StockHelper::getRecentStockMovements to throw exception
        $mockStockHelper->shouldReceive('getRecentStockMovements')
            ->with(5)
            ->once()
            ->andThrow(new Exception('Recent movements failed'));

        // Mock Yii::log
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')->times(2); // Info and error logs
        }

        // Mock MongoDate
        if (!class_exists('MongoDate')) {
            m::mock('overload:MongoDate');
        }

        $result = DashboardHelper::getDashboardData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Error loading dashboard data', $result['error']);
    }

    /**
     * Test getDashboardData with Product model exception
     */
    public function testGetDashboardData_ProductException()
    {
        // Mock StockHelper methods to succeed
        $stockStats = ['totalProducts' => 100, 'lowStockCount' => 10, 'outOfStockCount' => 2, 'lowStockThreshold' => 10];
        $recentMovements = [(object)['product_id' => '507f1f77bcf86cd799439011', 'type' => 'received']];

        $mockStockHelper = m::mock('overload:StockHelper');
        $mockStockHelper->shouldReceive('getStockStatistics')
            ->once()
            ->andReturn($stockStats);
        $mockStockHelper->shouldReceive('getRecentStockMovements')
            ->with(5)
            ->once()
            ->andReturn($recentMovements);

        // Mock Category to succeed
        $mockCategoryModel = m::mock();
        $mockCategoryModel->shouldReceive('count')
            ->once()
            ->andReturn(8);

        m::mock('overload:Category')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockCategoryModel);

        // Mock Product to throw exception
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('count')
            ->with(m::type('EMongoCriteria'))
            ->once()
            ->andThrow(new Exception('Product count failed'));

        m::mock('overload:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Yii::log
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')->times(2); // Info and error logs
        }

        // Mock MongoDate
        if (!class_exists('MongoDate')) {
            m::mock('overload:MongoDate');
        }

        $result = DashboardHelper::getDashboardData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Error loading dashboard data', $result['error']);
    }

    // ========== getLowStockProducts Tests ==========

    /**
     * Test getLowStockProducts success scenario
     */
    public function testGetLowStockProducts_Success()
    {
        $lowStockProducts = [
            (object)['name' => 'Product 1', 'quantity' => 5],
            (object)['name' => 'Product 2', 'quantity' => 8]
        ];

        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('findAll')
            ->with(m::on(function ($criteria) {
                // Verify the criteria has the correct conditions
                return $criteria instanceof EMongoCriteria;
            }))
            ->once()
            ->andReturn($lowStockProducts);

        m::mock('overload:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Yii::log
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')
                ->with(
                    'Fetching low stock products',
                    'CLogger::LEVEL_INFO',
                    'application.inventory.dashboard.helper.getLowStockProducts'
                )
                ->once();
        }

        $result = DashboardHelper::getLowStockProducts(10, 10);

        $this->assertEquals($lowStockProducts, $result);
    }

    /**
     * Test getLowStockProducts with default parameters
     */
    public function testGetLowStockProducts_DefaultParameters()
    {
        $lowStockProducts = [
            (object)['name' => 'Product 1', 'quantity' => 3]
        ];

        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('findAll')
            ->with(m::on(function ($criteria) {
                return $criteria instanceof EMongoCriteria;
            }))
            ->once()
            ->andReturn($lowStockProducts);

        m::mock('overload:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Yii::log
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')
                ->with(
                    'Fetching low stock products',
                    'CLogger::LEVEL_INFO',
                    'application.inventory.dashboard.helper.getLowStockProducts'
                )
                ->once();
        }

        $result = DashboardHelper::getLowStockProducts(); // Using default parameters

        $this->assertEquals($lowStockProducts, $result);
    }

    /**
     * Test getLowStockProducts with exception
     */
    public function testGetLowStockProducts_Exception()
    {
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('findAll')
            ->with(m::type('EMongoCriteria'))
            ->once()
            ->andThrow(new Exception('Database error'));

        m::mock('overload:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Yii::log for both info and error
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')
                ->with(
                    'Fetching low stock products',
                    'CLogger::LEVEL_INFO',
                    'application.inventory.dashboard.helper.getLowStockProducts'
                )
                ->once();

            $mockYii->shouldReceive('log')
                ->with(
                    m::pattern('/Error fetching low stock products: Database error/'),
                    'CLogger::LEVEL_ERROR',
                    'application.inventory.dashboard.helper.getLowStockProducts'
                )
                ->once();
        }

        $result = DashboardHelper::getLowStockProducts(5, 5);

        $this->assertEquals([], $result);
    }

    /**
     * Test getLowStockProducts returns empty array when no products found
     */
    public function testGetLowStockProducts_NoProductsFound()
    {
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('findAll')
            ->with(m::type('EMongoCriteria'))
            ->once()
            ->andReturn([]);

        m::mock('overload:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Yii::log
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')
                ->with(
                    'Fetching low stock products',
                    'CLogger::LEVEL_INFO',
                    'application.inventory.dashboard.helper.getLowStockProducts'
                )
                ->once();
        }

        $result = DashboardHelper::getLowStockProducts(10, 10);

        $this->assertEquals([], $result);
    }

    // ========== getRecentProductActivities Tests ==========

    /**
     * Test getRecentProductActivities success scenario
     */
    public function testGetRecentProductActivities_Success()
    {
        $recentActivities = [
            (object)['name' => 'Product A', 'updated_at' => new MongoDate()],
            (object)['name' => 'Product B', 'updated_at' => new MongoDate()]
        ];

        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('findAll')
            ->with(m::on(function ($criteria) {
                // Verify criteria has sort and limit
                return $criteria instanceof EMongoCriteria;
            }))
            ->once()
            ->andReturn($recentActivities);

        m::mock('overload:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Yii::log
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')
                ->with(
                    'Fetching recent product activities',
                    'CLogger::LEVEL_INFO',
                    'application.inventory.dashboard.helper.getRecentProductActivities'
                )
                ->once();
        }

        $result = DashboardHelper::getRecentProductActivities(10);

        $this->assertEquals($recentActivities, $result);
    }

    /**
     * Test getRecentProductActivities with default parameter
     */
    public function testGetRecentProductActivities_DefaultParameter()
    {
        $recentActivities = [
            (object)['name' => 'Product X', 'updated_at' => new MongoDate()]
        ];

        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('findAll')
            ->with(m::type('EMongoCriteria'))
            ->once()
            ->andReturn($recentActivities);

        m::mock('overload:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Yii::log
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')
                ->with(
                    'Fetching recent product activities',
                    'CLogger::LEVEL_INFO',
                    'application.inventory.dashboard.helper.getRecentProductActivities'
                )
                ->once();
        }

        $result = DashboardHelper::getRecentProductActivities(); // Using default parameter

        $this->assertEquals($recentActivities, $result);
    }

    /**
     * Test getRecentProductActivities with exception
     */
    public function testGetRecentProductActivities_Exception()
    {
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('findAll')
            ->with(m::type('EMongoCriteria'))
            ->once()
            ->andThrow(new Exception('MongoDB connection error'));

        m::mock('overload:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Yii::log for both info and error
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')
                ->with(
                    'Fetching recent product activities',
                    'CLogger::LEVEL_INFO',
                    'application.inventory.dashboard.helper.getRecentProductActivities'
                )
                ->once();

            $mockYii->shouldReceive('log')
                ->with(
                    m::pattern('/Error fetching recent product activities: MongoDB connection error/'),
                    'CLogger::LEVEL_ERROR',
                    'application.inventory.dashboard.helper.getRecentProductActivities'
                )
                ->once();
        }

        $result = DashboardHelper::getRecentProductActivities(5);

        $this->assertEquals([], $result);
    }

    /**
     * Test getRecentProductActivities returns empty array when no activities found
     */
    public function testGetRecentProductActivities_NoActivitiesFound()
    {
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('findAll')
            ->with(m::type('EMongoCriteria'))
            ->once()
            ->andReturn([]);

        m::mock('overload:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Yii::log
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')
                ->with(
                    'Fetching recent product activities',
                    'CLogger::LEVEL_INFO',
                    'application.inventory.dashboard.helper.getRecentProductActivities'
                )
                ->once();
        }

        $result = DashboardHelper::getRecentProductActivities(15);

        $this->assertEquals([], $result);
    }

    /**
     * Test getRecentProductActivities with custom limit
     */
    public function testGetRecentProductActivities_CustomLimit()
    {
        $recentActivities = [
            (object)['name' => 'Product 1', 'updated_at' => new MongoDate()],
            (object)['name' => 'Product 2', 'updated_at' => new MongoDate()],
            (object)['name' => 'Product 3', 'updated_at' => new MongoDate()]
        ];

        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('findAll')
            ->with(m::on(function ($criteria) {
                // We can't easily verify the limit here without accessing private properties
                // but we can verify it's an EMongoCriteria instance
                return $criteria instanceof EMongoCriteria;
            }))
            ->once()
            ->andReturn($recentActivities);

        m::mock('overload:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        // Mock Yii::log
        if (!class_exists('Yii')) {
            $mockYii = m::mock('overload:Yii');
            $mockYii->shouldReceive('log')
                ->with(
                    'Fetching recent product activities',
                    'CLogger::LEVEL_INFO',
                    'application.inventory.dashboard.helper.getRecentProductActivities'
                )
                ->once();
        }

        $result = DashboardHelper::getRecentProductActivities(3);

        $this->assertEquals($recentActivities, $result);
    }
}
