<?php
class MongoAggregator{
  private static $_instance = null;
  private $_connection = null;
  private $_db = null;
  private $_collection = null;
  private $_pipeline = [];
  public function __construct()
  {
    // $this->_connection = new MongoClient("mongodb://localhost:32771");
    // $this->_db = $this->_connection->darwinbox;
  }
  public static function getInstance()
  {
    if(isset(self::$_instance))
      return self::$_instance;
    return self::$_instance = new self;
  }
  public function getDB()
  {
    return $this->_db;
  }
  public function selectCollection($collectionName)
  {
    $this->_collection = $this->_db->selectCollection($collectionName);
    return $this;
  }
  public function setCollection($collection)
  {
    $this->_collection = $collection;
    return $this;
  }
  public function find(array $q)
  {
    return $this->_collection->find($q);
  }
  public function addStage(array $stage)
  {
    $this->_pipeline[] = $stage;
    return $this;
  }
  public function limit($limit)
  {
    $this->_pipeline[] = ['$limit'=>$limit];
    return $this;
  }
  public function skip($skip)
  {
    $this->_pipeline[] = ['$skip'=>$skip];
    return $this;
  }
  public function count()
  {
    $this->_pipeline[] = ['$count'=>"count"];
    return $this;
  }
  public function sort(array $stage)
  {
    $this->_pipeline[] = ['$sort'=>$stage];
    return $this;
  }
  public function project(array $stage)
  {
    //$this->_pipeline = array_merge($this->_pipeline, ['$project'=>$stage]);
    $this->_pipeline[] = ['$project'=>$stage];
    return $this;
  }
  public function group(array $stage)
  {
    $this->_pipeline[] = ['$group'=>$stage];
    return $this;
  }
  public function match(array $stage)
  {
    //$this->_pipeline = array_merge($this->_pipeline, ['$merge'=>$stage]);
    $this->_pipeline[] = ['$match'=>$stage];
    return $this;
  }
  public function aggregate(array $pipeline = [])
  {
    $pipeline = $pipeline ? $pipeline : $this->_pipeline;
    $cursor =  $this->_collection->aggregate(MongoCollectionHelper::fromLegacy($pipeline),[ 'allowDiskUse' => true, 'cursor' => [ 'batchSize' => 1000]]);
    return [
      'ok' => 1.0,
      'result' => MongoCollectionHelper::toLegacy($cursor),
      'waitedMS' => 0,
    ];
  }
}
?>