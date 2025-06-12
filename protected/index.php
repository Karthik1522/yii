<?php

// require 'pre_job_entry.php'; 
$autoload = dirname(__FILE__) . '/../vendor/autoload.php';  
require_once $autoload;
$yii = dirname(__FILE__) . '/../framework/yii.php';
$config = dirname(__FILE__) . '/config/console.php';

var_dump($yii);
var_dump($config);
var_dump($autoload);

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 0);

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__).'/..'); // Point to project root
$dotenv->load();

require_once $yii;

$app = Yii::createConsoleApplication($config);

$app->run();
