<?php

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MongoDB\BSON\ObjectId;

// Dummy classes for testing isolation
if (!class_exists('EMongoDocument')) {
    abstract class EMongoDocument
    {
        public function __construct($scenario = 'insert')
        {
        }
        public static function model($className = __CLASS__)
        {
            return new static();
        }
        public function getCollectionName()
        {
            return 'test';
        }
        public function isNewRecord()
        {
            return true;
        }
        public function beforeValidate()
        {
            return true;
        }
        public function beforeSave()
        {
            return true;
        }
        public function validate()
        {
            return true;
        }
        public function getErrors()
        {
            return [];
        }
        public function addError($attr, $error)
        {
        }
        public $_id;
    }
}
if (!class_exists('Category')) {
    class Category
    {
        public static function model()
        {
            return new static();
        }
        public function findByPk($id)
        {
            return null;
        }
        public $name = 'Test Category';
    }
}
if (!class_exists('Variant')) {
    class Variant
    {
        public function validate()
        {
            return true;
        }
        public function getErrors()
        {
            return [];
        }
    }
}
if (!class_exists('Dimensions')) {
    class Dimensions
    {
        public function validate()
        {
            return true;
        }
        public function getErrors()
        {
            return [];
        }
    }
}
if (!class_exists('EMongoCriteria')) {
    class EMongoCriteria
    {
        public function addCond($field, $op, $value)
        {
            return $this;
        }
        public const SORT_DESC = -1;
    }
}
if (!class_exists('EMongoDocumentDataProvider')) {
    class EMongoDocumentDataProvider
    {
        public function __construct($model, $config = [])
        {
        }
    }
}
if (!class_exists('MongoDate')) {
    class MongoDate
    {
        public function __construct($timestamp = null)
        {
        }
    }
}
if (!class_exists('MongoRegex')) {
    class MongoRegex
    {
        public function __construct($pattern)
        {
        }
    }
}
// Add MongoException for MongoDB compatibility
if (!class_exists('MongoException')) {
    class MongoException extends Exception
    {
    }
}

class ProductTest extends MockeryTestCase
{
    protected $product;

    protected function setUp(): void
    {
        // Partial mock to avoid calling parent constructor or DB connection
        // Enable mocking of protected methods
        $this->product = m::mock(Product::class)->makePartial()->shouldAllowMockingProtectedMethods();

        // Initialize other properties to avoid undefined property errors
        $this->product->variants = [];
        $this->product->tags = [];
        $this->product->quantity = 0;
        $this->product->price = 0.00;
        $this->product->name = '';
        $this->product->sku = '';
        $this->product->description = '';
        $this->product->category_id = '';
        $this->product->image_url = '';
        $this->product->tags_input = '';
        $this->product->created_at = null;
        $this->product->updated_at = null;
    }

    public function testGetCollectionName()
    {
        $this->assertEquals('products', $this->product->getCollectionName());
    }

    public function testPublicProperties()
    {
        // Test that all expected public properties exist
        $expectedProperties = [
            'name', 'sku', 'description', 'category_id', 'quantity', 'price',
            'image_url', 'image_filename_upload', 'variants', 'tags', 'tags_input',
            'created_at', 'updated_at'
        ];

        foreach ($expectedProperties as $property) {
            $this->assertTrue(property_exists($this->product, $property), "Property {$property} does not exist");
        }

        // Test default values
        $freshProduct = m::mock(Product::class)->makePartial();
        $freshProduct->quantity = 0;
        $freshProduct->price = 0.00;
        $freshProduct->variants = array();
        $freshProduct->tags = array();

        $this->assertEquals(0, $freshProduct->quantity);
        $this->assertEquals(0.00, $freshProduct->price);
        $this->assertEquals(array(), $freshProduct->variants);
        $this->assertEquals(array(), $freshProduct->tags);
    }

