<?php
 
// change the following paths if necessary
$yiit=dirname(__FILE__).'/../../framework/yiit.php';
$config=dirname(__FILE__).'/../config/test.php';
 
require_once($yiit);
// require_once __DIR__ . '/../../vendor/autoload.php';
// require_once(dirname(__FILE__).'/WebTestCase.php');
 
 
defined('YII_TEST') or define('YII_TEST', true);
 
Yii::createWebApplication($config);
 