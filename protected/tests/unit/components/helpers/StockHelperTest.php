<?php

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

class StockHelperTest extends MockeryTestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testPrepareStockLogSearchWithoutSearchData()
    {
        $mockStockLog = m::mock('overload:StockLog');
        $mockStockLog->shouldReceive('unsetAttributes')->once();

        $result = StockHelper::prepareStockLogSearch(null);

        $this->assertInstanceOf('StockLog', $result);
    }

    public function testPrepareStockLogSearchWithSearchData()
    {
        $searchData = ['product_name' => 'Test Item', 'status' => 1];

        $mockStockLog = m::mock('overload:StockLog');
        $mockStockLog->shouldReceive('unsetAttributes')->once();
        // Mock the attributes property assignment
        $mockStockLog->attributes = null;
        $mockStockLog->shouldReceive('setAttribute')->never(); // This method isn't called

        $result = StockHelper::prepareStockLogSearch($searchData);

        $this->assertInstanceOf('StockLog', $result);
    }

    public function testPrepareStockLogSearchThrowsException()
    {
        $mockStockLog = m::mock('overload:StockLog');
        $mockStockLog->shouldReceive('__construct')
            ->andThrow(new \Exception('Test exception'));

        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('Error preparing stock log search.');

        StockHelper::prepareStockLogSearch();
    }

    public function testGetStockStatisticsSuccess()
    {
        // Mock Product model
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('count')
            ->withNoArgs()
            ->once()
            ->andReturn(150);

        $mockProductModel->shouldReceive('count')
            ->with(m::type('EMongoCriteria'))
            ->twice()
            ->andReturn(25, 5);

        m::mock('alias:Product')
            ->shouldReceive('model')
            ->times(3)
            ->andReturn($mockProductModel);

        // Don't overload EMongoCriteria, just mock its methods when used
        $result = StockHelper::getStockStatistics();

        $expected = [
            'totalProducts' => 150,
            'lowStockCount' => 25,
            'outOfStockCount' => 5,
            'lowStockThreshold' => 10
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetStockStatisticsHandlesException()
    {
        $mockProductModel = m::mock();
        $mockProductModel->shouldReceive('count')
            ->withNoArgs()
            ->once()
            ->andThrow(new \Exception('Database error'));

        m::mock('alias:Product')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockProductModel);

        $result = StockHelper::getStockStatistics();

        $expected = [
            'totalProducts' => 0,
            'lowStockCount' => 0,
            'outOfStockCount' => 0,
            'lowStockThreshold' => 10
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetRecentStockMovementsSuccess()
    {
        $expectedMovements = [
            (object)['log_id' => 1, 'change' => 10],
            (object)['log_id' => 2, 'change' => -5]
        ];

        $mockStockLogModel = m::mock();
        $mockStockLogModel->shouldReceive('findAll')
            ->with(m::type('EMongoCriteria'))
            ->once()
            ->andReturn($expectedMovements);

        m::mock('alias:StockLog')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockStockLogModel);

        $result = StockHelper::getRecentStockMovements(10);
        $this->assertEquals($expectedMovements, $result);
    }

    public function testGetRecentStockMovementsHandlesException()
    {
        $mockStockLogModel = m::mock();
        $mockStockLogModel->shouldReceive('findAll')
            ->with(m::type('EMongoCriteria'))
            ->once()
            ->andThrow(new \Exception('Database error'));

        m::mock('alias:StockLog')
            ->shouldReceive('model')
            ->once()
            ->andReturn($mockStockLogModel);

        $result = StockHelper::getRecentStockMovements();
        $this->assertEquals([], $result);
    }
}
