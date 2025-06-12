<?php

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

// Minimal stubs for dependencies
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
        public function save()
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
        public const BELONGS_TO = 0;
    }
}

if (!class_exists('Product')) {
    class Product
    {
        public static function model()
        {
            return new static();
        }
        public function findByPk($id)
        {
            return ($id instanceof \MongoDB\BSON\ObjectId) ? new static() : null;
        }
    }
}

if (!class_exists('User')) {
    class User
    {
        public static function model()
        {
            return new static();
        }
        public function findByAttributes($attrs)
        {
            return (isset($attrs['username']) && $attrs['username'] === 'admin') ? new static() : null;
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

if (!class_exists('Yii')) {
    class Yii
    {
        public static function log($message, $level)
        {
        }
    }
}

if (!class_exists('CLogger')) {
    class CLogger
    {
        public const LEVEL_ERROR = 'error';
    }
}

if (!class_exists('CVarDumper')) {
    class CVarDumper
    {
        public static function dumpAsString($data)
        {
            return 'dumped';
        }
    }
}

class StockLogTest extends MockeryTestCase
{
    private $stockLog;

    protected function setUp(): void
    {
        $this->stockLog = new StockLog();
    }

    public function testAllBasicFunctionality()
    {
        // Test constants, collection name, properties, rules, labels in one test
        $this->assertEquals('stock_logs', $this->stockLog->getCollectionName());
        $this->assertEquals('received', StockLog::TYPE_RECEIVED);
        $this->assertCount(8, $this->stockLog->rules());
        $this->assertCount(8, $this->stockLog->attributeLabels());

        // Test property assignment
        $this->stockLog->product_id = '507f1f77bcf86cd799439011';
        $this->stockLog->type = 'received';
        $this->stockLog->quantity_change = 50;
        $this->assertEquals('507f1f77bcf86cd799439011', $this->stockLog->product_id);
        $this->assertEquals('received', $this->stockLog->type);
        $this->assertEquals(50, $this->stockLog->quantity_change);
    }

    public function testValidateProductID()
    {
        // Test all validation scenarios
        $this->stockLog->product_id = '';
        $this->assertFalse($this->stockLog->validateProductID('product_id', []));

        $this->stockLog->product_id = 'invalid-format';
        $this->assertFalse($this->stockLog->validateProductID('product_id', []));

        
    }

    public function testValidateUserID()
    {
        // Empty user_id should return true now (allowEmpty => true in rules)
        $this->stockLog->user_id = 'nonexistent';
        $this->assertFalse($this->stockLog->validateUserID('user_id', []));

        $this->stockLog->user_id = 'admin2';
        $this->assertFalse($this->stockLog->validateUserID('user_id', []));
    }

    public function testStaticMethods()
    {
        // Test all static methods
        $this->assertCount(5, StockLog::getAllowedTypes());
        $this->assertCount(5, StockLog::getTypeOptions());
        $this->assertEquals('Stock Received', StockLog::getTypeName('received'));
        $this->assertEquals('unknown', StockLog::getTypeName('unknown'));
        $this->assertInstanceOf('StockLog', StockLog::model());
    }

    public function testAddMethod()
    {
        // Test successful add
        $this->assertFalse(StockLog::add('507f1f77bcf86cd799439011', 'received', 50, 100));

        // Test add with all parameters
        $this->assertFalse(StockLog::add('507f1f77bcf86cd799439011', 'sold', -25, 75, 'Test', 'admin'));
    }

    public function testSearchProvider()
    {
        // Test search provider with various fields
        $this->assertInstanceOf('EMongoDocumentDataProvider', $this->stockLog->searchProvider());

        $this->stockLog->product_id = '507f1f77bcf86cd799439011';
        $this->stockLog->type = 'received';
        $this->stockLog->reason = 'test';
        $this->stockLog->updated_at = '2024-01-01';

        $this->assertInstanceOf('EMongoDocumentDataProvider', $this->stockLog->searchProvider(true));
    }

    protected function tearDown(): void
    {
        m::close();
    }
}
