<?php

use Mockery\MockInterface;
use MongoDB\Collection;
use MongoDB\InsertOneResult;

class MongoMock
{
    private $collection_backups = [];
    private $collection_mocks = [];

    public function close(): void
    {
        foreach ($this->collection_backups as $model_class => $collection) {
            $model_class::model()->setCollection($collection);
        }
        $this->collection_backups = [];
        $this->collection_mocks = [];
    }

    public function mock(string $model_class)
    {
        $mock = $this->ensureMockCollectionExists($model_class);
        $mock->shouldReceive('instantiate')->andReturnSelf();
        $mock->shouldReceive('listIndexes')->andReturn([]);
        $mock->shouldReceive('createIndex');
        $mock->shouldReceive('init');
        $CacheHelper = Mockery::mock('alias:CacheHelper');
        $CacheHelper->shouldReceive('loggedUserTenantIdMatches')->andReturn(null);
        $CacheHelper->shouldReceive('loggedUserIdMatches')->andReturn(null);
        $CacheHelper->shouldReceive('getLoggedUserModelCache')->andReturn(null);
        $CacheHelper->shouldReceive('setLoggedUserModelCache')->andReturn(null);
        return $mock;
    }

    public function mockFindAll(string $model_class, array $mock_value = [], $criteria_to_match = null)
    {
        $criteria_to_match = self::ifArrayThenToCriteria($criteria_to_match);
        $mock = $this->mock($model_class);
        $mock
            ->shouldReceive('find')
            ->withArgs(function ($filter) use ($criteria_to_match) {
                return $this->matchCriteria($criteria_to_match, $filter);
            })
            ->andReturn($mock_value)
            ->byDefault();
        return $mock;
    }

    public function mockFindAllArr(string $model_class, array $mock_value = [], $criteria_to_match = null)
    {
        $criteria_to_match = self::ifArrayThenToCriteria($criteria_to_match);
        $mock = $this->mock($model_class);
        $mock
            ->shouldReceive('find')
            ->withArgs(function ($filter) use ($criteria_to_match) {
                return $this->matchCriteria($criteria_to_match, $filter);
            })
            ->andReturn(...$mock_value)
            ->byDefault();
        return $mock;
    }

    public function mockFind(string $model_class, $mock_value = null, $criteria_to_match = null)
    {
        $criteria_to_match = self::ifArrayThenToCriteria($criteria_to_match);
        $mock = $this->mock($model_class);
        $mock
            ->shouldReceive('findOne')
            ->withArgs(function ($filter) use ($criteria_to_match) {
                return $this->matchCriteria($criteria_to_match, $filter);
            })
            ->andReturn($mock_value)
            ->byDefault();
        return $mock;
    }

    public function mockFindByPk(string $model_class, $mock_value = null, $pk = null, $criteria_to_match = null)
    {
        $criteria_to_match = self::ifArrayThenToCriteria($criteria_to_match);
        $mock = $this->mock($model_class);
        $mock
            ->shouldReceive('findOne')
            ->withArgs(function ($filter) use ($criteria_to_match) {
                return $this->matchCriteria($criteria_to_match, $filter);
            })
            ->andReturn($mock_value)
            ->byDefault();
        return $mock;
    }

    public function mockFindByAttributes(string $model_class, $mock_value = null, $criteria_to_match = null)
    {
        $criteria_to_match = self::ifArrayThenToCriteria($criteria_to_match);
        $mock = $this->mock($model_class);
        $mock
            ->shouldReceive('findOne')
            ->withArgs(function ($filter) use ($criteria_to_match) {
                return $this->matchCriteria($criteria_to_match, $filter);
            })
            ->andReturn($mock_value)
            ->byDefault();
        return $mock;
    }

    public function mockFindsToReturnNull(...$model_classes)
    {
        foreach ($model_classes as $model_class) {
            $this->mockFind($model_class);
        }
    }

    public function mockDistinct(
        string $model_class,
        $mock_value = null,
        $attribute_to_match = null,
        $criteria_to_match = null,
    ) {
        $criteria_to_match = self::ifArrayThenToCriteria($criteria_to_match);
        $mock = $this->mock($model_class);
        $mock
            ->shouldReceive('distinct')
            ->withArgs(function (string $fieldName, $filter) use ($attribute_to_match, $criteria_to_match) {
                if (!empty($attribute_to_match) && $attribute_to_match != $fieldName) {
                    return false;
                }
                return $this->matchCriteria($criteria_to_match, $filter);
            })
            ->andReturn($mock_value)
            ->byDefault();
        $mock->shouldReceive('nonNeoUser')->andReturn($mock_value);
        return $mock;
    }

    public function mockCount(string $model_class, int $mock_value, $criteria_to_match = null)
    {
        $criteria_to_match = self::ifArrayThenToCriteria($criteria_to_match);
        $mock = $this->mock($model_class);
        $mock
            ->shouldReceive('count')
            ->withArgs(function ($filter) use ($criteria_to_match) {
                return $this->matchCriteria($criteria_to_match, $filter);
            })
            ->andReturn($mock_value)
            ->byDefault();
        return $mock;
    }

