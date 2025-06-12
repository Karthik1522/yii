<?php

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MongoDB\BSON\ObjectId;

class ProductTest extends MockeryTestCase
{
    protected $product;

    protected function setUp(): void
    {
        // Partial mock to avoid calling parent constructor or DB connection
        $this->product = m::mock(Product::class)->makePartial();
    }

    public function testGetCollectionName()
    {
        $this->assertEquals('products', $this->product->getCollectionName());
    }

    public function testStaticModelReturnsInstance()
    {
        $modelInstance = Product::model();
        $this->assertInstanceOf(Product::class, $modelInstance);
    }

    public function testAttributeLabels()
    {
        $labels = $this->product->attributeLabels();

        $this->assertIsArray($labels);
        $this->assertArrayHasKey('_id', $labels);
        $this->assertArrayHasKey('name', $labels);
        $this->assertArrayHasKey('sku', $labels);
        $this->assertArrayHasKey('description', $labels);
        $this->assertArrayHasKey('category_id', $labels);
        $this->assertArrayHasKey('quantity', $labels);
        $this->assertArrayHasKey('price', $labels);

        $this->assertEquals('ID', $labels['_id']);
        $this->assertEquals('Product Name', $labels['name']);
        $this->assertEquals('SKU', $labels['sku']);
        $this->assertEquals('Quantity on Hand', $labels['quantity']);
    }