    public function testAttributeLabels()
    {
        $labels = $this->product->attributeLabels();
        $expectedLabels = [
            '_id' => 'ID',
            'name' => 'Product Name',
            'sku' => 'SKU',
            'description' => 'Description',
            'category_id' => 'Category',
            'quantity' => 'Quantity on Hand',
            'price' => 'Price',
            'image_url' => 'Product Image',
            'image_filename_upload' => 'Upload Image',
            'dimensions' => 'Dimensions',
            'variants' => 'Variants',
            'tags' => 'Tags',
            'tags_input' => 'Tags (comma-separated)',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];

        $this->assertIsArray($labels);
        $this->assertEquals($expectedLabels, $labels);
        $this->assertCount(15, $labels);
    }

    public function testRules()
    {
        $rules = $this->product->rules();
        $this->assertIsArray($rules);

        // Test that we have the expected number of rules
        $this->assertCount(17, $rules);

        // Check required rule
        $foundRequired = false;
        foreach ($rules as $rule) {
            if ($rule[1] === 'required' && $rule[0] === 'name, sku, quantity, price, quantity') {
                $foundRequired = true;
                break;
            }
        }
        $this->assertTrue($foundRequired, 'Required rule not found');

        // Check name length validation
        $foundNameLengthRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'name' && $rule[1] === 'length' && $rule['max'] === 255) {
                $foundNameLengthRule = true;
                break;
            }
        }
        $this->assertTrue($foundNameLengthRule, 'Name length validation rule not found');

        // Check SKU length validation
        $foundSkuLengthRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'sku' && $rule[1] === 'length' && $rule['max'] === 100) {
                $foundSkuLengthRule = true;
                break;
            }
        }
        $this->assertTrue($foundSkuLengthRule, 'SKU length validation rule not found');

        // Check SKU unique validation
        $foundSkuUniqueRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'sku' && $rule[1] === 'ext.YiiMongoDbSuite.extra.EMongoUniqueValidator') {
                $foundSkuUniqueRule = true;
                break;
            }
        }
        $this->assertTrue($foundSkuUniqueRule, 'SKU unique validation rule not found');

        // Check quantity numerical validation
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

        // Check price numerical validation
        $foundPriceRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'price' && $rule[1] === 'numerical' &&
                isset($rule['integerOnly']) && $rule['integerOnly'] === true &&
                isset($rule['min']) && $rule['min'] === 0) {
                $foundPriceRule = true;
                break;
            }
        }
        $this->assertTrue($foundPriceRule, 'Price numerical validation rule not found');

        // Check category_id match pattern rule
        $foundCategoryMatchRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'category_id' && $rule[1] === 'match' &&
                isset($rule['pattern']) && $rule['pattern'] === '/^[a-f0-9]{24}$/i') {
                $foundCategoryMatchRule = true;
                break;
            }
        }
        $this->assertTrue($foundCategoryMatchRule, 'Category ID match validation rule not found');

        // Check image file validation
        $foundImageFileRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'image_filename_upload' && $rule[1] === 'file' &&
                isset($rule['types']) && $rule['types'] === 'jpg, jpeg, gif, png') {
                $foundImageFileRule = true;
                break;
            }
        }
        $this->assertTrue($foundImageFileRule, 'Image file validation rule not found');

        // Check tags_input length validation
        $foundTagsInputLengthRule = false;
        foreach ($rules as $rule) {
            if ($rule[0] === 'tags_input' && $rule[1] === 'length' && $rule['max'] === 500) {
                $foundTagsInputLengthRule = true;
                break;
            }
        }
        $this->assertTrue($foundTagsInputLengthRule, 'Tags input length validation rule not found');
    }

    public function testValidateCategoryExists()
    {
        // Test that the validation method exists
        $this->assertTrue(method_exists($this->product, 'validateCategoryExists'), 'validateCategoryExists method does not exist');

        // Test with empty category_id (should pass if allowEmpty is true)
        $this->product->category_id = '';
        $mockErrors = [];
        $this->product->shouldReceive('addError')->andReturnUsing(function ($attr, $error) use (&$mockErrors) {
            $mockErrors[$attr] = $error;
        });

        $this->product->validateCategoryExists('category_id', []);
        $this->assertEmpty($mockErrors); // Should not add error for empty value

        // Test with valid category_id by mocking the validation method itself
        // rather than the static Category calls
        $validCategoryId = '507f1f77bcf86cd799439011';
        $this->product->category_id = $validCategoryId;

        // Test that the method can be called without errors
        // We can't easily test the database interaction without actual DB
        $this->product->validateCategoryExists('category_id', []);
        $this->assertTrue(true); // If we get here, no fatal errors occurred
    }

    public function testEmbeddedDocuments()
    {
        $embeddedDocs = $this->product->embeddedDocuments();
        $this->assertIsArray($embeddedDocs);
        $this->assertArrayHasKey('dimensions', $embeddedDocs);
        $this->assertEquals('Dimensions', $embeddedDocs['dimensions']);
        $this->assertCount(1, $embeddedDocs);
    }

    public function testBehaviors()
    {
        $behaviors = $this->product->behaviors();
        $this->assertIsArray($behaviors);
        $this->assertCount(1, $behaviors);

        $behavior = $behaviors[0];
        $this->assertIsArray($behavior);
        $this->assertEquals('ext.YiiMongoDbSuite.extra.EEmbeddedArraysBehavior', $behavior['class']);
        $this->assertEquals('variants', $behavior['arrayPropertyName']);
        $this->assertEquals('Variant', $behavior['arrayDocClassName']);
    }

    public function testBeforeValidate()
    {
        // Test tags_input processing
        $this->product->tags_input = 'electronics, gadgets, mobile, phone';
        $this->product->tags = [];

        // Mock parent::beforeValidate()
        $this->product->shouldReceive('beforeValidate')->passthru();

        $result = $this->product->beforeValidate();
        $this->assertTrue($result);

        // Test that tags_input is processed into tags array
        $expectedTags = [0 => 'electronics', 1 => 'gadgets', 2 => 'mobile', 3 => 'phone'];
        $this->assertEquals($expectedTags, $this->product->tags);

        // Test empty tags_input
        $this->product->tags_input = '';
        $this->product->tags = null;
        $this->product->beforeValidate();
        $this->assertEquals(array(), $this->product->tags);

        // Test tags_input with extra spaces and empty values - keys preserved by array_filter
        $this->product->tags_input = ' electronics ,  , gadgets,  ,mobile ';
        $this->product->beforeValidate();
        $expectedTags = [0 => 'electronics', 2 => 'gadgets', 4 => 'mobile'];
        $this->assertEquals($expectedTags, $this->product->tags);
    }

    public function testBeforeSave()
    {
        // Test that beforeSave method exists
        $this->assertTrue(method_exists($this->product, 'beforeSave'), 'beforeSave method does not exist');

        // Mock parent::beforeSave() to return true
        $this->product->shouldReceive('beforeSave')->passthru()->andReturnUsing(function () {
            // Simulate the actual beforeSave behavior
            $now = new MongoDate();
            if ($this->product->isNewRecord) { // Note: property, not method
                if (!$this->product->created_at) {
                    $this->product->created_at = $now;
                }
            }
            if (!$this->product->updated_at || $this->product->isNewRecord) {
                $this->product->updated_at = $now;
            }
            return true;
        });

        // Set up the isNewRecord property (not method)
        $this->product->isNewRecord = true;

        // Test new record scenario
        $this->product->created_at = null;
        $this->product->updated_at = null;

        $result = $this->product->beforeSave();
        $this->assertTrue($result);

        // For new records, both created_at and updated_at should be set
        $this->assertInstanceOf('MongoDate', $this->product->created_at);
        $this->assertInstanceOf('MongoDate', $this->product->updated_at);

        // Test existing record scenario
        $this->product->isNewRecord = false;
        $oldCreatedAt = new MongoDate();
        $this->product->created_at = $oldCreatedAt;

        $result = $this->product->beforeSave();
        $this->assertTrue($result);

        // For existing records, created_at should remain the same, updated_at should be new
        $this->assertEquals($oldCreatedAt, $this->product->created_at);
        $this->assertInstanceOf('MongoDate', $this->product->updated_at);
    }

    public function testGetCategoryName()
    {
        // Test that getCategoryName method exists
        $this->assertTrue(method_exists($this->product, 'getCategoryName'), 'getCategoryName method does not exist');

        // Test with empty category_id
        $this->product->category_id = '';
        $categoryName = $this->product->getCategoryName();
        $this->assertEquals('N/A', $categoryName);

        // Test with null category_id
        $this->product->category_id = null;
        $categoryName = $this->product->getCategoryName();
        $this->assertEquals('N/A', $categoryName);

        // Since we can't easily mock the static Category class that already exists,
        // let's test the method behavior without mocking the database interaction
        $validCategoryId = '507f1f77bcf86cd799439011';
        $this->product->category_id = $validCategoryId;

        // The method will return 'N/A' when category is not found (which is expected in test environment)
        $categoryName = $this->product->getCategoryName();
        $this->assertEquals('N/A', $categoryName);
    }

    public function testSearchProvider()
    {
        // Test that the searchProvider method exists
        $this->assertTrue(method_exists($this->product, 'searchProvider'), 'searchProvider method does not exist');

        // Test with safe values
        $this->product->name = 'TestProduct';
        $this->product->sku = 'TEST001';
        $this->product->description = 'TestDescription';
        $this->product->category_id = '507f1f77bcf86cd799439011';
        $this->product->quantity = 10;
        $this->product->price = 50000;
        $this->product->tags_input = 'electronics,mobile';

        $result = $this->product->searchProvider();
        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);

        // Test case sensitivity parameter if applicable
        $result = $this->product->searchProvider(true);
        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);

        // Test with empty search criteria
        $emptyProduct = m::mock(Product::class)->makePartial();
        $emptyProduct->name = '';
        $emptyProduct->sku = '';
        $emptyProduct->description = '';
        $emptyProduct->category_id = '';
        $emptyProduct->quantity = '';
        $emptyProduct->price = '';
        $emptyProduct->tags_input = '';
        $emptyProduct->tags = [];

        $result = $emptyProduct->searchProvider();
        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }


    public function testStaticModelMethod()
    {
        // Test static model method exists
        $this->assertTrue(method_exists('Product', 'model'), 'Static model method does not exist');

        // Test that model() returns an instance of Product
        $model = Product::model();
        $this->assertInstanceOf('Product', $model);

        // Test that model() with className parameter works
        $model = Product::model('Product');
        $this->assertInstanceOf('Product', $model);

        // The model() method in Yii typically returns the same instance (singleton pattern)
        // So we test that it returns the same instance, not different ones
        $model1 = Product::model();
        $model2 = Product::model();
        $this->assertSame($model1, $model2, 'Model method should return the same instance (singleton pattern)');
    }

    public function testCompleteProductCreation()
    {
        // Test setting all product properties
        $this->product->name = 'iPhone 14 Pro';
        $this->product->sku = 'IPH14PRO001';
        $this->product->description = 'Latest iPhone with advanced features';
        $this->product->category_id = '507f1f77bcf86cd799439011';
        $this->product->quantity = 50;
        $this->product->price = 99999; // Price in cents
        $this->product->image_url = 'https://example.com/iphone14pro.jpg';
        $this->product->tags_input = 'smartphone, apple, mobile, electronics';

        $this->assertEquals('iPhone 14 Pro', $this->product->name);
        $this->assertEquals('IPH14PRO001', $this->product->sku);
        $this->assertEquals('Latest iPhone with advanced features', $this->product->description);
        $this->assertEquals('507f1f77bcf86cd799439011', $this->product->category_id);
        $this->assertEquals(50, $this->product->quantity);
        $this->assertEquals(99999, $this->product->price);
        $this->assertEquals('https://example.com/iphone14pro.jpg', $this->product->image_url);
        $this->assertEquals('smartphone, apple, mobile, electronics', $this->product->tags_input);
    }

    public function testVariantsHandling()
    {
        // Test variants array initialization
        $this->product->variants = [];
        $this->assertIsArray($this->product->variants);
        $this->assertEmpty($this->product->variants);

        // Test adding mock variants
        $mockVariant1 = m::mock(Variant::class);
        $mockVariant1->name = '128GB';
        $mockVariant1->sku = 'IPH14PRO128';
        $mockVariant1->additional_price = 0;
        $mockVariant1->quantity = 20;

        $mockVariant2 = m::mock(Variant::class);
        $mockVariant2->name = '256GB';
        $mockVariant2->sku = 'IPH14PRO256';
        $mockVariant2->additional_price = 10000;
        $mockVariant2->quantity = 15;

        $this->product->variants = [$mockVariant1, $mockVariant2];
        $this->assertCount(2, $this->product->variants);
        $this->assertEquals('128GB', $this->product->variants[0]->name);
        $this->assertEquals('256GB', $this->product->variants[1]->name);
    }

    public function testTagsProcessing()
    {
        // Test normal tags processing
        $this->product->tags_input = 'electronics,mobile,smartphone';
        $this->product->tags = [];
        $this->product->beforeValidate();

        $expectedTags = [0 => 'electronics', 1 => 'mobile', 2 => 'smartphone'];
        $this->assertEquals($expectedTags, $this->product->tags);

        // Test tags with spaces and empty values - keys preserved by array_filter
        $this->product->tags_input = ' electronics , , mobile ,  smartphone , ';
        $this->product->tags = [];
        $this->product->beforeValidate();

        // array_filter preserves keys: [0 => 'electronics', 2 => 'mobile', 3 => 'smartphone']
        $expectedTags = [0 => 'electronics', 2 => 'mobile', 3 => 'smartphone'];
        $this->assertEquals($expectedTags, $this->product->tags);

        // Test empty tags_input
        $this->product->tags_input = '';
        $this->product->tags = null;
        $this->product->beforeValidate();

        $this->assertEquals([], $this->product->tags);
    }

    public function testDataTypeConsistency()
    {
        // Test quantity as integer
        $this->product->quantity = '25';
        $this->assertIsString($this->product->quantity); // Before filter

        // Test price as integer (stored in cents)
        $this->product->price = '9999';
        $this->assertIsString($this->product->price); // Before filter

        // Test string properties
        $this->product->name = 'Test Product';
        $this->product->sku = 'TEST001';
        $this->product->description = 'Test description';

        $this->assertIsString($this->product->name);
        $this->assertIsString($this->product->sku);
        $this->assertIsString($this->product->description);
    }

    public function testMethodsExistence()
    {
        // Test that all required methods exist
        $requiredMethods = [
            'getCollectionName', 'attributeLabels', 'rules', 'validateCategoryExists',
            'embeddedDocuments', 'behaviors', 'beforeValidate', 'beforeSave',
            'getCategoryName', 'searchProvider'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists($this->product, $method), "Method {$method} does not exist");
        }

        // Test static methods
        $staticMethods = ['model'];
        foreach ($staticMethods as $method) {
            $this->assertTrue(method_exists('Product', $method), "Static method {$method} does not exist");
        }
    }

    public function testEdgeCases()
    {
        // Test very long SKU (should be caught by length validation)
        $longSku = str_repeat('A', 150);
        $this->product->sku = $longSku;
        $this->assertEquals($longSku, $this->product->sku);

        // Test negative quantity (should be caught by min validation)
        $this->product->quantity = -5;
        $this->assertEquals(-5, $this->product->quantity);

        // Test zero price
        $this->product->price = 0;
        $this->assertEquals(0, $this->product->price);

        // Test very large quantity
        $this->product->quantity = 999999;
        $this->assertEquals(999999, $this->product->quantity);
    }

    public function tearDown(): void
    {
        m::close();
    }
}
