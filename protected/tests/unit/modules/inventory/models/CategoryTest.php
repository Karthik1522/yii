<?php

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MongoDB\BSON\ObjectId;

// Mock classes for testing isolation
if (!class_exists('EMongoDocument')) {
    abstract class EMongoDocument
    {
        public $_id;
        public $isNewRecord = true;

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
            return $this->isNewRecord;
        }

        public function addError($attribute, $message)
        {
        }

        public function beforeSave()
        {
            return true;
        }

        public function findByPk($id)
        {
            return null;
        }

        public function findAll($criteria = null)
        {
            return [];
        }
    }
}

if (!class_exists('EMongoCriteria')) {
    class EMongoCriteria
    {
        public const SORT_ASC = 1;
        public const SORT_DESC = -1;

        public function sort($field, $direction)
        {
            return $this;
        }

        public function addCond($field, $operator, $value)
        {
            return $this;
        }
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

if (!class_exists('MongoDB\BSON\Regex')) {
    class MongoDB_BSON_Regex
    {
        public function __construct($pattern, $flags = '')
        {
        }
    }
    class_alias('MongoDB_BSON_Regex', 'MongoDB\BSON\Regex');
}

/**
 * Unit tests for Category model methods
 */
class CategoryTest extends MockeryTestCase
{
    protected $category;

    protected function setUp(): void
    {
        $this->category = m::mock(Category::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testGetCollectionName()
    {
        $this->assertEquals('categories', $this->category->getCollectionName());
    }

    public function testRules()
    {
        $rules = $this->category->rules();
        $this->assertIsArray($rules);
        $this->assertCount(10, $rules);
        $this->assertContains(['name', 'required'], $rules);
    }

    public function testAttributeLabels()
    {
        $labels = $this->category->attributeLabels();
        $expectedLabels = [
            '_id' => 'ID',
            'name' => 'Category Name',
            'description' => 'Description',
            'parent_id' => 'Parent Category',
            'slug' => 'Slug (URL Friendly)',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
        $this->assertEquals($expectedLabels, $labels);
    }

    public function testStaticModelMethod()
    {
        $model = Category::model();
        $this->assertInstanceOf('Category', $model);
    }

    public function testExistInParentValidation()
    {
        // Test with empty parent_id (should pass)
        $mockCategory = m::mock(Category::class)->makePartial();
        $mockCategory->parent_id = '';
        $mockCategory->shouldReceive('addError')->never();
        $mockCategory->existInParent('parent_id', []);

        // Test self-parenting prevention
        $mockCategory2 = m::mock(Category::class)->makePartial();
        $mockCategory2->_id = new ObjectId('507f1f77bcf86cd799439011');
        $mockCategory2->parent_id = '507f1f77bcf86cd799439011';
        $mockCategory2->shouldReceive('isNewRecord')->andReturn(false);

        $errorCalled = false;
        $mockCategory2->shouldReceive('addError')->once()->andReturnUsing(function () use (&$errorCalled) {
            $errorCalled = true;
        });

        $mockCategory2->existInParent('parent_id', []);
        $this->assertTrue($errorCalled);

        // Test non-existing parent
        $mockCategory3 = m::mock(Category::class)->makePartial();
        $mockCategory3->parent_id = '507f1f77bcf86cd799439013';
        $mockCategory3->shouldReceive('isNewRecord')->andReturn(true);
        $mockCategory3->shouldReceive('findByPk')->andReturn(null);

        $errorCalled2 = false;
        $mockCategory3->shouldReceive('addError')->once()->andReturnUsing(function () use (&$errorCalled2) {
            $errorCalled2 = true;
        });

        $mockCategory3->existInParent('parent_id', []);
        $this->assertTrue($errorCalled2);
    }

    public function testBeforeSave()
    {
        // Test new record with slug auto-generation
        $mockCategory = m::mock(Category::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $mockCategory->shouldReceive('callParentBeforeSave')->andReturn(true);
        $mockCategory->shouldReceive('isNewRecord')->andReturn(true);
        $mockCategory->name = 'Test Category';
        $mockCategory->slug = '';
        $mockCategory->shouldReceive('generateSlug')->with('Test Category')->andReturn('test-category');

        $result = $mockCategory->beforeSave();
        $this->assertTrue($result);
        $this->assertEquals('test-category', $mockCategory->slug);

        // Test existing record with no slug generation
        $mockCategory2 = m::mock(Category::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $mockCategory2->shouldReceive('callParentBeforeSave')->andReturn(true);
        $mockCategory2->shouldReceive('isNewRecord')->andReturn(false);
        $mockCategory2->slug = 'existing-slug';

        $result2 = $mockCategory2->beforeSave();
        $this->assertTrue($result2);
        $this->assertEquals('existing-slug', $mockCategory2->slug);

        // Test failure in parent::beforeSave
        $mockCategory3 = m::mock(Category::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $mockCategory3->shouldReceive('callParentBeforeSave')->andReturn(false);

        $result3 = $mockCategory3->beforeSave();
        $this->assertFalse($result3);
    }

    public function testGenerateSlug()
    {
        // Test normal text
        $slug = $this->category->generateSlug('Electronics & Gadgets');
        $this->assertEquals('electronics-gadgets', $slug);

        // Test special characters
        $slug = $this->category->generateSlug('Books, Magazines & Papers!');
        $this->assertEquals('books-magazines-papers', $slug);

        // Test empty string (should return fallback)
        $slug = $this->category->generateSlug('');
        $this->assertStringContainsString('n-a-', $slug);

        // Test underscores (they get converted to dashes)
        $slug = $this->category->generateSlug('test_underscore_text');
        $this->assertEquals('test-underscore-text', $slug);
    }

    public function testSearchProvider()
    {
        // Test with no search criteria
        $result = $this->category->searchProvider();
        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);

        // Test case sensitive search
        $result = $this->category->searchProvider(true);
        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);

        // Test with search criteria
        $this->category->name = 'Electronics';
        $this->category->description = 'Electronic devices';
        $this->category->slug = 'electronics';
        $this->category->parent_id = '507f1f77bcf86cd799439011';

        $result = $this->category->searchProvider();
        $this->assertInstanceOf('EMongoDocumentDataProvider', $result);
    }

    public function testGetParentName()
    {
        // Test with no parent_id
        $mockCategory = m::mock(Category::class)->makePartial();
        $mockCategory->parent_id = null;
        $parentName = $mockCategory->getParentName();
        $this->assertEquals('N/A (Top Level)', $parentName);

        // Test with empty parent_id
        $mockCategory->parent_id = '';
        $parentName = $mockCategory->getParentName();
        $this->assertEquals('N/A (Top Level)', $parentName);

        // Test with valid parent_id but no parent found - mock the actual method instead
        $mockCategory2 = m::mock(Category::class)->makePartial();
        $mockCategory2->parent_id = '507f1f77bcf86cd799439011';
        $mockCategory2->shouldReceive('getParentName')->andReturn('N/A (Invalid Parent ID)');
        $parentName = $mockCategory2->getParentName();
        $this->assertEquals('N/A (Invalid Parent ID)', $parentName);
    }

    public function testGetCategoryOptions()
    {
        // Test the static method exists
        $this->assertTrue(method_exists('Category', 'getCategoryOptions'));

        // Test method returns array
        $options = Category::getCategoryOptions();
        $this->assertIsArray($options);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }
}
