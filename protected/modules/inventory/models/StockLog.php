<?php

use MongoDB\BSON\ObjectId;

class StockLog extends EMongoDocument
{
    public $product_id;
    public $type;
    public $quantity_change;
    public $quantity_after_change;
    public $reason;
    public $user_id;
    public $updated_at;

    public const TYPE_RECEIVED   = 'received';
    public const TYPE_SOLD       = 'sold';
    public const TYPE_ADJUSTED   = 'adjusted';
    public const TYPE_DAMAGED    = 'damaged';
    public const TYPE_INITIAL    = 'initial';

    public function getCollectionName()
    {
        return "stock_logs";
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function rules()
    {
        return [
            array('product_id, type, quantity_change, quantity_after_change', 'required'),
            array('product_id', 'validateProductID'),
            array('user_id', 'validateUserID', 'allowEmpty' => true),
            array('quantity_change, quantity_after_change', 'numerical', 'integerOnly' => true),
            array('quantity_change, quantity_after_change', 'filter', 'filter' => 'intval'),
            array('type', 'in', 'range' => self::getAllowedTypes()),

            array('reason', 'length', 'max' => 255),
            array('updated_at', 'safe'),
        ];
    }

    public function validateProductID($attribute, $params)
    {
        if (empty($this->$attribute)) {
            $this->addError($attribute, 'Product ID is required.');
            return false;
        }

        try {
            // For testing purposes, accept valid ObjectId format strings
            if (preg_match('/^[0-9a-f]{24}$/i', $this->$attribute)) {
                $productId = new \MongoDB\BSON\ObjectId($this->$attribute);
                $product = Product::model()->findByPk($productId);
                if ($product === null) {
                    $this->addError($attribute, 'Product not found.');
                    return false;
                }
                return true; // Product found and valid
            } else {
                throw new \Exception('Invalid format');
            }
        } catch (\Exception $e) {
            $this->addError($attribute, 'Invalid Product ID format.');
            return false;
        }
    }


    public function validateUserID($attribute, $params)
    {
        // Fix the bug: check $this->$attribute instead of $attribute
        if (!empty($this->$attribute)) {
            $user = User::model()->findByAttributes(['username' => $this->$attribute]);

            if ($user !== null) {
                return true;
            } else {
                $this->addError($attribute, 'User not found.');
                return false;
            }
        }

        return true;
    }

    public function attributeLabels()
    {
        return array(
            '_id' => 'Log ID',
            'product_id' => 'Product',
            'type' => 'Log Type',
            'quantity_change' => 'Total Quantity Change (+/-)',
            'quantity_after_change' => 'New Total Product Stock',
            'reason' => 'Reason / Reference',
            'user_id' => 'User',
            'updated_at' => 'Date',
        );
    }

    public static function getAllowedTypes()
    {
        return array(
            self::TYPE_RECEIVED, self::TYPE_SOLD, self::TYPE_ADJUSTED,
            self::TYPE_DAMAGED, self::TYPE_INITIAL,
        );
    }

    public static function getTypeOptions()
    {
        return array(
            self::TYPE_RECEIVED => 'Stock Received',
            self::TYPE_SOLD => 'Stock Sold',
            self::TYPE_ADJUSTED => 'Stock Adjusted',
            self::TYPE_DAMAGED => 'Stock Damaged',
            self::TYPE_INITIAL => 'Initial Stock Set',
        );
    }
    public static function getTypeName($typeKey)
    {
        $options = self::getTypeOptions();
        return isset($options[$typeKey]) ? $options[$typeKey] : $typeKey;
    }

    public static function add(
        $productId,
        $type,
        $quantityChange,
        $newTotalProductStockLevel,
        $reason = null,
        $userId = null
    ) {
        $log = new StockLog();
        $log->product_id = $productId;
        $log->type = $type;
        $log->quantity_change = (int)$quantityChange;
        $log->quantity_after_change = (int)$newTotalProductStockLevel;
        $log->reason = $reason;
        $log->updated_at = new MongoDate();

        if ($userId !== null) {
            $log->user_id = $userId;
        }

        // Validate the log before saving
        if (!$log->validate()) {
            Yii::log("StockLog validation failed: " . CVarDumper::dumpAsString($log->getErrors()), CLogger::LEVEL_ERROR);
            return false;
        }

        if (!$log->save()) {
            Yii::log("StockLog save failed: " . CVarDumper::dumpAsString($log->getErrors()), CLogger::LEVEL_ERROR);
            return false;
        }
        return true;
    }

    // public function relations()
    // {
    //     return array(
    //         'product' => array(self::BELONGS_TO, 'Product', 'product_id'),
    //         'user' => array(self::BELONGS_TO, 'User', 'user_id'),
    //     );
    // }


    public function searchProvider($caseSensitive = false)
    {
        $criteria = new EMongoCriteria();

        if (!empty($this->_id)) {
            $criteria->addCond('_id', '==', (string)$this->_id);
        }

        if (!empty($this->product_id)) {
            $criteria->addCond('product_id', '==', (string)$this->product_id);
        }

        if (!empty($this->type)) {
            $criteria->addCond('type', '==', $this->type);
        }

        if (!empty($this->reason)) {
            $regexFlags = $caseSensitive ? '' : 'i';
            $criteria->addCond('reason', '==', new MongoRegex("/{$this->reason}/{$regexFlags}"));
        }

        if (!empty($this->quantity_change)) {
            $criteria->addCond('quantity_change', '==', (int)$this->quantity_change);
        }
        if (!empty($this->quantity_after_change)) {
            $criteria->addCond('quantity_after_change', '==', (int)$this->quantity_after_change);
        }

        if (!empty($this->user_id)) {
            $regexFlags = $caseSensitive ? '' : 'i';
            $criteria->addCond('user_id', '==', new MongoRegex("/{$this->user_id}/{$regexFlags}"));
        }

        if (!empty($this->updated_at) && ($timestamp = strtotime($this->updated_at))) {
            $startOfDay = new MongoDate(strtotime(date('Y-m-d 00:00:00', $timestamp)));
            $endOfDay = new MongoDate(strtotime(date('Y-m-d 23:59:59', $timestamp)));

            $criteria->addCond('updated_at', '>=', $startOfDay);
            $criteria->addCond('updated_at', '<=', $endOfDay);
        }

        return new EMongoDocumentDataProvider($this, [
            'criteria' => $criteria,
            'sort' => [
                'defaultOrder' => ['updated_at' => EMongoCriteria::SORT_DESC],
                'attributes' => [
                    '_id', 'product_id', 'type', 'reason', 'user_id', 'updated_at',
                    'quantity_change', 'quantity_after_change'
                ],
            ],
            'pagination' => ['pageSize' => 20],
        ]);
    }

}
