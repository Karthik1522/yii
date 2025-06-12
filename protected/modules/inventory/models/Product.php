<?php

use MongoDB\BSON\ObjectId;

class Product extends EMongoDocument
{
    public $name;
    public $sku;
    public $description;
    public $category_id;
    public $quantity = 0;
    public $price = 0.00;
    public $image_url;
    public $image_filename_upload;
    public $dimensions;
    public $variants = array();
    public $tags = array();
    public $tags_input;
    public $created_at;
    public $updated_at;


    public function getCollectionName()
    {
        return "products";
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function attributeLabels()
    {
        return array(
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
            'variants' => "Variants",
            'tags' => 'Tags',
            'tags_input' => 'Tags (comma-separated)',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        );
    }

    public function rules()
    {
        return array(
            array('name, sku, quantity, price, quantity', 'required'),
            array('name', 'length', 'max' => 255),
            array('sku', 'length', 'max' => 100),
            array('sku', 'ext.YiiMongoDbSuite.extra.EMongoUniqueValidator', 'className' => 'Product', 'attributeName' => 'sku', 'caseSensitive' => false, 'message' => 'This SKU already exists.'),
            array('description', 'safe'),
            array('quantity', 'numerical', 'integerOnly' => true, 'min' => 0),
            array('quantity', 'filter', 'filter' => 'intval'),
            array('price', 'filter', 'filter' => 'intval'),
            array('price', 'numerical', 'integerOnly' => true, 'min' => 0),
            array('category_id', 'match', 'pattern' => '/^[a-f0-9]{24}$/i', 'message' => 'Invalid ID format.', 'allowEmpty' => true), // allowEmpty if not required
            array('category_id', 'validateCategoryExists', 'allowEmpty' => true),

            array('image_url', 'length'),
            array('image_filename_upload', 'file', 'types' => 'jpg, jpeg, gif, png', 'allowEmpty' => true, 'on' => 'insert, update'),

            array('dimensions, tags, variants', 'safe'),
            array('tags_input', 'length', 'max' => 500),

            array('created_at, updated_at', 'safe'),
            array('name, sku, category_id, quantity, price', 'safe', 'on' => 'search'),
        );
    }

    public function validateCategoryExists($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            try {
                $category = Category::model()->findByPk(new ObjectId($this->$attribute));
                if ($category === null) {
                    $this->addError($attribute, 'The selected category does not exist.');
                }
            } catch (Exception $e) {
                $this->addError($attribute, 'Invalid Category ID format.');
            }
        }
    }


    public function embeddedDocuments()
    {
        return array(
            'dimensions' => 'Dimensions',
        );
    }

    public function behaviors()
    {
        return array(
            array(
                'class' => 'ext.YiiMongoDbSuite.extra.EEmbeddedArraysBehavior',
                'arrayPropertyName' => 'variants',
                'arrayDocClassName' => 'Variant'
            ),
        );
    }


    protected function beforeValidate()
    {
        $valid = parent::beforeValidate();

        if (!empty($this->tags_input) && is_string($this->tags_input)) {
            $this->tags = array_map('trim', explode(',', $this->tags_input));
            $this->tags = array_filter($this->tags, function ($tag) {
                return !empty($tag);
            });
        } elseif (empty($this->tags_input) && !is_array($this->tags)) {
            $this->tags = array();
        }

        if (is_array($this->variants)) {
            foreach ($this->variants as $index => $variant) {
                if (!$variant->validate()) {
                    foreach ($variant->getErrors() as $attr => $errors) {
                        foreach ($errors as $error) {
                            $this->addError('variants_' . $index . '_' . $attr, $error);
                        }
                    }
                    return false;
                }
            }
        }

        if ($this->dimensions && !$this->dimensions->validate()) {
            foreach ($this->dimensions->getErrors() as $attr => $errors) {
                foreach ($errors as $error) {
                    $this->addError('dimensions_' . $attr, $error);
                }
            }
            return false;
        }

        return $valid;
    }



    public function beforeSave()
    {
        if (parent::beforeSave()) {
            $now = new MongoDate();
            if ($this->isNewRecord) {
                if (!$this->created_at) {
                    $this->created_at = $now;
                }
            }
            if (!$this->updated_at || $this->isNewRecord) {
                $this->updated_at = $now;
            }
            return true;
        }
        return false;
    }

    public function getCategoryName()
    {
        if (!empty($this->category_id)) {
            $category = Category::model()->findByPk(new ObjectId($this->category_id));
            if ($category !== null) {
                return $category->name;
            }
        }

        return "N/A";
    }


    public function searchProvider($caseSensitive = false) // Renamed from search to avoid conflict with CModel::search
    {
        $criteria = new EMongoCriteria();

        $regexFlags = $caseSensitive ? '' : 'i';

        if ($this->name) {
            $criteria->addCond('name', '==', new MongoRegex("/{$this->name}/{$regexFlags}"));
        }

        if ($this->sku) {
            $criteria->addCond('sku', '==', new MongoRegex("/{$this->sku}/{$regexFlags}"));
        }

        if ($this->description) {
            $criteria->addCond('description', '==', new MongoRegex("/{$this->description}/{$regexFlags}"));

        }


        if ($this->category_id && preg_match('/^[a-f0-9]{24}$/i', $this->category_id)) {
            $criteria->addCond('category_id', '==', $this->category_id);
        }

        if ($this->quantity !== '' && $this->quantity !== null) {
            $criteria->addCond('quantity', '==', (int)$this->quantity);
        }
        if ($this->price !== '' && $this->price !== null) {
            $criteria->addCond('price', '==', (float)$this->price);
        }

        if (!empty($this->tags_input)) { // Search based on tags_input for convenience
            $tagsToSearch = array_map('trim', explode(',', $this->tags_input));
            $tagsToSearch = array_filter($tagsToSearch);
            if (!empty($tagsToSearch)) {
                $criteria->addCond('tags', 'all', $tagsToSearch);
            }
        } elseif (is_array($this->tags) && !empty($this->tags)) { // Fallback to tags array if tags_input is empty
            $criteria->addCond('tags', 'all', $this->tags);
        }

        return new EMongoDocumentDataProvider($this, array(
            'criteria' => $criteria,
            'sort' => array(
                'defaultOrder' => array('created_at' => EMongoCriteria::SORT_DESC),
                'attributes' => array('name', 'sku', 'quantity', 'price', 'created_at'),
            ),
            'pagination' => array('pageSize' => 10),
        ));
    }
}
