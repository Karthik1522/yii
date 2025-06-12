<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local', 'path/to/local-folder');

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => ' Inventory Management System',
    // preloading 'log' component
    'preload' => array('log'),
    'defaultController' => 'site',
    // autoloading model and component classes
    'import' => array(
        'application.models.*',
        'application.components.*',
        'application.components.helpers.*',
        'application.modules.inventory.models.*',
        'ext.YiiMongoDbSuite.*',
        
    ),
    'modules' => array(
        'inventory' => array(
            // 'defaultController' => 'product',
        ),
        'user' => array(
            'class' => 'application.components.WebUser',
            'defaultController' => 'manage',
        ),
        'report' => array(
            'defaultController' => 'default',
        ),
        'gii' => array(
            'class' => 'system.gii.GiiModule',
            'password' => '123',
            // If removed, Gii defaults to localhost only. Edit carefully to taste.
            'ipFilters' => array('127.0.0.1', '::1', '172.18.0.3', '192.168.1.7', '192.168.0.*', '*.*.*.*'),
        ),
    ),


    // application components
    'components' => array(
        'user' => array(
            'class' => 'WebUser',
            'allowAutoLogin' => true,
            // 'loginUrl' => array('user/login'),
        ),

        // 'session' => array(
        //     'class' => 'CHttpSession',
        // ),
        'session' => array(
            'class' => 'application.components.RedisSessionManager',
            'autoStart' => true,
            'cookieMode'=>'allow', //set php.ini to session.use_cookies = 0, session.use_only_cookies = 0
            'useTransparentSessionID' => true, //set php.ini to session.use_trans_sid = 1
            'sessionName' => 'PHPSESSID',
            'saveHandler'=>'redis',
            'savePath' => 'redis',
            'timeout' => 28800, //8h
            'cookieParams' => array(
                'samesite' => 'lax',
                'secure' => false,
                'httpOnly' => true

            )
        ),

        'cookies' => array(
            'class' => 'CHttpCookie',
        ),

        // uncomment the following to enable URLs in path-format

        'urlManager' => array(
            'urlFormat' => 'path',
            'rules' => array(
                '<module:\w+>/<controller:\w+>/create' => '<module>/<controller>/create',
                '<module:\w+>/<controller:\w+>/update/<id:\w+>' => '<module>/<controller>/update',
                '<module:\w+>/<controller:\w+>/delete/<id:\w+>' => '<module>/<controller>/delete',
                '<module:\w+>/<controller:\w+>/view/<id:\w+>' => '<module>/<controller>/view',
                '<module:\w+>/<controller:\w+>/<action:\w+>/<id:\w+>' => '<module>/<controller>/<action>',
                '<module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/<action>',
                '<module:\w+>/<controller:\w+>' => '<module>/<controller>/index',
                '<module:\w+>' => '<module>/default/index',

                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:[a-z0-9]+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                

            ),
        ),




        // database settings are configured in database.php
        'db' => require(dirname(__FILE__) . '/database.php'),
        // $username =$_ENV['mongodb_username'],
        // $password = $_ENV['mongodb_password'],
        'mongodb' => array(
            'class' => 'EMongoDB',
            //'connectionString' => "mongodb://tatv:tatv123@ac-c7e7tgj-shard-00-00.nwokpx1.mongodb.net:27017,ac-c7e7tgj-shard-00-01.nwokpx1.mongodb.net:27017,ac-c7e7tgj-shard-00-02.nwokpx1.mongodb.net:27017/?ssl=true&replicaSet=atlas-c1nrg0-shard-0&authSource=admin&retryWrites=true&w=majority",
            'connectionString' => "mongodb://mongo",
            'dbName' => 'local',
            'fsyncFlag' => true,
            'safeFlag' => true,
        ),

        'errorHandler' => array(
            // use 'site/error' action to display errors
            // 'errorAction' => 'site/error',
            'errorAction'=>YII_DEBUG ? null : 'site/error',
        ),
        // 'errorHandler'=>array(
        //     'errorAction'=>'site/error',
        // ),

        'mailer' => [
            'class' => 'application.components.GmailMailer',
            'username' => 'arvapallikarthikeya@gmail.com',
            'password' => 'epvc kews brmi gips',
            'from' => 'arvapallikarthikeya@gmail.com',
        ],


        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                // array(
                //     'class' => 'CWebLogRoute',
                //     'levels' => 'info,error, warning',
                //     'categories' => 'system.*, application.*',
                // ),

                // array(
                //     'class' => 'CProfileLogRoute',
                //     'categories' => 'system.*, application.*',
                //     'report' => 'summary'
                // ),

                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'info, error, warning, profile, trace',
                    'categories' => 'system.*, application.*',
                ),

                array(
                    'class' => 'application.components.MongoDbLogRoute',
                    'levels' => 'error, warning, info',
                    // 'collectionName' => 'YiiLog',
                    // 'connectionID' => 'mongodb',
                ),

                // array(
                //     'class' => 'application.components.GmailLogRoute',
                //     'levels' => 'error, warning, info',
                //     'emails' => 'karthikarvapalli01@gmail.com',
                // )
            ),
        ),
        'cache' => array(
            'class' => 'CRedisCache',
            'hostname' => 'redis',
            'port' => 6379,
            'database' => 0,
            'hashKey' => false,
            'keyPrefix' => '',
        ),
        // 'cache' => array(
        //     'class' => 'CFileCache'
        // ),

        'clientScript' => [
            'scriptMap' => [
                'jquery.js' => 'https://code.jquery.com/jquery-3.7.1.js',
            ],
        ],

        's3uploader' => array( 
            'class' => 'application.components.S3Uploader',
        ),

    ),
    // application-level parameters that can be accessed
    // using Yii::app()->params['paramName']
    'params' => array(
        // this is used in contact page
        'adminEmail' => 'webmaster@example.com',
        'roles' => array(
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'staff' => 'Staff User',
        ),
        'bcryptCost' => 10
    ),
);
