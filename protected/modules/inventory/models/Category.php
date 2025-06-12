<?php

use MongoDB\BSON\ObjectId;

class Category extends EMongoDocument
{
    public $name;
    public $description;
    public $parent_id;
    public $slug;

    public $created_at;
    public $updated_at;


    public function getCollectionName()
    {
        return 'categories';
    }


    public function rules()
    {
        return array(
            array('name', 'required'),
            array('name', 'length', 'max' => 100),
            array('name', 'ext.YiiMongoDbSuite.extra.EMongoUniqueValidator',
                'className' => 'Category',
                'attributeName' => 'name',
                'caseSensitive' => false, // Usually category names are case-insensitive for uniqueness
                'message' => 'This category name already exists.'
            ),
            array('description', 'safe'),
            array('parent_id', 'match', 'pattern' => '/^[a-f0-9]{24}$/i', 'allowEmpty' => true, 'message' => 'Invalid Parent ID format.'),
            array('parent_id', 'existInParent', 'on' => 'insert, update'), // Custom validator

            array('slug', 'length', 'max' => 120),
            array('slug', 'ext.YiiMongoDbSuite.extra.EMongoUniqueValidator',
                'className' => 'Category',
                'attributeName' => 'slug',
                'allowEmpty' => true, // Slug might be auto-generated or optional
                'message' => 'This slug already exists.'
            ),

            array('created_at, updated_at', 'safe'),
            // The following rule is used by search().
            array('name, description, parent_id, slug', 'safe', 'on' => 'search'),
        );
    }

    public function attributeLabels()
    {
        return array(
            '_id' => 'ID',
            'name' => 'Category Name',
            'description' => 'Description',
            'parent_id' => 'Parent Category',
            'slug' => 'Slug (URL Friendly)',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        );
    }


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function existInParent($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            // Prevent self-parenting
            if (!$this->isNewRecord && (string)$this->_id === $this->$attribute) {
                $this->addError($attribute, 'A category cannot be its own parent.');
                return;
            }
            $parentCategory = self::model()->findByPk(new ObjectId($this->$attribute));
            if ($parentCategory === null) {
                $this->addError($attribute, 'The selected parent category does not exist.');
            }
        }
    }

    protected function callParentBeforeSave()
    {
        return parent::beforeSave();
    }

    public function beforeSave()
    {
        if ($this->callParentBeforeSave()) {
            $now = new MongoDate();
            if ($this->isNewRecord) {
                $this->created_at = $now;
            }
            $this->updated_at = $now;

            // Auto-generate slug if empty and name is present
            if (empty($this->slug) && !empty($this->name)) {
                $this->slug = $this->generateSlug($this->name);
            }
            return true;
        }
        return false;
    }

    protected function generateSlug($text)
    {
        // Normalize a string
        $text = preg_replace('~[^\pL\d]+~u', '-', $text); // Replace non letter or digits by -
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text); // Transliterate
        $text = preg_replace('~[^-\w]+~', '', $text); // Remove unwanted characters
        $text = trim($text, '-'); // Trim
        $text = preg_replace('~-+~', '-', $text); // Remove duplicate -
        $text = strtolower($text); // Lowercase

        if (empty($text)) {
            return 'n-a-' . time(); // Fallback for empty slugs
        }
        return $text;
    }


    // Rename this method from searchProvider to search
    public function searchProvider($caseSensitive = false)
    {
        $criteria = new EMongoCriteria();
        $regexFlags = $caseSensitive ? '' : 'i';


        if ($this->name) {
            $criteria->addCond('name', '==', new MongoDB\BSON\Regex($this->name, $regexFlags));
        }
        if ($this->description) {
            $criteria->addCond('description', '==', new MongoDB\BSON\Regex($this->description, $regexFlags));
        }
        if ($this->slug) {
            $criteria->addCond('slug', '==', new MongoDB\BSON\Regex($this->slug, $regexFlags));
        }


        if ($this->parent_id && preg_match('/^[a-f\d]{24}$/i', $this->parent_id)) {
            $criteria->addCond('parent_id', '==', $this->parent_id);
        }

        return new EMongoDocumentDataProvider($this, array( // EMongoDataProvider, not EMongoDocumentDataProvider
            'criteria' => $criteria,
            'sort' => array(
                'defaultOrder' => array('created_at' => EMongoCriteria::SORT_DESC), // Default sort by created_at descending
                'attributes' => array(
                    'name',
                    'description',
                    'slug',
                    'parent_id',
                    'created_at',
                    'updated_at',
                ),
            ),
            'pagination' => array('pageSize' => 20),
        ));
    }

    public function getParentName()
    {
        if ($this->parent_id) {
            $parent = self::model()->findByPk(new ObjectId($this->parent_id));
            return $parent ? $parent->name : 'N/A (Invalid Parent ID)';
        }
        return 'N/A (Top Level)';
    }

    public static function getCategoryOptions($excludeId = null)
    {
        $criteria = new EMongoCriteria();
        $criteria->sort('name', EMongoCriteria::SORT_ASC);

        if ($excludeId) {
            $criteria->addCond('_id', '!=', new ObjectId($excludeId));
        }

        $categories = self::model()->findAll($criteria);
        $options = array();

        foreach ($categories as $category) {
            $options[(string) $category->_id] = $category->name;
        }

        return $options;
    }
}
