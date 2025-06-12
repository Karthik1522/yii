<?php
use MongoDB\Collection;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON;
use MongoDB\Model;
class MongoCollectionHelper {
    public $sec;
    public $usec;
    public function save(&$a, array $options = [])
    {
        $id = $this->ensureDocumentHasMongoId($a);

        $document = (array) $a;

        $options['upsert'] = true;

        try {
            /** @var \MongoDB\UpdateResult $result */
            $result = $this->collection->replaceOne(
                ['_id' => $id],
                $document,
                $this->convertWriteConcernOptions($options)
            );

            if (! $result->isAcknowledged()) {
                return true;
            }

            $resultArray = [
                'ok' => 1.0,
                'nModified' => $result->getModifiedCount(),
                'n' => $result->getUpsertedCount() + $result->getModifiedCount(),
                'err' => null,
                'errmsg' => null,
                'updatedExisting' => $result->getUpsertedCount() == 0 && $result->getModifiedCount() > 0,
            ];
            if ($result->getUpsertedId() !== null) {
                $resultArray['upserted'] = $result->getUpsertedId();
            }

            return $resultArray;
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e);
        }
    }
    public static function convertWriteConcernOptions(array $options)
    {
        if (isset($options['safe'])) {
            $options['w'] = ($options['safe']) ? 1 : 0;
        }
        unset($options['safe']);
        unset($options['w']);

        return $options;
    }
    private function getWriteConcern()
    {
        if ($this->writeConcern === null) {
            $this->writeConcern = new \MongoDB\Driver\WriteConcern(1);
        }

        return [
            'w' => $this->writeConcern->getW(),
            'wtimeout' => $this->writeConcern->getWtimeout(),
        ];
    }
    private function createWriteConcernFromParameters($wstring, $wtimeout)
    {
        // Convert legacy write concern
        if (is_bool($wstring)) {
            $wstring = (int) $wstring;
        }

        if (! is_string($wstring) && ! is_int($wstring)) {
            trigger_error("w for WriteConcern must be a string or integer", E_USER_WARNING);
            return false;
        }

        // Ensure wtimeout is not < 0
        return new \MongoDB\Driver\WriteConcern($wstring, max($wtimeout, 0));
    }

    public static function toLegacy($value)
    {
        switch (true) {
            case $value instanceof BSON\Type:
                return self::convertBSONObjectToLegacy($value);
            case is_array($value):
            case is_object($value):
                $result = [];

                foreach ($value as $key => $item) {    
                        if($item instanceof MongoDate)
                            $result[$key] = $item;                
                        else
                            $result[$key] = self::toLegacy($item);
                }
                
                return $result;
            default:
                return $value;
        }
    }

    public static function fromLegacy($value)
    {
        switch (true) {
            case $value instanceof TypeInterface:
                return $value->toBSONType();
            case $value instanceof BSON\Type:
                return $value;
            case $value instanceof \DateTimeInterface:
                return self::fromLegacy((array) $value);
            case is_array($value):
            case is_object($value):
                $result = [];

                foreach ($value as $key => $item) {
                    $result[$key] = self::fromLegacy($item);
                }

                return self::ensureCorrectType($result, is_object($value));
            default:
                return $value;
        }
    }

    private static function ensureCorrectType(array $array, $wasObject = false)
    {
        if ($wasObject || ! static::isNumericArray($array)) {
            return new Model\BSONDocument($array);
        }

        return $array;
    }

    private static function convertBSONObjectToLegacy(BSON\Type $value)
    {   
        /* After upgrading to mongo5.0 and removing "alcaeus/mongo-php-adapter": "^1.1" 
            some of the classes are also replaced with $value 
            previous code:
                   switch (true) {
            case $value instanceof BSON\ObjectID:
                return new \MongoId($value);
            case $value instanceof BSON\Binary:
                return new \MongoBinData($value);
            case $value instanceof BSON\Javascript:
                return new \MongoCode($value);
            case $value instanceof BSON\MaxKey:
                return new \MongoMaxKey();
            case $value instanceof BSON\MinKey:
                return new \MongoMinKey();
            case $value instanceof BSON\Regex:
                return new \MongoRegex($value);
            case $value instanceof BSON\Timestamp:
                return new \MongoTimestamp($value);
            case $value instanceof BSON\UTCDatetime:
                return new \MongoDate($value);
            case $value instanceof Model\BSONDocument:
            case $value instanceof Model\BSONArray:
                return array_map(
                    ['self', 'toLegacy'],
                    $value->getArrayCopy()
                );     
                                                                
        */
        switch (true) {
            case $value instanceof BSON\ObjectID:
            case $value instanceof BSON\Binary:
            case $value instanceof BSON\Javascript:
            case $value instanceof BSON\MaxKey:
            case $value instanceof BSON\MinKey:
            case $value instanceof BSON\Regex:
                return $value;
            case $value instanceof BSON\Timestamp:
                return new \MongoTimestamp($value);
            case $value instanceof BSON\UTCDatetime:
                return new \MongoDate($value);
            case $value instanceof Model\BSONDocument:
                $bsonArray = (array) $value;
                if(array_key_exists('sec',$bsonArray))
                    return new \MongoDate($value->sec);
            case $value instanceof Model\BSONArray:
                return array_map(
                    ['self', 'toLegacy'],
                    $value->getArrayCopy()
                );
            default:
                return $value;
        }
    }
    public static function ensureDocumentHasMongoId(&$document)
    {
        if (is_array($document) || $document instanceof ArrayObject) {
            if (! isset($document['_id'])) {
                $document['_id'] = new \MongoId();
            }

            self::checkKeys((array) $document);

            return $document['_id'];
        } elseif (is_object($document)) {
            $reflectionObject = new \ReflectionObject($document);
            foreach ($reflectionObject->getProperties() as $property) {
                if (! $property->isPublic()) {
                    throw new \MongoException('zero-length keys are not allowed, did you use $ with double quotes?');
                }
            }

            if (! isset($document->_id)) {
                $document->_id = new \MongoId();
            }

            self::checkKeys((array) $document);

            return $document->_id;
        }

        return null;
    }

    private static function checkKeys(array $array)
    {
        foreach ($array as $key => $value) {
            if (empty($key) && $key !== 0 && $key !== '0') {
                throw new \MongoException('zero-length keys are not allowed, did you use $ with double quotes?');
            }

            if (is_object($value) || is_array($value)) {
                self::checkKeys((array) $value);
            }
        }
    }
    public static function isNumericArray(array $array)
    {
        if ($array === []) {
            return true;
        }

        $keys = array_keys($array);
        // array_keys gives us a clean numeric array with keys, so we expect an
        // array like [0 => 0, 1 => 1, 2 => 2, ..., n => n]
        return array_values($keys) === array_keys($keys);
    }

}