    public function mockSave(string $model_class, &$save_attributes)
    {
        /**
         * @var InsertOneResult|MockInterface $mock_result
         */
        $mock_result = Mockery::mock(InsertOneResult::class);
        $mock_result->shouldReceive('getInsertedId')->andReturn(1);
        $mock_result->shouldReceive('getMatchedCount')->andReturn(1);
        $mock = $this->mock($model_class);
        $mock
            ->shouldReceive('insertOne')
            ->withArgs(function ($attributes, $flags) use (&$save_attributes) {
                $save_attributes = $attributes;
                return true;
            })
            ->andReturn($mock_result);
        $mock
            ->shouldReceive('replaceOne')
            ->withArgs(function ($id, $attributes, $flags) use (&$save_attributes) {
                $save_attributes = $attributes;
                return true;
            })
            ->andReturn($mock_result);
        $mock->shouldReceive('save');
        return $mock;
    }

    public function mockSaveAttributes(string $model_class, &$save_attributes)
    {
        $mock = $this->mock($model_class);
        $mock->shouldReceive('updateOne')->withArgs(function ($id, $set, $flags) use (&$save_attributes) {
            $save_attributes = iterator_to_array($set[array_key_first($set)]);
            return true;
        });
        $mock->shouldReceive('save');
        return $mock;
    }

    public function mockAggregate(string $model_class, $mock_value = null)
    {
        $mock = $this->mock($model_class);
        $mock->shouldReceive('aggregate')->andReturn($mock_value);
        return $mock;
    }

    public function mockUpdate(string $model_class, &$save_attributes)
    {
        $mock = $this->mock($model_class);
        $mock->shouldReceive('updateOne')->withArgs(function ($id, $set, $flags) use (&$save_attributes) {
            $save_attributes = iterator_to_array($set[array_key_first($set)]);
            return true;
        });
        $mock->shouldReceive('replaceOne')->withArgs(function ($id, $attributes, $flags) use (&$save_attributes) {
            $save_attributes = $attributes;
            return true;
        });
        $mock->shouldReceive('update');
        $mock->shouldReceive('updateMany');
        return $mock;
    }

    public function mockFindOneAndUpdate(string $model_class, $mock_value = null)
    {
        $mock = $this->mock($model_class);
        $mock->shouldReceive('findOneAndUpdate')->andReturn($mock_value);
        return $mock;
    }

    public static function getEmailRegex($email)
    {
        $email = preg_quote($email);
        return new MongoRegex("/^$email$/i");
    }

    public function mockFindArr(string $model_class, $mock_value = [], $criteria_to_match = null)
    {
        $criteria_to_match = self::ifArrayThenToCriteria($criteria_to_match);
        $mock = $this->mock($model_class);
        $mock
            ->shouldReceive('findOne')
            ->withArgs(function ($filter) use ($criteria_to_match) {
                return $this->matchCriteria($criteria_to_match, $filter);
            })
            // ->andReturn(...$mock_value)
            ->andReturnValues($mock_value)
            ->byDefault();
        return $mock;
    }

    private function ifArrayThenToCriteria($criteria_to_match)
    {
        if (is_array($criteria_to_match)) {
            $criteria = new EMongoCriteria();
            foreach ($criteria_to_match as $key => $value) {
                $criteria->addCond($key, '==', $value);
            }
            return $criteria;
        }

        return $criteria_to_match;
    }

    private function matchCriteria($criteria_to_match, $filter): bool
    {
        if ($criteria_to_match == null) {
            return true;
        }

        $criteria_to_match = MongoCollectionHelper::fromLegacy($criteria_to_match->getConditions());
        foreach ($criteria_to_match as $key => $value) {
            if (empty($filter[$key]) || $filter[$key] != $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Collection|MockInterface
     */
    private function ensureMockCollectionExists(string $model_class)
    {
        if (empty($this->collection_backups[$model_class])) {
            $this->collection_backups[$model_class] = $model_class::model()->getCollection();
            $this->collection_mocks[$model_class] = Mockery::mock(Collection::class);
            $model_class::model()->setCollection($this->collection_mocks[$model_class]);
        }

        return $this->collection_mocks[$model_class];
    }

    public function mockDelete(string $model_class)
    {
        $mock = $this->mock($model_class);
        $mock->shouldReceive('deleteOne')->andReturn(true);
        return $mock;
    }

    public function mockUpdateAll(string $model_class, $mock_value = null)
    {
        $mock = $this->mock($model_class);
        $mock->shouldReceive('updateMany')->andReturn($mock_value);
        return $mock;
    }

    public function mockSaveAttributesException(string $model_class)
    {
        $mock = $this->mock($model_class);
        $mock->shouldReceive('updateOne')->andThrow(new Exception('test'));
        $mock->shouldReceive('saveAttributes')->andThrow(new Exception('test'));

        return $mock;
    }
}
