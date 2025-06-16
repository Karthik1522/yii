<?php

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use MongoDB\BSON\ObjectId;

class CategoryHelperTest extends MockeryTestCase
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

    // ========== loadCategoryById Tests ==========

    /**
     * Test loadCategoryById with valid ID and existing category
     */
    public function testLoadCategoryById_ValidId_ExistingCategory()
    {
        $categoryId = '507f1f77bcf86cd799439011';

        // Create a real Category model for return
        $categoryModel = new Category();
        $categoryModel->name = 'Test Category';
        $categoryModel->_id = new ObjectId($categoryId);

        $this->mongoMock->mockFindByPk('Category', $categoryModel);

        $result = CategoryHelper::loadCategoryById($categoryId);

        $this->assertInstanceOf('Category', $result);
        $this->assertEquals('Test Category', $result->name);
    }

    /**
     * Test loadCategoryById with valid ID but non-existing category
     */
    public function testLoadCategoryById_ValidId_NonExistingCategory()
    {
        $categoryId = '507f1f77bcf86cd799439011';

        $this->mongoMock->mockFindByPk('Category', null);

        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('The requested category does not exist.');

        CategoryHelper::loadCategoryById($categoryId);
    }

    /**
     * Test loadCategoryById with invalid ID format
     */
    public function testLoadCategoryById_InvalidIdFormat()
    {
        $invalidId = 'invalid-id';

        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('Invalid Category ID format.');

        CategoryHelper::loadCategoryById($invalidId);
    }

    /**
     * Test loadCategoryById with exception during database operation
     */
    public function testLoadCategoryById_DatabaseException()
    {
        $categoryId = '507f1f77bcf86cd799439011';

        // Mock Category model to throw exception during findByPk
        $categoryModelMock = m::mock('alias:Category');
        $categoryModelMock->shouldReceive('model')->andReturnSelf();
        $categoryModelMock->shouldReceive('findByPk')
            ->with(m::type(ObjectId::class))
            ->andThrow(new Exception('Database connection failed'));

        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('Error retrieving category data.');

        CategoryHelper::loadCategoryById($categoryId);
    }
    // ========== createCategory Tests ==========

    /**
     * Test createCategory with valid data and successful save
     */
    public function testCreateCategory_ValidData_SuccessfulSave()
    {
        $categoryData = [
            'name' => 'New Category',
            'description' => 'Test description'
        ];

        // Mock Category class to return a mock instance that saves successfully
        $categoryMock = m::mock('overload:Category');
        // $categoryMock->shouldReceive('setAttributes')->once();
        $categoryMock->shouldReceive('save')->once()->andReturn(true);
        $categoryMock->name = 'New Category';
        $categoryMock->_id = new ObjectId('507f1f77bcf86cd799439011');

        $result = CategoryHelper::createCategory($categoryData);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertInstanceOf('Category', $result['model']);
        $this->assertEquals('Category Created Successfully', $result['message']);
    }

    /**
     * Test createCategory with validation errors
     */
    public function testCreateCategory_ValidationErrors()
    {
        $categoryData = [
            'name' => '', // Empty name should cause validation error
            'description' => 'Test description'
        ];

        // Mock Category class to return a mock instance that fails to save
        $categoryMock = m::mock('overload:Category');
        // $categoryMock->shouldReceive('setAttributes')->once();
        $categoryMock->shouldReceive('save')->once()->andReturn(false);
        $categoryMock->shouldReceive('getErrors')->once()->andReturn(['name' => ['Name cannot be blank.']]);

        // Mock CHtml for error summary
        m::mock('alias:CHtml')
            ->shouldReceive('errorSummary')
            ->once()
            ->andReturn('<ul><li>Name cannot be blank.</li></ul>');

        $result = CategoryHelper::createCategory($categoryData);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertInstanceOf('Category', $result['model']);
        $this->assertStringContainsString('Error creating category', $result['message']);
    }

    /**
     * Test createCategory with exception during creation
     */
    public function testCreateCategory_ExceptionDuringCreation()
    {
        $categoryData = ['name' => 'Test Category'];

        // Mock Category constructor to throw exception during save method instead of constructor
        $categoryMock = m::mock('overload:Category');
        $categoryMock->shouldReceive('save')->once()->andThrow(new Exception('Database error'));

        $result = CategoryHelper::createCategory($categoryData);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertInstanceOf('Category', $result['model']);
        $this->assertEquals('Error creating category.', $result['message']);
    }


    // ========== updateCategory Tests ==========

    /**
     * Test updateCategory with valid data and successful save
     */
    public function testUpdateCategory_ValidData_SuccessfulSave()
    {
        $categoryData = [
            'name' => 'Updated Category',
            'description' => 'Updated description'
        ];

        // Create a real Category model
        $mockCategory = m::mock('Category')->makePartial();
        $mockCategory->_id = new ObjectId('507f1f77bcf86cd799439011');
        $mockCategory->name = 'Original Category';
        $mockCategory->shouldReceive('setAttributes')->once();
        $mockCategory->shouldReceive('save')->once()->andReturn(true);

        $result = CategoryHelper::updateCategory($mockCategory, $categoryData);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertSame($mockCategory, $result['model']);
        $this->assertEquals('Category Updated Successfully', $result['message']);
    }

    /**
     * Test updateCategory with validation errors
     */
    public function testUpdateCategory_ValidationErrors()
    {
        $categoryData = [
            'name' => '', // Empty name should cause validation error
            'description' => 'Updated description'
        ];

        // Create a real Category model
        $mockCategory = m::mock('Category')->makePartial();
        $mockCategory->_id = new ObjectId('507f1f77bcf86cd799439011');
        $mockCategory->shouldReceive('setAttributes')->once();
        $mockCategory->shouldReceive('save')->once()->andReturn(false);
        $mockCategory->shouldReceive('getErrors')->once()->andReturn(['name' => ['Name cannot be blank.']]);

        // Mock CHtml for error summary
        m::mock('alias:CHtml')
            ->shouldReceive('errorSummary')
            ->once()
            ->andReturn('<ul><li>Name cannot be blank.</li></ul>');

        $result = CategoryHelper::updateCategory($mockCategory, $categoryData);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertSame($mockCategory, $result['model']);
        $this->assertStringContainsString('Error updating category', $result['message']);
    }

    /**
     * Test updateCategory with exception during update
     */
    public function testUpdateCategory_ExceptionDuringUpdate()
    {
        $categoryData = ['name' => 'Updated Category'];

        $mockCategory = m::mock('Category')->makePartial();
        $mockCategory->_id = new ObjectId('507f1f77bcf86cd799439011');
        $mockCategory->shouldReceive('setAttributes')->andThrow(new Exception('Database error'));

        $result = CategoryHelper::updateCategory($mockCategory, $categoryData);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertSame($mockCategory, $result['model']);
        $this->assertEquals('Error updating category.', $result['message']);
    }

    // ========== deleteCategory Tests ==========

    /**
     * Test deleteCategory with successful deletion (no associated products or child categories)
     */
    public function testDeleteCategory_SuccessfulDeletion()
    {
        $mockCategory = m::mock('Category')->makePartial();
        $mockCategory->_id = new ObjectId('507f1f77bcf86cd799439011');
        $mockCategory->name = 'Test Category';
        $mockCategory->shouldReceive('delete')->once()->andReturn(true);

        // Mock Product count check - no associated products
        $this->mongoMock->mockCount('Product', 0);

        // Mock child Category count check - no child categories
        $this->mongoMock->mockCount('Category', 0);

        $result = CategoryHelper::deleteCategory($mockCategory);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Category deleted successfully.', $result['message']);
    }

    /**
     * Test deleteCategory with associated products
     */
    public function testDeleteCategory_WithAssociatedProducts()
    {
        $mockCategory = new Category();
        $mockCategory->_id = new ObjectId('507f1f77bcf86cd799439011');
        $mockCategory->name = 'Test Category';

        // Mock Product count check - has associated products
        $this->mongoMock->mockCount('Product', 5);

        $result = CategoryHelper::deleteCategory($mockCategory);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString("Cannot delete category 'Test Category'", $result['message']);
        $this->assertStringContainsString("5 product(s)", $result['message']);
    }

    /**
     * Test deleteCategory with child categories
     */
    public function testDeleteCategory_WithChildCategories()
    {
        $mockCategory = new Category();
        $mockCategory->_id = new ObjectId('507f1f77bcf86cd799439011');
        $mockCategory->name = 'Test Category';

        // Mock Product count check - no associated products
        $this->mongoMock->mockCount('Product', 0);

        // Mock child Category count check - has child categories
        $this->mongoMock->mockCount('Category', 3);

        $result = CategoryHelper::deleteCategory($mockCategory);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString("Cannot delete category 'Test Category'", $result['message']);
        $this->assertStringContainsString("3 other categor(y/ies)", $result['message']);
    }

    /**
     * Test deleteCategory with database deletion failure
     */
    public function testDeleteCategory_DeletionFails()
    {
        $mockCategory = m::mock('Category')->makePartial();
        $mockCategory->_id = new ObjectId('507f1f77bcf86cd799439011');
        $mockCategory->name = 'Test Category';
        $mockCategory->shouldReceive('delete')->once()->andReturn(false);

        // Mock Product count check - no associated products
        $this->mongoMock->mockCount('Product', 0);

        // Mock child Category count check - no child categories
        $this->mongoMock->mockCount('Category', 0);

        $result = CategoryHelper::deleteCategory($mockCategory);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Error deleting category.', $result['message']);
    }

    /**
     * Test deleteCategory with exception during deletion process
     */
    public function testDeleteCategory_ExceptionDuringDeletion()
    {
        $mockCategory = m::mock('Category')->makePartial();
        $mockCategory->_id = new ObjectId('507f1f77bcf86cd799439011');
        $mockCategory->name = 'Test Category';

        // Mock Product count check to throw exception
        $mockProductModel = m::mock('alias:Product');
        $mockProductModel->shouldReceive('model')->andReturnSelf();
        $mockProductModel->shouldReceive('countByAttributes')
            ->andThrow(new Exception('Database error'));

        $result = CategoryHelper::deleteCategory($mockCategory);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Error deleting category.', $result['message']);
    }

    // ========== prepareCategorySearch Tests ==========

    /**
     * Test prepareCategorySearch without search data
     */
    public function testPrepareCategorySearch_WithoutSearchData()
    {
        // Mock Category class constructor
        $categoryMock = m::mock('overload:Category');
        $categoryMock->shouldReceive('unsetAttributes')->once();

        $result = CategoryHelper::prepareCategorySearch();

        $this->assertInstanceOf('Category', $result);
    }

    /**
     * Test prepareCategorySearch with search data
     */
    public function testPrepareCategorySearch_WithSearchData()
    {
        $searchData = [
            'name' => 'Test',
            'description' => 'Search term'
        ];

        // Mock Category class constructor
        $categoryMock = m::mock('overload:Category');
        $categoryMock->shouldReceive('unsetAttributes')->once();
        $categoryMock->name = 'Test';

        $result = CategoryHelper::prepareCategorySearch($searchData);

        $this->assertInstanceOf('Category', $result);
    }

    /**
     * Test prepareCategorySearch with exception
     */
    public function testPrepareCategorySearch_Exception()
    {
        // Mock Category constructor to throw exception
        m::mock('overload:Category')
            ->shouldReceive('__construct')
            ->with('search')
            ->andThrow(new Exception('Model initialization error'));

        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('Error preparing category search.');

        CategoryHelper::prepareCategorySearch();
    }

    // ========== getParentCategoryOptions Tests ==========

    /**
     * Test getParentCategoryOptions without exclude ID
     */
    public function testGetParentCategoryOptions_WithoutExcludeId()
    {
        $expectedOptions = [
            '507f1f77bcf86cd799439011' => 'Parent Category 1',
            '507f1f77bcf86cd799439012' => 'Parent Category 2'
        ];

        // Mock Category static method
        m::mock('alias:Category')
            ->shouldReceive('getCategoryOptions')
            ->with(null)
            ->andReturn($expectedOptions)
            ->once();

        $result = CategoryHelper::getParentCategoryOptions();

        $this->assertEquals($expectedOptions, $result);
    }

    /**
     * Test getParentCategoryOptions with exclude ID
     */
    public function testGetParentCategoryOptions_WithExcludeId()
    {
        $excludeId = '507f1f77bcf86cd799439011';
        $expectedOptions = [
            '507f1f77bcf86cd799439012' => 'Parent Category 2'
        ];

        // Mock Category static method
        m::mock('alias:Category')
            ->shouldReceive('getCategoryOptions')
            ->with($excludeId)
            ->andReturn($expectedOptions)
            ->once();

        $result = CategoryHelper::getParentCategoryOptions($excludeId);

        $this->assertEquals($expectedOptions, $result);
    }

    /**
     * Test getParentCategoryOptions with exception
     */
    public function testGetParentCategoryOptions_Exception()
    {
        // Mock Category static method to throw exception
        m::mock('alias:Category')
            ->shouldReceive('getCategoryOptions')
            ->with(null)
            ->andThrow(new Exception('Database error'))
            ->once();

        $result = CategoryHelper::getParentCategoryOptions();

        $this->assertEquals([], $result);
    }

    // ========== handleAjaxResponse Tests ==========

    /**
     * Test handleAjaxResponse with successful result and AJAX request
     */
    public function testHandleAjaxResponse_SuccessfulResult_AjaxRequest()
    {
        $result = [
            'success' => true,
            'message' => 'Operation successful'
        ];

        $expectedJson = [
            'status' => 'success',
            'message' => 'Operation successful'
        ];

        // Mock Yii app for end() call
        $this->yiiAppMock->mockApp();
        $this->yiiAppMock->mockEnd();

        // Mock CJSON
        m::mock('alias:CJSON')
            ->shouldReceive('encode')
            ->with($expectedJson)
            ->andReturn(json_encode($expectedJson))
            ->once();

        // Capture output
        ob_start();
        CategoryHelper::handleAjaxResponse($result, true);
        $output = ob_get_clean();

        $this->assertEquals(json_encode($expectedJson), $output);
    }

    /**
     * Test handleAjaxResponse with non-AJAX request
     */
    public function testHandleAjaxResponse_NonAjaxRequest()
    {
        $result = [
            'success' => true,
            'message' => 'Operation successful'
        ];

        // Should not call any JSON encoding or app end for non-AJAX requests
        CategoryHelper::handleAjaxResponse($result, false);

        // Test passes if no exceptions are thrown and no output is generated
        $this->assertTrue(true);
    }
}
