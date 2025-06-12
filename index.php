<?php 
// error_reporting(E_ALL);
// ini_set('display_errors',1);
// ini_set('log_errors',1);

// require_once __DIR__ . '/public/myApp.php';
require('./vendor/autoload.php');
// change the following paths if necessary
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$yii = dirname(__FILE__) . '/framework/yii.php';
$config = dirname(__FILE__) . '/protected/config/main.php';


// xdebug_info();
// exit;
// remove the following lines when in production mode
defined('YII_DEBUG') or define('YII_DEBUG', true);
// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

require_once($yii);
// echo Yii::getVersion(); 
// exit;
Yii::createWebApplication($config)->run();

?>