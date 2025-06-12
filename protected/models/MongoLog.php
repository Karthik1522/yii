<?php

class MongoLog extends EMongoDocument
{
    public $level;
    public $category;
    public $logtime;
    public $message;

    public function getCollectionName()
    {
        return 'YiiLog'; // your desired collection name
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function rules()
    {
        return array(
            array('level, category, logtime, message', 'safe'),
        );
    }
}
