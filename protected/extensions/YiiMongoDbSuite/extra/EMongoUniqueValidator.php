<?php
/**
 * Enhanced EMongoUniqueValidator for YiiMongoDbSuite
 */
class EMongoUniqueValidator extends CValidator
{
    public $allowEmpty = true;
    public $className;
    public $attributeName;
    public $caseSensitive = true;
    public $criteria = [];

    public function validateAttribute($object, $attribute)
    {
        $value = $object->$attribute;
        if ($this->allowEmpty && ($value === null || $value === '')) {
            return;
        }

        // Determine the class and attribute to check
        $className = $this->className ?: get_class($object);
        $attributeName = $this->attributeName ?: $attribute;

        /** @var EMongoDocument $model */
        $model = $className::model();

        $criteria = new EMongoCriteria;

        // Case-insensitive match
        if (!$this->caseSensitive && is_string($value)) {
            $criteria->$attributeName = new MongoRegex('/^' . preg_quote($value, '/') . '$/i');
        } else {
            $criteria->$attributeName = $value;
        }

        // Add custom criteria if provided
        foreach ($this->criteria as $key => $val) {
            $criteria->$key = $val;
        }

        // Exclude current document (_id) when updating
        if (!$object->isNewRecord && $object->_id) {
            $criteria->_id = array('$ne' => $object->_id);
        }

        $count = $model->count($criteria);

        if ($count > 0) {
            $this->addError(
                $object,
                $attribute,
                $this->message !== null ? $this->message : Yii::t('yii', '{attribute} is already taken.')
            );
        }
    }
}