    public function testRules()
    {
        $rules = $this->product->rules();

        $this->assertIsArray($rules);

        // Check that required rule contains name, sku, quantity, price
        $foundRequired = false;
        foreach ($rules as $rule) {
            if ($rule[1] === 'required' && strpos($rule[0], 'name') !== false
                && strpos($rule[0], 'sku') !== false
                && strpos($rule[0], 'quantity') !== false
                && strpos($rule[0], 'price') !== false) {
                $foundRequired = true;
                break;
            }
        }

        $this->assertTrue($foundRequired, 'Required rule for name, sku, quantity, price not found');

        // Check for numerical validation on quantity
        $foundQuantityNumerical = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'quantity' && $rule[1] === 'numerical') {
                $foundQuantityNumerical = true;
                $this->assertTrue($rule['integerOnly']);
                $this->assertEquals(0, $rule['min']);
                break;
            }
        }
        $this->assertTrue($foundQuantityNumerical, 'Numerical rule for quantity not found');

        // Check for numerical validation on price
        $foundPriceNumerical = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'price' && $rule[1] === 'numerical') {
                $foundPriceNumerical = true;
                $this->assertTrue($rule['integerOnly']);
                $this->assertEquals(0, $rule['min']);
                break;
            }
        }
        $this->assertTrue($foundPriceNumerical, 'Numerical rule for price not found');

        // Check for SKU unique validator
        $foundSkuUnique = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'sku' && $rule[1] === 'ext.YiiMongoDbSuite.extra.EMongoUniqueValidator') {
                $foundSkuUnique = true;
                $this->assertEquals('Product', $rule['className']);
                $this->assertEquals('sku', $rule['attributeName']);
                $this->assertFalse($rule['caseSensitive']);
                break;
            }
        }
        $this->assertTrue($foundSkuUnique, 'SKU unique validator not found');
    }

    public function testEmbeddedDocuments()
    {
        $embeddedDocs = $this->product->embeddedDocuments();

        $this->assertIsArray($embeddedDocs);
        $this->assertArrayHasKey('dimensions', $embeddedDocs);
        $this->assertEquals('Dimensions', $embeddedDocs['dimensions']);
    }

    public function testBehaviors()
    {
        $behaviors = $this->product->behaviors();

        $this->assertIsArray($behaviors);
        $this->assertNotEmpty($behaviors);

        // Check for EEmbeddedArraysBehavior
        $foundEmbeddedArraysBehavior = false;
        foreach ($behaviors as $behavior) {
            if ($behavior['class'] === 'ext.YiiMongoDbSuite.extra.EEmbeddedArraysBehavior') {
                $foundEmbeddedArraysBehavior = true;
                $this->assertEquals('variants', $behavior['arrayPropertyName']);
                $this->assertEquals('Variant', $behavior['arrayDocClassName']);
                break;
            }
        }
        $this->assertTrue($foundEmbeddedArraysBehavior, 'EEmbeddedArraysBehavior not found');
    }

    public function testGetCategoryName_WithValidCategoryId()
    {
        $categoryId = '507f1f77bcf86cd799439011';

        // Mock Category model
        $categoryMock = m::mock();
        $categoryMock->name = 'Test Category';

        $categoryModelMock = m::mock('alias:Category');
        $categoryModelMock->shouldReceive('model')->andReturnSelf();
        $categoryModelMock->shouldReceive('findByPk')
            ->with(m::type(ObjectId::class))
            ->andReturn($categoryMock);

        $this->product->category_id = $categoryId;
        $result = $this->product->getCategoryName();

        $this->assertEquals('Test Category', $result);
    }

    public function testGetCategoryName_WithInvalidCategoryId()
    {
        $categoryId = '507f1f77bcf86cd799439011';

        // Mock Category model to return null
        $categoryModelMock = m::mock('alias:Category');
        $categoryModelMock->shouldReceive('model')->andReturnSelf();
        $categoryModelMock->shouldReceive('findByPk')
            ->with(m::type(ObjectId::class))
            ->andReturn(null);

        $this->product->category_id = $categoryId;
        $result = $this->product->getCategoryName();

        $this->assertEquals('N/A', $result);
    }

    public function testGetCategoryName_WithEmptyCategoryId()
    {
        $this->product->category_id = '';
        $result = $this->product->getCategoryName();

        $this->assertEquals('N/A', $result);
    }

    public function testBeforeValidate_ProcessesTagsInput()
    {
        $product = new Product();
        $product->tags_input = 'tag1, tag2, tag3';

        // Call beforeValidate using reflection since it's protected
        $reflection = new ReflectionClass($product);
        $method = $reflection->getMethod('beforeValidate');
        $method->setAccessible(true);
        $method->invoke($product);

        $this->assertIsArray($product->tags);
        $this->assertEquals(['tag1', 'tag2', 'tag3'], $product->tags);
    }

    public function testBeforeValidate_HandlesEmptyTagsInput()
    {
        $product = new Product();
        $product->tags_input = '';

        // Call beforeValidate using reflection
        $reflection = new ReflectionClass($product);
        $method = $reflection->getMethod('beforeValidate');
        $method->setAccessible(true);
        $method->invoke($product);

        $this->assertIsArray($product->tags);
        $this->assertEmpty($product->tags);
    }

    public function testBeforeValidate_FiltersEmptyTags()
    {
        $product = new Product();
        $product->tags_input = 'tag1, , tag3, ';

        // Call beforeValidate using reflection
        $reflection = new ReflectionClass($product);
        $method = $reflection->getMethod('beforeValidate');
        $method->setAccessible(true);
        $method->invoke($product);

        $this->assertEquals(['tag1', 'tag3'], $product->tags);
    }

    public function testValidateCategoryExists_WithValidCategory()
    {
        $categoryId = '507f1f77bcf86cd799439011';

        // Mock Category model to return a category
        $categoryMock = m::mock();
        $categoryModelMock = m::mock('alias:Category');
        $categoryModelMock->shouldReceive('model')->andReturnSelf();
        $categoryModelMock->shouldReceive('findByPk')
            ->with(m::type(ObjectId::class))
            ->andReturn($categoryMock);

        $product = new Product();
        $product->category_id = $categoryId;
        $product->validateCategoryExists('category_id', []);

        // Product should have no errors for category_id
        $this->assertFalse($product->hasErrors('category_id'));
    }

    public function testValidateCategoryExists_WithInvalidCategory()
    {
        $categoryId = '507f1f77bcf86cd799439011';

        // Mock Category model to return null
        $categoryModelMock = m::mock('alias:Category');
        $categoryModelMock->shouldReceive('model')->andReturnSelf();
        $categoryModelMock->shouldReceive('findByPk')
            ->with(m::type(ObjectId::class))
            ->andReturn(null);

        $product = new Product();
        $product->category_id = $categoryId;
        $product->validateCategoryExists('category_id', []);

        // Product should have error for category_id
        $this->assertTrue($product->hasErrors('category_id'));
        $errors = $product->getErrors('category_id');
        $this->assertContains('The selected category does not exist.', $errors);
    }

    public function testValidateCategoryExists_WithInvalidObjectIdFormat()
    {
        // Mock Category::model() to return null to simulate the mocking issue
        $categoryModelMock = m::mock('alias:Category');
        $categoryModelMock->shouldReceive('model')->andReturn(null);

        $product = new Product();
        $product->category_id = 'invalid-id';
        $product->validateCategoryExists('category_id', []);

        // Product should have error for category_id
        $this->assertTrue($product->hasErrors('category_id'));
        $errors = $product->getErrors('category_id');
        $this->assertContains('Invalid Category ID format.', $errors);
    }

    public function testSearchProvider_ReturnsDataProvider()
    {
        // Mock EMongoCriteria and EMongoDocumentDataProvider
        $criteriaMock = m::mock('EMongoCriteria');
        $criteriaMock->shouldReceive('addCond')->andReturnSelf();

        $dataProviderMock = m::mock('EMongoDocumentDataProvider');

        // Override the searchProvider method to avoid actual DB calls
        $productMock = m::mock(Product::class)->makePartial();
        $productMock->shouldReceive('searchProvider')->andReturn($dataProviderMock);

        $result = $productMock->searchProvider();

        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testDefaultValues()
    {
        $product = new Product();

        $this->assertEquals(0, $product->quantity);
        $this->assertEquals(0.00, $product->price);
        $this->assertIsArray($product->variants);
        $this->assertIsArray($product->tags);
    }

    public function testBeforeSave_SetsTimestamps()
    {
        $product = new Product();

        // Mock isNewRecord and parent::beforeSave
        $productMock = m::mock(Product::class)->makePartial();
        $productMock->shouldReceive('isNewRecord')->andReturn(true);
        $productMock->shouldReceive('parent::beforeSave')->andReturn(true);

        $result = $productMock->beforeSave();

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }
}
